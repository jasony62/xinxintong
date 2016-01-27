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
		//
		$openid = $this->doAuth($mpid, $code, $mocker);
		// page
		$options = array(
			'cascaded' => 'N',
			'fields' => 'title',
		);
		$page = $this->model('app\merchant\page')->byType('op.orderlist', $shop, 0, 0, $options);
		$page = $page[0];
		\TPL::assign('title', $page->title);
		/* 订单列表页 */
		\TPL::output('/app/merchant/orderlist');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($mpid, $shop) {
		// shop
		$shop = $this->model('app\merchant\shop')->byId($shop, array('fields' => 'id,title,order_status'));
		$shop->order_status = empty($shop->order_status) ? new \stdClass : json_decode($shop->order_status);
		// current visitor
		$user = $this->getUser($mpid);
		// page
		$page = $this->model('app\merchant\page')->byType('orderlist', $shop->id);
		if (empty($page)) {
			return new \ResponseError('没有获得订单页定义');
		}
		$page = $page[0];
		// return
		$params = array(
			'shop' => $shop,
			'user' => $user,
			'page' => $page,
		);

		return new \ResponseData($params);
	}
	/**
	 * 当前用户发起的订单
	 *
	 * @param string $mpid
	 * @param int $order
	 * @param int shop
	 */
	public function get_action($mpid, $shop, $page = 1, $size = 30, $status = '') {
		$user = $this->getUser($mpid);
		if (empty($user->openid)) {
			return new \ResponseError('无法获得当前用户信息');
		}
		$options = array(
			'openid' => $user->openid,
		);
		$p = new \stdClass;
		$p->page = $page;
		$p->size = $size;
		$options['page'] = $p;

		!empty($status) && $options['status'] = explode(',', $status);

		$result = $this->model('app\merchant\order')->byShopid($shop, $options);

		return new \ResponseData($result);
	}
}