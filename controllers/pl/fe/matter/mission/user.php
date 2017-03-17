<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class user extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/mission/frame');
		exit;
	}
	/**
	 * 获得项目的用户列表
	 *
	 * @param int $mission mission's id
	 */
	public function list_action($mission, $page = 1, $size = 30) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$mission = $this->model('matter\mission')->byId($mission, ['fields' => 'user_app_id,user_app_type']);
		if ($mission === false) {
			return new \ObjectNotFoundError();
		}

		$criteria = $this->getPostJson();
		$options = [
			'page' => $page,
			'size' => $size,
		];

		$modelUsr = $this->model('matter\mission\user');
		$result = $modelUsr->byMission($mission, $criteria, $options);
		if ($result[0] === false) {
			return new \ResponseError($result[1]);
		}

		return new \ResponseData($result[1]);
	}
	/**
	 * 获得指定用户在项目中的行为记录
	 */
	public function recordByUser_action($mission, $user) {
		$result = new \stdClass;

		$modelEnlRec = $this->model('matter\enroll\record');
		$records = $modelEnlRec->byMission($mission, ['userid' => $user]);
		$result->enroll = $records;

		$modelSigRec = $this->model('matter\signin\record');
		$records = $modelSigRec->byMission($mission, ['userid' => $user]);
		$result->signin = $records;

		$modelGrpRec = $this->model('matter\group\player');
		$records = $modelGrpRec->byMission($mission, ['userid' => $user]);
		$result->group = $records;

		return new \ResponseData($result);
	}
	/**
	 * 登记数据导出
	 *
	 * 如果活动关联了报名活动，需要将关联的数据导出
	 */
	public function export_action($site, $mission) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$mission = $this->model('matter\mission')->byId($mission, ['fields' => 'user_app_id,user_app_type']);
		if ($mission === false) {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\mission\user');
		$result = $modelUsr->byMission($mission);
		if ($result[0] === false) {
			return new \ResponseError($result[1]);
		}

		$records = $result[1];

		//需要获取登记或者签到活动的登记项名称
		if($mission->user_app_type === 'enroll'){
			// 登记活动
			$app = $this->model('matter\enroll')->byId(
				$mission->user_app_id,
				['fields' => 'id,title,data_schemas,scenario,enroll_app_id,group_app_id', 'cascaded' => 'N']
			);
		}elseif($mission->user_app_type === 'signin'){
			$app = $this->model('matter\signin')->byId(
				$mission->user_app_id,
				['fields' => 'id,title,data_schemas,enroll_app_id,tags', 'cascaded' => 'Y']
			);
		}else{
			return [false, '不支持的项目的用户清单活动类型'];
		}

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

		$colNumber = 0;
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '登记时间');

		if (!empty($round)) {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到时间');
		} else {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到次数');
			foreach ($app->rounds as $rnd) {
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, $rnd->title);
			}
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '迟到次数');
		}
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '审核通过');
		foreach ($schemas as $schema) {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, $schema->title);
		}
		if (!empty($app->tags)) {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到标签');
		}
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到备注');
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '报名标签');
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '报名备注');

		// 转换数据
		$rowNumber = 2;
		foreach ($records as $record) {
			$colNumber = 0;
			// 基本信息
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, date('y-m-j H:i', $record->enroll_at));
			// 处理签到日志
			$signinLog = empty($record->signin_log) ? new \stdClass : json_decode($record->signin_log);
			if (!empty($round)) {
				if (isset($signinLog->{$round->rid})) {
					$signinAt = $signinLog->{$round->rid};
					if (!empty($round->late_at) && $signinAt > $round->late_at + 59) {
						$objActiveSheet->setCellValueByColumnAndRow($colNumber, $rowNumber, date('y-m-j H:i', $signinAt));
						$objActiveSheet->getStyleByColumnAndRow($colNumber++, $rowNumber)->getFont()->getColor()->setRGB('FF0000');
					} else {
						$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, date('y-m-j H:i', $signinAt));
					}
				} else {
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
				}
			} else {
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $record->signin_num);
				$lateCount = 0;
				foreach ($app->rounds as $rnd) {
					if (isset($signinLog->{$rnd->rid})) {
						$signinAt = $signinLog->{$rnd->rid};
						if (!empty($rnd->late_at) && $signinAt > $rnd->late_at + 59) {
							$objActiveSheet->setCellValueByColumnAndRow($colNumber, $rowNumber, date('y-m-j H:i', $signinAt));
							$objActiveSheet->getStyleByColumnAndRow($colNumber++, $rowNumber)->getFont()->getColor()->setRGB('FF0000');
							$lateCount++;
						} else {
							$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, date('y-m-j H:i', $signinAt));
						}
					} else {
						$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
					}
				}
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $lateCount);
			}
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $record->verified);
			// 处理登记项
			//$data = str_replace("\n", ' ', $record->data);
			$data = json_decode($record->data);
			foreach ($schemas as $schema) {
				$v = isset($data->{$schema->id}) ? $data->{$schema->id} : '';
				switch ($schema->type) {
				case 'single':
				case 'phase':
					foreach ($schema->ops as $op) {
						if ($op->v === $v) {
							$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $op->l);
							$disposed = true;
							break;
						}
					}
					empty($disposed) && $objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $v);
					break;
				case 'multiple':
					$labels = [];
					$v = explode(',', $v);
					foreach ($v as $oneV) {
						foreach ($schema->ops as $op) {
							if ($op->v === $oneV) {
								$labels[] = $op->l;
								break;
							}
						}
					}
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, implode(',', $labels));
					break;
				default:
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $v);
					break;
				}
			}
			// 基本信息
			if (!empty($app->tags)) {
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($record->tags) ? $record->tags : '');
			}
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($record->comment) ? $record->comment : '');
			// 关联的报名记录
			if (!empty($record->verified_enroll_key)) {
				$q = [
					'enroll_at,tags,comment',
					'xxt_enroll_record',
					"state=1 and aid='{$app->enroll_app_id}' and enroll_key='{$record->verified_enroll_key}'",
				];
				if ($enrollRecord = $modelApp->query_obj_ss($q)) {
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($enrollRecord->tags) ? $enrollRecord->tags : '');
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($enrollRecord->comment) ? $enrollRecord->comment : '');
				} else {
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
				}
			} else {
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
			}
			// next row
			$rowNumber++;
		}

		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $app->title . '.xlsx"');
		header('Cache-Control: max-age=0');
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');

		exit;
	}
	/**
	 * 导出登记数据中的图片
	 */
	public function exportImage_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			die('请先登录系统');
		}
		if (defined('SAE_TMP_PATH')) {
			die('部署环境不支持该功能');
		}

		$nameSchema = null;
		$imageSchemas = [];

		// 登记活动
		$modelApp = $this->model('matter\signin');
		$signinApp = $modelApp->byId(
			$app,
			['fields' => 'id,title,data_schemas,enroll_app_id', 'cascaded' => 'Y']
		);
		$schemas = json_decode($signinApp->data_schemas);

		// 关联的登记活动
		if (!empty($signinApp->enroll_app_id)) {
			$enrollApp = $this->model('matter\enroll')->byId($signinApp->enroll_app_id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$enrollSchemas = json_decode($enrollApp->data_schemas);
			$mapOfSigninSchemas = [];
			foreach ($schemas as $schema) {
				$mapOfSigninSchemas[] = $schema->id;
			}
			foreach ($enrollSchemas as $schema) {
				if (!in_array($schema->id, $mapOfSigninSchemas)) {
					$schemas[] = $schema;
				}
			}
		}
		foreach ($schemas as $schema) {
			if ($schema->type === 'image') {
				$imageSchemas[] = $schema;
			} else if ($schema->id === 'name' || (in_array($schema->title, array('姓名', '名称')))) {
				$nameSchema = $schema;
			}
		}
		if (count($imageSchemas) === 0) {
			die('活动不包含图片数据');
		}

		$q = [
			'data',
			'xxt_signin_record',
			["aid" => $signinApp->id, 'state' => 1],
		];
		$records = $this->model()->query_objs_ss($q);
		if (count($records) === 0) {
			die('record empty');
		}

		// 转换数据
		$aImages = [];
		for ($j = 0, $jj = count($records); $j < $jj; $j++) {
			$record = $records[$j];
			// 处理登记项
			$data = json_decode($record->data);
			for ($i = 0, $ii = count($imageSchemas); $i < $ii; $i++) {
				$schema = $imageSchemas[$i];
				if (!empty($data->{$schema->id})) {
					$aImages[] = ['url' => $data->{$schema->id}, 'schema' => $schema, 'data' => $data];
				}
			}
		}
		$usedRecordName = [];
		// 输出打包文件
		$zipFilename = tempnam('/tmp', $signinApp->id);
		$zip = new \ZipArchive;
		if ($zip->open($zipFilename, \ZIPARCHIVE::CREATE) === false) {
			die('无法打开压缩文件，或者文件创建失败');
		}
		foreach ($aImages as $image) {
			$imageFilename = TMS_APP_DIR . '/' . $image['url'];
			if (file_exists($imageFilename)) {
				$imageName = basename($imageFilename);
				/**
				 * 图片文件名称替换
				 */
				if (isset($nameSchema)) {
					$data = $image['data'];
					$recordName = $data->{$nameSchema->id};
					if (!empty($recordName)) {
						if (isset($usedRecordName[$recordName])) {
							$usedRecordName[$recordName]++;
							$recordName = $recordName . '_' . $usedRecordName[$recordName];
						} else {
							$usedRecordName[$recordName] = 0;
						}
						$imageName = $recordName . '.' . explode('.', $imageName)[1];
					}
				}
				$zip->addFile($imageFilename, $image['schema']->title . '/' . $imageName);
			}
		}
		$zip->close();

		if (!file_exists($zipFilename)) {
			exit("无法找到压缩文件");
		}
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header('Content-disposition: attachment; filename=' . $signinApp->title . '.zip');
		header("Content-Type: application/zip");
		header("Content-Transfer-Encoding: binary");
		header('Content-Length: ' . filesize($zipFilename));
		@readfile($zipFilename);

		exit;
	}
}