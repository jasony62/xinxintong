<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 记录活动日志控制器
 */
class log extends main_base {
	/**
	 * 查询日志
	 *
	 */
	public function list_action($app, $logType = 'site', $page = 1, $size = 30) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N', 'notDecode' => true]);
		if (false === $oApp || $oApp->state != 1) {
			return new \ObjectNotFountError();
		}

		$modelLog = $this->model('matter\log');
		$criteria = $this->getPostJson();
		$aOptions = [];
		if (!empty($criteria->byUser)) {
			$aOptions['byUser'] = $modelLog->escape($criteria->byUser);
		}
		if (!empty($criteria->byOp) && (strcasecmp('all', $criteria->byOp) != 0)) {
			$aOptions['byOp'] = $modelLog->escape($criteria->byOp);
		}
		if (!empty($criteria->byRid) && (strcasecmp('all', $criteria->byRid) != 0)) {
			$aOptions['byRid'] = $modelLog->escape($criteria->byRid);
		}
		if (!empty($criteria->startAt)) {
			$aOptions['startAt'] = $modelLog->escape($criteria->startAt);
		}
		if (!empty($criteria->endAt)) {
			$aOptions['endAt'] = $modelLog->escape($criteria->endAt);
		}

		if ($logType === 'pl') {
			$reads = $modelLog->listMatterOp($oApp->id, 'enroll', $aOptions, $page, $size);
		} else if ($logType === 'page') {
			if (empty($criteria->target_type) || empty($criteria->target_id) || !in_array($criteria->target_type, ['topic', 'repos', 'cowork'])) {
				return new \ResponseError('参数不完整或暂不支持此页面');
			}
			$target_id = $modelLog->escape($criteria->target_id);
			$target_type = $modelLog->escape($criteria->target_type);
			// 查询整个活动
			if ($target_id === $app) {
				$aOptions['byApp'] = $target_id;
			}
			if (!empty($page) && !empty($size)) {
				$aOptions['paging'] = ['page' => $page, 'size' => $size];
			}

			if (isset($aOptions['byOp'])) {
				$reads = $modelLog->listMatterActionByevent($oApp->siteid, 'enroll.' . $target_type, $target_id, $aOptions['byOp'], $aOptions);
			} else {
				$reads = $modelLog->listMatterAction($oApp->siteid, 'enroll.' . $target_type, $target_id, $aOptions);
			}
		} else {
			$reads = $this->model('matter\enroll\log')->list($oApp->id, $aOptions, $page, $size);
		}

		return new \ResponseData($reads);
	}
	/**
	 * 导出日志
	 */
	public function exportLog_action($app, $logType = 'site', $target_type = '', $target_id = '', $startAt = '', $endAt = '', $byOp = '', $byRid = '', $byUser = '') {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N', 'notDecode' => true]);
		if (false === $oApp || $oApp->state != 1) {
			die('指定的活动不存在');
		}

		$modelLog = $this->model('matter\log');
		$options = [];
		if (!empty($byUser)) {
			$options['byUser'] = $byUser;
		}
		if (!empty($byOp) && (strcasecmp('all', $byOp) != 0)) {
			$options['byOp'] = $byOp;
		}
		if (!empty($byRid) && (strcasecmp('all', $byRid) != 0)) {
			$options['byRid'] = $byRid;
		}
		if (!empty($startAt)) {
			$options['startAt'] = $startAt;
		}
		if (!empty($endAt)) {
			$options['endAt'] = $endAt;
		}

		if ($logType === 'pl') {
			$reads = $modelLog->listMatterOp($oApp->id, 'enroll', $options, '', '');
		} else if ($logType === 'page') {
			if (empty($target_type) || empty($target_id) || !in_array($target_type, ['topic', 'repos', 'cowork'])) {
				die('参数不完整或暂不支持此页面');
			}
			// 查询整个活动
			if ($target_id === $app) {
				$options['byApp'] = $target_id;
			}

			if (isset($options['byOp'])) {
				$reads = $modelLog->listMatterActionByevent($oApp->siteid, 'enroll.' . $target_type, $target_id, $options['byOp'], $options);
			} else {
				$reads = $modelLog->listMatterAction($oApp->siteid, 'enroll.' . $target_type, $target_id, $options);
			}
		} else {
			$reads = $this->model('matter\enroll\log')->list($oApp->id, $options, '', '');
		}

		$logs = $reads->logs;
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
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '时间');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '用户名');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '操作');
		if ($logType === 'page') {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '来源');
		}

		// 事件转换表
		$operations = [];
		$operations['read'] = '阅读';
		$operations['site.matter.enroll.submit'] = '提交';
		$operations['updateData'] = '修改记录';
		$operations['removeData'] = '删除记录';
		$operations['restoreData'] = '恢复记录';
		$operations['site.matter.enroll.data.do.like'] = '表态其他人的填写内容';
		$operations['site.matter.enroll.cowork.do.submit'] = '提交协作新内容';
		$operations['site.matter.enroll.do.remark'] = '评论';
		$operations['site.matter.enroll.cowork.do.like'] = '表态其他人填写的协作内容';
		$operations['site.matter.enroll.remark.do.like'] = '表态其他人的评论';
		$operations['site.matter.enroll.data.get.agree'] = '对记录表态';
		$operations['site.matter.enroll.cowork.get.agree'] = '对协作记录表态';
		$operations['site.matter.enroll.remark.get.agree'] = '对评论表态';
		$operations['site.matter.enroll.remark.as.cowork'] = '将用户留言设置为协作记录';
		$operations['site.matter.enroll.remove'] = '删除记录';
		$operations['add'] = '新增记录';
		$operations['U'] = '修改活动';
		$operations['C'] = '创建活动';
		$operations['verify.batch'] = '审核通过指定记录';
		$operations['verify.all'] = '审核通过全部记录';
		$operations['shareT'] = '分享';
		$operations['shareF'] = '转发';

		// 转换数据
		for ($j = 0, $jj = count($logs); $j < $jj; $j++) {
			$log = $logs[$j];
			$rowIndex = $j + 2;
			$columnNum2 = 0; //列号
			switch ($logType) {
			case 'pl':
			case 'site':
				$action_at = $log->operate_at;
				$event = $operations[$log->operation];
				break;
			default:
				$action_at = $log->action_at;
				if ($log->act_read > 0) {
					$event = '阅读';
				} else if ($log->act_share_timeline > 0) {
					$event = '分享至朋友圈';
				} else if ($log->act_share_friend > 0) {
					$event = '转发给朋友';
				} else {
					$event = '未知';
				}
				break;
			}
			$actionAt = date('Y-m-d H:i:s', $action_at);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $actionAt);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->nickname);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $event);
			if ($logType === 'page') {
				$originNickname = isset($log->origin_nickname) ? $log->origin_nickname : '';
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $originNickname);
			}
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