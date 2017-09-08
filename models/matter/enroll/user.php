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
		}else{
			$q2 = ['o' => 'last_enroll_at desc'];
		}
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		if ($users = $this->query_objs_ss($q, $q2)) {
			if ($cascaded === 'Y') {
				foreach ($users as $user) {
					$p = [
						'wx_openid,yx_openid,qy_openid',
						"xxt_site_account",
						"uid = '{$user->userid}'",
					];
					if ($openid = $this->query_obj_ss($p)) {
						$user->wx_openid = $openid->wx_openid;
						$user->yx_openid = $openid->yx_openid;
						$user->qy_openid = $openid->qy_openid;
					} else {
						$user->wx_openid = '';
						$user->yx_openid = '';
						$user->qy_openid = '';
					}
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