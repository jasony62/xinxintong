<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 记录活动用户
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
		 * 获取当前用户在记录活动中的数据
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
			$modelGrpRec = $this->model('matter\group\record');
			$modelGrpTeam = $this->model('matter\group\team');
			/* 用户所属分组信息 */
			$oGrpApp = (object) ['id' => $assocGroupAppId];
			if (!empty($oUser->group_id)) {
				$GrpRoundTitle = $modelGrpTeam->byId($oUser->group_id, ['fields' => 'title']);
				$oUser->group_title = $GrpRoundTitle->title;
				// 同组成员
				$others = $modelGrpRec->byTeam($oUser->group_id, ['fields' => 'is_leader,userid,nickname']);
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

		$oPosted = $this->getPostJson(false);
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
			$bInKanbanGroup = $this->model('matter\group\record')->isInTeam($oApp->actionRule->role->kanban->group, $oVisitor->uid);
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