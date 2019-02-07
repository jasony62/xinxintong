<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动用户
 */
class user extends base {
	/**
	 *
	 */
	public function get_action($app, $rid = '') {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ObjectNotFoundError();
		}

		$modelEnlUsr = $this->model('matter\enroll\user');
		$oEnlRndUser = $modelEnlUsr->byId($oApp, $this->who->uid, ['rid' => empty($rid) ? $oApp->appRound->rid : $rid]);
		if ($oEnlRndUser) {
			$oEnlAppUser = $modelEnlUsr->byId($oApp, $this->who->uid, ['rid' => 'ALL', 'fields' => 'custom']);
			$oEnlRndUser->custom = $oEnlAppUser->custom;
		}

		return new \ResponseData($oEnlRndUser);
	}
	/**
	 *
	 */
	public function get2_action($app, $rid = '') {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);
		/**
		 * 获取当前用户在登记活动中的数据
		 */
		if (!empty($rid) || isset($oApp->appRound->rid)) {
			$modelEnlUsr = $this->model('matter\enroll\user');
			$oEnlRndUser = $modelEnlUsr->byId($oApp, $oUser->uid, ['rid' => empty($rid) ? $oApp->appRound->rid : $rid]);
			if ($oEnlRndUser) {
				$oEnlAppUser = $modelEnlUsr->byId($oApp, $oUser->uid, ['rid' => 'ALL', 'fields' => 'custom']);
				$oEnlRndUser->custom = $oEnlAppUser->custom;
			}
			$oUser->enrollUser = $oEnlRndUser;
		}
		/**
		 * 获得当前活动的分组和当前用户所属的分组，是否为组长，及同组成员
		 */
		if (!empty($oApp->entryRule->group->id)) {
			$assocGroupAppId = $oApp->entryRule->group->id;
			$modelGrpUsr = $this->model('matter\group\user');
			$modelGrpTeam = $this->model('matter\group\team');
			/* 用户所属分组信息 */
			$oGrpApp = (object) ['id' => $assocGroupAppId];
			if (!empty($oUser->group_id)) {
				$GrpRoundTitle = $modelGrpTeam->byId($oUser->group_id, ['fields' => 'title']);
				$oUser->group_title = $GrpRoundTitle->title;
				// 同组成员
				$others = $modelGrpUsr->byRound($oGrpApp->id, $oUser->group_id, ['fields' => 'is_leader,userid,nickname']);
				$oUser->groupOthers = [];
				foreach ($others as $other) {
					if ($other->userid !== $oUser->uid) {
						$oUser->groupOthers[] = $other;
					}
				}
			}
			/* 获得角色分组信息 */
			if (!empty($oUser->role_teams)) {
				$roleTeams = $modelGrpTeam->byApp($assocGroupAppId, ['fields' => "team_id,title", 'team_type' => 'R']);
				foreach ($roleTeams as $oRoleTeam) {
					$roleTeams[$oRoleTeam->team_id] = $oRoleTeam;
				}
				foreach ($oUser->role_teams as $k => $oUsrRoleTeam) {
					$oUser->role_teams[$k] = $roleTeams[$oUsrRoleTeam];
				}
			}
		}

		return new \ResponseData($oUser);
	}
	/**
	 * 更新用户设置
	 */
	public function updateCustom_action($app) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ObjectNotFoundError();
		}

		$modelEnlUsr = $this->model('matter\enroll\user');
		$oEnlUser = $modelEnlUsr->byId($oApp, $this->who->uid, ['fields' => 'aid,userid,custom']);
		if (false === $oEnlUser) {
			$oEnlUser = $modelEnlUsr->add($oApp, $this->who);
			$oEnlUser->custom = new \stdClass;
		}

		$oPosted = $this->getPostJson();
		foreach ($oPosted as $prop => $val) {
			switch ($prop) {
			case 'cowork':
			case 'event':
			case 'favor':
			case 'input':
			case 'kanban':
			case 'list':
			case 'marks':
			case 'rank':
			case 'repos':
			case 'score':
			case 'share':
			case 'stat':
			case 'topic':
			case 'view':
			case 'votes':
				$oPurifiedVal = new \stdClass;
				if (is_object($val)) {
					foreach ($val as $prop2 => $val2) {
						switch ($prop2) {
						case 'nav':
							if (is_object($val2)) {
								$oPurifiedVal->nav = new \stdClass;
								foreach ($val2 as $prop3 => $val3) {
									switch ($prop3) {
									case 'stopTip':
										$oPurifiedVal->nav->stopTip = is_bool($val3) ? $val3 : false;
										break;
									}
								}
							}
							break;
						case 'act':
							if (is_object($val2)) {
								$oPurifiedVal->act = new \stdClass;
								foreach ($val2 as $prop3 => $val3) {
									switch ($prop3) {
									case 'stopTip':
										$oPurifiedVal->act->stopTip = is_bool($val3) ? $val3 : false;
										break;
									}
								}
							}
							break;
						}
					}
				}
				break;
			case 'profile':
				$oPurifiedVal = new \stdClass;
				if (is_object($val)) {
					foreach ($val as $prop2 => $val2) {
						switch ($prop2) {
						case 'public':
							$oPurifiedVal->public = ($val2 === true ? true : false);
							break;
						}
					}
				}
				break;
			}
			$oEnlUser->custom->{$prop} = $oPurifiedVal;
		}

		$modelEnlUsr->update(
			'xxt_enroll_user',
			['custom' => $modelEnlUsr->escape($modelEnlUsr->toJson($oEnlUser->custom))],
			['aid' => $oEnlUser->aid, 'userid' => $oEnlUser->userid]
		);

		return new \ResponseData('ok');
	}
	/**
	 * 返回当前用户任务完成的情况
	 */
	public function task_action($app) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);
		$options = [];
		if ($oActiveRound = $this->model('matter\enroll\round')->getActive($oApp)) {
			$options['rid'] = $oActiveRound->rid;
		}
		$oEnrollee = $this->model('matter\enroll\user')->byId($oApp, $oUser->uid, $options);

		return new \ResponseData($oEnrollee);
	}
	/**
	 * 列出填写人名单列表
	 */
	public function list_action($site, $app, $owner = 'U', $schema_id, $page = 1, $size = 30) {
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp && $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->getUser($oApp);
		//参与者列表
		$modelRnd = $this->model('matter\enroll\round');
		$rnd = $modelRnd->getActive($oApp);
		$rid = !empty($rnd) ? $rnd->rid : '';
		//全部或分组
		switch ($owner) {
		case 'G':
			$modelUsr = $this->model('matter\enroll\user');
			$options = ['fields' => 'group_id'];
			$oEnrollee = $modelUsr->byId($oApp, $oUser->uid, $options);
			$group_id = isset($oEnrollee->group_id) ? $oEnrollee->group_id : '';
			break;
		default:
			break;
		}
		//设定范围
		$q1 = [
			'*',
			'xxt_enroll_user',
			['siteid' => $site, 'aid' => $app, 'rid' => $rid],
		];
		isset($group_id) && $q1[2]['group_id'] = $group_id;
		$q2['o'] = "id asc";
		$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		$users = $modelEnl->query_objs_ss($q1, $q2);
		foreach ($users as $oUser) {
			//添加分组信息
			$dataSchemas = $oApp->dynaDataSchemas;
			foreach ($dataSchemas as $value) {
				if ($value->id == '_round_id') {
					$ops = $value->ops;
				}
			}
			if (isset($ops) && $oUser->group_id) {
				foreach ($ops as $p) {
					if ($oUser->group_id == $p->v) {
						$oUser->group = $p;
					}
				}
			}
			//通信录的信息
			if (isset($oApp->entryRule->scope->member) && $oApp->entryRule->scope->member === 'Y') {
				if (empty($schema_id)) {
					return new \ResponseError('传入的通信录ID参数不能为空！');
				}
				$addressbook = $modelEnl->query_obj_ss([
					'*',
					'xxt_site_member',
					['siteid' => $site, 'userid' => $oUser->userid, 'schema_id' => $schema_id],
				]);

				if ($addressbook) {
					if (isset($schema_id)) {
						$schema = $modelEnl->query_obj_ss(['id,title', 'xxt_site_member_schema', ['id' => $schema_id]]);
					}
					$extattr = json_decode($addressbook->extattr);
					$addressbook->schema_title = $schema->title;
					$addressbook->enroll_num = $oUser->enroll_num;
					$addressbook->do_remark_num = $oUser->do_remark_num;
					$addressbook->do_like_num = $oUser->do_like_num;
				}
				$oUser->mschema = $addressbook;
			}
			if (isset($oApp->entryRule->scope->sns) && $oApp->entryRule->scope->sns === 'Y') {
				//公众号的信息
				$sns = $modelEnl->query_obj_ss([
					'assoc_id,wx_openid,yx_openid,qy_openid,uname,headimgurl,ufrom,uid,unionid,nickname',
					'xxt_site_account',
					['siteid' => $site, 'uid' => $oUser->userid],
				]);
				$oUser->sns = $sns;
			}
		}

		$oResult = new \stdClass;
		$oResult->records = $users;
		$q1[0] = 'count(*)';
		$oResult->total = $modelEnl->query_val_ss($q1);

		return new \ResponseData($oResult);
	}
	/**
	 * 活动中用户的摘要信息
	 * 1、必须是在活动分组中的用户，或者是超级用户，或者是组长
	 * 2、支持按照轮次过滤
	 * 2、如果指定了轮次，支持看看缺席情况
	 */
	public function kanban_action($app, $rid = '', $gid = '', $page = 1, $size = 999) {
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N', 'fields' => 'siteid,id,state,mission_id,entry_rule,action_rule,absent_cause,data_schemas']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oVisitor = $this->getUser($oApp);

		/* 当前用户属于有查看看板权限的用户组 */
		if (!empty($oApp->actionRule->role->kanban->group)) {
			$bInKanbanGroup = $this->model('matter\group\user')->isInRound($oApp->actionRule->role->kanban->group, $oVisitor->uid);
			$oVisitor->bInKanbanGroup = $bInKanbanGroup;
		}
		/* 数据是否公开可见 */
		$fnIsKeepPrivate = function ($oUser) use ($oVisitor) {
			if ($this->getDeepValue($oUser, 'custom.profile.public') === true) {
				return false;
			}
			// if ($oUser->userid === $oVisitor->uid) {
			// 	return false;
			// }
			if (!empty($oVisitor->is_leader)) {
				if ($oVisitor->is_leader === 'S') {
					/* 超级用户可以查看所有信息 */
					return false;
				}
				if ($oVisitor->is_leader === 'Y') {
					if (isset($oUser->group->id) && isset($oVisitor->group_id) && $oUser->group->id === $oVisitor->group_id) {
						/* 同组组长可以查看组内用户 */
						return false;
					}
				}
				if (!empty($oVisitor->bInKanbanGroup)) {
					return false;
				}
			}

			return true;
		};

		/* 处理原始数据 */
		$oStat = new \stdClass;
		$fnSort = function (&$users, $prop) use ($oStat) {
			usort($users, function ($a, $b) use ($prop) {
				if ($a->{$prop} === $b->{$prop}) {
					return 0;
				}
				return ($b->{$prop} < $a->{$prop}) ? -1 : 1;
			});
			$sum = 0;
			$max = 0;
			$mean = 0;
			foreach ($users as $pos => $oUser) {
				$sum += $oUser->{$prop};
				if ($oUser->{$prop} > $max) {
					$max = (int) $oUser->{$prop};
				}
				$oUser->{$prop} = (object) ['pos' => $pos + 1, 'val' => (float) $oUser->{$prop}];
			}
			$oStat->{$prop} = (object) ['sum' => $sum, 'max' => $max, 'mean' => round($sum / count($users), 2)];

			return $users;
		};

		$modelUsr = $this->model('matter\enroll\user');
		$oResult = $modelUsr->enrolleeByApp($oApp, $page, $size, ['rid' => $rid, 'byGroup' => $gid]);
		if (count($oResult->users)) {
			foreach ($oResult->users as $oUser) {
				unset($oUser->siteid);
				unset($oUser->aid);
				unset($oUser->modify_log);
				unset($oUser->wx_openid);
				$oUser->custom = empty($oUser->custom) ? new \stdClass : json_decode($oUser->custom);
				/* 用户的贡献行为次数 */
				$oUser->devote = (int) $oUser->enroll_num + (int) $oUser->do_cowork_num + (int) $oUser->do_remark_num + (int) $oUser->do_like_num + (int) $oUser->do_like_cowork_num + (int) $oUser->do_like_remark_num;
				/* 隐藏用户身份信息 */
				if ($fnIsKeepPrivate($oUser)) {
					$oUser->nickname = '隐身';
				}
			}
			/* 计算指标排序 */
			foreach (['user_total_coin', 'score', 'entry_num', 'total_elapse', 'devote'] as $prop) {
				$fnSort($oResult->users, $prop);
			}
		}
		$oResult->stat = $oStat;

		/* 未完成任务用户 */
		if ($rid) {
			$oResultUndone = $modelUsr->undoneByApp($oApp, $rid);
			foreach ($oResultUndone->users as $oUndoneUser) {
				/* 隐藏用户身份信息 */
				if ($fnIsKeepPrivate($oUndoneUser)) {
					$oUndoneUser->nickname = '隐身';
				}
			}
			$oResult->undone = $oResultUndone->users;
		}

		return new \ResponseData($oResult);
	}
}