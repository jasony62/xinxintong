<?php
namespace pl\fe\matter\channel;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/*
 * 日志控制器
 */
class log extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action($id) {
		\TPL::output('/pl/fe/matter/channel/frame');
		exit;
	}
	/**
	 * 查询日志
	 *
	 */
	public function userMatterAction_action($appId, $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		$filter = $this->getPostJson();
		$options = [];
		if (empty($filter->byUserId)) {
			return new \ResponseError('未指定用户');
		}
		if (empty($filter->byOp)) {
			return new \ResponseError('未指定用户行为');
		}
		$options['byUserId'] = $modelLog->escape($filter->byUserId);
		$options['byOp'] = $modelLog->escape($filter->byOp);
		
		if (!empty($filter->start)) {
			$options['start'] = $modelLog->escape($filter->start);
		}
		if (!empty($filter->end)) {
			$options['end'] = $modelLog->escape($filter->end);
		}
		if (!empty($filter->shareby)) {
			$options['shareby'] = $modelLog->escape($filter->shareby);
		}

		$reads = $modelLog->userMatterAction($appId, 'channel', $options, $page, $size);

		return new \ResponseData($reads);
	}
	/**
	 * 运营传播统计
	 */
	public function operateStat_action($site, $appId, $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		$options = [];
		$options['groupby'] = 'r.userid';
		$filter = $this->getPostJson();

		if (!empty($page) && !empty($size)) {
			$options['paging'] = ['page' => $page, 'size' => $size];
		}
		if (!empty($filter->start)) {
			$options['start'] = $modelLog->escape($filter->start);
		}
		if (!empty($filter->end)) {
			$options['end'] = $modelLog->escape($filter->end);
		}
		if (!empty($filter->byUser)) {
			$options['byUser'] = $modelLog->escape($filter->byUser);
		}
		if (!empty($filter->shareby)) {
			$options['shareby'] = $modelLog->escape($filter->shareby);
		}

		$logs = $modelLog->operateStat($site, $appId, 'channel', $options);

		return new \ResponseData($logs);
	}
	/**
	 * 导出传播情况
	 */
	public function exportOperateStat_action($site, $appId, $start = '', $end = '', $shareby = '') {
		$modelAct = $this->model('matter\channel');
		$oApp = $modelAct->byId($appId, ['fields' => 'id,title']);
		if ($oApp === false) {
			return new \ObjectNotFoundError();
		}

		$options = [];
		$options['groupby'] = 'r.userid';
		if (!empty($start)) {
			$options['start'] = $start;
		}
		if (!empty($end)) {
			$options['end'] = $end;
		}
		if (!empty($shareby)) {
			$options['shareby'] = $shareby;
		}

		$modelLog = $this->model('matter\log');
		$logs = $modelLog->operateStat($site, $appId, 'channel', $options)->logs;

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
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '昵称');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '阅读数');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '转发数');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '分享数');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '带来的阅读数');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '带来的阅读人数');
		
		// 转换数据
		for ($j = 0, $jj = count($logs); $j < $jj; $j++) {
			$log = $logs[$j];
			$rowIndex = $j + 2;
			$columnNum2 = 0; //列号
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->nickname);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->readNum);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->shareFNum);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->shareTNum);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->attractReadNum);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->attractReaderNum);
		}
		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Cache-Control: max-age=0');

		$filename = $oApp->title . '.xlsx';
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