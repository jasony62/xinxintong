<?php
namespace pl\fe\matter\merchant;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 定制页面
 */
class page extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function byShop_action($site, $shop) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelPage = $this->model('matter\merchant\page');
		$modelCode = $this->model('code\page');

		$shopPages = array(
			array(
				'name' => '用户.商品列表',
				'title' => '商品列表',
				'type' => 'shelf',
				'seq' => 1,
			),
			array(
				'name' => '用户.商品',
				'title' => '商品页',
				'type' => 'product',
				'seq' => 2,
			),
			array(
				'name' => '用户.购物车',
				'title' => '购物车',
				'type' => 'cart',
				'seq' => 3,
			),
			array(
				'name' => '用户.新建订单',
				'title' => '新建订单',
				'type' => 'ordernew',
				'seq' => 4,
			),
			array(
				'name' => '用户.查看订单',
				'title' => '查看订单',
				'type' => 'order',
				'seq' => 5,
			),
			array(
				'name' => '用户.订单列表',
				'title' => '订单列表',
				'type' => 'orderlist',
				'seq' => 6,
			),
			array(
				'name' => '用户.支付',
				'title' => '支付',
				'type' => 'pay',
				'seq' => 7,
			),
			array(
				'name' => '用户.支付完成',
				'title' => '支付完成',
				'type' => 'payok',
				'seq' => 8,
			),
			array(
				'name' => '客服.订单',
				'title' => '订单',
				'type' => 'op.order',
				'seq' => 101,
			),
			array(
				'name' => '客服.订单列表',
				'title' => '订单列表',
				'type' => 'op.orderlist',
				'seq' => 102,
			),
		);
		$pages = array();
		foreach ($shopPages as $sp) {
			$page = $modelPage->byType($sp['type'], $shop);
			if (empty($page)) {
				$page = $modelPage->add($site, $sp, $shop);
				$tmplateDir = TMS_APP_TEMPLATE . '/pl/fe/matter/merchant/' . str_replace('.', '/', $sp['type']) . '/';
				$data = array(
					'html' => file_get_contents($tmplateDir . 'basic.html'),
					'css' => file_get_contents($tmplateDir . 'basic.css'),
					'js' => file_get_contents($tmplateDir . 'basic.js'),
				);
				$modelCode->modify($page->code_id, $data);
			}
			if (is_array($page)) {
				$pages = array_merge($pages, $page);
			} else {
				$pages[] = $page;
			}
		}

		return new \ResponseData($pages);
	}
	/**
	 * 创建店铺下的定制页面
	 */
	public function createByShop_action($site, $shop, $type) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelPage = $this->model('matter\merchant\page');
		$modelCode = $this->model('code\page');

		$shopPages = array(
			'shelf' => array(
				'name' => '用户.商品列表',
				'title' => '商品列表',
				'type' => 'shelf',
				'seq' => 1,
			),
		);

		$sp = $shopPages[$type];
		$page = $modelPage->add($site, $sp, $shop);
		$tmplateDir = TMS_APP_TEMPLATE . '/pl/fe/matter/merchant/' . str_replace('.', '/', $type) . '/';
		$data = array(
			'html' => file_get_contents($tmplateDir . 'basic.html'),
			'css' => file_get_contents($tmplateDir . 'basic.css'),
			'js' => file_get_contents($tmplateDir . 'basic.js'),
		);
		$modelCode->modify($page->code_id, $data);

		return new \ResponseData($page);
	}
	/**
	 * 获得分类下定制的页面
	 */
	public function byCatelog_action($catelog) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelCate = $this->model('matter\merchant\catelog');
		$modelPage = $this->model('matter\merchant\page');

		$catelog = $modelCate->byId($catelog);
		$catePages = array(
			'product',
			'ordernew.skus',
			'order.skus',
			'cart.skus',
			'op.order.skus',
		);
		$pages = array();
		foreach ($catePages as $cp) {
			$page = $modelPage->byType($cp, $catelog->sid, $catelog->id);
			if (!empty($page)) {
				if (is_array($page)) {
					$pages = array_merge($pages, $page);
				} else {
					$pages[] = $page;
				}
			}
		}

		return new \ResponseData($pages);
	}
	/**
	 * 创建分类下的定制页面
	 */
	public function createByCatelog_action($site, $catelog, $type) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelCate = $this->model('matter\merchant\catelog');
		$modelPage = $this->model('matter\merchant\page');
		$modelCode = $this->model('code\page');

		$catePages = array(
			'product' => array(
				'name' => '用户.商品',
				'title' => '商品',
				'type' => 'product',
				'seq' => 2,
			),
			'ordernew.skus' => array(
				'name' => '用户.新建订单.库存',
				'title' => '新建订单.库存',
				'type' => 'ordernew.skus',
				'seq' => 3,
			),
			'order.skus' => array(
				'name' => '用户.查看订单.库存',
				'title' => '查看订单.库存',
				'type' => 'order.skus',
				'seq' => 4,
			),
			'cart.skus' => array(
				'name' => '用户.购物车.库存',
				'title' => '购物车.库存',
				'type' => 'cart.skus',
				'seq' => 5,
			),
			'op.order.skus' => array(
				'name' => '客服.查看订单.库存',
				'title' => '查看订单.库存',
				'type' => 'op.order.skus',
				'seq' => 101,
			),
		);
		$catelog = $modelCate->byId($catelog);
		$pattern = $catelog->pattern;

		$cp = $catePages[$type];
		$cp['sid'] = $catelog->sid;
		$cp['cate_id'] = $catelog->id;
		$page = $modelPage->add($site, $cp, $catelog->sid, $catelog->id);
		/*根据模板设置页面内容*/
		$tmplateDir = TMS_APP_TEMPLATE . '/pl/fe/matter/merchant/' . str_replace('.', '/', $type) . '/';
		$code = array(
			'html' => file_get_contents($tmplateDir . $pattern . '.html'),
			'css' => file_get_contents($tmplateDir . $pattern . '.css'),
			'js' => file_get_contents($tmplateDir . $pattern . '.js'),
		);
		$modelCode->modify($page->code_id, $code);
		/*记录状态*/
		if (isset($catelog->pages)) {
			$catelog->pages = json_decode($catelog->pages);
		} else {
			$catelog->pages = new \stdClass;
		}
		$catelog->pages->{$type} = 'Y';
		$catelog->pages = json_encode($catelog->pages);
		$modelCate->update(
			'xxt_merchant_catelog',
			array('pages' => $catelog->pages),
			"id=$catelog->id"
		);

		return new \ResponseData($page);
	}
	/**
	 * 用模板重置页面
	 */
	public function reset_action($page) {
		$page = $this->model('matter\merchant\page')->byId($page);
		$modelCode = $this->model('code\page');

		if ($page->cate_id != 0) {
			$modelCate = $this->model('matter\merchant\catelog');
			$catelog = $modelCate->byId($page->cate_id);
			$pattern = $catelog->pattern;
		} else {
			$pattern = 'basic';
		}
		$dir = str_replace('.', '/', $page->type);
		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/matter/merchant/' . $dir . '/';
		$data = array(
			'html' => file_get_contents($templateDir . $pattern . '.html'),
			'css' => file_get_contents($templateDir . $pattern . '.css'),
			'js' => file_get_contents($templateDir . $pattern . '.js'),
		);
		$modelCode->modify($page->code_id, $data);

		return new \ResponseData('ok');
	}
	/**
	 * 删除定制页面
	 *
	 * @param int $page
	 */
	public function remove_action($page) {
		$modelPage = $this->model('matter\merchant\page');
		$page = $modelPage->byId($page, array('cascaded' => 'N'));
		if ($page->cate_id && $page->prod_id === '0') {
			$modelCate = $this->model('matter\merchant\catelog');
			$catelog = $modelCate->byId($page->cate_id);
			if (isset($catelog->pages)) {
				$catelog->pages = json_decode($catelog->pages);
			} else {
				$catelog->pages = new \stdClass;
			}
			$catelog->pages->{$page->type} = 'N';
			$catelog->pages = json_encode($catelog->pages);
			$modelCate->update('xxt_merchant_catelog', array('pages' => $catelog->pages), "id=$catelog->id");
		}

		$this->model('code\page')->remove($page->code_id);
		$rst = $modelPage->delete('xxt_merchant_page', "id=$page->id");

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function update_action($shop, $page) {
		$posted = $this->getPostJson();

		$updated = array(
			'title' => $posted->title,
		);
		$rst = $this->model()->update(
			'xxt_merchant_page',
			$updated,
			"sid=$shop and id=$page"
		);

		return new \ResponseData($rst);
	}
}