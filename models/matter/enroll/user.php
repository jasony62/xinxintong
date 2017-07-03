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
	public function enrolleeByApp($oApp, $page = 1, $size = 30, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$result = new \stdClass;
		$q = [
			$fields,
			"xxt_enroll_user",
			"aid='{$oApp->id}' and enroll_num>0",
		];
		$q2 = [
			'o' => 'last_enroll_at desc',
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
	 * 活动中提交过数据的用户
	 */
	public function enrolleeByMschema($oApp, $oMschema, $page = 1, $size = 30, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : 'userid,email,mobile,name,extattr';

		$result = new \stdClass;
		$q = [
			$fields,
			"xxt_site_member",
			['schema_id' => $oMschema->id, 'verified' => 'Y', 'forbidden' => 'N'],
		];
		$q2 = [
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];
		$members = $this->query_objs_ss($q, $q2);
		if (count($members)) {
			foreach ($members as &$oMember) {
				$oMember->extattr = empty($oMember->extattr) ? new \stdClass : json_decode($oMember->extattr);
				$oEnrollee = new \stdClass;
				$oEnrollee->userid = $oMember->userid;
				$oMember->report = $this->reportByUser($oApp, $oEnrollee);
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
			"aid='{$oApp->id}' and remark_other_num>0",
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