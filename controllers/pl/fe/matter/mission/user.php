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

		$records = $result[1]->records;

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
		foreach ($schemas as $schema) {
			/* 跳过图片和文件 */
			if (in_array($schema->type, ['image', 'file'])) {
				continue;
			}
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, $schema->title);
		}
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '昵称');
		if($mission->user_app_type === 'signin'){
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到次数');
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '迟到次数');
		}
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '审核通过');
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '所属分组');
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '备注');

		// 转换数据
		$rowNumber = 2;
		foreach ($records as $record) {
			$colNumber = 0;
			// 基本信息
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, date('y-m-j H:i', $record->enroll_at));
			// 处理登记项
			$data = $record->data;
			foreach ($schemas as $schema) {
				$v = isset($data->{$schema->id}) ? $data->{$schema->id} : '';
				switch ($schema->type) {
				case 'single':
				case 'phase':
					$disposed = null;
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
				case 'image':
				case 'file':
					break;
				default:
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $v);
					break;
				}
			}
			//昵称
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $record->nickname);
			if($mission->user_app_type === 'signin'){
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $record->signin_num);
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $record->lateCount);
			}
			//审核通过
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $record->verified);
			// 所属分组
			if(!empty($record->groupRecords)){
				$groups = [];
				foreach ($record->groupRecords as $group) {
					$groups[] = $group->app.':'.$group->round_title;
				}
				$group = implode(',',$groups);
			}
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($group) ? $group : '');
			//备注
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($record->comment) ? $record->comment : '');
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
}