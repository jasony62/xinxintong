<?php
/**
 *
 */
class site_model extends \TMS_MODEL {
	/**
	 * 创建站点
	 */
	public function create($data) {
		$account = \TMS_CLIENT::account();
		$siteid = $this->uuid($account->uid);
		$data['id'] = $siteid;
		$data['creater'] = $account->uid;
		$data['creater_name'] = $account->nickname;
		$data['create_at'] = time();
		$this->insert('xxt_site', $data, false);

		return $siteid;
	}
	/**
	 *
	 */
	public function &byId($id, $options = array()) {
		$fields = empty($options['fields']) ? '*' : $options['fields'];
		$q = array(
			$fields,
			'xxt_site',
			"id='$id'",
		);

		$site = $this->query_obj_ss($q);

		return $site;
	}
}