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

		$oResult = $modelUsr->enrolleeByApp($oApp, $page, $size, $aOptions);
		/* 由于版本原因，判断是否需要系统获取填写人信息 */
		if (0 === count($oResult->users)) {
			if ($this->_refresh($oApp) > 0) {
				$oResult = $modelUsr->enrolleeByApp($oApp, $page, $size, $aOptions);
			}
		}

		/* 查询有openid的用户发送消息的情况 */
		if (count($oResult->users)) {
			if (!empty($oApp->group_app_id)) {
				foreach ($oApp->dataSchemas as $schema) {
					if ($schema->id == '_round_id') {
						$aUserRounds = $schema->ops;
						break;
					}
				}
			}
			foreach ($oResult->users as &$user) {
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

		return new \ResponseData($oResult);
	}
	/**
	 * 未完成任务用户列表
	 */
	public function undone_action($app, $rid = '') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}
		empty($rid) && $rid = 'ALL';

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N', 'fields' => 'siteid,id,state,mission_id,entry_rule,action_rule,group_app_id,absent_cause']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		$oResult = $modelUsr->undoneByApp($oApp, $rid);

		return new \ResponseData($oResult);
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
		$oResult = $modelUsr->enrolleeByMschema($oApp, $oMschema, $page, $size, $options);
		/*查询有openid的用户发送消息的情况*/
		if (count($oResult->members)) {
			foreach ($oResult->members as $member) {
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

		return new \ResponseData($oResult);
	}
	/**
	 * 发表过留言的用户
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
		$oResult = $modelUsr->remarkerByApp($oApp, $page, $size);

		return new \ResponseData($oResult);
	}
	/**
	 * 数据导出
	 */
	public function export_action($site, $app, $mschema = '', $rid = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		if (false === ($oApp = $this->model('matter\enroll')->byId($app, ['fields' => 'siteid,id,title,entry_rule,group_app_id,data_schemas,absent_cause', 'cascaded' => 'N']))) {
			return new \ParameterError();
		}

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
			$oResult = $modelUsr->enrolleeByMschema($oApp, $oMschema, $page = '', $size = '', $options);
			$data = $oResult->members;
		} else {
			$options = [];
			!empty($rid) && $options['rid'] = $rid;
			$oResult = $modelUsr->enrolleeByApp($oApp, $page = '', $size = '', $options);
			$data = $oResult->users;
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
		$objActiveSheet->setTitle('已参与');
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
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '留言');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '点赞');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '获得推荐');
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
				if (isset($record->user->do_remark_num)) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->user->do_remark_num);
				} else {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, '');
				}
				if (isset($record->user->do_like_num)) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->user->do_like_num);
				} else {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, '');
				}
				if (isset($record->user->agree_num)) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->user->agree_num);
				} else {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, '');
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
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '留言');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '点赞');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '获得推荐');
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
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->do_remark_num);
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->do_like_num);
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->agree_num);
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

		/* 未完成活动任务用户 */
		$oResult = $modelUsr->undoneByApp($oApp, $rid);
		$undoneUsers = $oResult->users;
		if (count($undoneUsers)) {
			$objPHPExcel->createSheet();
			$objPHPExcel->setActiveSheetIndex(1);
			$objActiveSheet2 = $objPHPExcel->getActiveSheet();
			$objActiveSheet2->setTitle('缺席');

			$colNumber = 0;
			$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '序号');
			$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '姓名');
			$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '分组');
			$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '备注');

			$rowNumber = 2;
			foreach ($undoneUsers as $k => $oUndoneUser) {
				$colNumber = 0;
				$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, $k + 1);
				$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, $oUndoneUser->nickname);
				$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($oUndoneUser->round_title) ? $oUndoneUser->round_title : '');
				$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($oUndoneUser->absent_cause->cause) ? $oUndoneUser->absent_cause->cause : '');

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
	/**
	 * 根据用户对应的分组信息
	 */
	public function repairGroup_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		if (!empty($oApp->group_app_id)) {
			$assocGroupId = $oApp->group_app_id;
		} else if (isset($oApp->entryRule->scope->group) && $oApp->entryRule->scope->group === 'Y' && isset($oApp->entryRule->group->id)) {
			$assocGroupId = $oApp->entryRule->group->id;
		}

		if (!isset($assocGroupId)) {
			return new \ResponseError('没有指定关联的分组活动');
		}

		$updatedCount = 0;
		$oAssocGrpApp = (object) ['id' => $assocGroupId];
		$modelGrpUsr = $this->model('matter\group\player');
		$q = [
			'id,userid,group_id',
			'xxt_enroll_user',
			['aid' => $oApp->id, 'state' => 1],
		];
		$oEnrolleeGroups = new \stdClass; // 用户和分组的对应
		$enrollees = $modelGrpUsr->query_objs_ss($q);
		foreach ($enrollees as $oEnrollee) {
			if (isset($oEnrolleeGroups->{$oEnrollee->userid})) {
				$groupId = $oEnrolleeGroups->{$oEnrollee->userid};
			} else {
				$oGrpMemb = $modelGrpUsr->byUser($oAssocGrpApp, $oEnrollee->userid, ['fields' => 'round_id', 'onlyOne' => true]);
				$groupId = $oEnrolleeGroups->{$oEnrollee->userid} = $oGrpMemb ? $oGrpMemb->round_id : '';
			}
			if ($oEnrollee->group_id !== $groupId) {
				$modelGrpUsr->update('xxt_enroll_user', ['group_id' => $groupId], ['id' => $oEnrollee->id]);
				$updatedCount++;
			}
		}

		return new \ResponseData($updatedCount);
	}
}