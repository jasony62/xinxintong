<?php
namespace site\fe\matter\merchant;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 订单
 */
class orderlist extends \site\fe\matter\base {
	/**
	 * 进入发起订单页
	 *
	 * 要求当前用户必须是认证用户
	 *
	 * $site mpid'id
	 * $shop shop'id
	 * $sku sku'id
	 */
	public function index_action($site, $shop, $mocker = null, $code = null) {
		//
		$user = $this->who;
		// page
		$options = array(
			'cascaded' => 'N',
			'fields' => 'title',
		);
		/* 订单列表页 */
		$page = $this->model('matter\merchant\page')->byType('op.orderlist', $shop, 0, 0, $options);
		$page = $page[0];
		\TPL::assign('title', $page->title);
		\TPL::output('/site/fe/matter/merchant/orderlist');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($site, $shop) {
		// shop
		$shop = $this->model('matter\merchant\shop')->byId($shop, array('fields' => 'id,title,order_status'));
		$shop->order_status = empty($shop->order_status) ? new \stdClass : json_decode($shop->order_status);
		// current visitor
		$user = $this->who;
		// page
		$page = $this->model('matter\merchant\page')->byType('orderlist', $shop->id);
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
	 * @param string $site
	 * @param int $order
	 * @param int shop
	 */
	public function get_action($site, $shop, $page = 1, $size = 30, $status = '') {
		$user = $this->who;
		if (empty($user->uid)) {
			return new \ResponseError('无法获得当前用户信息');
		}
		$options = array(
			'userid' => $user->uid,
		);
		$p = new \stdClass;
		$p->page = $page;
		$p->size = $size;
		$options['page'] = $p;

		!empty($status) && $options['status'] = explode(',', $status);

		$result = $this->model('matter\merchant\order')->byShopid($shop, $options);

		return new \ResponseData($result);
	}
}