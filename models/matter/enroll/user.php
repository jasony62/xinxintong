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
			$oNewUsr->{$k} = $v;
		}
		$oNewUsr->id = $this->insert('xxt_enroll_user', $oNewUsr, true);

		return $oNewUsr;
	}
	/**
	 * 删除1条记录
	 */
	public function removeRecord($oRecord) {
		if (empty($oRecord->userid) || !isset($oRecord->rid)) {
			return [false, '参数不完整'];
		}

		$updateSql = 'update xxt_enroll_user set enroll_num=enroll_num-1 where enroll_num>0 and userid="' . $oRecord->userid . '"';
		$this->update($updateSql . ' and rid="' . $oRecord->rid . '"');
		$rst = $this->update($updateSql . ' and rid="ALL"');

		return [true, $rst];
	}
	/**
	 * 恢复1条记录
	 */
	public function restoreRecord($oRecord) {
		if (empty($oRecord->userid) || !isset($oRecord->rid)) {
			return [false, '参数不完整'];
		}

		$updateSql = 'update xxt_enroll_user set enroll_num=enroll_num+1 where userid="' . $oRecord->userid . '"';
		$this->update($updateSql . ' and rid="' . $oRecord->rid . '"');
		$rst = $this->update($updateSql . ' and rid="ALL"');

		return [true, $rst];
	}
	/**
	 * 活动中提交过数据的用户
	 */
	public function enrolleeByApp($oApp, $page = '', $size = '', $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';

		$result = new \stdClass;
		$q = [
			$fields,
			"xxt_enroll_user",
			"aid='{$oApp->id}' and enroll_num>0",
		];
		if (!empty($options['rid'])) {
			$q[2] .= " and rid = '" . $this->escape($options['rid']) . "'";
		} else {
			$q[2] .= " and rid = 'ALL'";
		}
		if (!empty($options['byGroup'])) {
			$q[2] .= " and group_id = '" . $this->escape($options['byGroup']) . "'";
		}
		if (!empty($options['orderby'])) {
			$q2 = ['o' => $options['orderby'] . ' desc'];
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
			$sel = ['fields' => 'nickname,last_enroll_at,enroll_num,last_remark_at,remark_num,last_like_at,like_num,last_like_remark_at,like_remark_num,last_remark_other_at,remark_other_num,last_like_other_at,like_other_num,last_like_other_remark_at,like_other_remark_num,user_total_coin,score,group_id'];
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
	 */
	public function absentByApp($oApp, $rid = '') {
		$aAbsentUsrs = [];
		if (isset($oApp->entry_rule->scope) && in_array($oApp->entry_rule->scope, ['group', 'member'])) {
			if ($oApp->entry_rule->scope === 'group' && isset($oApp->entry_rule->group)) {
				$oGrpApp = $oApp->entry_rule->group;
				$modelGrpUsr = $this->model('matter\group\player');
				$aGrpUsrs = $modelGrpUsr->byRound(
					$oGrpApp->id,
					isset($oGrpApp->round->id) ? $oGrpApp->round->id : null,
					['fields' => 'userid,nickname,wx_openid,yx_openid,qy_openid,is_leader,round_id,round_title']
				);
				foreach ($aGrpUsrs as $oGrpUsr) {
					if (false === $this->byId($oApp, $oGrpUsr->userid)) {
						$aAbsentUsrs[] = $oGrpUsr;
					}
				}
			} else if ($oApp->entry_rule->scope === 'member' && isset($oApp->entry_rule->member)) {
				$modelMem = $this->model('site\user\member');
				foreach ($oApp->entry_rule->member as $mschemaId => $rule) {
					$members = $modelMem->byMschema($mschemaId);
					foreach ($members as $oMember) {
						if (false === $this->byId($oApp, $oMember->userid)) {
							$oUser = new \stdClass;
							$oUser->userid = $oMember->userid;
							$oUser->nickname = $oMember->name;
							$aAbsentUsrs[] = $oUser;
						}
					}
				}
			}
		} else if (!empty($oApp->group_app_id)) {
			$modelGrpUsr = $this->model('matter\group\player');
			$aGrpUsrs = $modelGrpUsr->byApp($oApp->group_app_id, ['fields' => 'userid,nickname,wx_openid,yx_openid,qy_openid,is_leader,round_id,round_title']);
			foreach ($aGrpUsrs->players as $oGrpUsr) {
				if (false === $this->byId($oApp, $oGrpUsr->userid)) {
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
						if (false === $this->byId($oApp, $oMember->userid)) {
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
							$aAbsentUsrs[] = $oRec;
						}
					}
				} else if ($oMission->user_app_type === 'signin') {
					$modelRec = $this->model('matter\signin\record');
					$result = $modelRec->byApp($oMission->user_app_id);
					if (!empty($result->records)) {
						foreach ($result->records as $oRec) {
							$aAbsentUsrs[] = $oRec;
						}
					}
				} else if ($oMission->user_app_type === 'group') {
					$modelRec = $this->model('matter\group\player');
					$result = $modelRec->byApp($oMission->user_app_id);
					if (!empty($result->players)) {
						foreach ($result->players as $oRec) {
							$aAbsentUsrs[] = $oRec;
						}
					}
				}
			}
		}

		$result = new \stdClass;
		$result->users = $aAbsentUsrs;

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
		$records = $modelRec->byUser($oApp, $oUser, ['fields' => 'id']);
		$result->enroll_num = count($records);

		/* 发表评论次数 */
		$modelRec = $this->model('matter\enroll\remark');
		$remarks = $modelRec->byUser($oApp, $oUser, ['fields' => 'id']);
		$result->remark_other_num = count($remarks);

		return $result;
	}
}