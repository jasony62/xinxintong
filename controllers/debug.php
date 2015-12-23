<?php
include_once dirname(__FILE__) . '/member_base.php';
/**
 *
 */
class debug extends member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action($mpid) {
		TPL::output('debug');
	}
	/**
	 * 清除用户认证信息
	 */
	public function cleanMemberCookie_action($mpid) {
		/**
		 * member identity
		 */
		$authapi = $this->model('user/authapi')->byUrl($mpid, '/rest/member/auth', 'authid');
		$this->mySetCookie("_{$mpid}_{$authapi->authid}_member", '', 0);

		return new ResponseData('ok');
	}
	/**
	 *
	 */
	public function cleanOAuthUserCookie_action($mpid) {
		$user = $this->getUser($mpid, array('verbose' => array('fan' => 'Y', 'member' => 'Y')));

		$this->setCookieOAuthUser($mpid, '');

		return new ResponseData($user);
	}
}