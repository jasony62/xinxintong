<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动用户
 */
class user extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回提交过填写记录的用户列表
	 */
	public function enrollee_action($app, $page = 1, $size = 30) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		$modelUsr = $this->model('matter\enroll\user');
		$post = $this->getPostJson();
		$aOptions = [];
		!empty($post->orderby) && $aOptions['orderby'] = $post->orderby;
		!empty($post->byGroup) && $aOptions['byGroup'] = $post->byGroup;
		!empty($post->rid) && $aOptions['rid'] = $post->rid;
		!empty($post->onlyEnrolled) && $aOptions['onlyEnrolled'] = $post->onlyEnrolled;

		$result = $modelUsr->enrolleeByApp($oApp, $page, $size, $aOptions);
		/* 由于版本原因，判断是否需要系统获取填写人信息 */
		if (0 === count($result->users)) {
			if ($this->_refresh($oApp) > 0) {
				$result = $modelUsr->enrolleeByApp($oApp, $page, $size, $aOptions);
			}
		}

		/* 查询有openid的用户发送消息的情况 */
		if (count($result->users)) {
			if (!empty($oApp->group_app_id)) {
				foreach ($oApp->dataSchemas as $schema) {
					if ($schema->id == '_round_id') {
						$aUserRounds = $schema->ops;
						break;
					}
				}
			}
			foreach ($result->users as &$user) {
				$q = [
					'd.tmplmsg_id,d.status,b.create_at',
					'xxt_log_tmplmsg_detail d,xxt_log_tmplmsg_batch b',
					"d.userid = '{$user->userid}' and d.openid<>'' and d.batch_id = b.id and b.send_from = 'enroll:" . $user->aid . "'",
				];
				$q2 = [
					'r' => ['o' => 0, 'l' => 1],
					'o' => 'b.create_at desc',
				];
				if ($tmplmsg = $modelUsr->query_objs_ss($q, $q2)) {
					$user->tmplmsg = $tmplmsg[0];
				} else {
					$user->tmplmsg = new \stdClass;
				}
				if (isset($aUserRounds) && $user->group_id) {
					foreach ($aUserRounds as $v) {
						if ($v->v == $user->group_id) {
							$user->group = $v;
						}
					}
				}
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 缺席用户列表
	 * 1、如果活动指定了通讯录用户参与；如果活动指定了分组活动的分组用户
	 * 2、如果活动关联了分组活动
	 * 3、如果活动所属项目指定了用户名单
	 */
	public function absent_action($app, $rid = '') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		empty($rid) && $rid = 'ALL';

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N', 'fields' => 'siteid,id,mission_id,entry_rule,group_app_id,absent_cause']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		/* 获得当前活动的参与人 */
		$oUsers = $modelUsr->enrolleeByApp($oApp, '', '', ['fields' => 'id,userid', 'onlyEnrolled' => 'Y', 'cascaded' => 'N', 'rid' => $rid]);
		$result = $modelUsr->absentByApp($oApp, $oUsers->users, $rid);

		return new \ResponseData($result);
	}
	/**
	 * 根据通讯录返回用户完成情况
	 */
	public function byMschema_action($app, $mschema, $rid = '', $page = 1, $size = 30) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelMs = $this->model('site\user\memberschema');
		$oMschema = $modelMs->byId($mschema, ['cascaded' => 'N']);
		if (false === $oMschema) {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		$options = [];
		!empty($rid) && $options['rid'] = $rid;
		$result = $modelUsr->enrolleeByMschema($oApp, $oMschema, $page, $size, $options);
		/*查询有openid的用户发送消息的情况*/
		if (count($result->members)) {
			foreach ($result->members as $member) {
				$q = [
					'd.tmplmsg_id,d.status,b.create_at',
					'xxt_log_tmplmsg_detail d,xxt_log_tmplmsg_batch b',
					"d.userid = '{$member->userid}' and d.batch_id = b.id and b.send_from = 'enroll:" . $oApp->id . "'",
				];
				$q2 = [
					'r' => ['o' => 0, 'l' => 1],
					'o' => 'b.create_at desc',
				];
				if ($tmplmsg = $modelUsr->query_objs_ss($q, $q2)) {
					$member->tmplmsg = $tmplmsg[0];
				} else {
					$member->tmplmsg = new \stdClass;
				}
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 发表过评论的用户
	 */
	public function remarker_action($app, $page = 1, $size = 30) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		$result = $modelUsr->remarkerByApp($oApp, $page, $size);

		return new \ResponseData($result);
	}
	/**
	 * 数据导出
	 */
	public function export_action($site, $app, $mschema = '', $rid = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		if (false === ($oApp = $this->model('matter\enroll')->byId($app, ['fields' => 'siteid,id,title,entry_rule,user_task,group_app_id,data_schemas,absent_cause', 'cascaded' => 'N']))) {
			return new \ParameterError();
		}
		$oUserTask = $oApp->userTask;

		$modelUsr = $this->model('matter\enroll\user');
		//判断关联公众号
		$road = ['wx', 'qy', 'yx'];
		$sns = new \stdClass;
		foreach ($road as $v) {
			$arr = array();
			$config = $modelUsr->query_obj_ss(['joined', 'xxt_site_' . $v, ['siteid' => $site]]);
			if (!empty($config->joined)) {
				$arr['joined'] = $config->joined;
				$sns->{$v} = (object) $arr;
			}
		}
		$oApp->sns = $sns;

		if (!empty($mschema)) {
			$modelMs = $this->model('site\user\memberschema');
			$mschema = $modelMs->escape($mschema);
			$oMschema = $modelMs->byId($mschema);
			if (false === $oMschema) {
				return new \ObjectNotFoundError();
			}
			$options = [];
			!empty($rid) && $options['rid'] = $rid;
			$result = $modelUsr->enrolleeByMschema($oApp, $oMschema, $page = '', $size = '', $options);
			$data = $result->members;
		} else {
			$options = [];
			!empty($rid) && $options['rid'] = $rid;
			$result = $modelUsr->enrolleeByApp($oApp, $page = '', $size = '', $options);
			$data = $result->users;
		}

		foreach ($data as &$user) {
			//添加openid
			$p = [
				'wx_openid,yx_openid,qy_openid',
				"xxt_site_account",
				"uid = '{$user->userid}'",
			];
			if ($openid = $modelUsr->query_obj_ss($p)) {
				$user->wx_openid = $openid->wx_openid;
				$user->yx_openid = $openid->yx_openid;
				$user->qy_openid = $openid->qy_openid;
			} else {
				$user->wx_openid = '';
				$user->yx_openid = '';
				$user->qy_openid = '';
			}
			//添加模板消息 用户任务 和 分组
			$q = [
				'd.tmplmsg_id,d.status,b.create_at',
				'xxt_log_tmplmsg_detail d,xxt_log_tmplmsg_batch b',
				"d.userid = '{$user->userid}' and d.batch_id = b.id and b.send_from = 'enroll:" . $oApp->id . "'",
			];
			$q2 = [
				'r' => ['o' => 0, 'l' => 1],
				'o' => 'b.create_at desc',
			];
			if ($tmplmsg = $modelUsr->query_objs_ss($q, $q2)) {
				$user->tmplmsg = $tmplmsg[0];
			} else {
				$user->tmplmsg = new \stdClass;
			}
			foreach ($oApp->dataSchemas as $v1) {
				if ($v1->id == '_round_id') {
					$ops = $v1->ops;
				}
			}
			if (isset($ops)) {
				foreach ($ops as $v) {
					if (isset($user->group_id) && $v->v == $user->group_id) {
						$user->group = $v;
					} else if (isset($user->user->group_id) && $v->v == $user->user->group_id) {
						$user->group = $v;
					}
				}
			}
		}

		require_once TMS_APP_DIR . '/lib/PHPExcel.php';
		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator(APP_TITLE)
			->setLastModifiedBy(APP_TITLE)
			->setTitle($oApp->title)
			->setSubject($oApp->title)
			->setDescription($oApp->title);

		$objPHPExcel->setActiveSheetIndex(0);
		$objActiveSheet = $objPHPExcel->getActiveSheet();
		$objActiveSheet->setTitle('已参与活动人员');
		$columnNum1 = 0; //列号
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '序号');
		// 转换标题
		if (!empty($mschema)) {
			if ($oMschema->attr_name[0] === '0') {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '姓名');
			}
			if ($oMschema->attr_mobile[0] === '0') {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '手机号');
			}
			if ($oMschema->attr_email[0] === '0') {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '电子邮箱');
			}
			if (!empty($oMschema->extattr)) {
				foreach ($oMschema->extattr as $extattr) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, $extattr->label);
				}
			}
			if (!empty($oApp->group_app_id)) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '分组');
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '记录');
			if (isset($oUserTask->minEnrollNum) && $oUserTask->minEnrollNum > 0) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '要求记录数');
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '评论');
			if (isset($oUserTask->minRemarkNum) && $oUserTask->minRemarkNum > 0) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '要求评论数');
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '点赞');
			if (isset($oUserTask->minLikeNum) && $oUserTask->minLikeNum > 0) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '要求赞同数');
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '积分');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '得分');
			if (isset($sns->wx->joined) && $sns->wx->joined === 'Y') {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '已关联微信');
			}
			if (isset($sns->yx->joined) && $sns->yx->joined === 'Y') {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '已关联易信');
			}
			if (isset($sns->qy->joined) && $sns->qy->joined === 'Y') {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '已关联微企');
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '最后一次通知发送时间');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '最后一次通知发送结果');

			// 转换数据
			for ($j = 0; $j < count($data); $j++) {
				$record = $data[$j];
				$rowIndex = $j + 2;
				$columnNum2 = 0; //列号

				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $j + 1);
				if ($oMschema->attr_name[0] === '0') {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->name);
				}
				if ($oMschema->attr_mobile[0] === '0') {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->mobile);
				}
				if ($oMschema->attr_email[0] === '0') {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->email);
				}
				if (!empty($oMschema->extattr)) {
					foreach ($oMschema->extattr as $extattr) {
						if (isset($record->extattr->{$extattr->id})) {
							$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->extattr->{$extattr->id});
						} else {
							$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, '');
						}
					}
				}

				if (!empty($oApp->group_app_id)) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, empty($record->group) ? '' : $record->group->l);
				}
				if (isset($record->user->enroll_num)) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->user->enroll_num);
				} else {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, '');
				}
				if (isset($oUserTask->minEnrollNum) && $oUserTask->minEnrollNum > 0) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUserTask->minEnrollNum);
				}
				if (isset($record->user->remark_other_num)) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->user->remark_other_num);
				} else {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, '');
				}
				if (isset($oUserTask->minRemarkNum) && $oUserTask->minRemarkNum > 0) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUserTask->minRemarkNum);
				}
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->user->like_other_num);
				if (isset($oUserTask->minLikeNum) && $oUserTask->minLikeNum > 0) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUserTask->minLikeNum);
				}
				if (isset($record->user->user_total_coin)) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->user->user_total_coin);
				} else {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, '');
				}
				if (isset($record->user->score)) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->user->score);
				} else {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, '');
				}
				if (isset($sns->wx->joined) && $sns->wx->joined === 'Y') {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, !empty($record->wx_openid) ? "是" : '');
				}
				if (isset($sns->yx->joined) && $sns->yx->joined === 'Y') {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, !empty($record->yx_openid) ? "是" : '');
				}
				if (isset($sns->qy->joined) && $sns->qy->joined === 'Y') {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, !empty($record->qy_openid) ? "是" : '');
				}
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, isset($record->tmplmsg->create_at) ? date('Y-m-d H:i:s') : '');
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, isset($record->tmplmsg->status) ? $record->tmplmsg->status : '');
			}
		} else {
			// 转换标题
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '用户');
			if (!empty($oApp->group_app_id)) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '分组');
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '记录');
			if (isset($oUserTask->minEnrollNum) && $oUserTask->minEnrollNum > 0) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '要求记录数');
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '评论');
			if (isset($oUserTask->minRemarkNum) && $oUserTask->minRemarkNum > 0) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '要求评论数');
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '点赞');
			if (isset($oUserTask->minLikeNum) && $oUserTask->minLikeNum > 0) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '要求赞同数');
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '积分');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '得分');
			if (isset($sns->wx->joined) && $sns->wx->joined === 'Y') {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '已关联微信');
			}
			if (isset($sns->yx->joined) && $sns->yx->joined === 'Y') {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '已关联易信');
			}
			if (isset($sns->qy->joined) && $sns->qy->joined === 'Y') {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '已关联微企');
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '最后一次通知发送时间');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '最后一次通知发送结果');
			// 转换数据
			for ($j = 0; $j < count($data); $j++) {
				$record = $data[$j];
				$rowIndex = $j + 2;
				$columnNum2 = 0; //列号

				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $j + 1);
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->nickname);
				if (!empty($oApp->group_app_id)) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, empty($record->group) ? '' : $record->group->l);
				}
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->enroll_num);
				if (isset($oUserTask->minEnrollNum) && $oUserTask->minEnrollNum > 0) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUserTask->minEnrollNum);
				}
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->remark_other_num);
				if (isset($oUserTask->minRemarkNum) && $oUserTask->minRemarkNum > 0) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUserTask->minRemarkNum);
				}
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->like_other_num);
				if (isset($oUserTask->minLikeNum) && $oUserTask->minLikeNum > 0) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUserTask->minLikeNum);
				}
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->user_total_coin);
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->score);
				if (isset($sns->wx->joined) && $sns->wx->joined === 'Y') {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, !empty($record->wx_openid) ? "是" : '');
				}
				if (isset($sns->yx->joined) && $sns->yx->joined === 'Y') {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, !empty($record->yx_openid) ? "是" : '');
				}
				if (isset($sns->qy->joined) && $sns->qy->joined === 'Y') {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, !empty($record->qy_openid) ? "是" : '');
				}
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, isset($record->tmplmsg->create_at) ? date('Y-m-d H:i:s') : '');
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, isset($record->tmplmsg->status) ? $record->tmplmsg->status : '');
			}
		}

		/* 未签到用户 */
		$result = $modelUsr->absentByApp($oApp, $data, $rid);
		$absentUsers = $result->users;
		if (count($absentUsers)) {
			$objPHPExcel->createSheet();
			$objPHPExcel->setActiveSheetIndex(1);
			$objActiveSheet2 = $objPHPExcel->getActiveSheet();
			$objActiveSheet2->setTitle('缺席人员');

			$colNumber = 0;
			$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '序号');
			$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '姓名');
			$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '分组');
			$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '备注');

			$rowNumber = 2;
			foreach ($absentUsers as $k => $absentUser) {
				$colNumber = 0;
				$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, $k + 1);
				$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, $absentUser->nickname);
				if (isset($absentUser->round_title)) {
					$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, $absentUser->round_title);
				} else {
					$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
				}
				$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, $absentUser->absent_cause->cause);

				$rowNumber++;
			}
		}

		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Cache-Control: max-age=0');

		$filename = $oApp->title . '.xlsx';
		$ua = $_SERVER["HTTP_USER_AGENT"];
		if (preg_match("/MSIE/", $ua) || preg_match("/Trident\/7.0/", $ua)) {
			$encoded_filename = urlencode($filename);
			$encoded_filename = str_replace("+", "%20", $encoded_filename);
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
	private function _refresh($oApp) {
		$count = 0;

		return $count;
	}
	/**
	 * 根据用户的填写记录更新用户数据
	 */
	public function repair_action($app, $rid = '', $onlyCheck = 'Y') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		$aUpdatedResult = $modelUsr->renew($oApp, $rid, $onlyCheck);

		return new \ResponseData($aUpdatedResult);
	}
}