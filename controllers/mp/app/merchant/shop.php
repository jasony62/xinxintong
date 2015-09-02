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

		$shop->staffs = $this->model('app\merchant\shop')->staffAcls($this->mpid, $id, 'c');

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
	/**
	 * 按角色设置参与投稿活动的人
	 */
	public function setStaff_action($shopId, $role) {
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
			$i['mpid'] = $this->mpid;
			$i['shopid'] = $shopId;
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
	public function delStaff_action($acl) {
		$rst = $this->model()->delete(
			'xxt_merchant_staff',
			"mpid='$this->mpid' and id=$acl"
		);

		return new \ResponseData($rst);
	}
}
