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
	public function matterActions_action($site, $type, $page = 1, $size = 30) {
		$filter = $this->getPostJson();
		$options = [];
		!empty($filter->orderby) && $options['orderby'] = $orderby;
		!empty($filter->startAt) && $options['startAt'] = $startAt;
		!empty($filter->endAt) && $options['endAt'] = $endAt;
		!empty($filter->isAdmin) && $options['isAdmin'] = $isAdmin;
		!empty($filter->byCreator) && $options['byCreator'] = $byCreator;
		$rest = $this->_getMatterActions($site, $type, $options, $page, $size);

		return new \ResponseData($rest);
	}
	/**
	 *  导出素材行为统计数据
	 */
	public function exportMatterActions_action($site, $type, $orderby = '', $startAt = '', $endAt = '', $isAdmin = '', $byCreator = '') {
		$options = [];
		!empty($orderby) && $options['orderby'] = $orderby;
		!empty($startAt) && $options['startAt'] = $startAt;
		!empty($endAt) && $options['endAt'] = $endAt;
		!empty($isAdmin) && $options['isAdmin'] = $isAdmin;
		!empty($byCreator) && $options['byCreator'] = $byCreator;
		$rst = $this->_getMatterActions($site, $type, $options);
		if ($rst->total == 0) {
			die('日志为空');
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
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '作者');
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
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->matter_creater_name);
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
	private function _getMatterActions($site, $type, $options = [], $page = '', $size = '') {
		$fields = "l.matter_type,l.matter_id,sum(l.act_read) read_num,sum(l.act_share_friend) share_friend_num,sum(l.act_share_timeline) share_timeline_num";
		$q = [
			$fields,
			'xxt_log_matter_action l',
		];

		$w = "l.siteid = '$site' and l.matter_type = '$type'";
		if (!empty($options['startAt'])) {
			$w .= " and l.action_at >= " . $options['startAt'];
		}
		if (!empty($options['endAt'])) {
			$w .= " and l.action_at <= " . $options['endAt'];
		}
		// 过滤非管理员
		if (!empty($options['isAdmin'])) {
			if ($options['isAdmin'] === 'Y') {
				$w .= " and case when l.act_read > 0 then exists (select 1 from xxt_log_matter_read lr,xxt_site_account sa,xxt_site_admin sa2 where lr.id = l.original_logid and lr.siteid = sa.siteid and lr.userid = sa.uid and sa.unionid = sa2.uid and sa2.siteid = lr.siteid) when l.act_share_timeline > 0 or l.act_share_friend > 0 then exists (select 1 from xxt_log_matter_share lr,xxt_site_account sa,xxt_site_admin sa2 where lr.id = l.original_logid and lr.siteid = sa.siteid and lr.userid = sa.uid and sa.unionid = sa2.uid and sa2.siteid = lr.siteid) end";
			} else {
				$w .= " and case when l.act_read > 0 then not exists (select 1 from xxt_log_matter_read lr,xxt_site_account sa,xxt_site_admin sa2 where lr.id = l.original_logid and lr.siteid = sa.siteid and lr.userid = sa.uid and sa.unionid = sa2.uid and sa2.siteid = lr.siteid) when l.act_share_timeline > 0 or l.act_share_friend > 0 then not exists (select 1 from xxt_log_matter_share lr,xxt_site_account sa,xxt_site_admin sa2 where lr.id = l.original_logid and lr.siteid = sa.siteid and lr.userid = sa.uid and sa.unionid = sa2.uid and sa2.siteid = lr.siteid) end";
			}
		}

		switch ($type) {
			case 'article':
				$q[0] .= ",a.title matter_title,a.creater_name matter_creater_name";
				$q[1] .= ",xxt_article a";
				$w .= " and a.id = l.matter_id";
				!empty($options['byCreator']) && $w .= " and a.creater_name like '%" . $options['byCreator'] . "%'";
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
		if (!empty($options['orderby']) && in_array($options['orderby'], ['read', 'share_friend', 'share_timeline'])) {
			$q2['o'] = $options['orderby'] . '_num desc';
		} else {
			$q2['o'] = 'read_num desc';
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
			if (!empty($options['orderby']) && $options['orderby'] == 'fav') {
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
	public function userActions_action($site, $page = 1, $size = 30) {
		$filter = $this->getPostJson();
		$options = [];
		!empty($filter->orderby) && $options['orderby'] = $orderby;
		!empty($filter->startAt) && $options['startAt'] = $startAt;
		!empty($filter->endAt) && $options['endAt'] = $endAt;
		!empty($filter->isAdmin) && $options['isAdmin'] = $isAdmin;
		$rst = $this->_getUserActions($site, $options, $page, $size);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	private function _getUserActions($site, $options = [], $page = '', $size = '') {
		$model = $this->model();
		$q = [];

		$s = 'l.nickname,l.userid';
		$s .= ',sum(l.act_read) read_num';
		$s .= ',sum(l.act_share_friend) share_friend_num';
		$s .= ',sum(l.act_share_timeline) share_timeline_num';
		
		$q[] = $s;
		$q[] = 'xxt_log_user_action l';

		$w = "l.siteid='$site'";
		if (!empty($options['startAt'])) {
			$w .= " and l.action_at >= " . $options['startAt'];
		}
		if (!empty($options['endAt'])) {
			$w .= "  and l.action_at <= " . $options['endAt'];
		}
		// 过滤团队管理员
		if (!empty($options['isAdmin'])) {
			if ($options['isAdmin'] === 'Y') {
				$w .= " and exists(select 1 from xxt_site_account sa,xxt_site_admin sa2 where l.siteid = sa.siteid and l.userid = sa.uid and sa.unionid = sa2.uid and l.siteid = sa2.siteid)";
			} else {
				$w .= " and not exists(select 1 from xxt_site_account sa,xxt_site_admin sa2 where l.siteid = sa.siteid and l.userid = sa.uid and sa.unionid = sa2.uid and l.siteid = sa2.siteid)";
			}
		}
		$q[] = $w;
		$q2 = ['g' => 'l.userid', 'o' => 'read_num desc'];
		if (!empty($options['orderby'])) {
			$q2['o'] = $options['orderby'] . '_num desc';
		}
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
	public function exportUserActions_action($site, $orderby = '', $startAt = '', $endAt = '', $isAdmin = '') {
		$options = [];
		!empty($orderby) && $options['orderby'] = $orderby;
		!empty($startAt) && $options['startAt'] = $startAt;
		!empty($endAt) && $options['endAt'] = $endAt;
		!empty($isAdmin) && $options['isAdmin'] = $isAdmin;
		$rst = $this->_getUserActions($site, $options);
		if ($rst->total == 0) {
			die('日志为空');
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