<?php
namespace site\fe\matter\merchant;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 商品分类
 */
class catelog extends \site\fe\matter\base {
	/**
	 * 获得已经上线的可用的分类
	 *
	 * @param string $site
	 * @param int $shop
	 *
	 */
	public function list_action($site, $shop) {
		$state = array(
			'disabled' => 'N',
			'active' => 'Y',
		);
		$fields = 'id,name,has_validity';
		$options = array(
			'state' => $state,
			'fields' => $fields,
		);
		$catelogs = $this->model('matter\merchant\catelog')->byShopId($shop, $options);

		return new \ResponseData($catelogs);
	}
	/**
	 * 获得属性的可选值
	 *
	 * $propId 属性ID
	 * $assoPropVid 关联的属性ID
	 */
	public function propValueGet_action($propId, $assoPropVid = null) {
		$pvs = $this->model('matter\merchant\catelog')->valuesById($propId, $assoPropVid);

		return new \ResponseData($pvs);
	}
}