<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 货架
 */
class shelf extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 进入产品展示页
	 *
	 * $mpid mpid'id
	 * $shop shop'id
	 */
	public function index_action($mpid, $page, $mocker = null, $code = null) {
		/**
		 * 获得当前访问用户
		 */
		$openid = $this->doAuth($mpid, $code, $mocker);

		$this->afterOAuth($mpid, $page, $openid);
	}
	/**
	 * 返回页面
	 */
	public function afterOAuth($mpid, $page, $openid) {
		$page = $this->model('app\merchant\page')->byId($page);
		\TPL::assign('title', $page->title);
		\TPL::output('/app/merchant/shelf');
		exit;
	}
	/**
	 *
	 */
	public function get_action($mpid, $page) {
		// current visitor
		$user = $this->getUser($mpid);
		// page
		$page = $this->model('app\merchant\page')->byId($page);

		$params = array(
			'user' => $user,
			'page' => $page,
		);

		return new \ResponseData($params);
	}
}