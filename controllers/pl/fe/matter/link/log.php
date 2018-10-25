<?php
namespace pl\fe\matter\link;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/*
 * 登记活动日志控制器
 */
class log extends \pl\fe\matter\main_base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/link/frame');
		exit;
	}
	/**
	 * 查询日志
	 *
	 */
	public function list_action($id, $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		$reads = $modelLog->listUserMatterOp($id, 'link', [], $page, $size);

		return new \ResponseData($reads);
	}
	/**
	 * 查询传播日志
	 *
	 */
	public function userMatterAction_action($site, $appId, $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		$filter = $this->getPostJson();
		$options = [];
		if (!empty($filter->byEvent)) {
			$options['byEvent'] = $modelLog->escape($filter->byEvent);
		}
		if (!empty($filter->startAt)) {
			$options['startAt'] = $modelLog->escape($filter->startAt);
		}
		if (!empty($filter->endAt)) {
			$options['endAt'] = $modelLog->escape($filter->endAt);
		}
		if (!empty($page) && !empty($size)) {
			$options['paging'] = ['page' => $page, 'size' => $size];
		}

		$data = $modelLog->listMatterAction($site, 'link', $appId, $options);

		return new \ResponseData($data);
	}
	/**
	 * 导出传播情况
	 */
	public function exportOperateStat_action($site, $appId, $startAt = '', $endAt = '', $byEvent = '') {
		if (empty($startAt)) {
			die('未找到开始时间');
		}
		$modelAct = $this->model('matter\link');
		$oLink = $modelAct->byId($appId, ['fields' => 'id,title']);
		if ($oLink === false) {
			die('指定链接不存在或已删除');
		}

		$options = [];
		$options['startAt'] = $startAt;
		if (!empty($endAt)) {
			$options['endAt'] = $endAt;
		}
		if (!empty($byEvent)) {
			$options['byEvent'] = $byEvent;
		}

		$modelLog = $this->model('matter\log');
		$logs = $modelLog->listMatterAction($site, 'link', $appId, $options)->logs;

		require_once TMS_APP_DIR . '/lib/PHPExcel.php';
		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator(APP_TITLE)
			->setLastModifiedBy(APP_TITLE)
			->setTitle($oLink->title)
			->setSubject($oLink->title)
			->setDescription($oLink->title);
		$objActiveSheet = $objPHPExcel->getActiveSheet();
		$columnNum1 = 0; //列号
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '时间');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '用户名');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '操作');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '来源');
		
		// 转换数据
		for ($j = 0, $jj = count($logs); $j < $jj; $j++) {
			$log = $logs[$j];
			$rowIndex = $j + 2;
			$columnNum2 = 0; //列号
			$actionAt = date('Y-m-d H:i:s', $log->action_at);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $actionAt);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->nickname);
			if ($log->act_read > 0) {
				$event = '阅读';
			} else if ($log->act_share_timeline > 0) {
				$event = '分享至朋友圈';
			} else if ($log->act_share_friend > 0) {
				$event = '转发给朋友';
			} else {
				$event = '未知';
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $event);
			$originNickname = isset($log->origin_nickname)? $log->origin_nickname : '';
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $originNickname);
		}
		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Cache-Control: max-age=0');

		$filename = $oLink->title . '.xlsx';
		$ua = $_SERVER["HTTP_USER_AGENT"];
		//if (preg_match("/MSIE/", $ua) || preg_match("/Trident\/7.0/", $ua)) {
		if (preg_match("/MSIE/", $ua)) {
			$encoded_filename = urlencode($filename);
			$encoded_filename = str_replace("+", "%20", $encoded_filename);
			$encoded_filename = iconv('UTF-8', 'GBK//IGNORE', $encoded_filename);
			header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
		} else if (preg_match("/Firefox/", $ua)) {
			header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
		} else {
			header('Content-Disposition: attachment; filename="' . $filename . '"');
		}

		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}
}