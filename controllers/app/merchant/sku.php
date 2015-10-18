<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 库存
 */
class sku extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/*
	 *
	 */
	public function get_action($sku) {
		return new \ResponseData('building');
	}
	/*
	 *
	 */
	public function byProduct_action($product) {
		$modelSku = $this->model('app\merchant\sku');

		$skus = $modelSku->byProduct($product);

		return new \ResponseData($skus);
	}
}