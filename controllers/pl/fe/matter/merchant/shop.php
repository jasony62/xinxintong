<?php
namespace pl\fe\matter\merchant;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 店铺
 */
class shop extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/merchant/shop/frame');
		exit;
	}
	/**
	 *
	 */
	public function catelog_action() {
		\TPL::output('/pl/fe/matter/merchant/shop/frame');
		exit;
	}
	/**
	 *
	 */
	public function product_action() {
		\TPL::output('/pl/fe/matter/merchant/shop/frame');
		exit;
	}
	/**
	 *
	 */
	public function page_action() {
		\TPL::output('/pl/fe/matter/merchant/shop/frame');
		exit;
	}
	/**
	 *
	 */
	public function order_action() {
		\TPL::output('/pl/fe/matter/merchant/shop/frame');
		exit;
	}
	/**
	 * 商店
	 */
	public function get_action($site, $shop) {
		$modelShop = $this->model('matter\merchant\shop');
		$shop = $modelShop->byId($shop);
		if ($shop) {
			$shop->buyer_api = empty($shop->buyer_api) ? new \stdClass : json_decode($shop->buyer_api);
			$shop->order_status = empty($shop->order_status) ? new \stdClass : json_decode($shop->order_status);
			$shop->staffs = $modelShop->staffAcls($site, $shop->id, 'c');
		}

		return new \ResponseData($shop);
	}
	/**
	 * 商店列表
	 */
	public function list_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$shops = $this->model('matter\merchant\shop')->byMpid($site);

		return new \ResponseData($shops);
	}
	/**
	 * 创建新商店
	 */
	public function create_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$current = time();

		$shop = array(
			'siteid' => $site,
			'creater' => $user->id,
			'creater_name' => $user->name,
			'create_at' => $current,
			'modifier' => $user->id,
			'modifier_name' => $user->name,
			'modify_at' => $current,
			'title' => '新商店',
			'pic' => '',
			'summary' => '新商店',
		);
		$shopId = $this->model()->insert('xxt_merchant_shop', $shop, true);

		/*记录操作日志*/
		$app = $this->model('matter\merchant\shop')->byId($shopId);
		$app->type = 'merchant';
		$this->model('log')->matterOp($site, $user, $app, 'C');

		return new \ResponseData($shopId);
	}
	/**
	 * 更新商店的属性信息
	 *
	 * @param int $shop
	 */
	public function update_action($site, $shop) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$current = time();

		$nv = (array) $this->getPostJson();
		if (isset($nv['buyer_api'])) {
			$nv['buyer_api'] = \TMS_MODEL::toJson($nv['buyer_api']);
		}
		if (isset($nv['order_status'])) {
			$nv['order_status'] = \TMS_MODEL::toJson($nv['order_status']);
		}
		$nv['modifier'] = $user->id;
		$nv['modifier_name'] = $user->name;
		$nv['modify_at'] = $current;

		$rst = $this->model()->update('xxt_merchant_shop', $nv, "id='$shop'");

		return new \ResponseData($rst);
	}
	/**
	 * 按角色设置参与投稿活动的人
	 */
	public function setStaff_action($site, $shop, $role) {
		$user = $this->getPostJson();

		if (empty($user->identity)) {
			return new \ResponseError('没有指定用户的唯一标识');
		}

		if (isset($user->id)) {
			$u['identity'] = $user->identity;
			$rst = $this->model()->update(
				'xxt_merchant_staff',
				$u,
				"id=$user->id"
			);
			return new \ResponseData($rst);
		} else {
			$i['siteid'] = $site;
			$i['sid'] = $shop;
			$i['role'] = $role;
			$i['identity'] = $user->identity;
			$i['idsrc'] = empty($user->idsrc) ? '' : $user->idsrc;
			$i['label'] = empty($user->label) ? $user->identity : $user->label;

			$i['id'] = $this->model()->insert('xxt_merchant_staff', $i, true);

			return new \ResponseData($i);
		}
	}
	/**
	 * 按角色设置参与投稿活动的人
	 * $id
	 * $acl aclid
	 */
	public function delStaff_action($site, $acl) {
		$rst = $this->model()->delete(
			'xxt_merchant_staff',
			"siteid='$site' and id=$acl"
		);

		return new \ResponseData($rst);
	}
}