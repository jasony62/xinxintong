<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/xxt_base.php';
/**
 * 商品分类
 */
class catelog extends \xxt_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 获得已经上线的可用的分类
	 *
	 * @param string $mpid
	 * @param int $shop
	 *
	 */
	public function list_action($mpid, $shop) {
		$state = array(
			'disabled' => 'N',
			'active' => 'Y',
		);
		$fields = 'id,name,has_validity';
		$options = array(
			'state' => $state,
			'fields' => $fields,
		);
		$catelogs = $this->model('app\merchant\catelog')->byShopId($shop, $options);

		return new \ResponseData($catelogs);
	}
	/**
	 * 获得属性的可选值
	 *
	 * $propId 属性ID
	 * $assoPropVid 关联的属性ID
	 */
	public function propValueGet_action($propId, $assoPropVid = null) {
		$pvs = $this->model('app\merchant\catelog')->valuesById($propId, $assoPropVid);

		return new \ResponseData($pvs);
	}
}