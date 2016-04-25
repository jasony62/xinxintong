<?php
namespace pl\fe\matter\merchant;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 商品
 */
class product extends \pl\fe\matter\base {
	/**
	 * 打开订购商品管理页面
	 */
	public function index_action() {
		$this->view_action('/mp/app/merchant/shop');
	}
	/**
	 *
	 */
	public function get_action($shop) {
		$groups = $this->model('matter\merchant\group')->byShopId($shop);

		return new \ResponseData($groups);
	}
	/**
	 *
	 */
	public function create_action($site, $shop) {
		$creater = \TMS_CLIENT::get_client_uid();

		$group = array(
			'siteid' => $site,
			'sid' => $shop,
			'create_at' => time(),
			'creater' => $creater,
			'name' => '新分组',
		);

		$group['id'] = $this->model()->insert('xxt_merchant_group', $product, true);

		return new \ResponseData($group);
	}
	/**
	 *
	 */
	public function update_action($id) {
		$reviser = \TMS_CLIENT::get_client_uid();

		$nv = $this->getPostJson();

		$nv->reviser = $reviser;
		$nv->modify_at = time();

		$rst = $this->model()->update('xxt_merchant_group', (array) $nv, "id='$id'");

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function remove_action() {
		return new \ResponseData('ok');
	}
}