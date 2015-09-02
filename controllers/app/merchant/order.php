<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 订单
 */
class order extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 进入发起订单页
	 *
	 * 要求当前用户必须是认证用户
	 *
	 * $mpid mpid'id
	 * $shop shop'id
	 * $sku sku'id
	 */
	public function index_action($mpid, $shop, $sku, $mocker = null, $code = null) {
		/**
		 * 获得当前访问用户
		 */
		$openid = $this->doAuth($mpid, $code, $mocker);

		$this->afterOAuth($mpid, $shop, $sku, $openid);
	}
	/**
	 *
	 */
	public function afterOAuth($mpid, $shopId, $skuId, $openid) {
		$this->view_action('/app/merchant/order');
	}
	/**
	 * 购买商品
	 */
	public function buy_action($mpid, $sku) {
		$user = $this->getUser($mpid, array('verbose' => array('fan' => 'Y')));
		if (empty($user->openid)) {
			return new \ResponseError('无法获得当前用户身份信息');
		}

		$orderInfo = $this->getPostJson();

		$order = $this->model('app\merchant\order')->create($sku, $user, $orderInfo);

		return new \ResponseData('ok');
	}
	/**
	 *
	 */
	private function notify($mpid, $order) {
		/**
		 * 如果设置了客户人员，向客服人员发消息
		 */
		$url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/op/merchant/order";
		$url .= "?mpid=" . $mpid;
		$url .= "&shop=" . $order->sid;
		$url .= "&order=" . $order->id;

		$txt = urlencode("有新订单，");
		$txt .= "<a href=\"$url\">";
		$txt .= urlencode("请处理");
		$txt .= "</a>";
		$message = array(
			"msgtype" => "text",
			"text" => array(
				"content" => $txt,
			),
		);
		$modelFan = $this->model('user/fans');
		$staffs = $this->model('app\merchant\shop')->staffAcls($mpid, $id, 'c');
		foreach ($staffs as $staff) {
			switch ($staff->idsrc) {
			case 'M':
				$fan = $modelFan->byMid($staff->identity);
				$this->sendByOpenid($mpid, $fan->openid, $message);
				break;
			}
		}

		return true;
	}
}
