<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 填写记录的留言
 */
class remark extends main_base {
	/**
	 * 返回一条填写记录的所有留言
	 *
	 * @param string $ek
	 * @param string $schema schema's id，如果不指定，返回的是对整条记录的留言
	 * @param string $id xxt_enroll_record_data's id
	 *
	 */
	public function list_action($ek, $schema = '', $page = 1, $size = 99, $id = '') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		// 会按照指定的用户id进行过滤，所以去掉用户id，获得所有数据
		$oUser = new \stdClass;

		$options = [];
		if (!empty($id)) {
			$data_id = [];
			$data_id[] = $id;
			$options['data_id'] = $data_id;
		}
		$result = $this->model('matter\enroll\remark')->listByRecord($oUser, $ek, $schema, $page, $size, $options);

		return new \ResponseData($result);
	}
	/**
	 * 返回指定活动下所有留言
	 */
	public function byApp_action($app, $page = 1, $size = 30) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$oCriteria = $this->getPostJson();
		$aOptions = [
			'fields' => 'id,userid,create_at,nickname,content,agreed,like_num,schema_id,data_id,enroll_key',
			'criteria' => $oCriteria,
		];
		$oResult = $this->model('matter\enroll\remark')->listByApp($oApp, $page, $size, $aOptions);

		return new \ResponseData($oResult);
	}
	/**
	 * 导出留言
	 */
	public function export_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oApp = $this->model('matter\enroll')->byId($app, ['fields' => 'siteid,id,state,title,data_schemas,entry_rule,assigned_nickname,scenario,mission_id,sync_mission_round,round_cron', 'cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			die('访问的对象不存在或不可用');
		}

		$oCriteria = $this->getPostJson();
		$aOptions = [
			'fields' => 'id,userid,create_at,nickname,content,agreed,like_num,schema_id,data_id,enroll_key',
			'criteria' => $oCriteria,
		];
		$oResult = $this->model('matter\enroll\remark')->listByApp($oApp, null, null, $aOptions);

		if (empty($oResult->remarks)) {
			die('没有留言');
		}

		$aSchemasById = [];
		$shareables = [];
		foreach ($oApp->dynaDataSchemas as $oSchema) {
			$aSchemasById[$oSchema->id] = $oSchema;
			if ($this->getDeepValue($oSchema, 'shareable') === 'Y') {
				$shareables[] = $oSchema;
			}
		}
		$modelSch = $this->model('matter\enroll\schema');
		$fnGetRemarkTarget = function ($oRemark) use ($oResult, $aSchemasById, $shareables, $modelSch) {
			$oTarget = new \stdClass;
			if (isset($oResult->records->{$oRemark->enroll_key})) {
				$oAssocRecord = $oResult->records->{$oRemark->enroll_key};
				/* 被留言用户 */
				$oTarget->nickname = $oAssocRecord->nickname;
				/* 被留言题目 */
				if (!empty($oRemark->schema_id) && isset($aSchemasById[$oRemark->schema_id])) {
					$oTargetSchema = $aSchemasById[$oRemark->schema_id];
					$oTarget->title = $oTargetSchema->title;
				}
				/* 被留言内容 */
				if ($oRemark->data_id > 0) {
					if (isset($oTargetSchema)) {
						$oTarget->content = $modelSch->strRecData($oAssocRecord->data, [$oTargetSchema], ['fnDataFilter' => function ($dataId) use ($oRemark) {return $dataId == $oRemark->data_id;}]);
					}
				} else {
					$oTarget->content = $modelSch->strRecData($oAssocRecord->data, $shareables);
				}
			}

			return $oTarget;
		};
		require_once TMS_APP_DIR . '/lib/PHPExcel.php';

		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator(APP_TITLE)
			->setLastModifiedBy(APP_TITLE)
			->setTitle($oApp->title)
			->setSubject($oApp->title)
			->setDescription($oApp->title);

		$objActiveSheet = $objPHPExcel->getActiveSheet();
		$columnNum1 = 0; //列号
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '留言时间');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '留言用户');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '留言内容');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '赞同数');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '被留言题目');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '被留言用户');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '被留言内容');

		foreach ($oResult->remarks as $i => $oRemark) {
			$columnNum2 = 0; //列号
			$rowIndex = $i + 2;
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, date('y-m-j H:i', $oRemark->create_at));
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oRemark->nickname);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oRemark->content);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oRemark->like_num);
			$oRemarkTarget = $fnGetRemarkTarget($oRemark);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $this->getDeepValue($oRemarkTarget, 'title', ''));
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $this->getDeepValue($oRemarkTarget, 'nickname', ''));
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $this->getDeepValue($oRemarkTarget, 'content', ''));
		}

		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Cache-Control: max-age=0');
		$filename = $oApp->title . '（留言）' . '.xlsx';
		\TMS_App::setContentDisposition($filename);

		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}
}