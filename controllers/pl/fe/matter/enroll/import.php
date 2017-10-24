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
		} else {
			$modelFs = $this->model('fs/local', $site, '_resumable');
			$dest = '/enroll_' . $app . '_' . $_POST['resumableFilename'];
			$resumable = $this->model('fs/resumable', $site, $dest, $modelFs);
		}

		$resumable->handleRequest($_POST);

		exit;
	}
	/**
	 * 上传文件结束
	 */
	public function endUpload_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$file = $this->getPostJson();

		if (defined('SAE_TMP_PATH')) {
			return new \ResponseError('not support');
		} else {
			// 文件存储在本地
			$modelFs = $this->model('fs/local', $site, '_resumable');
			$fileUploaded = 'enroll_' . $app . '_' . $file->name;
			$records = $this->_extract($app, $modelFs->rootDir . '/' . $fileUploaded);
			$modelFs->delete($fileUploaded);
		}

		$eks = $this->_persist($site, $app, $records);

		// 记录操作日志
		//$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		//$app->type = 'enroll';
		//$this->model('matter\log')->matterOp($site, $user, $app, 'add', $ek);

		return new \ResponseData($eks);
	}
	/**
	 * 从文件中提取数据
	 */
	private function &_extract($appId, $filename) {
		require_once TMS_APP_DIR . '/lib/PHPExcel.php';

		$oApp = $this->model('matter\enroll')->byId($appId, ['cascaded' => 'N']);
		$schemas = json_decode($oApp->data_schemas);
		$schemasByTitle = [];
		foreach ($schemas as $schema) {
			$schemasByTitle[$schema->title] = $schema;
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
				} else if (in_array($oSchema->type, ['single', 'phase'])) {
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
			$records[] = $oRecord;
		}

		return $records;
	}
	/**
	 * 保存数据
	 */
	private function _persist($site, $appId, &$records) {
		$current = time();
		$modelApp = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');
		$enrollKeys = [];

		foreach ($records as $record) {
			$ek = $modelRec->genKey($site, $appId);

			$r = array();
			$r['aid'] = $appId;
			$r['siteid'] = $site;
			$r['enroll_key'] = $ek;
			$r['enroll_at'] = $current;
			$r['verified'] = isset($record->verified) ? $record->verified : 'N';
			$r['comment'] = isset($record->comment) ? $record->comment : '';
			if (isset($record->tags)) {
				$r['tags'] = $record->tags;
				$modelApp->updateTags($appId, $record->tags);
			}
			$id = $modelRec->insert('xxt_enroll_record', $r, true);
			$r['id'] = $id;
			/**
			 * 登记数据
			 */
			if (isset($record->data)) {
				//
				$jsonData = $modelRec->toJson($record->data);
				$modelRec->update('xxt_enroll_record', ['data' => $jsonData], "enroll_key='$ek'");
				$enrollKeys[] = $ek;
				//
				foreach ($record->data as $n => $v) {
					if (is_object($v) || is_array($v)) {
						$v = json_encode($v);
					}
					if (count($v)) {
						$cd = [
							'aid' => $appId,
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