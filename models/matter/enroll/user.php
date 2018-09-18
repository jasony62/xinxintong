<?php
namespace matter\enroll;
/**
 * 参加登记活动的用户
 */
class user_model extends \TMS_MODEL {
	/**
	 * 获得指定活动下的指定用户
	 */
	public function byId($oApp, $userid, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$q = [
			$fields,
			'xxt_enroll_user',
			['aid' => $oApp->id, 'userid' => $userid],
		];

		if (isset($aOptions['rid'])) {
			$q[2]['rid'] = $aOptions['rid'];
		} else {
			$q[2]['rid'] = 'ALL';
		}

		$oUser = $this->query_obj_ss($q);
		if ($oUser) {
			if (property_exists($oUser, 'modify_log')) {
				$oUser->modify_log = empty($oUser->modify_log) ? [] : json_decode($oUser->modify_log);
			}
		}

		return $oUser;
	}
	/**
	 * 用户的详细信息
	 */
	public function detail($oApp, $who, $oEnrolledData = null) {
		$oUser = clone $who;
		$oUser->members = new \stdClass;
		$oEntryRule = $oApp->entryRule;

		/* 用户通讯录数据 */
		if (isset($oEntryRule->scope->member) && $oEntryRule->scope->member === 'Y' && isset($oEntryRule->member)) {
			$mschemaIds = array_keys(get_object_vars($oEntryRule->member));
			if (count($mschemaIds)) {
				$modelMem = $this->model('site\user\member');
				$modelAcnt = $this->model('site\user\account');
				$oUser->members = new \stdClass;
				if (empty($oUser->unionid)) {
					$oSiteUser = $modelAcnt->byId($oUser->uid, ['fields' => 'unionid']);
					if ($oSiteUser && !empty($oSiteUser->unionid)) {
						$unionid = $oSiteUser->unionid;
					}
				} else {
					$unionid = $oUser->unionid;
				}
				if (empty($unionid)) {
					$aMembers = $modelMem->byUser($oUser->uid, ['schemas' => implode(',', $mschemaIds)]);
					foreach ($aMembers as $oMember) {
						$oUser->members->{$oMember->schema_id} = $oMember;
					}
				} else {
					$aUnionUsers = $modelAcnt->byUnionid($unionid, ['siteid' => $oApp->siteid, 'fields' => 'uid']);
					foreach ($aUnionUsers as $oUnionUser) {
						$aMembers = $modelMem->byUser($oUnionUser->uid, ['schemas' => implode(',', $mschemaIds)]);
						foreach ($aMembers as $oMember) {
							$oUser->members->{$oMember->schema_id} = $oMember;
						}
					}
					/* 站点用户替换成和注册账号绑定的站点用户 */
					$oRegUser = $modelAcnt->byPrimaryUnionid($oApp->siteid, $unionid);
					if ($oRegUser && $oRegUser->uid !== $oUser->uid) {
						$oUser->uid = $oRegUser->uid;
						$oUser->nickname = $oRegUser->nickname;
					}
				}
			}
		}
		/*获得用户昵称*/
		if (isset($oEnrolledData) && (isset($oApp->assignedNickname->valid) && $oApp->assignedNickname->valid === 'Y') && isset($oApp->assignedNickname->schema->id)) {
			/* 指定的用户昵称 */
			if (isset($oEnrolledData)) {
				$modelEnlRec = $this->model('matter\enroll\record');
				$oUser->nickname = $modelEnlRec->getValueBySchema($oApp->assignedNickname->schema, $oEnrolledData);
			}
		} else {
			/* 曾经用过的昵称 */
			$modelEnlUsr = $this->model('matter\enroll\user');
			$oEnlUser = $modelEnlUsr->byId($oApp, $oUser->uid, ['fields' => 'nickname']);
			if ($oEnlUser) {
				$oUser->nickname = $oEnlUser->nickname;
			} else {
				$modelEnl = $this->model('matter\enroll');
				$userNickname = $modelEnl->getUserNickname($oApp, $oUser);
				$oUser->nickname = $userNickname;
			}
		}

		/* 获得用户所属分组 */
		if (!empty($oApp->group_app_id)) {
			$assocGroupId = $oApp->group_app_id;
		} else if (isset($oApp->entryRule->scope->group) && $oApp->entryRule->scope->group === 'Y' && isset($oApp->entryRule->group->id)) {
			$assocGroupId = $oApp->entryRule->group->id;
		}
		if (isset($assocGroupId)) {
			$modelGrpUsr = $this->model('matter\group\player');
			$oAssocGrpApp = (object) ['id' => $assocGroupId];
			$oGrpMemb = $modelGrpUsr->byUser($oAssocGrpApp, $oUser->uid, ['fields' => 'round_id,is_leader,role_rounds', 'onlyOne' => true]);
			if ($oGrpMemb) {
				$oUser->group_id = $oGrpMemb->round_id;
				$oUser->is_leader = $oGrpMemb->is_leader;
				$oUser->role_rounds = $oGrpMemb->role_rounds;
			}
		}

		/* 当前用户是否为编辑 */
		if (!empty($oApp->actionRule->role->editor->group)) {
			$oUser->is_editor = 'N';
			if (!empty($oUser->group_id)) {
				if ($oUser->group_id === $oApp->actionRule->role->editor->group) {
					$oUser->is_editor = 'Y';
				}
			}
			if ($oUser->is_editor === 'N' && !empty($oUser->role_rounds)) {
				if (in_array($oApp->actionRule->role->editor->group, $oUser->role_rounds)) {
					$oUser->is_editor = 'Y';
				}
			}
		}

		return $oUser;
	}
	/**
	 * 添加一个活动用户
	 */
	public function add($oApp, $oUser, $data = []) {
		$oNewUsr = new \stdClass;
		$oNewUsr->siteid = $oApp->siteid;
		$oNewUsr->aid = $oApp->id;
		$oNewUsr->userid = $oUser->uid;
		$oNewUsr->group_id = empty($oUser->group_id) ? '' : $oUser->group_id;
		$oNewUsr->nickname = empty($oUser->nickname) ? '' : $this->escape($oUser->nickname);

		foreach ($data as $k => $v) {
			switch ($k) {
			case 'modify_log':
				if (!is_string($v)) {
					$oNewUsr->{$k} = json_encode([$v]);
				}
				break;
			default:
				$oNewUsr->{$k} = $v;
			}
		}
		$oNewUsr->id = $this->insert('xxt_enroll_user', $oNewUsr, true);

		return $oNewUsr;
	}
	/**
	 * 修改用户数据
	 */
	public function modify($oBeforeData, $oUpdatedData) {
		$aDbData = [];
		foreach ($oUpdatedData as $field => $value) {
			switch ($field) {
			case 'last_enroll_at':
			case 'last_cowork_at':
			case 'last_do_cowork_at':
			case 'last_like_at':
			case 'last_like_cowork_at':
			case 'last_do_like_at':
			case 'last_do_like_cowork_at':
			case 'last_remark_at':
			case 'last_remark_cowork_at':
			case 'last_do_remark_at':
			case 'last_like_remark_at':
			case 'last_do_like_remark_at':
			case 'last_agree_at':
			case 'last_agree_cowork_at':
			case 'last_agree_remark_at':
			case 'last_topic_at':
				$aDbData[$field] = $value;
				break;
			case 'enroll_num':
			case 'revise_num':
			case 'cowork_num':
			case 'do_cowork_num':
			case 'do_like_num':
			case 'do_like_cowork_num':
			case 'do_like_remark_num':
			case 'like_num':
			case 'like_cowork_num':
			case 'like_remark_num':
			case 'do_remark_num':
			case 'remark_num':
			case 'remark_cowork_num':
			case 'agree_num':
			case 'agree_cowork_num':
			case 'agree_remark_num':
			case 'user_total_coin':
			case 'topic_num':
			case 'do_repos_read_num':
			case 'do_topic_read_num':
			case 'topic_read_num':
			case 'do_cowork_read_num':
			case 'cowork_read_num':
			case 'do_cowork_read_elapse':
			case 'cowork_read_elapse':
			case 'do_topic_read_elapse':
			case 'topic_read_elapse':
			case 'do_repos_read_elapse':
				$aDbData[$field] = $value + (int) $oBeforeData->{$field};
				break;
			case 'score':
			case 'state':
			case 'group_id':
				$aDbData[$field] = $value;
				break;
			case 'modify_log':
				if (empty($oBeforeData->modify_log)) {
					$oBeforeData->modify_log = [];
				}
				array_unshift($oBeforeData->modify_log, $value);
				$aDbData['modify_log'] = json_encode($oBeforeData->modify_log);
				break;
			}
		}

		$rst = $this->update('xxt_enroll_user', $aDbData, ['id' => $oBeforeData->id]);

		return $rst;
	}
	/**
	 * 删除1条记录
	 */
	public function removeRecord($oApp, $oRecord) {
		if (empty($oApp->id) || empty($oRecord->enroll_key) || empty($oRecord->userid) || !isset($oRecord->rid)) {
			return [false, '参数不完整'];
		}
		$oRecord2 = $this->model('matter\enroll\record')->byId($oRecord->enroll_key, ['score']);
		if (false === $oRecord2) {
			return [false, '记录不存在'];
		}
		/* 记录得分 */
		$score = 0;
		if (isset($oRecord2->score->sum)) {
			$score = $oRecord2->score->sum;
		}

		$rst = $this->update(
			'xxt_enroll_user',
			[
				'enroll_num' => (object) ['op' => '-=', 'pat' => 1],
				'score' => (object) ['op' => '-=', 'pat' => $score],
			],
			['aid' => $oApp->id, 'userid' => $oRecord->userid, 'rid' => [$oRecord->rid, 'ALL'], 'enroll_num' => (object) ['op' => '>', 'pat' => 0]]
		);

		return [true, $rst];
	}
	/**
	 * 恢复1条记录
	 */
	public function restoreRecord($oApp, $oRecord) {
		if (empty($oApp->id) || empty($oRecord->enroll_key) || empty($oRecord->userid) || !isset($oRecord->rid)) {
			return [false, '参数不完整'];
		}
		$oRecord2 = $this->model('matter\enroll\record')->byId($oRecord->enroll_key, ['score']);
		if (false === $oRecord2) {
			return [false, '记录不存在'];
		}
		/* 记录得分 */
		$score = 0;
		if (isset($oRecord2->score->sum)) {
			$score = $oRecord2->score->sum;
		}

		$rst = $this->update(
			'xxt_enroll_user',
			[
				'enroll_num' => (object) ['op' => '+=', 'pat' => 1],
				'score' => (object) ['op' => '+=', 'pat' => $score],
				'state' => 1,
			],
			['aid' => $oApp->id, 'userid' => $oRecord->userid, 'rid' => [$oRecord->rid, 'ALL']]
		);

		return [true, $rst];
	}
	/**
	 * 参与活动的用户
	 */
	public function enrolleeByApp($oApp, $page = '', $size = '', $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$cascaded = isset($aOptions['cascaded']) ? $aOptions['cascaded'] : 'Y';

		$result = new \stdClass;
		$q = [
			$fields,
			"xxt_enroll_user",
			"aid='{$oApp->id}' and state=1",
		];
		if (!empty($aOptions['onlyEnrolled']) && $aOptions['onlyEnrolled'] === 'Y') {
			$q[2] .= " and enroll_num>0";
		}
		if (!empty($aOptions['rid'])) {
			$q[2] .= " and rid = '" . $this->escape($aOptions['rid']) . "'";
		} else {
			$q[2] .= " and rid = 'ALL'";
		}
		if (!empty($aOptions['byGroup'])) {
			$q[2] .= " and group_id = '" . $this->escape($aOptions['byGroup']) . "'";
		}
		if (!empty($aOptions['orderby'])) {
			$q2 = ['o' => $aOptions['orderby'] . ' desc'];
		} else {
			$q2 = ['o' => 'last_enroll_at desc'];
		}
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		$users = $this->query_objs_ss($q, $q2);
		if ($cascaded === 'Y' && count($users)) {
			foreach ($users as $oUser) {
				$p = [
					'wx_openid,yx_openid,qy_openid',
					"xxt_site_account",
					['uid' => $oUser->userid],
				];
				if ($oOpenid = $this->query_obj_ss($p)) {
					$oUser->wx_openid = $oOpenid->wx_openid;
					if (!empty($oOpenid->wx_openid)) {
						if (!isset($modelWxfan)) {
							$modelWxfan = $this->model('sns\wx\fan');
						}
						$oWxfan = $modelWxfan->byOpenid($oApp->siteid, $oOpenid->wx_openid, 'nickname,headimgurl', 'Y');
						if ($oWxfan) {
							$oUser->wxfan = $oWxfan;
						}
					}
					$oUser->yx_openid = $oOpenid->yx_openid;
					if (!empty($oOpenid->yx_openid)) {
						if (!isset($modelYxfan)) {
							$modelYxfan = $this->model('sns\yx\fan');
						}
						$oYxfan = $modelYxfan->byOpenid($oApp->siteid, $oOpenid->yx_openid, 'nickname,headimgurl', 'Y');
						if ($oYxfan) {
							$oUser->yxfan = $oYxfan;
						}
					}
				} else {
					$oUser->wx_openid = '';
					$oUser->yx_openid = '';
				}
			}
		}

		$result->users = $users;

		/* 符合条件的用户总数 */
		$q[0] = 'count(*)';
		$total = (int) $this->query_val_ss($q);
		$result->total = $total;

		return $result;
	}
	/**
	 * 活动中提交过数据的用户
	 */
	public function enrolleeByMschema($oApp, $oMschema, $page = '', $size = '', $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : 'm.userid,m.email,m.mobile,m.name,m.extattr';

		$result = new \stdClass;
		$q = [
			$fields . ',a.wx_openid,a.yx_openid,a.qy_openid',
			"xxt_site_member m,xxt_site_account a",
			"m.schema_id = $oMschema->id and m.verified = 'Y' and m.forbidden = 'N' and a.uid = m.userid",
		];
		$q2 = [
			'o' => 'm.create_at desc',
		];
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		$members = $this->query_objs_ss($q, $q2);
		if (count($members)) {
			$sel = ['fields' => 'nickname,last_enroll_at,enroll_num,last_remark_at,remark_num,last_like_at,like_num,last_like_remark_at,like_remark_num,last_do_remark_at,do_remark_num,last_do_like_at,do_like_num,last_do_like_remark_at,do_like_remark_num,last_agree_at,agree_num,user_total_coin,score,group_id'];
			!empty($aOptions['rid']) && $sel['rid'] = $this->escape($aOptions['rid']);
			foreach ($members as &$oMember) {
				$oMember->extattr = empty($oMember->extattr) ? new \stdClass : json_decode($oMember->extattr);
				//$oEnrollee = new \stdClass;
				//$oEnrollee->userid = $oMember->userid;
				//$oMember->report = $this->reportByUser($oApp, $oEnrollee);
				$oMember->user = $this->byId($oApp, $oMember->userid, $sel);
			}
		}
		$result->members = $members;

		$q[0] = 'count(*)';
		$total = (int) $this->query_val_ss($q);
		$result->total = $total;

		return $result;
	}
	/**
	 * 获得活动指定的参与
	 */
	public function assignedByApp($oApp) {
		$aAssignedUsrs = [];
		$oEntryRule = $oApp->entryRule;
		if (isset($oEntryRule->scope->group) && $oEntryRule->scope->group === 'Y') {
			$oGrpApp = $oEntryRule->group;
			$modelGrpUsr = $this->model('matter\group\player');
			if (empty($oGrpApp->round->id)) {
				$aGrpUsrs = $modelGrpUsr->byApp(
					$oGrpApp->id,
					['fields' => 'userid,nickname']
				);
				foreach ($aGrpUsrs->players as $oGrpUsr) {
					$aAssignedUsrs[] = $oGrpUsr;
				}
			} else {
				$aGrpUsrs = $modelGrpUsr->byRound(
					$oGrpApp->id,
					$oGrpApp->round->id,
					['fields' => 'userid,nickname']
				);
				foreach ($aGrpUsrs as $oGrpUsr) {
					$aAssignedUsrs[] = $oGrpUsr;
				}
			}
			$oReferenceApp = $this->model('matter\group')->byId($oGrpApp->id, ['fields' => 'id,title,data_schemas']);
		} else if (isset($oEntryRule->scope->member) && $oEntryRule->scope->member === 'Y') {
			$modelMem = $this->model('site\user\member');
			foreach ($oEntryRule->member as $mschemaId => $rule) {
				$members = $modelMem->byMschema($mschemaId);
				foreach ($members as $oMember) {
					$oUser = new \stdClass;
					$oUser->userid = $oMember->userid;
					$oUser->nickname = $oMember->name;
					$aAssignedUsrs[] = $oUser;
				}
			}
		} else if (!empty($oApp->group_app_id)) {
			$modelGrpUsr = $this->model('matter\group\player');
			$aGrpUsrs = $modelGrpUsr->byApp($oApp->group_app_id, ['fields' => 'userid,nickname']);
			foreach ($aGrpUsrs->players as $oGrpUsr) {
				$aAssignedUsrs[] = $oGrpUsr;
			}
			$oReferenceApp = $this->model('matter\group')->byId($oApp->group_app_id, ['fields' => 'id,title,data_schemas']);
		} else if (!empty($oApp->mission_id)) {
			$modelMis = $this->model('matter\mission');
			$oMission = $modelMis->byId($oApp->mission_id, ['fields' => 'user_app_id,user_app_type,entry_rule']);
			if (isset($oMission->entry_rule->scope) && $oMission->entry_rule->scope === 'member') {
				$modelMem = $this->model('site\user\member');
				foreach ($oMission->entry_rule->member as $mschemaId => $rule) {
					$members = $modelMem->byMschema($mschemaId);
					foreach ($members as $oMember) {
						$oUser = new \stdClass;
						$oUser->userid = $oMember->userid;
						$oUser->nickname = $oMember->name;
						$aAssignedUsrs[] = $oUser;
					}
				}
			} else {
				if ($oMission->user_app_type === 'enroll') {
					$modelRec = $this->model('matter\enroll\record');
					$oResult = $modelRec->byApp($oMission->user_app_id);
					if (!empty($oResult->records)) {
						foreach ($oResult->records as $oRec) {
							$aAssignedUsrs[] = $oRec;
						}
					}
				} else if ($oMission->user_app_type === 'signin') {
					$modelRec = $this->model('matter\signin\record');
					$oResult = $modelRec->byApp($oMission->user_app_id);
					if (!empty($oResult->records)) {
						foreach ($oResult->records as $oRec) {
							$aAssignedUsrs[] = $oRec;
						}
					}
				} else if ($oMission->user_app_type === 'group') {
					$modelRec = $this->model('matter\group\player');
					$aGrpUsrs = $modelRec->byApp($oMission->user_app_id);
					if (!empty($aGrpUsrs->players)) {
						foreach ($aGrpUsrs->players as $oRec) {
							$aAssignedUsrs[] = $oRec;
						}
					}
					$oReferenceApp = $this->model('matter\group')->byId($oMission->user_app_id, ['fields' => 'id,title,data_schemas']);
				}
			}
		}

		/* userid去重 */
		$aAssignedUsrs2 = [];
		foreach ($aAssignedUsrs as $oAssignedUsr) {
			$bValid = true;
			foreach ($aAssignedUsrs2 as $oAssignedUsr2) {
				if (empty($oAssignedUsr->userid) || $oAssignedUsr->userid === $oAssignedUsr2->userid) {
					$bValid = false;
					break;
				}
			}
			if ($bValid) {
				$aAssignedUsrs2[] = $oAssignedUsr;
			}
		}

		$oResult = new \stdClass;
		$oResult->users = $aAssignedUsrs2;
		if (isset($oReferenceApp)) {
			unset($oReferenceApp->data_schemas);
			$oResult->app = $oReferenceApp;
		}

		return $oResult;
	}
	/**
	 * 指定的用户是否没有完成活动要求的任务
	 *
	 * 1. 如果没有指定任务规则，检查用户是否进行过登记
	 */
	public function isUndone($oApp, $rid, $oAssignedUser) {
		$oAppUser = $this->byId($oApp, $oAssignedUser->userid, ['rid' => $rid, 'fields' => 'state,enroll_num,do_remark_num']);
		if (false === $oAppUser || $oAppUser->state !== '1') {
			return true;
		}
		if (isset($oApp->actionRule)) {
			$oRule = $oApp->actionRule;
		} else {
			$oApp2 = $this->model('matter\enroll')->byId($oApp->id, ['fileds' => 'actionRule']);
			$oRule = $oApp2->actionRule;
		}

		$aUndoneTasks = []; // 没有完成的任务
		$countOfDone = 0; // 已完成的任务数量
		/* 提交记录 */
		if (isset($oRule->record->submit->end->min)) {
			$bUndone = (int) $oAppUser->enroll_num < (int) $oRule->record->submit->end->min;
			$aUndoneTasks['enroll_num'] = [$bUndone, (int) $oRule->record->submit->end->min, (int) $oAppUser->enroll_num];
			if (true === $bUndone && empty($oRule->record->submit->optional)) {
				return $aUndoneTasks;
			}
			if (false === $bUndone) {
				$countOfDone++;
			}
		}
		/* 提交评论 */
		if (isset($oRule->remark->submit->end->min)) {
			$bUndone = (int) $oAppUser->do_remark_num < (int) $oRule->remark->submit->end->min;
			$aUndoneTasks['do_remark_num'] = [$bUndone, (int) $oRule->remark->submit->end->min, (int) $oAppUser->do_remark_num];
			if (true === $bUndone && empty($oRule->remark->submit->optional)) {
				return $aUndoneTasks;
			}
			if (false === $bUndone) {
				$countOfDone++;
			}
		}
		/* 没有指定任务，默认要求提交至少1条记录 */
		if (empty($aUndoneTasks)) {
			if ((int) $oAppUser->enroll_num <= 0) {
				return ['enroll_num' => [false, 1, 0]];
			}
		}
		/* 完成的可选任务数量 */
		if (isset($oRule->done->optional->num)) {
			if ($countOfDone < (int) $oRule->done->optional->num) {
				return $aUndoneTasks;
			}
		} else if ($countOfDone < 1) {
			/* 默认至少要完成一项任务 */
			return $aUndoneTasks;
		}

		return false;
	}
	/**
	 * 获得指定活动指定轮次没有完成任务的用户
	 */
	public function undoneByApp($oApp, $rid) {
		$oAssignedUsrsResult = $this->assignedByApp($oApp);
		if (empty($oAssignedUsrsResult->users)) {
			return (object) ['users' => []];
		}

		$aUndoneUsrs = []; // 没有完成任务的用户
		$oAssignedUsrs = $oAssignedUsrsResult->users;
		foreach ($oAssignedUsrs as $oAssignedUser) {
			if ($tasks = $this->isUndone($oApp, $rid, $oAssignedUser)) {
				if (isset($oApp->absent_cause->{$oAssignedUser->userid}->{$rid})) {
					$oAssignedUser->absent_cause = new \stdClass;
					$oAssignedUser->absent_cause->cause = $oApp->absent_cause->{$oAssignedUser->userid}->{$rid};
					$oAssignedUser->absent_cause->rid = $rid;
				}
				if (true !== $tasks) {
					$oAssignedUser->tasks = $tasks;
				}
				$aUndoneUsrs[] = $oAssignedUser;
			}
		}

		$oResult = new \stdClass;
		$oResult->users = $aUndoneUsrs;
		if (isset($oAssignedUsrsResult->app)) {
			$oResult->app = $oAssignedUsrsResult->app;
		}

		return $oResult;
	}
	/**
	 * 发表过留言的用户
	 */
	public function remarkerByApp($oApp, $page = 1, $size = 30, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';

		$oResult = new \stdClass;
		$q = [
			$fields,
			"xxt_enroll_user",
			"aid='{$oApp->id}' and do_remark_num>0 and rid = 'ALL'",
		];
		$q2 = [
			'o' => 'last_do_remark_at desc',
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];
		$users = $this->query_objs_ss($q, $q2);
		$oResult->users = $users;

		$q[0] = 'count(*)';
		$total = (int) $this->query_val_ss($q);
		$oResult->total = $total;

		return $oResult;
	}
	/**
	 * 指定用户的行为报告
	 */
	public function reportByUser($oApp, $oUser) {
		$oResult = new \stdClass;

		/* 登记次数 */
		$modelRec = $this->model('matter\enroll\record');
		$records = $modelRec->byUser($oApp, $oUser, ['fields' => 'id,comment']);
		if (false === $records) {
			return false;
		}
		$oResult->enroll_num = count($records);
		$oResult->comment = '';
		if ($oResult->enroll_num > 0) {
			$comments = [];
			foreach ($records as $record) {
				if (!empty($record->comment)) {
					$comments[] = $record->comment;
				}
			}
			$oResult->comment = implode(',', $comments);
		}
		/* 发表留言次数 */
		$modelRec = $this->model('matter\enroll\remark');
		$remarks = $modelRec->byUser($oApp, $oUser, ['fields' => 'id']);
		$oResult->do_remark_num = count($remarks);

		return $oResult;
	}
	/**
	 * 根据用户的填写记录更新用户数据
	 */
	public function renew($oApp, $rid = '') {
		$aUpdatedResult = [];
		/**
		 * 按轮次更新用户数据
		 */
		$q = [
			'id,userid,rid,enroll_num,score',
			'xxt_enroll_user',
			['aid' => $oApp->id, 'rid' => (object) ['op' => '<>', 'pat' => 'ALL']],
		];
		$enrollees = $this->query_objs_ss($q);
		if (count($enrollees)) {
			foreach ($enrollees as $oEnrollee) {
				$q = [
					'score',
					['xxt_enroll_record'],
					['aid' => $oApp->id, 'userid' => $oEnrollee->userid, 'rid' => $oEnrollee->rid, 'state' => 1],
				];
				$oEnrolleeRecs = $this->query_objs_ss($q);
				$enrollNum = 0;
				$score = 0;
				foreach ($oEnrolleeRecs as $oEnrolleeRec) {
					$enrollNum++;
					if (!empty($oEnrolleeRec->score)) {
						$oEnrolleeRec->score = json_decode($oEnrolleeRec->score);
						if (!empty($oEnrolleeRec->score->sum)) {
							$score += $oEnrolleeRec->score->sum;
						}
					}
				}
				/* 更新数据 */
				$rst = $this->update(
					'xxt_enroll_user',
					['enroll_num' => $enrollNum, 'score' => $score],
					['id' => $oEnrollee->id]
				);
				if ($rst) {
					$aUpdatedResult[] = $oEnrollee;
				}
			}
		}
		/**
		 * 更新用户在活动中的累积数据
		 */
		$q = [
			'id,userid,enroll_num,score',
			'xxt_enroll_user',
			['aid' => $oApp->id, 'rid' => 'ALL'],
		];
		$enrollees = $this->query_objs_ss($q);
		if (count($enrollees)) {
			foreach ($enrollees as $oEnrollee) {
				$q = [
					'sum(enroll_num) enroll_num,sum(score) score',
					'xxt_enroll_user',
					['aid' => $oApp->id, 'userid' => $oEnrollee->userid, 'rid' => (object) ['op' => '<>', 'pat' => 'ALL']],
				];
				$oAll = $this->query_obj_ss($q);
				/* 更新数据 */
				$rst = $this->update(
					'xxt_enroll_user',
					['enroll_num' => $oAll->enroll_num, 'score' => $oAll->score],
					['id' => $oEnrollee->id]
				);
				if ($rst) {
					$aUpdatedResult[] = $oEnrollee;
				}
			}
		}

		return $aUpdatedResult;
	}
	/**
	 * 活动用户获得奖励积分
	 */
	public function awardCoin($oApp, $userid, $rid, $coinEvent, $coinRules = null) {
		if (empty($coinRules)) {
			$modelCoinRule = $this->model('matter\enroll\coin');
			$coinRules = $modelCoinRule->rulesByMatter($coinEvent, $oApp);
		}
		if (empty($coinRules)) {
			return [false];
		}

		$deltaCoin = 0; // 增加的积分
		foreach ($coinRules as $rule) {
			$deltaCoin += (int) $rule->actor_delta;
		}
		if ($deltaCoin === 0) {
			return [false];
		}

		/* 参与活动的用户 */
		$oEnrollUsr = $this->byId($oApp, $userid, ['fields' => 'id,userid,nickname,user_total_coin', 'rid' => $rid]);
		if (false === $oEnrollUsr) {
			return [false, $deltaCoin];
		}

		/* 奖励积分 */
		$modelCoinLog = $this->model('site\coin\log')->setOnlyWriteDbConn(true);
		$oResult = $modelCoinLog->award($oApp, $oEnrollUsr, $coinEvent, $coinRules);

		return [true, $deltaCoin];
	}
	/**
	 * 活动用户扣除奖励积分
	 */
	public function deductCoin($oApp, $userid, $rid, $coinEvent, $deductCoin) {
		/* 参与活动的用户 */
		$oEnrollUsr = $this->byId($oApp, $userid, ['fields' => 'id,userid,nickname,user_total_coin', 'rid' => $rid]);
		if (false === $oEnrollUsr) {
			return [false];
		}

		/* 奖励积分 */
		$modelCoinLog = $this->model('site\coin\log')->setOnlyWriteDbConn(true);
		$modelCoinLog->deduct($oApp, $oEnrollUsr, $coinEvent, $deductCoin);

		$this->update(
			'xxt_enroll_user',
			['user_total_coin' => (int) $oEnrollUsr->user_total_coin - $deductCoin],
			['id' => $oEnrollUsr->id]
		);

		$oEnrollUsrALL = $this->byId($oApp, $userid, ['fields' => 'id,userid,nickname,user_total_coin', 'rid' => 'ALL']);
		if ($oEnrollUsrALL) {
			$this->update(
				'xxt_enroll_user',
				['user_total_coin' => (int) $oEnrollUsrALL->user_total_coin - $deductCoin],
				['id' => $oEnrollUsrALL->id]
			);
		}

		return [true, $deductCoin];
	}
	/**
	 * 接收记录提交事件通知的接收人
	 */
	public function getSubmitReceivers($oApp, $oRecord, $oRule) {
		if (empty($oRule->receiver->scope) || !is_array($oRule->receiver->scope)) {
			return false;
		}
		/* 分组活动中的接收人 */
		if (in_array('group', $oRule->receiver->scope) && !empty($oRule->receiver->group->id)) {
			$q = [
				'distinct userid',
				'xxt_group_player',
				['state' => 1, 'aid' => $oRule->receiver->group->id, 'userid' => (object) ['op' => '<>', 'pat' => $oRecord->userid]],
			];
			if (!empty($oRule->receiver->group->round->id)) {
				$q[2]['round_id'] = $oRule->receiver->group->round->id;
			}
			$receivers = $this->query_objs_ss($q);
		}
		/* 分组活动中的组长 */
		if (in_array('leader', $oRule->receiver->scope) && !empty($oRecord->userid)) {
			if (isset($oApp->entryRule->scope->group) && $oApp->entryRule->scope->group === 'Y' && !empty($oApp->entryRule->group->id)) {
				$q = [
					'round_id',
					'xxt_group_player',
					['state' => 1, 'aid' => $oApp->entryRule->group->id, 'userid' => $oRecord->userid],
				];
				$oUserRounds = $this->query_objs_ss($q);
				if (!empty($oUserRounds)) {
					$q = [
						'distinct userid',
						'xxt_group_player',
						['state' => 1, 'aid' => $oApp->entryRule->group->id, 'round_id' => $oUserRounds[0]->round_id, 'is_leader' => 'Y', 'userid' => (object) ['op' => '<>', 'pat' => $oRecord->userid]],
					];
					if (empty($receivers)) {
						$receivers = $this->query_objs_ss($q);
					} else {
						$leaders = $this->query_objs_ss($q);
						if (!empty($leaders)) {
							$receivers = array_merge($receivers, $leaders);
						}
					}
				}
			}
		}

		return isset($receivers) ? $receivers : false;
	}
	/**
	 * 接收评论提交事件通知的接收人
	 */
	public function getRemarkReceivers($oApp, $oRecord, $oRemark, $oRule) {
		if (empty($oRule->receiver->scope) || !is_array($oRule->receiver->scope)) {
			return false;
		}
		/* 分组活动中的接收人 */
		if (in_array('group', $oRule->receiver->scope) && !empty($oRule->receiver->group->id)) {
			$q = [
				'distinct userid',
				'xxt_group_player',
				['state' => 1, 'aid' => $oRule->receiver->group->id, 'userid' => (object) ['op' => '<>', 'pat' => $oRemark->userid]],
			];
			if (!empty($oRule->receiver->group->round->id)) {
				$q[2]['round_id'] = $oRule->receiver->group->round->id;
			}
			$receivers = $this->query_objs_ss($q);
		}
		/* 和评论对象相关的用户 */
		if (in_array('related', $oRule->receiver->scope)) {
			$relateds = [];
			/* 被评论的记录 */
			if (isset($oRecord->userid) && (empty($oRemark->userid) || $oRemark->userid !== $oRecord->userid)) {
				$relateds[] = (object) ['userid' => $oRecord->userid];
			}
			/* 被评论的数据 */
			if (!empty($oRemark->data_id)) {
				$oBeRemarkedData = $this->model('matter\enroll\data')->byId($oRemark->data_id, ['fields' => 'userid']);
				if (empty($oRemark->userid) || $oRemark->userid !== $oBeRemarkedData->userid) {
					$relateds[] = (object) ['userid' => $oBeRemarkedData->userid];
				}
			}
			/* 被评论的评论 */
			if (!empty($oRemark->remark_id)) {
				$oBeRemarkedRemark = $this->model('matter\enroll\remark')->byId($oRemark->remark_id, ['fields' => 'userid']);
				if (empty($oRemark->userid) || $oRemark->userid !== $oBeRemarkedRemark->userid) {
					$relateds[] = (object) ['userid' => $oBeRemarkedRemark->userid];
				}
			}
			if (isset($receivers)) {
				$receivers = array_merge($receivers, $relateds);
			} else {
				$receivers = $relateds;
			}
		}

		return isset($receivers) ? $receivers : false;
	}
}