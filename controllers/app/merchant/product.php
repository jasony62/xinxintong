<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 产品
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
	 * 进入产品展示页
	 *
	 * $mpid mpid'id
	 * $shop shop'id
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
	public function afterOAuth($mpid, $shopId, $openid) {
		\TPL::output('/app/merchant/product');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($mpid, $shop) {
		// current visitor
		$user = $this->getUser($mpid);
		// page
		$page = $this->model('app\merchant\page')->byType($shop, 'product');
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
	public function getByPropValue_action($catelog, $vids = '', $cascaded = 'N') {
		$vids = empty($vids) ? array() : explode(',', $vids);

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

		return new \ResponseData($products);
	}
	/*
	 *
	 */
	public function get_action($id) {
		$modelProd = $this->model('app\merchant\product');
		$prod = $modelProd->byId($id, 'Y');

		/*$prodPropValues = array();
		foreach ($prod->catelog->properties as $prop) {
		$prodPropValues[] = array(
		'name' => $prop->name,
		'value' => $prod->propValue2->{$prop->id}->name,
		);
		}*/
		//$prod->propValues = $prodPropValues;

		return new \ResponseData($prod);
	}
	/**
	 *
	 */
	public function skuGet_action($id) {
		$sku = $this->model('app\merchant\sku')->byId($id);

		if ($sku === false) {
			return new \ResponseError('指定的库存不存在');
		}

		$modelProd = $this->model('app\merchant\product');
		$prod = $modelProd->byId($sku->prod_id);
		$cascaded = $modelProd->cascaded($prod->id);

		$prodPropValues = array();
		foreach ($cascaded->catelog->properties as $prop) {
			$prodPropValues[] = array(
				'name' => $prop->name,
				'value' => $cascaded->propValue2->{$prop->id}->name,
			);
		}
		$prod->propValues = $prodPropValues;

		return new \ResponseData(array('sku' => $sku, 'prod' => $prod, 'cate' => $cascaded->catelog, 'propValues' => $cascaded->propValue2));
	}
}