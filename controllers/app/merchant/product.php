<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 商品
 */
class product extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 进入商品展示页
	 *
	 * @param string $mpid mpid'id
	 * @param int $shop shop'id
	 * @param int $catelog
	 * @param int $product
	 *
	 * @return
	 */
	public function index_action($mpid, $shop, $catelog, $product, $mocker = null, $code = null) {
		//$openid = $this->doAuth($mpid, $code, $mocker);
		$options = array(
			'fields' => 'title',
			'cascaded' => 'N',
		);
		$page = $this->model('app\merchant\page')->byType('product', $shop, $catelog, 0, $options);
		if (empty($page)) {
			$this->outputError('指定的页面不存在');
		} else {
			$page = $page[0];
			\TPL::assign('title', $page->title);
			\TPL::output('/app/merchant/product');
			exit;
		}
	}
	/**
	 * 获得商品的页面定义
	 */
	public function pageGet_action($mpid, $shop, $catelog, $product) {
		// current visitor
		$user = $this->getUser($mpid);
		// page
		$page = $this->model('app\merchant\page')->byType('product', $shop, $catelog);
		if (empty($page)) {
			return new \ResponseError('没有获得商品页定义');
		}
		$page = $page[0];

		$params = array(
			'user' => $user,
			'page' => $page,
		);

		return new \ResponseData($params);
	}
	/**
	 * 获得属性的可选值
	 *
	 * @param int $catelog
	 * @param string 逗号分隔的属性值
	 */
	public function list_action($catelog, $vids = '', $cascaded = 'N') {
		$vids = empty($vids) ? array() : explode(',', $vids);

		$cateFields = 'id,sid,name,pattern,pages';
		$catelog = $this->model('app\merchant\catelog')->byId($catelog, array('fields' => $cateFields, 'cascaded' => 'Y'));

		$state = array(
			'disabled' => 'N',
			'active' => 'Y',
		);
		$options = array(
			'fields' => 'id,cate_id,name,main_img,img,detail_img,detail_text,prop_value,sku_info',
			'state' => $state,
			'cascaded' => $cascaded,
		);
		$products = $this->model('app\merchant\product')->byPropValue($catelog, $vids, $options);

		return new \ResponseData(array('products' => $products, 'catelog' => $catelog));
	}
	/**
	 *
	 * @param int $product
	 */
	public function get_action($product) {
		$modelProd = $this->model('app\merchant\product');
		$options = array(
			'cascaded' => 'Y',
		);
		$prod = $modelProd->byId($product, $options);

		return new \ResponseData($prod);
	}
}