<?php
namespace site\op\matter\merchant;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 * 订单
 */
class orderlist extends \site\op\base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action($shop) {
		// page
		$options = array(
			'cascaded' => 'N',
			'fields' => 'title',
		);
		$page = $this->model('matter\merchant\page')->byType('op.orderlist', $shop, 0, 0, $options);
		$page = $page[0];
		\TPL::assign('title', $page->title);
		\TPL::output('/site/op/matter/merchant/orderlist');
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
		$user = new \stdClass;
		// page
		$page = $this->model('matter\merchant\page')->byType('op.orderlist', $shop->id);
		if (empty($page)) {
			return new \ResponseError('没有获得订单页定义');
		}
		$page = $page[0];

		$params = array(
			'shop' => $shop,
			'user' => $user,
			'page' => $page,
		);

		return new \ResponseData($params);
	}
	/**
	 * 查询订单
	 *
	 * @param string $site
	 * @param int $shop
	 * @param int $page
	 * @param int $size
	 * @param string $statu
	 */
	public function get_action($site, $shop, $page = 1, $size = 30, $status = '') {
		$p = new \stdClass;
		$p->page = $page;
		$p->size = $size;
		$options = array(
			'page' => $p,
		);
		!empty($status) && $options['status'] = explode(',', $status);

		$orders = $this->model('matter\merchant\order')->byShopid($shop, $options);

		return new \ResponseData($orders);
	}
}