<?php
namespace pl\fe\matter\article;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动日志控制器
 */
class log extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action($id) {
		$access = $this->accessControlUser('article', $id);
		if ($access[0] === false) {
			die($access[1]);
		}

		\TPL::output('/pl/fe/matter/article/frame');
		exit;
	}
	/**
	 * 查询日志
	 *
	 */
	public function list_action($id, $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		$reads = $modelLog->listUserMatterOp($id, 'article', [], $page, $size);

		return new \ResponseData($reads);
	}
	/**
	 *
	 */
	public function operateStat_action($site, $appId, $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		$options = [];
		$options['operateType'] = $operateType;
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
		if (!empty($filter->nickname)) {
			$options['nickname'] = $modelLog->escape($filter->nickname);
		}
		if (!empty($filter->shareby)) {
			$options['shareby'] = $modelLog->escape($filter->shareby);
		}

		$logs = $modelLog->operateStat($site, $appId, 'article', $options);

		return new \ResponseData($logs);
	}
	/**
	 * 导出传播情况
	 */
	public function exportOperateStat_action($site, $appId, $operateType = 'read', $filter = '') {
		$modelLog = $this->model('matter\log');
		$filter = $modelLog->unescape($filter);
		$filter = empty($filter) ? new \stdClass : json_decode($filter);

		$options = [];
		$options['operateType'] = $operateType;
		if (!empty($filter->start)) {
			$options['start'] = $modelLog->escape($filter->start);
		}
		if (!empty($filter->end)) {
			$options['end'] = $modelLog->escape($filter->end);
		}
		if (!empty($filter->nickname)) {
			$options['nickname'] = $modelLog->escape($filter->nickname);
		}
		if (!empty($filter->shareby)) {
			$options['shareby'] = $modelLog->escape($filter->shareby);
		}

		$logs = $modelLog->operateStat($site, $appId, 'article', $options)->logs;

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
	/**
	 * 附件下载日志
	 */
	public function attachmentLog($id, $page = 1, $size = 30) {
		$model = $this->model();
		$filter = $this->getPostJson();

		$q = [
			'id,userid,openid,nickname,download_at,attachment_id',
			'xxt_article_download_log',
			['article_id' = $id]
		];
		if (!empty($filter->start)) {
			$start = new \stdClass;
			$start->op = '>';
			$start->pat = $model->escape($filter->start);
			$q[2]['download_at'] = $start;
		}
		if (!empty($filter->end)) {
			$end = new \stdClass;
			$end->op = '<';
			$end->pat = $model->escape($filter->end);
			$q[2]['download_at'] = $end;
		}
		if (!empty($filter->nickname)) {
			$q['2']['nickname'] = $model->escape($filter->nickname);
		}

		$p = ['o' => 'download_at desc'];
		if (!empty($page) && !empty($size)) {
			$p['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		$logs = $model->query_objs_ss($q, $p);

		$data = new \stdClass;
		$data->logs = $logs;
		$q[0] = 'count(id)';
		$data->total = $model->query_val_ss($q);

		return new \ResponseData($data);
	}
}