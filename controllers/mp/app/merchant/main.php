<?php
namespace mp\app\merchant;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 商店管理
 */
class main extends \mp\app\app_base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'shop';
	}
	/**
	 * 商店列表
	 */
	public function index_action($shopId = null) {
		if ($shopId === null) {
			$this->view_action('/mp/app/merchant');
		} else {
			$this->view_action('/mp/app/merchant/shop');
		}

	}

}
