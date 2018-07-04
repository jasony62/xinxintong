<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 分组活动控制器
 */
class player extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/group/frame');
		exit;
	}
	/**
	 * 查看分组数据
	 */
	public function list_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelGrp = $this->model('matter\group');
		$modelPlayer = $this->model('matter\group\player');

		$oApp = $modelGrp->byId($app);
		$filter = $this->getPostJson();
		$options = [];
		if (isset($filter->roleRoundId)) {
			$options['roleRoundId'] = $modelPlayer->escape($filter->roleRoundId);
		}
		$result = $modelPlayer->byApp($oApp, $options);

		return new \ResponseData($result);
	}
	/**
	 * 导出分组数据
	 */
	public function export_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelGrp = $this->model('matter\group');
		$modelPlayer = $this->model('matter\group\player');

		$app = $modelGrp->byId($app);
		$schemas = json_decode($app->data_schemas);
		$result = $modelPlayer->byApp($app);
		if ($result->total == 0) {
			die('player empty');
		}
		$players = $result->players;

		require_once TMS_APP_DIR . '/lib/PHPExcel.php';

		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator("信信通")
			->setLastModifiedBy("信信通")
			->setTitle($app->title)
			->setSubject($app->title)
			->setDescription($app->title);

		$objActiveSheet = $objPHPExcel->getActiveSheet();

		$colNumber = 0;
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '分组');

		// 转换标题
		foreach ($schemas as $schema) {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, $schema->title);
		}
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '备注');
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '标签');

		// 转换数据
		$rowNumber = 2;
		foreach ($players as $player) {
			$colNumber = 0;
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $player->round_title);
			// 处理登记项
			$data = (object) $player->data;
			foreach ($schemas as $schema) {
				$v = isset($data->{$schema->id}) ? $data->{$schema->id} : '';
				switch ($schema->type) {
				case 'single':
					foreach ($schema->ops as $op) {
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
						foreach ($schema->ops as $op) {
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
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, empty($player->comment) ? '' : $player->comment);
			// 标签
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, empty($player->tags) ? '' : $player->tags);

			// next row
			$rowNumber++;
		}

		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $app->title . '.xlsx"');
		header('Cache-Control: max-age=0');
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');

		exit;
	}
	/**
	 * 分组用户数量
	 */
	public function count_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$q = array(
			'count(*)',
			"xxt_group_player",
			"aid='$app' and state=1",
		);

		$cnt = $this->model()->query_val_ss($q);

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
			$modelGrpUsr = $this->model('matter\group\player');
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
	 * 从关联活动同步数据
	 *
	 * 同步在最后一次同步之后的数据或已经删除的数据
	 */
	public function syncByApp_action($site, $app, $onlySpeaker = 'N') {
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
				$count = $this->_syncByEnroll($site, $oApp, $sourceApp->id);
			} else if ($sourceApp->type === 'signin') {
				$count = $this->_syncBySignin($site, $oApp, $sourceApp->id);
			} else if ($sourceApp->type === 'wall') {
				$count = $this->_syncByWall($site, $oApp, $sourceApp->id, $onlySpeaker);
			} else if ($sourceApp->type === 'mschema') {
				$count = $this->_syncByMschema($site, $oApp, $sourceApp->id);
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

		$modelPly = $this->model('matter\group\player');
		return $modelPly->_syncRecord($siteId, $objGrp, $records, $modelRec, 'mschema');
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

		$modelPly = $this->model('matter\group\player');
		return $modelPly->_syncRecord($siteId, $objGrp, $records, $modelRec);
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

		$modelPly = $this->model('matter\group\player');
		return $modelPly->_syncRecord($siteId, $objGrp, $records, $modelRec);
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

		$modelPly = $this->model('matter\group\player');
		if (!empty($wallUsers)) {
			foreach ($wallUsers as $wallUser) {
				$wallUser->data = empty($wallUser->data) ? '' : json_decode($wallUser->data);
				$user = new \stdClass;
				$user->uid = $wallUser->userid;
				$user->nickname = $wallUser->nickname;
				$user->wx_openid = $wallUser->wx_openid;
				$user->yx_openid = $wallUser->yx_openid;
				$user->qy_openid = $wallUser->qy_openid;
				$user->headimgurl = $wallUser->headimgurl;
				if ($modelPly->byId($objGrp->id, $wallUser->enroll_key, ['cascaded' => 'N'])) {
					// 已经同步过的用户
					$modelPly->setData($objGrp, $wallUser->enroll_key, $wallUser->data);
				} else {
					// 新用户
					$modelPly->enroll($objGrp, $user, ['enroll_key' => $wallUser->enroll_key, 'enroll_at' => $wallUser->join_at]);
					$modelPly->setData($objGrp, $wallUser->enroll_key, $wallUser->data);
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

		$modelPly = $this->model('matter\group\player');
		return $modelPly->_syncRecord($oGrpApp->siteid, $oGrpApp, $records, $modelRec, 'mschema');
	}
	/**
	 * 手工添加分组用户信息
	 *
	 * @param string $app
	 */
	public function add_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		$current = time();
		$modelGrp = $this->model('matter\group');
		$modelPly = $this->model('matter\group\player');

		$oApp = $modelGrp->byId($app);
		$ek = $modelPly->genKey($oApp->siteid, $oApp->id);
		/**
		 * 分组用户登记数据
		 */
		$oEnrollee = new \stdClass;
		$oEnrollee->uid = '';
		$oEnrollee->nickname = '';
		$oEnrollee->wx_openid = '';
		$oEnrollee->yx_openid = '';
		$oEnrollee->qy_openid = '';
		$oEnrollee->headimgurl = '';

		$oPlayer = new \stdClass;
		$oPlayer->enroll_key = $ek;
		$oPlayer->enroll_at = $current;
		$oPlayer->comment = isset($oPosted->comment) ? $oPosted->comment : '';
		if (isset($oPosted->tags)) {
			$oPlayer->tags = $oPosted->tags;
			$modelGrp->updateTags($oApp->id, $oPosted->tags);
		}
		if (!empty($oPosted->round_id)) {
			$modelRnd = $this->model('matter\group\round');
			$round = $modelRnd->byId($oPosted->round_id);
			$oPlayer->round_id = $oPosted->round_id;
			$oPlayer->round_title = $round->title;
		}
		if (!empty($oPosted->role_rounds)) {
			$roleRounds = [];
			foreach ($oPosted->role_rounds as $roleRound) {
				$roleRounds[] = $roleRound;
			}
			$oPlayer->role_rounds = json_encode($roleRounds);
		}

		$modelPly->enroll($oApp, $oEnrollee, $oPlayer);
		$result = $modelPly->setData($oApp, $ek, $oPosted->data);
		if (false === $result[0]) {
			return new \ResponseError($result[1]);
		}
		$oPlayer->data = json_decode($result[1]);
		if (!empty($oPlayer->role_rounds)) {
			$oPlayer->role_rounds = json_decode($oPlayer->role_rounds);
		}

		return new \ResponseData($oPlayer);
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
		$modelPly = $this->model('matter\group\player');

		$oApp = $modelGrp->byId($app);
		if (false === $oApp || $oApp->state !== '1') {
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
			$oNewPlayer->tags = $modelPly->escape($oPosted->tags);
		}
		if (empty($oPosted->round_id)) {
			$oNewPlayer->round_id = '';
			$oNewPlayer->round_title = '';
		} else {
			$modelRnd = $this->model('matter\group\round');
			if ($round = $modelRnd->byId($oPosted->round_id)) {
				$oNewPlayer->round_id = $oPosted->round_id;
				$oNewPlayer->round_title = $round->title;
			}
		}
		if (empty($oPosted->role_rounds)) {
			$oNewPlayer->role_rounds = '';
		} else {
			$roleRounds = [];
			foreach ($oPosted->role_rounds as $roleRound) {
				$roleRounds[] = $roleRound;
			}
			$oNewPlayer->role_rounds = json_encode($roleRounds);
		}

		$modelPly->update(
			'xxt_group_player',
			$oNewPlayer,
			["aid" => $oApp->id, "enroll_key" => $ek]
		);
		/* 更新用户数据 */
		$result = $modelPly->setData($oApp, $ek, $oPosted->data);
		if (false === $result[0]) {
			return new \ResponseError($result[1]);
		}

		$oNewPlayer = $modelPly->byId($oApp->id, $ek);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'update', $oNewPlayer);

		return new \ResponseData($oPosted);
	}
	/**
	 * 未分组的人
	 */
	public function pendingsGet_action($app, $rid = null) {
		$result = $this->model('matter\group\player')->pendings($app);

		return new \ResponseData($result);
	}
	/**
	 * 清空一条登记信息
	 */
	public function remove_action($site, $app, $ek, $keepData = 'Y') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\group\player')->remove($app, $ek, $keepData === 'N');

		return new \ResponseData($rst);
	}
	/**
	 * 清空登记信息
	 */
	public function empty_action($app, $keepData = 'Y') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelGrpPly = $this->model('matter\group\player');
		$modelGrpPly->clean($app, $keepData === 'N');

		$rst = $modelGrpPly->update('xxt_group', ['last_sync_at' => 0], ['id' => $app]);

		return new \ResponseData($rst);
	}
	/**
	 * 将用户移出分组
	 */
	public function quitGroup_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$eks = $this->getPostJson();
		if (empty($eks)) {
			return new \ResponseError('没有指定用户');
		}

		$result = new \stdClass;
		$modelPly = $this->model('matter\group\player');
		foreach ($eks as $ek) {
			if ($player = $modelPly->byId($app, $ek)) {
				if ($modelPly->quitGroup($app, $ek)) {
					$result->{$ek} = $player->round_id;
				} else {
					$result->{$ek} = false;
				}
			} else {
				$result->{$ek} = false;
			}
		}

		// 记录操作日志
		$app = $this->model('matter\group')->byId($app, ['cascaded' => 'N']);
		$this->model('matter\log')->matterOp($site, $oUser, $app, 'quitGroup', $result);

		return new \ResponseData($result);
	}
	/**
	 * 将用户移入分组
	 */
	public function joinGroup_action($site, $app, $round) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$eks = $this->getPostJson();
		if (empty($eks)) {
			return new \ResponseError('没有指定用户');
		}

		$round = $this->model('matter\group\round')->byId($round);

		$result = new \stdClass;
		$modelPly = $this->model('matter\group\player');
		foreach ($eks as $ek) {
			if ($player = $modelPly->byId($app, $ek)) {
				if ($modelPly->joinGroup($app, $round, $ek)) {
					$result->{$ek} = $round->round_id;
				} else {
					$result->{$ek} = false;
				}
			} else {
				$result->{$ek} = false;
			}
		}

		// 记录操作日志
		$app = $this->model('matter\group')->byId($app, ['cascaded' => 'N']);
		$this->model('matter\log')->matterOp($site, $oUser, $app, 'joinGroup', $result);

		return new \ResponseData($result);
	}
}