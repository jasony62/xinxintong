<?php
namespace matter\enroll;
/**
 * 参加登记活动的用户
 */
class user_model extends \TMS_MODEL {
	/**
	 * 获得指定活动下的指定用户
	 */
	public function byId($oApp, $userid, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_enroll_user',
			['aid' => $oApp->id, 'userid' => $userid],
		];

		if (isset($options['rid'])) {
			$q[2]['rid'] = $options['rid'];
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
	 * 添加一个活动用户
	 */
	public function add($oApp, $oUser, $data = []) {
		$oNewUsr = new \stdClass;
		$oNewUsr->siteid = $oApp->siteid;
		$oNewUsr->aid = $oApp->id;
		$oNewUsr->userid = $oUser->uid;
		$oNewUsr->group_id = empty($oUser->group_id) ? '' : $oUser->group_id;
		$oNewUsr->nickname = $this->escape($oUser->nickname);

		foreach ($data as $k => $v) {
			switch ($k) {
			case 'modify_log':
				if (!is_string($v)) {
					$oNewUsr->{$k} = [json_encode($v)];
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
			case 'last_like_at':
			case 'last_like_other_at':
			case 'last_remark_at':
			case 'last_remark_other_at':
			case 'last_like_remark_at':
			case 'last_like_other_remark_at':
			case 'last_recommend_at':
				$aDbData[$field] = $value;
				break;
			case 'enroll_num':
			case 'like_num':
			case 'like_other_num':
			case 'remark_num':
			case 'remark_other_num':
			case 'like_remark_num':
			case 'like_other_remark_num':
			case 'recommend_num':
			case 'user_total_coin':
				$aDbData[$field] = (int) $oBeforeData->{$field}+$value;
				break;
			case 'socre':
				$aDbData[$field] = $value;
				break;
			case 'group_id':
				$aDbData['group_id'] = $value;
				break;
			case 'modify_log':
				$oBeforeData->modify_log[] = $value;
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
	public function enrolleeByMschema($oApp, $oMschema, $page = '', $size = '', $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : 'm.userid,m.email,m.mobile,m.name,m.extattr';

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
			$sel = ['fields' => 'nickname,last_enroll_at,enroll_num,last_remark_at,remark_num,last_like_at,like_num,last_like_remark_at,like_remark_num,last_remark_other_at,remark_other_num,last_like_other_at,like_other_num,last_like_other_remark_at,like_other_remark_num,last_recommend_at,recommend_num,user_total_coin,score,group_id'];
			!empty($options['rid']) && $sel['rid'] = $this->escape($options['rid']);
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
	 * 缺席用户
	 *
	 * 1、如果活动指定了通讯录用户参与；如果活动指定了分组活动的分组用户
	 * 2、如果活动关联了分组活动
	 * 3、如果活动所属项目指定了用户名单
	 *   $oUsers 当前轮次的所有用户
	 */
	public function absentByApp($oApp, $oUsers, $rid = '') {
		empty($rid) && $rid = 'ALL';
		$oUsers2 = [];
		foreach ($oUsers as $oUser) {
			$oUsers2[$oUser->id] = $oUser->userid;
		}
		/* 获取未登记人员 */
		$aAbsentUsrs = [];
		$oEntryRule = $oApp->entry_rule;
		if (isset($oEntryRule->scope->group) && $oEntryRule->scope->group === 'Y') {
			$oGrpApp = $oEntryRule->group;
			$modelGrpUsr = $this->model('matter\group\player');
			$aGrpUsrs = $modelGrpUsr->byRound(
				$oGrpApp->id,
				isset($oGrpApp->round->id) ? $oGrpApp->round->id : null,
				['fields' => 'userid,nickname,wx_openid,yx_openid,qy_openid,is_leader,round_id,round_title']
			);
			foreach ($aGrpUsrs as $oGrpUsr) {
				if (false === in_array($oGrpUsr->userid, $oUsers2)) {
					$aAbsentUsrs[] = $oGrpUsr;
				}
			}
		} else if (isset($oEntryRule->scope->member) && $oEntryRule->scope->member === 'Y') {
			$modelMem = $this->model('site\user\member');
			foreach ($oEntryRule->member as $mschemaId => $rule) {
				$members = $modelMem->byMschema($mschemaId);
				foreach ($members as $oMember) {
					if (false === in_array($oMember->userid, $oUsers2)) {
						$oUser = new \stdClass;
						$oUser->userid = $oMember->userid;
						$oUser->nickname = $oMember->name;
						$aAbsentUsrs[] = $oUser;
					}
				}
			}
		} else if (!empty($oApp->group_app_id)) {
			$modelGrpUsr = $this->model('matter\group\player');
			$aGrpUsrs = $modelGrpUsr->byApp($oApp->group_app_id, ['fields' => 'userid,nickname,wx_openid,yx_openid,qy_openid,is_leader,round_id,round_title']);
			foreach ($aGrpUsrs->players as $oGrpUsr) {
				if (false === in_array($oGrpUsr->userid, $oUsers2)) {
					$aAbsentUsrs[] = $oGrpUsr;
				}
			}
		} else if (!empty($oApp->mission_id)) {
			$modelMis = $this->model('matter\mission');
			$oMission = $modelMis->byId($oApp->mission_id, ['fields' => 'user_app_id,user_app_type,entry_rule']);
			if (isset($oMission->entry_rule->scope) && $oMission->entry_rule->scope === 'member') {
				$modelMem = $this->model('site\user\member');
				foreach ($oMission->entry_rule->member as $mschemaId => $rule) {
					$members = $modelMem->byMschema($mschemaId);
					foreach ($members as $oMember) {
						if (false === in_array($oMember->userid, $oUsers2)) {
							$oUser = new \stdClass;
							$oUser->userid = $oMember->userid;
							$oUser->nickname = $oMember->name;
							$aAbsentUsrs[] = $oUser;
						}
					}
				}
			} else {
				if ($oMission->user_app_type === 'enroll') {
					$modelRec = $this->model('matter\enroll\record');
					$result = $modelRec->byApp($oMission->user_app_id);
					if (!empty($result->records)) {
						foreach ($result->records as $oRec) {
							if (false === in_array($oRec->userid, $oUsers2)) {
								$aAbsentUsrs[] = $oRec;
							}
						}
					}
				} else if ($oMission->user_app_type === 'signin') {
					$modelRec = $this->model('matter\signin\record');
					$result = $modelRec->byApp($oMission->user_app_id);
					if (!empty($result->records)) {
						foreach ($result->records as $oRec) {
							if (false === in_array($oRec->userid, $oUsers2)) {
								$aAbsentUsrs[] = $oRec;
							}
						}
					}
				} else if ($oMission->user_app_type === 'group') {
					$modelRec = $this->model('matter\group\player');
					$result = $modelRec->byApp($oMission->user_app_id);
					if (!empty($result->players)) {
						foreach ($result->players as $oRec) {
							if (false === in_array($oRec->userid, $oUsers2)) {
								$aAbsentUsrs[] = $oRec;
							}
						}
					}
				}
			}
		}

		/* userid去重 */
		$aAbsentUsrs2 = [];
		foreach ($aAbsentUsrs as $aAbsentUsr) {
			$state = true;
			foreach ($aAbsentUsrs2 as $aAbsentUsr2) {
				if ($aAbsentUsr->userid === $aAbsentUsr2->userid || empty($aAbsentUsr->userid)) {
					$state = false;
					break;
				}
			}
			if ($state) {
				//获取未签到人员的信息，并从$oApp->absent_cause中筛选出已经签到的人
				if (isset($oApp->absent_cause->{$aAbsentUsr->userid}) && isset($oApp->absent_cause->{$aAbsentUsr->userid}->{$rid})) {
					$aAbsentUsr->absent_cause = new \stdClass;
					$aAbsentUsr->absent_cause->cause = $oApp->absent_cause->{$aAbsentUsr->userid}->{$rid};
					$aAbsentUsr->absent_cause->rid = $rid;
				} else {
					$aAbsentUsr->absent_cause = new \stdClass;
					$aAbsentUsr->absent_cause->rid = $rid;
					$aAbsentUsr->absent_cause->cause = '';
				}
				$aAbsentUsrs2[] = $aAbsentUsr;
			}
		}

		$result = new \stdClass;
		$result->users = $aAbsentUsrs2;

		return $result;
	}
	/**
	 * 发表过评论的用户
	 */
	public function remarkerByApp($oApp, $page = 1, $size = 30, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$result = new \stdClass;
		$q = [
			$fields,
			"xxt_enroll_user",
			"aid='{$oApp->id}' and remark_other_num>0 and rid = 'ALL'",
		];
		$q2 = [
			'o' => 'last_remark_other_at desc',
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];
		$users = $this->query_objs_ss($q, $q2);
		$result->users = $users;

		$q[0] = 'count(*)';
		$total = (int) $this->query_val_ss($q);
		$result->total = $total;

		return $result;
	}
	/**
	 * 指定用户的行为报告
	 */
	public function reportByUser($oApp, $oUser) {
		$result = new \stdClass;

		/* 登记次数 */
		$modelRec = $this->model('matter\enroll\record');
		$records = $modelRec->byUser($oApp, $oUser, ['fields' => 'id,comment']);
		if (false === $records) {
			return false;
		}
		$result->enroll_num = count($records);
		$result->comment = '';
		if ($result->enroll_num > 0) {
			$comments = [];
			foreach ($records as $record) {
				if (!empty($record->comment)) {
					$comments[] = $record->comment;
				}
			}
			$result->comment = implode(',', $comments);
		}
		/* 发表评论次数 */
		$modelRec = $this->model('matter\enroll\remark');
		$remarks = $modelRec->byUser($oApp, $oUser, ['fields' => 'id']);
		$result->remark_other_num = count($remarks);

		return $result;
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
			$modelCoinRule = $this->model('matter\enroll\coin')->setOnlyWriteDbConn(true);
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
			return [false];
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
}