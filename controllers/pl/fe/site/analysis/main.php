<?php
namespace pl\fe\site\analysis;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 团队运行统计管理控制器
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/frame');
		exit;
	}
	/**
	 * 素材运营统计数据
	 */
	public function matterActions_action($site, $type, $orderby, $startAt, $endAt, $page = 1, $size = 30) {
		$rest = $this->_getMatterActions($site, $type, $orderby, $startAt, $endAt, $page, $size);

		return new \ResponseData($rest);
	}
	/**
	 *  导出素材行为统计数据
	 */
	public function exportMatterActions_action($site, $type, $orderby, $startAt, $endAt) {
		$rst = $this->_getMatterActions($site, $type, $orderby, $startAt, $endAt);
		if ($rst->total == 0) {
			return new \ResponseError('日志为空');
		}

		require_once TMS_APP_DIR . '/lib/PHPExcel.php';
		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator(APP_TITLE)
			->setLastModifiedBy(APP_TITLE)
			->setTitle('素材运营统计数据')
			->setSubject('素材运营统计数据')
			->setDescription('素材运营统计数据');
		$objActiveSheet = $objPHPExcel->getActiveSheet();
		$columnNum1 = 0; //列号
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '名称');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '阅读');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '搜藏');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '发送给朋友');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '分享到朋友圈');

		// 转换数据
		$logs = $rst->matters;
		for ($j = 0, $jj = count(get_object_vars($logs)); $j < $jj; $j++) {
			$log = $logs->{$j};
			$rowIndex = $j + 2;
			$columnNum2 = 0; //列号

			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->matter_title);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->read_num);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->fav_num);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->share_friend_num);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->share_timeline_num);
		}
		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Cache-Control: max-age=0');

		$filename = '素材运营统计数据.xlsx';
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
	 * 
	 */
	private function _getMatterActions($site, $type, $orderby, $startAt = '', $endAt = '', $page = '', $size = '') {
		$fields = "l.matter_type,l.matter_id,sum(l.act_read) read_num,sum(l.act_share_friend) share_friend_num,sum(l.act_share_timeline) share_timeline_num";
		$q = [
			$fields,
			'xxt_log_matter_action l',
		];
		
		$w = "l.siteid = '$site' and l.matter_type = '$type'";
		if (!empty($startAt)) {
			$w .= " and l.action_at >= $startAt";
		}
		if (!empty($endAt)) {
			$w .= " and l.action_at <= $endAt";
		}

		switch ($type) {
			case 'article':
				$q[0] .= ",a.title matter_title";
				$q[1] .= ",xxt_article a";
				$w .= " and a.id = l.matter_id";
				break;
		}

		$q[2] = $w;
		$q2 = [
			'g' => 'l.matter_type,l.matter_id',
		];
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		//按照阅读数、分享数逆序排列
		if (in_array($orderby, ['read', 'share_friend', 'share_timeline'])) {
			$q2['o'] = $orderby . '_num desc';
		}

		$model = $this->model();
		if ($stat = $model->query_objs_ss($q, $q2)) {
			$b = new \stdClass;
			foreach ($stat as $k => $v) {
				$v->fav_num = $model->query_val_ss([
					'count(id)',
					'xxt_site_favor',
					"siteid='$site' and matter_type='$v->matter_type' and matter_id='$v->matter_id'",
				]);
				$c[$k] = $v->fav_num;
				$b->$k = $v;
			}
			//按照收藏数量逆序排列
			if ($orderby == 'fav') {
				arsort($c);
				foreach ($c as $k2 => $v2) {
					foreach ($b as $k3 => $v3) {
						if ($k2 == $k3 && $v2 == $v3->fav_num) {
							$e[] = $v3;
						}
					}
				}
				$b = (object) $e;
			}

			$stat = $b;
			$q[0] = 'count(distinct l.matter_type,l.matter_id)';
			$cnt = $model->query_val_ss($q);
		} else {
			$cnt = 0;
		}

		$data = new \stdClass;
		$data->matters = $stat;
		$data->total = $cnt;
		return $data;
	}
	/**
	 * 用户行为统计数据
	 */
	public function userActions_action($site, $orderby, $startAt, $endAt, $page = 1, $size = 30) {
		$rst = $this->_getUserActions($site, $orderby, $startAt, $endAt, $page, $size);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	private function _getUserActions($site, $orderby, $startAt = '', $endAt = '', $page = '', $size = '') {
		$model = $this->model();
		$q = [];

		$s = 'l.nickname,l.userid';
		$s .= ',sum(l.act_read) read_num';
		$s .= ',sum(l.act_share_friend) share_friend_num';
		$s .= ',sum(l.act_share_timeline) share_timeline_num';
		$q[] = $s;
		$q[] = 'xxt_log_user_action l';
		$w = "l.siteid='$site'";
		if (!empty($startAt)) {
			$w .= " and l.action_at>=$startAt";
		}
		if (!empty($endAt)) {
			$w .= "  and l.action_at<=$endAt";
		}
		$q[] = $w;
		$q2 = [
			'g' => 'userid',
			'o' => $orderby . '_num desc',
		];
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		if ($stat = $model->query_objs_ss($q, $q2)) {
			$q[0] = 'count(distinct userid)';
			$cnt = $model->query_val_ss($q);
		} else {
			$cnt = 0;
		}

		$data = new \stdClass;
		$data->users = $stat;
		$data->total = $cnt;

		return $data;
	}
	/**
	 *  导出用户行为统计数据
	 */
	public function exportUserActions_action($site, $orderby, $startAt, $endAt) {
		$rst = $this->_getUserActions($site, $orderby, $startAt, $endAt);
		if ($rst->total == 0) {
			return new \ResponseError('日志为空');
		}

		require_once TMS_APP_DIR . '/lib/PHPExcel.php';
		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator(APP_TITLE)
			->setLastModifiedBy(APP_TITLE)
			->setTitle('用户行为统计数据')
			->setSubject('用户行为统计数据')
			->setDescription('用户行为统计数据');
		$objActiveSheet = $objPHPExcel->getActiveSheet();
		$columnNum1 = 0; //列号
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '用户');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '阅读');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '发送给朋友');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '分享到朋友圈');

		// 转换数据
		$logs = $rst->users;
		for ($j = 0, $jj = count($logs); $j < $jj; $j++) {
			$log = $logs[$j];
			$rowIndex = $j + 2;
			$columnNum2 = 0; //列号

			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->nickname);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->read_num);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->share_friend_num);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->share_timeline_num);
		}
		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Cache-Control: max-age=0');

		$filename = '用户行为统计数据.xlsx';
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