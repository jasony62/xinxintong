<?php
namespace mp\app\merchant;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 定制页面
 */
class page extends \mp\app\app_base {
	/**
	 *
	 */
	public function byShop_action($shop) {
		$modelPage = $this->model('app\merchant\page');
		$modelCode = $this->model('code/page');

		$shopPages = array(
			array(
				'name' => '用户.商品列表页',
				'title' => '商品列表页',
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
				'name' => '用户.新建订单页',
				'title' => '新建订单页',
				'type' => 'ordernew',
				'seq' => 4,
			),
			array(
				'name' => '用户.查看订单页',
				'title' => '查看订单页',
				'type' => 'order',
				'seq' => 5,
			),
			array(
				'name' => '用户.订单列表页',
				'title' => '订单列表页',
				'type' => 'orderlist',
				'seq' => 6,
			),
			array(
				'name' => '用户.支付页',
				'title' => '支付页',
				'type' => 'pay',
				'seq' => 7,
			),
			array(
				'name' => '用户.支付完成页',
				'title' => '支付完成页',
				'type' => 'payok',
				'seq' => 8,
			),
			array(
				'name' => '客服.订单页',
				'title' => '订单页',
				'type' => 'op.order',
				'seq' => 101,
			),
			array(
				'name' => '客服.订单列表页',
				'title' => '订单列表页',
				'type' => 'op.orderlist',
				'seq' => 102,
			),
		);
		$pages = array();
		foreach ($shopPages as $sp) {
			$page = $modelPage->byType($sp['type'], $shop);
			if (empty($page)) {
				$page = $modelPage->add($this->mpid, $sp, $shop);
				$tmplateDir = dirname(__FILE__) . '/template/' . str_replace('.', '/', $sp['type']) . '/';
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
	 * 获得分类下定制的页面
	 */
	public function byCatelog_action($catelog) {
		$modelCate = $this->model('app\merchant\catelog');
		$modelPage = $this->model('app\merchant\page');
		$modelCode = $this->model('code/page');

		$catelog = $modelCate->byId($catelog);
		$catePages = array(
			array(
				'name' => '用户.商品',
				'title' => '商品页',
				'type' => 'product',
				'seq' => 2,
			),
		);
		$pattern = $catelog->pattern;
		$pages = array();
		foreach ($catePages as $cp) {
			$page = $modelPage->byType($cp['type'], $catelog->sid, $catelog->id);
			if (empty($page)) {
				$cp['sid'] = $catelog->sid;
				$cp['cate_id'] = $catelog->id;
				$page = $modelPage->add($this->mpid, $cp, $catelog->sid, $catelog->id);
				/*根据模板设置页面内容*/
				$tmplateDir = dirname(__FILE__) . '/template/' . str_replace('.', '/', $cp['type']) . '/';
				$code = array(
					'html' => file_get_contents($tmplateDir . $pattern . '.html'),
					'css' => file_get_contents($tmplateDir . $pattern . '.css'),
					'js' => file_get_contents($tmplateDir . $pattern . '.js'),
				);
				$modelCode->modify($page->code_id, $code);
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
	 * 用模板重置页面
	 */
	public function reset_action($page) {
		$page = $this->model('app\merchant\page')->byId($page);
		$modelCode = $this->model('code/page');

		if ($page->cate_id != 0) {
			$modelCate = $this->model('app\merchant\catelog');
			$catelog = $modelCate->byId($page->cate_id);
			$pattern = $catelog->pattern;
		} else {
			$pattern = 'basic';
		}
		$dir = str_replace('.', '/', $page->type);
		$templateDir = dirname(__FILE__) . '/template/' . $dir . '/';
		$data = array(
			'html' => file_get_contents($templateDir . $pattern . '.html'),
			'css' => file_get_contents($templateDir . $pattern . '.css'),
			'js' => file_get_contents($templateDir . $pattern . '.js'),
		);
		$modelCode->modify($page->code_id, $data);

		return new \ResponseData('ok');
	}
}