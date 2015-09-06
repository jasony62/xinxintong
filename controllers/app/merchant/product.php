<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/xxt_base.php';
/**
 * 产品
 */
class product extends \xxt_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 获得属性的可选值
	 *
	 * $propId 属性ID
	 * $assoPropId 关联的属性ID
	 * $assoPropVid 关联的属性ID
	 */
	public function getByPropValue_action($cateId, $vids, $cascaded = 'N') {
		$vids = explode(',', $vids);

		$products = $this->model('app\merchant\product')->byPropValue($cateId, $vids, $cascaded);

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
