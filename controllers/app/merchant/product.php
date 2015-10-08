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
		$this->view_action('/app/merchant/products');
	}
	/**
	 * 获得属性的可选值
	 *
	 * $propId 属性ID
	 * $assoPropId 关联的属性ID
	 * $assoPropVid 关联的属性ID
	 */
	public function getByPropValue_action($catelog, $vids = '', $cascaded = 'N') {
		$vids = empty($vids) ? array() : explode(',', $vids);

		$products = $this->model('app\merchant\product')->byPropValue($catelog, $vids, $cascaded);

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