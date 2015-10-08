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
	public function index_action($mpid, $product = null, $sku = null, $order = null, $mocker = null, $code = null) {
		/**
		 * 获得当前访问用户
		 */
		$openid = $this->doAuth($mpid, $code, $mocker);

		$this->afterOAuth($mpid, $product, $sku, $order, $openid);
	}
	/**
	 * 返回页面
	 */
	public function afterOAuth($mpid, $productId, $skuId, $orderId, $openid) {
		\TPL::output('/app/merchant/order');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($mpid, $shop) {
		// current visitor
		$user = $this->getUser($mpid);
		// page
		$page = $this->model('app\merchant\page')->byType($shop, 'order');
		if (empty($page)) {
			return new \ResponseError('没有获得订单页定义');
		}
		$page = $page[0];

		$params = array(
			'user' => $user,
			'page' => $page,
		);

		return new \ResponseData($params);
	}
	/**
	 *
	 */
	public function get_action($mpid, $order = null, $shop = null, $sku = null) {
		$order = $this->model('app\merchant\order')->byId($order);
		return new \ResponseData($order);
	}
	/**
	 * 创建订单
	 */
	public function create_action($mpid, $sku = null) {
		$user = $this->getUser($mpid, array('verbose' => array('fan' => 'Y')));
		if (empty($user->openid)) {
			return new \ResponseError('无法获得当前用户身份信息');
		}

		$orderInfo = $this->getPostJson();

		$order = $this->model('app\merchant\order')->create($sku, $user, $orderInfo);

		$this->notify($mpid, $order);

		return new \ResponseData($order->id);
	}
	/**
	 * 通知客服有新订单
	 */
	private function notify($mpid, $order) {
		/* 客服员工 */
		$staffs = $this->model('app\merchant\shop')->staffAcls($mpid, $order->sid, 'c');
		if (empty($staffs)) {
			return false;
		}
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
