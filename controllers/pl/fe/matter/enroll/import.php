<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 导入登记记录
 */
class import extends \pl\fe\matter\base {
	/**
	 * 下载导入模板
	 */
	public function downloadTemplate_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$app = $this->model('matter\enroll')->byId($app, ['fields' => 'id,title,data_schemas,scenario', 'cascaded' => 'N']);
		$schemas = $app->dataSchemas;

		require_once TMS_APP_DIR . '/lib/PHPExcel.php';

		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator("信信通")
			->setLastModifiedBy("信信通")
			->setTitle($app->title)
			->setSubject($app->title)
			->setDescription($app->title);

		$objActiveSheet = $objPHPExcel->getActiveSheet();
		// 转换标题
		$countOfCols = 0;
		for ($i = 0, $ii = count($schemas); $i < $ii; $i++) {
			$schema = $schemas[$i];
			if (!in_array($schema->type, ['file'])) {
				$objActiveSheet->setCellValueByColumnAndRow($countOfCols, 1, $schema->title);
				$countOfCols++;
			}
		}
		$objActiveSheet->setCellValueByColumnAndRow($countOfCols, 1, '审核通过');
		$objActiveSheet->setCellValueByColumnAndRow($countOfCols + 1, 1, '备注');
		$objActiveSheet->setCellValueByColumnAndRow($countOfCols + 2, 1, '标签');
		//
		$objPHPExcel->setActiveSheetIndex(0);
		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $app->title . '（导入模板）.xlsx"');
		header('Cache-Control: max-age=0');
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}
	/**
	 * 上传文件结束
	 */
	public function upload_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		if (defined('SAE_TMP_PATH')) {
			return new \ResponseError('not support');
		}

		$dest = '/enroll_' . $app . '_' . $_POST['resumableFilename'];
		$resumable = $this->model('fs/resumable', $site, $dest);
		$resumable->handleRequest($_POST);

		exit;
	}
	/**
	 * 上传文件结束
	 */
	public function endUpload_action($app, $oneRecordImgNum = 1) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		if (defined('SAE_TMP_PATH')) {
			return new \ResponseError('not support');
		}

		$file = $this->getPostJson();
		$type = $file->type;

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$modelFs = $this->model('fs/local', $oApp->siteid, '_resumable');
		$fileUploaded = 'enroll_' . $oApp->id . '_' . $file->name;
		if (in_array($type, ['application/x-zip-compressed','application/zip'])) {
			$recordImgs = $this->_extractZIP($oApp, $modelFs->rootDir . '/' . $fileUploaded, $modelFs, $file);
			if ($recordImgs[0] === false) {
				return new \ResponseError($recordImgs[1]);
			}
			$data = $this->_extractImg($oApp, $recordImgs[1], $oneRecordImgNum);
			if ($data[0] === false) {
				return new \ResponseError($data[1]);
			}
			$records = $data[1];
			// 删除解压后的文件包
			$this->_deldir($recordImgs[1]->toDir);
		} else if ($type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
			$records = $this->_extractExcel($oApp, $modelFs->rootDir . '/' . $fileUploaded)->records;
			$modelFs->delete($fileUploaded);
		} else {
			unlink($modelFs->rootDir . '/' . $fileUploaded);
			return new \ResponseError('暂不支持此格式文件');
		}

		$eks = $this->_persist($oApp, $records);

		return new \ResponseData($eks);
	}
	/**
	 * 从文件中提取数据
	 * int $oneRecordImgNum 每条数据抽取多少个图片
	 */
	private function &_extractImg($oApp, $imgs, $oneRecordImgNum = 1) {
		$schemas = $oApp->dataSchemas;
		$schemasByTitle = [];
		foreach ($schemas as $schema) {
			$schemasByTitle[$schema->title] = $schema;
		}

		// 取出excel中的数据
		$records = [];
		if (isset($imgs->{$oApp->title})) {
			$fileType = pathinfo($imgs->{$oApp->title}['oUrl']);
			if ($fileType['extension'] === 'xlsx') {
				$data = $this->_extractExcel($oApp, $imgs->{$oApp->title}['oUrl']);
				$records = $data->records;
			}
		}

		// 如果有excel，excel决定的数据条数
		$recordNumExcel = count($records);
		// 图片决定的数据条数
		$imgNum = 0;
		foreach ($imgs->data as $img) {
			$count = count($img);
			if ($count > $imgNum) {
				$imgNum = $count;
			}
		}
		if ($imgNum < $oneRecordImgNum) {
			$oneRecordImgNum = 1;
		}
		$recordNumImg = ceil($imgNum / $oneRecordImgNum);
		// 决定数据条数
		if ($recordNumExcel > $recordNumImg) {
			$recordNum = $recordNumExcel;
		} else {
			$recordNum = $recordNumImg;
		}
		/**
		 * 提取数据
		 * 将图片转换成base64位后再转存
		 */
		$newRecords = [];
		$modelRec = $this->model('matter\enroll\record');
		$fsuser = $this->model('fs/user', $oApp->siteid);
		for ($row = 0; $row < $recordNum; $row++) {
			if (isset($records[$row])) {
				$oRecord = $records[$row];
				$oRecData = $records[$row]->data;
			} else {
				$oRecord = new \stdClass;
				$oRecData = new \stdClass;	
			}
			foreach ($imgs->data as $key => &$imgArray) {
				// 从excle表中取出对应列的名称
				if (isset($schemasByTitle[$key]) && $schemasByTitle[$key]->type === 'image') {
					$oSchema = $schemasByTitle[$key];
					// 转存指定数量的图片
					$base64Imgs = [];
					for ($i = 1; $i <= $oneRecordImgNum; $i++) {
						$img = array_shift($imgArray);
						if (empty($img)) {
							continue;
						}
						//将图片转成base64位储存
						$mime_type = getimagesize($img['oUrl'])['mime']; 
				        $base64_data = base64_encode(file_get_contents($img['oUrl']));
				        $base64_img = 'data:'.$mime_type.';base64,'.$base64_data;
				        $newImg = new \stdClass;
				        $newImg->imgSrc = $base64_img;

						$base64Imgs[] = $newImg;
					}
					$treatedValue = [];
					foreach ($base64Imgs as $base64Img) {
						$rst = $fsuser->storeImg($base64Img);
						if (false === $rst[0]) {
							return $rst;
						}
						$treatedValue[] = $rst[1];
					}

					$treatedValue = implode(',', $treatedValue);
					$oRecData->{$oSchema->id} = $treatedValue;
				}
			}

			$oRecord->data = $oRecData;
			$newRecords[] = $oRecord;
		}

		unset($records);
		$rst = [true, $newRecords];
		return $rst;
	}
	/**
	 * 从文件中提取数据
	 */
	private function &_extractExcel($oApp, $filename) {
		require_once TMS_APP_DIR . '/lib/PHPExcel.php';

		/**
		 * 用作用户昵称的题目
		 */
		if (isset($oApp->assignedNickname->valid) && $oApp->assignedNickname->valid === 'Y' && isset($oApp->assignedNickname->schema->id)) {
			$nicknameSchemaId = $oApp->assignedNickname->schema->id;
		}
		$schemas = $oApp->dataSchemas;
		$schemasByTitle = [];
		foreach ($schemas as $schema) {
			$schemasByTitle[$schema->title] = $schema;
			if (isset($nicknameSchemaId) && $nicknameSchemaId === $schema->id) {
				$oNicknameSchema = $schema;
			}
		}
		$filename = \TMS_MODEL::toLocalEncoding($filename);
		$objPHPExcel = \PHPExcel_IOFactory::load($filename);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		$highestRow = $objWorksheet->getHighestRow();
		$highestColumn = $objWorksheet->getHighestColumn();
		$highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);
		/**
		 * 提取数据定义信息
		 */
		$schemasByCol = [];
		// 每列名称
		$clumnNames = [];
		for ($col = 0; $col < $highestColumnIndex; $col++) {
			$colTitle = (string) $objWorksheet->getCellByColumnAndRow($col, 1)->getValue();
			$clumnNames[$col] = $colTitle;
			if ($colTitle === '备注') {
				$schemasByCol[$col] = 'comment';
			} else if ($colTitle === '标签') {
				$schemasByCol[$col] = 'tags';
			} else if ($colTitle === '审核通过') {
				$schemasByCol[$col] = 'verified';
			} else if (isset($schemasByTitle[$colTitle])) {
				$schema = $schemasByTitle[$colTitle];
				if (in_array($schema->type, ['file', 'image'])) {
					$schemasByCol[$col] = false;
				} else {
					$schemasByCol[$col] = $schema;
				}
			} else {
				$schemasByCol[$col] = false;
			}
		}
		/**
		 * 提取数据
		 */
		$modelRec = $this->model('matter\enroll\record');
		$records = [];
		for ($row = 2; $row <= $highestRow; $row++) {
			$oRecord = new \stdClass;
			$oRecData = new \stdClass;
			for ($col = 0; $col < $highestColumnIndex; $col++) {
				$oSchema = $schemasByCol[$col];
				if ($oSchema === false) {
					continue;
				}
				$fileValue = (string) $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
				$fileValue = trim($fileValue);
				if ($oSchema === 'verified') {
					$oRecord->verified = in_array($fileValue, ['Y', '是']) ? 'Y' : 'N';
				} else if ($oSchema === 'comment') {
					$oRecord->comment = $fileValue;
				} else if ($oSchema === 'tags') {
					$oRecord->tags = $fileValue;
				} else if ($oSchema->type === 'member') {
					if (!isset($oRecData->member)) {
						$oRecData->member = new \stdClass;
					}
					$schemaId = explode('.', $oSchema->id);
					if (count($schemaId) === 2 && $schemaId[0] === 'member') {
						$schemaId = $schemaId[1];
						$oRecData->member->{$schemaId} = $fileValue;
					}
				} else if (in_array($oSchema->type, ['single'])) {
					foreach ($oSchema->ops as $op) {
						if ($op->l === $fileValue) {
							$oRecData->{$oSchema->id} = $op->v;
							break;
						}
					}
				} else if ('multiple' === $oSchema->type) {
					$values = explode(',', $fileValue);
					foreach ($oSchema->ops as $op) {
						if (in_array($op->l, $values)) {
							!isset($oRecData->{$oSchema->id}) && $oRecData->{$oSchema->id} = '';
							$oRecData->{$oSchema->id} .= $op->v . ',';
						}
					}
					if (isset($oRecData->{$oSchema->id})) {
						$oRecData->{$oSchema->id} = rtrim($oRecData->{$oSchema->id}, ',');
					}
				} else if ('score' === $oSchema->type) {
					$treatedValue = new \stdClass;
					$values = explode('/', $fileValue);
					foreach ($values as $value) {
						if (!empty($value) && strpos($value, ':')) {
							list($label, $score) = explode(':', $value);
							$label = trim($label);
							foreach ($oSchema->ops as $op) {
								if ($op->l === $label) {
									$treatedValue->{$op->v} = trim($score);
									break;
								}
							}
						}
					}
					$oRecData->{$oSchema->id} = $treatedValue;
				} else {
					$oRecData->{$oSchema->id} = $fileValue;
				}
			}
			$oRecord->data = $oRecData;
			/**
			 * 指定的用户昵称
			 */
			if (isset($oNicknameSchema)) {
				$oRecord->nickname = $modelRec->getValueBySchema($oNicknameSchema, $oRecData);
			}

			$records[] = $oRecord;
		}

		$data = new \stdClass;
		$data->records = $records;
		$data->clumnNames = $clumnNames;
		return $data;
	}
	/**
	 * 从文件中提取数据
	 */
	private function &_extractZIP($oApp, $zipfile, $modelFs, $file) {
		$savepath = $modelFs->rootDir . '/enroll_' . $oApp->id . '_importZIP_' . $file->uniqueIdentifier;
		if(!is_dir($savepath)) {  
        	mkdir($savepath, 0777, true);//创建目录保存解压内容  
	    }
	    // 文件目录
	    $fileDirectory = new \stdClass;
        $fileDirectory->toDir = $savepath;
	    if(file_exists($zipfile)) {
	    	// 判断客户端操作系统
	    	$agent = $_SERVER['HTTP_USER_AGENT'];
	    	if (preg_match('/win/i', $agent)) {
				$res = $this->_unZipWin($zipfile, $savepath, $fileDirectory);
		    } else {
		    	$res = $this->_unZipMac($zipfile, $savepath, $fileDirectory);
	    	}
	    } else {
	    	$res = [false, '压缩文件上传失败'];
	    }

    	return $res;
	}
	/*
	 * 解压windows下上传的压缩包
	 */
	private function _unZipWin($zipfile, $savepath, $fileDirectory) {
		require_once TMS_APP_DIR . '/lib/PHPZip.php';
        $archive   = new \PHPZip();
        $FileInfos  = $archive->GetZipInnerFilesInfo($zipfile);
        $failFiles = [];  
        $pssFiles = [];
        for($i=0; $i<count($FileInfos); $i++) {  
        	$fileInfo = $FileInfos[$i];
            if($fileInfo['folder'] == 0){  
            	$rst = $archive->unZip($zipfile, $savepath, $i, $fileInfo);
                if($rst['state'] === true){  
                	if ((strpos($fileInfo['filename'], '/')) !== false) {
                		$oUrl = $savepath . '/' . $fileInfo['filename'];
                		$names = explode('/', $fileInfo['filename']);
                		$newFile = [];
                		$newFile['title'] = $names[1];
                		$newFile['size'] = $fileInfo['size'];
                		$newFile['oUrl'] = $oUrl;
                		$pssFiles[$names[0]][] = $newFile;
                	} else {
                		$newFile = [];
                		$fileName = $fileInfo['filename'];
                		$newFile['title'] = substr($fileName, 0, strrpos($fileName, '.'));
                		$newFile['size'] = $fileInfo['size'];
                		$newFile['oUrl'] = $savepath . '/' . $fileInfo['filename'];
                		$fileDirectory->{$newFile['title']} = $newFile;
                	}
                }else{  
                    $failFiles[] = $fileInfo['filename'];  
                }  
            }else{
            	if(!@is_dir($savepath . '/' . $fileInfo['filename'])){ 
            	 	@mkdir($savepath . '/' . $fileInfo['filename'], 0777, true); 
            	}   
            	$fileName = $this->_iconvConvert($fileInfo['filename']);;
            	$pssFiles[substr($fileName, 0, -1)] = [];
            }  
        }
        unlink($zipfile);

    	$fileDirectory->data = $pssFiles;
    	$fileDirectory->failData = $failFiles;
    	$res = [true, $fileDirectory];

    	return $res;
	}
	/*
	 * 解压mac电脑下上传的压缩包
	 */
	private function _unZipMac($zipfile, $savepath, $fileDirectory) {
		$zip = new \ZipArchive;
		$failFiles = [];  
        $pssFiles = [];
		if ($zip->open($zipfile) === true) {
			$docnum = $zip->numFiles;
			for($i = 0; $i < $docnum; $i++) {
			    $statInfo = $zip->statIndex($i);
			    if($statInfo['crc'] == 0) {
			    	$dirName = substr($statInfo['name'], 0,-1);
			    	if (strpos($dirName, '__MACOSX') !== false) {
			    		continue;
			    	}
			    	if (!@is_dir($savepath.'/'.$dirName)) {
			       		@mkdir($savepath.'/'.$dirName, 0777, true);
			    	}
			    	$pssFiles[$dirName] = [];
			    } else {
			    	$dirName = $statInfo['name'];
			    	if (strpos($dirName, '__MACOSX') !== false || strpos($dirName, '.DS_Store') !== false) {
			    		continue;
			    	}
			    	if ((strpos($dirName, '/')) !== false) {
			    		$oUrl = $savepath . '/' . $dirName;
                		$names = explode('/', $dirName);
                		$newFile = [];
                		$newFile['title'] = $names[1];
                		$newFile['size'] = $statInfo['size'];
                		$newFile['oUrl'] = $oUrl;
                		$pssFiles[$names[0]][] = $newFile;
			    	} else {
                		$newFile = [];
                		$newFile['title'] = substr($dirName, 0, strrpos($dirName, '.'));
                		$newFile['size'] = $statInfo['size'];
                		$newFile['oUrl'] = $savepath . '/' . $dirName;
                		$fileDirectory->{$newFile['title']} = $newFile;
                	}
			        //拷贝文件
			        copy('zip://'.$zipfile.'#'.$statInfo['name'], $savepath.'/'.$dirName);
			    }
			}
			$zip->close();

			unlink($zipfile);
	    	$fileDirectory->data = $pssFiles;
	    	$fileDirectory->failData = $failFiles;
	    	$res = [true, $fileDirectory];

	    	return $res;
		} else {
			// 删除解压后的文件包
			unlink($zipfile);
			$res = [false, '压缩包打开失败'];
    		return $res;
		}
	}
	/**
	 * 保存数据
	 */
	private function _persist($oApp, $records, $rid = '') {
		$current = time();
		$modelApp = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');
		$enrollKeys = [];
		if (empty($rid)) {
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$rid = $activeRound->rid;
			}
		}
		
		foreach ($records as $oRecord) {
			$ek = $modelRec->genKey($oApp->siteid, $oApp->id);

			$r = array();
			$r['aid'] = $oApp->id;
			$r['rid'] = $rid;
			$r['siteid'] = $oApp->siteid;
			$r['enroll_key'] = $ek;
			$r['enroll_at'] = $current;
			$r['first_enroll_at'] = $current;
			$r['nickname'] = isset($oRecord->nickname) ? $oRecord->nickname : '';
			$r['verified'] = isset($oRecord->verified) ? $oRecord->verified : 'N';
			$r['comment'] = isset($oRecord->comment) ? $oRecord->comment : '';
			if (isset($oRecord->tags)) {
				$r['tags'] = $oRecord->tags;
				$modelApp->updateTags($oApp->id, $oRecord->tags);
			}
			$id = $modelRec->insert('xxt_enroll_record', $r, true);
			$r['id'] = $id;
			/**
			 * 登记数据
			 */
			if (isset($oRecord->data)) {
				//
				$jsonData = $modelRec->toJson($oRecord->data);
				$modelRec->update('xxt_enroll_record', ['data' => $jsonData], "enroll_key='$ek'");
				$enrollKeys[] = $ek;
				//
				foreach ($oRecord->data as $n => $v) {
					if (is_object($v) || is_array($v)) {
						$v = json_encode($v);
					}
					if (count($v)) {
						$cd = [
							'aid' => $oApp->id,
							'rid' => $rid,
							'enroll_key' => $ek,
							'schema_id' => $n,
							'value' => $v,
						];
						$modelRec->insert('xxt_enroll_record_data', $cd, false);
					}
				}
			}
		}

		return $enrollKeys;
	}
	/**
	 *
	 */
	private function _iconvConvert($str, $encoding = 'UTF-8//IGNORE') {
		$encode = mb_detect_encoding($str, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));

		if ($encode && $encode != 'UTF-8') {
			$str = iconv($encode, $encoding, $str);
		}

		return $str;
	}
	/**
	 * 删除文件夹
	 */
	private function _deldir($path) {
        $path .= '/';
        $files = scandir($path);
        foreach($files as $file){
            if($file !="." && $file !=".."){
                if(is_dir($path . $file)){
                    $this->_deldir($path . $file . '/');
                }else{
                    unlink($path . $file);
                }
            }
        }
        @rmdir($path);

        return 'ok';
    }
}