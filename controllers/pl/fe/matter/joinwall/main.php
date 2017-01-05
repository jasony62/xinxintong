<?php
namespace pl\fe\matter\joinwall;

require_once dirname(dirname(__FILE__)) . '/base.php';

class main extends \pl\fe\matter\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';
		return $rule_action;
	}
	/**
	 *
	 */
	public function list_action($site, $page=1, $size=50) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$q = array(
			'id,title',
			'xxt_wall',
			"siteid='$site'",
		);
		$q2['r'] = array('o' => ($page - 1) * $size, 'l' => $size);
		$walls = $this->model()->query_objs_ss($q,$q2);

		$modelWall = $this->model('matter\wall');
		foreach($walls as $wall){
			/**
			 * 获得每个讨论组的url
			 */
			$wall->url = $modelWall->getEntryUrl($site, $wall->id);
		}

		return new \ResponseData($walls);
	}
}