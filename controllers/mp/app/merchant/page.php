<?php
namespace mp\app\merchant;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 商品
 */
class page extends \mp\app\app_base {
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/mp/app/merchant/shop');
	}
	/**
	 *
	 */
	public function list_action($shop) {
		$modelPage = $this->model('app\merchant\page');
		$pages = $modelPage->byShopId($shop);
		if (empty($pages)) {
			/* shelf page */
			$page = array(
				'name' => '用户.商品列表页',
				'title' => '商品列表页',
				'type' => 'shelf',
			);
			$page = $this->model('app\merchant\page')->add($this->mpid, $shop, $page);
			//
			$codeModel = $this->model('code/page');
			$data = array(
				'html' => file_get_contents(dirname(__FILE__) . '/template/shelf/basic.html'),
				'css' => file_get_contents(dirname(__FILE__) . '/template/shelf/basic.css'),
				'js' => file_get_contents(dirname(__FILE__) . '/template/shelf/basic.js'),
			);
			$codeModel->modify($page->code_id, $data);
			$pages[] = $page;
		}
		$orderPage = $modelPage->byType($shop, 'order');
		if (empty($orderPage)) {
			$page = array(
				'name' => '用户.订单页',
				'title' => '订单页',
				'type' => 'order',
			);
			$page = $this->model('app\merchant\page')->add($this->mpid, $shop, $page);
			//
			$codeModel = $this->model('code/page');
			$data = array(
				'html' => file_get_contents(dirname(__FILE__) . '/template/order/basic.html'),
				'css' => file_get_contents(dirname(__FILE__) . '/template/order/basic.css'),
				'js' => file_get_contents(dirname(__FILE__) . '/template/order/basic.js'),
			);
			$codeModel->modify($page->code_id, $data);
			$pages[] = $page;
		}
		$payPage = $modelPage->byType($shop, 'pay');
		if (empty($payPage)) {
			$page = array(
				'name' => '用户.支付页',
				'title' => '支付页',
				'type' => 'pay',
			);
			$page = $this->model('app\merchant\page')->add($this->mpid, $shop, $page);
			//
			$codeModel = $this->model('code/page');
			$data = array(
				'html' => file_get_contents(dirname(__FILE__) . '/template/pay/basic.html'),
				'css' => file_get_contents(dirname(__FILE__) . '/template/pay/basic.css'),
				'js' => file_get_contents(dirname(__FILE__) . '/template/pay/basic.js'),
			);
			$codeModel->modify($page->code_id, $data);
			$pages[] = $page;
		}
		$orderlistPage = $modelPage->byType($shop, 'orderlist');
		if (empty($orderlistPage)) {
			$page = array(
				'name' => '用户.订单列表页',
				'title' => '订单列表页',
				'type' => 'orderlist',
			);
			$page = $this->model('app\merchant\page')->add($this->mpid, $shop, $page);
			//
			$codeModel = $this->model('code/page');
			$data = array(
				'html' => file_get_contents(dirname(__FILE__) . '/template/orderlist/basic.html'),
				'css' => file_get_contents(dirname(__FILE__) . '/template/orderlist/basic.css'),
				'js' => file_get_contents(dirname(__FILE__) . '/template/orderlist/basic.js'),
			);
			$codeModel->modify($page->code_id, $data);
			$pages[] = $page;
		}
		$payokPage = $modelPage->byType($shop, 'payok');
		if (empty($payokPage)) {
			$page = array(
				'name' => '用户.支付完成页',
				'title' => '支付完成页',
				'type' => 'payok',
			);
			$page = $this->model('app\merchant\page')->add($this->mpid, $shop, $page);
			//
			$codeModel = $this->model('code/page');
			$data = array(
				'html' => file_get_contents(dirname(__FILE__) . '/template/payok/basic.html'),
				'css' => file_get_contents(dirname(__FILE__) . '/template/payok/basic.css'),
				'js' => file_get_contents(dirname(__FILE__) . '/template/payok/basic.js'),
			);
			$codeModel->modify($page->code_id, $data);
			$pages[] = $page;
		}
		$opOrderPage = $modelPage->byType($shop, 'op.order');
		if (empty($opOrderPage)) {
			$page = array(
				'name' => '客服.订单页',
				'title' => '订单页',
				'type' => 'op.order',
			);
			$page = $this->model('app\merchant\page')->add($this->mpid, $shop, $page);
			//
			$codeModel = $this->model('code/page');
			$data = array(
				'html' => file_get_contents(dirname(__FILE__) . '/template/op/order/basic.html'),
				'css' => file_get_contents(dirname(__FILE__) . '/template/op/order/basic.css'),
				'js' => file_get_contents(dirname(__FILE__) . '/template/op/order/basic.js'),
			);
			$codeModel->modify($page->code_id, $data);
			$pages[] = $page;
		}
		$opOrderlistPage = $modelPage->byType($shop, 'op.orderlist');
		if (empty($opOrderlistPage)) {
			$page = array(
				'name' => '客服.订单列表页',
				'title' => '订单列表页',
				'type' => 'op.orderlist',
			);
			$page = $this->model('app\merchant\page')->add($this->mpid, $shop, $page);
			//
			$codeModel = $this->model('code/page');
			$data = array(
				'html' => file_get_contents(dirname(__FILE__) . '/template/op/orderlist/basic.html'),
				'css' => file_get_contents(dirname(__FILE__) . '/template/op/orderlist/basic.css'),
				'js' => file_get_contents(dirname(__FILE__) . '/template/op/orderlist/basic.js'),
			);
			$codeModel->modify($page->code_id, $data);
			$pages[] = $page;
		}

		return new \ResponseData($pages);
	}
	/**
	 *
	 */
	public function reset_action($page) {
		//
		$page = $this->model('app\merchant\page')->byId($page);
		/*用标准模版替换*/
		switch ($page->type) {
		case 'shelf':
		case 'order':
		case 'orderlist':
		case 'pay':
		case 'payok':
			$dir = $page->type;
			break;
		case 'op.order':
		case 'op.orderlist':
			$dir = str_replace('.', '/', $page->type);
		}
		//
		$modelCode = $this->model('code/page');
		$templateDir = dirname(__FILE__) . '/template/' . $dir;
		$data = array(
			'html' => file_get_contents($templateDir . '/basic.html'),
			'css' => file_get_contents($templateDir . '/basic.css'),
			'js' => file_get_contents($templateDir . '/basic.js'),
		);
		$modelCode->modify($page->code_id, $data);

		return new \ResponseData('ok');
	}
}