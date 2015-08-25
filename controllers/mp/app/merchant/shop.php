<?php
namespace mp\app\merchant;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 商店管理
 */
class shop extends \mp\app\app_base {
	/**
	 * 商店列表
	 */
	public function index_action($id) {
		$this->view_action('/mp/app/merchant/shop');
	}
	/**
	 * 商店
	 */
	public function get_action($id) {
		$shop = $this->model('app\merchant\shop')->byId($id);

		return new \ResponseData($shop);
	}
	/**
	 * 商店列表
	 */
	public function list_action() {
		$shops = $this->model('app\merchant\shop')->byMpid($this->mpid);

		return new \ResponseData($shops);
	}
	/**
	 * 创建新商店
	 */
	public function create_action() {
		$account = \TMS_CLIENT::account();
		if ($account === false) {
			return new \ResponseError('长时间未操作，请重新登陆！');
		}

		$creater = \TMS_CLIENT::get_client_uid();
		$creater_name = $account->nickname;

		$shop = array(
			'mpid' => $this->mpid,
			'create_at' => time(),
			'creater' => $creater,
			'creater_name' => $creater_name,
			'title' => '新商店',
			'pic' => '',
			'summary' => '新商店',
		);

		$shopId = $this->model()->insert('xxt_merchant_shop', $shop, true);

		return new \ResponseData($shopId);
	}
	/**
	 * 更新商店的属性信息
	 */
	public function update_action($id) {
		$nv = (array) $this->getPostJson();

		$rst = $this->model()->update('xxt_merchant_shop', $nv, "id='$id'");

		return new \ResponseData($rst);
	}
}
