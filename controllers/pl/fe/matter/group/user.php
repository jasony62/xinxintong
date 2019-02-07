<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 分组活动控制器
 */
class user extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/group/frame');
		exit;
	}
	/**
	 * 返回分组用户数据
	 */
	public function list_action($app) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelGrp = $this->model('matter\group');

		$oApp = $modelGrp->byId($app);
		if (false === $oApp && $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();

		$aOptions = [];
		if (isset($oPosted->roleTeamId)) {
			$aOptions['roleTeamId'] = $modelPlayer->escape($oPosted->roleTeamId);
		}
		if (isset($oPosted->teamId)) {
			$aOptions['teamId'] = $modelPlayer->escape($oPosted->teamId);
		}
		if (!empty($oPosted->kw) && !empty($oPosted->by)) {
			$aOptions[$oPosted->by] = $oPosted->kw;
		}

		$modelGrpUsr = $this->model('matter\group\user');
		$oResult = $modelGrpUsr->byApp($oApp, $aOptions);

		return new \ResponseData($oResult);
	}
	/**
	 * 分组用户数量
	 */
	public function count_action($app) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$q = [
			'count(*)',
			"xxt_group_user",
			['aid' => $app, 'state' => 1],
		];

		$cnt = (int) $this->model()->query_val_ss($q);

		return new \ResponseData($cnt);
	}
	/**
	 * 从其他活动导入数据
	 */
	public function assocWithApp_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelGrp = $this->model('matter\group');
		$oApp = $modelGrp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$oParams = $this->getPostJson();
		$oSourceApp = null;
		if (!empty($oParams->app)) {
			$modelGrpUsr = $this->model('matter\group\user');
			if ($oParams->appType === 'registration') {
				$oSourceApp = $modelGrpUsr->assocWithEnroll($oApp, $oParams->app);
			} else if ($oParams->appType === 'signin') {
				$oSourceApp = $modelGrpUsr->assocWithSignin($oApp, $oParams->app);
			} else if ($oParams->appType === 'wall') {
				$oSourceApp = $modelGrpUsr->assocWithWall($oApp, $oParams->app, $oParams->onlySpeaker);
			} else if ($oParams->appType === 'mschema') {
				$oSourceApp = $modelGrpUsr->assocWithMschema($oApp, $oParams->app);
			}
		}

		return new \ResponseData($oSourceApp);
	}
	/**
	 * 将用户移入分组
	 */
	public function joinGroup_action($app, $team) {
		if (false === ($oOpUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oApp = $this->model('matter\group')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$eks = $this->getPostJson();
		if (empty($eks)) {
			return new \ResponseError('没有指定用户');
		}

		$oTeam = $this->model('matter\group\team')->byId($team);
		if (false === $oTeam) {
			return new \ObjectNotFoundError();
		}

		$oResult = new \stdClass;
		$modelUsr = $this->model('matter\group\user');
		foreach ($eks as $ek) {
			if ($oUser = $modelUsr->byId($oApp->id, $ek)) {
				if ($modelUsr->joinGroup($oApp->id, $oTeam, $ek)) {
					$oResult->{$ek} = $oTeam->team_id;
				} else {
					$oResult->{$ek} = false;
				}
			} else {
				$oResult->{$ek} = false;
			}
		}

		// 记录操作日志
		$this->model('matter\log')->matterOp($oApp->siteid, $oOpUser, $oApp, 'joinGroup', $oResult);

		return new \ResponseData($oResult);
	}
	/**
	 * 将用户移出分组 (团队分组)
	 */
	public function quitGroup_action($app) {
		if (false === ($oOpUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oApp = $this->model('matter\group')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		$eks = $this->getPostJson();
		if (empty($eks)) {
			return new \ResponseError('没有指定用户');
		}

		$oResult = new \stdClass;
		$modelUsr = $this->model('matter\group\user');
		foreach ($eks as $ek) {
			if ($oUser = $modelUsr->byId($oApp->id, $ek)) {
				if ($modelUsr->quitGroup($oApp->id, $ek)) {
					$oResult->{$ek} = $oUser->team_id;
				} else {
					$oResult->{$ek} = false;
				}
			} else {
				$oResult->{$ek} = false;
			}
		}

		// 记录操作日志
		$this->model('matter\log')->matterOp($oApp->siteid, $oOpUser, $oApp, 'quitGroup', $oResult);

		return new \ResponseData($oResult);
	}
	/**
	 * 更新用户数据
	 *
	 * @param string $site
	 * @param string $app
	 * @param $ek record's key
	 */
	public function update_action($app, $ek) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		$modelGrp = $this->model('matter\group');
		$modelUsr = $this->model('matter\group\user');

		$oApp = $modelGrp->byId($app);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oBeforeUser = $modelUsr->byId($oApp->id, $ek, ['fields' => 'userid,state']);
		if (false === $oBeforeUser || $oBeforeUser->state !== '1') {
			return new \ObjectNotFoundError();
		}

		/* 更新记录数据 */
		$oNewPlayer = new \stdClass;
		if (isset($oPosted->is_leader)) {
			$oNewPlayer->is_leader = in_array($oPosted->is_leader, ['Y', 'N', 'S']) ? $oPosted->is_leader : 'N';
		}
		if (isset($oPosted->comment)) {
			$oNewPlayer->comment = $oPosted->comment;
		}
		if (isset($oPosted->tags)) {
			$oNewPlayer->tags = $modelUsr->escape($oPosted->tags);
		}
		if (empty($oPosted->team_id)) {
			$oNewPlayer->team_id = '';
			$oNewPlayer->team_title = '';
		} else {
			$modelTeam = $this->model('matter\group\team');
			if ($oTeam = $modelTeam->byId($oPosted->team_id)) {
				$oNewPlayer->team_id = $oPosted->team_id;
				$oNewPlayer->team_title = $oTeam->title;
			}
		}
		if (empty($oPosted->role_teams)) {
			$oNewPlayer->role_teams = '';
		} else if (!empty($oBeforeUser->userid)) {
			$roleTeams = array_map(function ($oTeam) {return $oTeam;}, $oPosted->role_teams);
			$oNewPlayer->role_teams = json_encode($roleTeams);
		}

		$modelUsr->update(
			'xxt_group_user',
			$oNewPlayer,
			["aid" => $oApp->id, "enroll_key" => $ek]
		);
		/* 更新用户数据 */
		$aResult = $modelUsr->setData($oApp, $ek, $oPosted->data);
		if (false === $aResult[0]) {
			return new \ResponseError($aResult[1]);
		}

		$oNewPlayer = $modelUsr->byId($oApp->id, $ek);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'update', $oNewPlayer);

		return new \ResponseData($oNewPlayer);
	}
	/**
	 * 手工添加分组用户信息
	 *
	 * @param string $app
	 */
	public function add_action($site, $app) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		$current = time();
		$modelGrp = $this->model('matter\group');
		$modelUsr = $this->model('matter\group\user');

		$oApp = $modelGrp->byId($app);
		$ek = $modelUsr->genKey($oApp->siteid, $oApp->id);
		/**
		 * 分组用户登记数据
		 */
		$oEnrollee = new \stdClass;
		$oEnrollee->uid = '';
		$oEnrollee->nickname = '';

		$oGrpUser = new \stdClass;
		$oGrpUser->enroll_key = $ek;
		$oGrpUser->enroll_at = $current;
		$oGrpUser->comment = isset($oPosted->comment) ? $oPosted->comment : '';
		if (isset($oPosted->tags)) {
			$oGrpUser->tags = $oPosted->tags;
			$modelGrp->updateTags($oApp->id, $oPosted->tags);
		}
		if (!empty($oPosted->team_id)) {
			$modelTeam = $this->model('matter\group\team');
			$oTeam = $modelTeam->byId($oPosted->team_id);
			$oGrpUser->team_id = $oPosted->team_id;
			$oGrpUser->team_title = $oTeam->title;
		}

		$modelUsr->enroll($oApp, $oEnrollee, $oGrpUser);
		$aResult = $modelUsr->setData($oApp, $ek, $oPosted->data);
		if (false === $aResult[0]) {
			return new \ResponseError($aResult[1]);
		}
		$oGrpUser->data = json_decode($aResult[1]);
		$oGrpUser->role_teams = [];

		return new \ResponseData($oGrpUser);
	}
	/**
	 * 清空一条登记信息
	 */
	public function remove_action($app, $ek, $keepData = 'Y') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\group\user')->remove($app, $ek, $keepData === 'N');

		return new \ResponseData($rst);
	}
	/**
	 * 清空登记信息
	 */
	public function empty_action($app, $keepData = 'Y') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelGrpUsr = $this->model('matter\group\user');
		$modelGrpUsr->clean($app, $keepData === 'N');

		$rst = $modelGrpUsr->update('xxt_group', ['last_sync_at' => 0], ['id' => $app]);

		return new \ResponseData($rst);
	}
	/**
	 * 从关联活动同步数据
	 *
	 * 同步在最后一次同步之后的数据或已经删除的数据
	 */
	public function syncByApp_action($app, $onlySpeaker = 'N') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelGrp = $this->model('matter\group');
		$oApp = $modelGrp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		$count = 0;
		if (!empty($oApp->source_app)) {
			$sourceApp = json_decode($oApp->source_app);
			if ($sourceApp->type === 'enroll') {
				$count = $this->_syncByEnroll($oApp->siteid, $oApp, $sourceApp->id);
			} else if ($sourceApp->type === 'signin') {
				$count = $this->_syncBySignin($oApp->siteid, $oApp, $sourceApp->id);
			} else if ($sourceApp->type === 'wall') {
				$count = $this->_syncByWall($oApp->siteid, $oApp, $sourceApp->id, $onlySpeaker);
			} else if ($sourceApp->type === 'mschema') {
				$count = $this->_syncByMschema($oApp->siteid, $oApp, $sourceApp->id);
			}
			// 更新同步时间
			$modelGrp->update(
				'xxt_group',
				['last_sync_at' => time()],
				['id' => $oApp->id]
			);
		}

		return new \ResponseData($count);
	}
	/**
	 * 从通讯录导入数据
	 *
	 * 同步在最后一次同步之后的数据或已经删除的数据
	 */
	private function _syncByMschema($siteId, &$objGrp, $bySchema) {
		/* 获取变化的登记数据 */
		$modelRec = $this->model('site\user\member');
		$q = [
			'id enroll_key,forbidden state',
			'xxt_site_member',
			"schema_id = $bySchema and (modify_at > {$objGrp->last_sync_at} or forbidden <> 'N')",
		];
		$records = $modelRec->query_objs_ss($q);

		$modelUsr = $this->model('matter\group\user');

		return $modelUsr->syncRecord($siteId, $objGrp, $records, $modelRec, 'mschema');
	}
	/**
	 * 从登记活动导入数据
	 *
	 * 同步在最后一次同步之后的数据或已经删除的数据
	 */
	private function _syncByEnroll($siteId, &$objGrp, $byApp) {
		/* 获取变化的登记数据 */
		$modelRec = $this->model('matter\enroll\record');
		$q = [
			'enroll_key,state',
			'xxt_enroll_record',
			"aid='$byApp' and (enroll_at>{$objGrp->last_sync_at} or state<>1)",
		];
		$records = $modelRec->query_objs_ss($q);

		$modelUsr = $this->model('matter\group\user');

		return $modelUsr->syncRecord($siteId, $objGrp, $records, $modelRec);
	}
	/**
	 * 从签到活动导入数据
	 *
	 * 同步在最后一次同步之后的数据或已经删除的数据
	 */
	private function _syncBySignin($siteId, &$objGrp, $byApp) {
		/* 获取数据 */
		$modelRec = $this->model('matter\signin\record');
		$q = array(
			'enroll_key,state',
			'xxt_signin_record',
			"aid='$byApp' and (enroll_at>{$objGrp->last_sync_at} or state<>1)",
		);
		$records = $modelRec->query_objs_ss($q);

		$modelUsr = $this->model('matter\group\user');

		return $modelUsr->syncRecord($siteId, $objGrp, $records, $modelRec);
	}
	/**
	 * 同步在最后一次同步之后的数据
	 * $onlySpeaker 是否为发言的用户
	 */
	private function _syncByWall($siteId, &$objGrp, $byApp, $onlySpeaker) {
		//获取新增用户数据
		$u = array(
			'*',
			'xxt_wall_enroll',
			"wid = '{$byApp}' and siteid = '{$siteId}' and join_at > {$objGrp->last_sync_at} ",
		);
		if ($onlySpeaker === 'Y') {
			$u[2] .= " and last_msg_at>0";
		}
		$wallUsers = $this->model()->query_objs_ss($u);

		$modelUsr = $this->model('matter\group\user');
		if (!empty($wallUsers)) {
			foreach ($wallUsers as $wallUser) {
				$wallUser->data = empty($wallUser->data) ? '' : json_decode($wallUser->data);
				$oUser = new \stdClass;
				$oUser->uid = $wallUser->userid;
				$oUser->nickname = $wallUser->nickname;
				if ($modelUsr->byId($objGrp->id, $wallUser->enroll_key, ['cascaded' => 'N'])) {
					// 已经同步过的用户
					$modelUsr->setData($objGrp, $wallUser->enroll_key, $wallUser->data);
				} else {
					// 新用户
					$modelUsr->enroll($objGrp, $oUser, ['enroll_key' => $wallUser->enroll_key, 'enroll_at' => $wallUser->join_at]);
					$modelUsr->setData($objGrp, $wallUser->enroll_key, $wallUser->data);
				}
			}
		}

		return count($wallUsers);
	}
	/**
	 * 从关联活动同步数据
	 *
	 * 同步在最后一次同步之后的数据或已经删除的数据
	 */
	public function addByApp_action($app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelGrp = $this->model('matter\group');
		$oApp = $modelGrp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		// 添加记录的id数组
		$ids = $this->getPostJson();
		$count = 0;
		if (!empty($oApp->source_app)) {
			$sourceApp = json_decode($oApp->source_app);
			if ($sourceApp->type === 'mschema') {
				$count = $this->_addByMschema($oApp, $sourceApp->id, $ids);
			}
		}

		return new \ResponseData($count);
	}
	/**
	 * 从通讯录添加数据
	 */
	private function _addByMschema($oGrpApp, $bySchema, $ids) {
		$modelRec = $this->model('site\user\member');
		$q = [
			'id enroll_key,forbidden state',
			'xxt_site_member',
			['schema_id' => $bySchema, 'id' => $ids],
		];
		$records = $modelRec->query_objs_ss($q);

		$modelUsr = $this->model('matter\group\user');

		return $modelUsr->syncRecord($oGrpApp->siteid, $oGrpApp, $records, $modelRec, 'mschema');
	}
	/**
	 * 未分组的人
	 *
	 * @param $teamType 分组类型 “T” 团队分组，"R" 角色分组
	 */
	public function pendingsGet_action($app, $rid = null, $teamType = 'T') {
		if ($teamType === 'R') {
			$result = $this->model('matter\group\user')->pendingsRole($app);
		} else {
			$result = $this->model('matter\group\user')->pendings($app);
		}

		return new \ResponseData($result);
	}
	/**
	 * 导出分组数据
	 */
	public function export_action($app) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelGrp = $this->model('matter\group');

		$oApp = $modelGrp->byId($app);
		$schemas = $oApp->dataSchemas;
		$oResult = $this->model('matter\group\user')->byApp($app);
		if ($oResult->total == 0) {
			die('users empty');
		}
		$grpUsers = $oResult->users;

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

		$colNumber = 0;
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '分组');

		// 转换标题
		foreach ($schemas as $oSchema) {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, $oSchema->title);
		}
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '备注');
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '标签');

		// 转换数据
		$rowNumber = 2;
		foreach ($grpUsers as $oGrpUser) {
			$colNumber = 0;
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $oGrpUser->team_title);
			// 处理登记项
			$oData = (object) $oGrpUser->data;
			foreach ($schemas as $oSchema) {
				$v = $this->getDeepValue($oData, $oSchema->id, '');
				switch ($oSchema->type) {
				case 'single':
					foreach ($oSchema->ops as $op) {
						if ($op->v === $v) {
							$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $op->l);
							$disposed = true;
							break;
						}
					}
					empty($disposed) && $objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $v);
					break;
				case 'multiple':
					$labels = [];
					$v = explode(',', $v);
					foreach ($v as $oneV) {
						foreach ($oSchema->ops as $op) {
							if ($op->v === $oneV) {
								$labels[] = $op->l;
								break;
							}
						}
					}
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, implode(',', $labels));
					break;
				default:
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $v);
					break;
				}
			}
			// 备注
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, empty($oGrpUser->comment) ? '' : $oGrpUser->comment);
			// 标签
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, empty($oGrpUser->tags) ? '' : $oGrpUser->tags);

			// next row
			$rowNumber++;
		}

		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $oApp->title . '.xlsx"');
		header('Cache-Control: max-age=0');
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');

		exit;
	}
}