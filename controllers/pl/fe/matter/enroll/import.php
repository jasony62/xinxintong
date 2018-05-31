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
		$schemas = json_decode($app->data_schemas);

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
			if (!in_array($schema->type, ['image', 'file'])) {
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
	public function endUpload_action($app, $type = 'ZIP', $oneRecordImgNum = 1) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		if (defined('SAE_TMP_PATH')) {
			return new \ResponseError('not support');
		}

		$file = $this->getPostJson();

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$modelFs = $this->model('fs/local', $oApp->siteid, '_resumable');
		$fileUploaded = 'enroll_' . $oApp->id . '_' . $file->name;
		if ($type === 'ZIP') {
			$recordImgs = $this->_extractZIP($oApp, $modelFs->rootDir . '/' . $fileUploaded, $modelFs, $file);
			if ($recordImgs[0] === false) {
				return new \ResponseError($recordImgs[1]);
			}
			$records = $this->_extractImg($oApp, $recordImgs[1], $oneRecordImgNum);
			// 删除解压后的文件包
			$this->deldir($recordImgs[1]['toDir']);
		} else {
			$records = $this->_extract($oApp, $modelFs->rootDir . '/' . $fileUploaded);
			$modelFs->delete($fileUploaded);
		}

		$eks = $this->_persist($oApp, $records);

		return new \ResponseData($eks);
	}
	/**
	 * 从文件中提取数据
	 */
	private function &_extractImg($oApp, $imgs, $oneRecordImgNum = 1) {
		$schemas = $oApp->dataSchemas;
		$schemasByTitle = [];
		foreach ($schemas as $schema) {
			$schemasByTitle[$schema->title] = $schema;
		}

		// 数据条数
		$imgNum = 0;
		foreach ($imgs as $img) {
			$count = count($img);
			if ($count > $imgNum) {
				$imgNum = $count;
			}
		}
		if ($imgNum < $oneRecordImgNum) {
			$oneRecordImgNum = 1;
		}
		$recordNum = ceil($imgNum / $oneRecordImgNum);
		/**
		 * 提取数据
		 */
		$modelRec = $this->model('matter\enroll\record');
		$records = [];
		$fsuser = $this->model('fs/user', $oApp->siteid);
		for ($row = 1; $row <= $recordNum; $row++) {
			$oRecord = new \stdClass;
			$oRecData = new \stdClass;
			foreach ($imgs as $key => &$imgArray) {
				if ($key === 'toDir') {
					continue;
				}
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
						$mime_type = getimagesize($img->oUrl)['mime']; 
				        $base64_data = base64_encode(file_get_contents($img->oUrl));
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

			$records[] = $oRecord;
		}

		return $records;
	}
	/**
	 * 删除文件夹
	 */
	private function deldir($path) {
        $path .= '/';
        $files = scandir($path);
        foreach($files as $file){
            if($file !="." && $file !=".."){
                if(is_dir($path . $file)){
                    $this->deldir($path . $file . '/');
                }else{
                    unlink($path . $file);
                }
            }
        }
        @rmdir($path);

        return 'ok';
    }
	/**
	 * 从文件中提取数据
	 */
	private function &_extract($oApp, $filename) {
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
		for ($col = 0; $col < $highestColumnIndex; $col++) {
			$colTitle = (string) $objWorksheet->getCellByColumnAndRow($col, 1)->getValue();
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

		return $records;
	}
	/**
	 * 从文件中提取数据
	 */
	private function &_extractZIP($oApp, $zipName, $modelFs, $file) {
		$toDir = $modelFs->rootDir . '/enroll_' . $oApp->id . '_importZIP_' . $file->uniqueIdentifier;
	    // 文件目录
	    $fileDirectory = [];
        $fileDirectory['toDir'] = $toDir;
		
		if(!is_dir($toDir)) {  
        	mkdir($toDir, 0777, true);//创建目录保存解压内容  
	    }
	    if(file_exists($zipName)) {
	        $resource = zip_open($zipName);  
	        while($zip_file = zip_read($resource)) {
	            if(zip_entry_open($resource, $zip_file)) {
	                $file_name1 = zip_entry_name($zip_file);
	                //如果是目录需要创建目录
	                if (substr($file_name1, -1) === '/') {
	                	if(!is_dir($toDir . '/' . $file_name1)) {
				        	mkdir($toDir . '/' . $this->_iconvConvert($file_name1), 0777, true);
					    }

	                	$fileDirectory[$this->_iconvConvert(substr($file_name1, 0, -1))] = [];
	                } else if (strpos($file_name1, '/') !== false) { // 二级目录
	                	$file_names = explode('/', $file_name1);
            			if(!is_dir($toDir . '/' . $file_names[0] . '/')) {
            				mkdir($toDir . '/' . $this->_iconvConvert($file_names[0]) . '/', 0777, true);
					    }
					    $save_path = $toDir .'/'. $this->_iconvConvert($file_names[0]) . '/' . $this->_iconvConvert($file_names[1]);
	                	$file_size = zip_entry_filesize($zip_file);
	                	$file = zip_entry_read($zip_file, $file_size);  
                        file_put_contents($save_path, $file);  
                        zip_entry_close($zip_file);

                        $img = new \stdClass;
                        $img->name = $this->_iconvConvert($file_names[1]);
                        $img->oUrl = $save_path;
                        $fileDirectory[$this->_iconvConvert($file_names[0])][] = $img;
	                } else {
	                	$save_path = $toDir .'/'. $this->_iconvConvert($file_name1);
	                	$file_size = zip_entry_filesize($zip_file);
	                	$file = zip_entry_read($zip_file, $file_size);  
                        file_put_contents($save_path, $file);  
                        zip_entry_close($zip_file);

                        if (strpos($file_name1, '.xlsx') !== false) {
	                        $img = new \stdClass;
	                        $img->name = $this->_iconvConvert($file_name1);
	                        $img->oUrl = $save_path;
	                        $name = substr($file_name1, 0, strrpos($file_name1, '.'));
	                        $fileDirectory[$this->_iconvConvert($name)] = $img;
	                    }
	                }
	            }  
	        }  
	        zip_close($resource);
	    	unlink($zipName);
	    	
	    	$res = [true, $fileDirectory];
	    	return $res;
	    } else {
	    	unlink($zipName);
	    	return [false, '未找到压缩文件'];
	    }
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
	 * 保存数据
	 */
	private function _persist($oApp, $records) {
		$current = time();
		$modelApp = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');
		$enrollKeys = [];

		foreach ($records as $oRecord) {
			$ek = $modelRec->genKey($oApp->siteid, $oApp->id);

			$r = array();
			$r['aid'] = $oApp->id;
			$r['siteid'] = $oApp->siteid;
			$r['enroll_key'] = $ek;
			$r['enroll_at'] = $current;
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
}