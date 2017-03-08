<?php
namespace matter\template;
/**
 * 模版访问控制
 */
class acl_model extends \TMS_MODEL {
	/**
	 *
	 *
	 * @param string $id
	 * @param string $type
	 *
	 */
	public function byMatter($id) {
		$q = [
			'*',
			'xxt_template_acl',
			['template_id' => $id]
		];

		$acls = $this->query_objs_ss($q);

		return $acls;
	}
	/**
	 *
	 */
	public function byReceiver($userId, $tid) {
		$q = [
			'*',
			'xxt_template_acl',
			"receiver='$userId' and template_id='$tid'",
		];
		$acl = $this->query_obj_ss($q);

		return $acl;
	}
	/**
	 *
	 */
	public function add(&$user, &$template, &$receiver) {
		$acl = new \stdClass;
		$acl->creater = $user->id;
		$acl->creater_name = $user->name;
		$acl->create_at = time();
		$acl->receiver = $receiver->receiver;
		$acl->receiver_label = $receiver->receiver_label;
		$acl->template_id = $template->id;
		$acl->matter_id = $template->matter_id;
		$acl->matter_type = $template->matter_type;
		$acl->scenario = $template->scenario;

		$acl->id = $this->insert('xxt_template_acl', $acl, true);

		return $acl;
	}
}