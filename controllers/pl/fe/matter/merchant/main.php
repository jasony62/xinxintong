<?php
namespace pl\fe\matter\merchant;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 商店管理
 */
class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'shop';
	}
	/**
	 * 商店列表
	 */
	public function index_action($id) {
		\TPL::output('/pl/fe/matter/merchant/shop/frame');
		exit;
	}
	/**
	 * 商店列表
	 */
	public function list_action($site) {
		$shops = $this->model('matter\merchant\shop')->bySite($site);

		return new \ResponseData($shops);
	}
}