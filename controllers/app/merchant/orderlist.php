<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 订单
 */
class orderlist extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 进入发起订单页
	 *
	 * 要求当前用户必须是认证用户
	 *
	 * $mpid mpid'id
	 * $shop shop'id
	 * $sku sku'id
	 */
	public function index_action($mpid, $shop, $mocker = null, $code = null) {
		/**
		 * 获得当前访问用户
		 */
		$openid = $this->doAuth($mpid, $code, $mocker);

		$this->afterOAuth($mpid, $shop, $openid);
	}
	/**
	 * 返回页面
	 */
	public function afterOAuth($mpid, $shop, $openid) {
		/* 订单列表 */
		\TPL::output('/app/merchant/orderlist');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($mpid, $shop) {
		// current visitor
		$user = $this->getUser($mpid);
		// page
		$page = $this->model('app\merchant\page')->byType($shop, 'orderlist');
		if (empty($page)) {
			return new \ResponseError('没有获得订单页定义');
		}
		$page = $page[0];

		$params = array(
			'user' => $user,
			'page' => $page,
		);

		return new \ResponseData($params);
	}
	/**
	 *
	 */
	public function get_action($mpid, $order = null, $shop = null, $sku = null) {
		$user = $this->getUser($mpid);

		$orders = $this->model('app\merchant\order')->byShopid($shop, $user->openid);

		return new \ResponseData($orders);
	}
}
