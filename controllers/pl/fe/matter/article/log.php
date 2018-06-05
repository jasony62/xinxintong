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

		$data = $modelLog->listMatterAction($site, 'article', $appId, $options);

		return new \ResponseData($data);
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

		$logs = $modelLog->operateStat($site, $appId, 'article', $options);

		return new \ResponseData($logs);
	}
	/**
	 * 导出传播情况
	 */
	public function exportOperateStat_action($site, $appId, $startAt = '', $endAt = '', $byEvent = '') {
		if (empty($startAt)) {
			die('未找到开始时间');
		}
		$modelAct = $this->model('matter\article');
		$oArticle = $modelAct->byId($appId, ['fields' => 'id,title']);
		if ($oArticle === false) {
			die('指定图文不存在或已删除');
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
		$logs = $modelLog->listMatterAction($site, 'article', $appId, $options)->logs;

		require_once TMS_APP_DIR . '/lib/PHPExcel.php';
		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator(APP_TITLE)
			->setLastModifiedBy(APP_TITLE)
			->setTitle($oArticle->title)
			->setSubject($oArticle->title)
			->setDescription($oArticle->title);
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
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->action_at);
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
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->originNickname);
		}
		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Cache-Control: max-age=0');

		$filename = $oArticle->title . '.xlsx';
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
	public function attachmentLog_action($appId, $page = 1, $size = 30) {
		$model = $this->model('matter\article');
		$oArticle = $model->byId($appId, ['fields' => 'id,title']);
		if ($oArticle === false) {
			return new \ObjectNotFoundError();
		}

		$filter = $this->getPostJson();

		$q = [
			'ar.id,ar.userid,ar.openid,ar.nickname,ar.download_at,ar.attachment_id,m.name',
			'xxt_article_download_log ar,xxt_matter_attachment m',
			"ar.article_id = $appId and ar.attachment_id = m.id"
		];
		if (!empty($filter->start)) {
			$q[2] .= " and ar.download_at > $model->escape($filter->start)";
		}
		if (!empty($filter->end)) {
			$q[2] .= " and ar.download_at < $model->escape($filter->end)";
		}
		if (!empty($filter->byUser)) {
			$q[2] .= " and ar.nickname like '%" . $model->escape($filter->byUser) . "%'";
		}

		$p = ['o' => 'ar.download_at desc'];
		if (!empty($page) && !empty($size)) {
			$p['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		$logs = $model->query_objs_ss($q, $p);

		$data = new \stdClass;
		$data->logs = $logs;
		$q[0] = 'count(ar.id)';
		$data->total = $model->query_val_ss($q);

		return new \ResponseData($data);
	}
}