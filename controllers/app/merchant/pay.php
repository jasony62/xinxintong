<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 支付
 */
class pay extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 进入支付页
	 *
	 * 要求当前用户必须是认证用户
	 *
	 * @param string $mpid mpid'id
	 * @param int $shop shop'id
	 * @param int $order order'id
	 *
	 */
	public function index_action($mpid, $shop, $order, $payby = null, $mocker = null, $code = null) {
		/*当前访问用户*/
		$openid = $this->doAuth($mpid, $code, $mocker);
		/*页面信息*/
		if ($payby === null) {
			$order = $this->model('app\merchant\order')->byId($order);
			$payby = $order->payby;
		}
		switch ($payby) {
		case 'coin':
			\TPL::output('/app/merchant/pay/coin');
			break;
		case 'wx':
			\TPL::output('/app/merchant/pay/wx');
			break;
		default:
			die('unknown pay channel');
		}
		exit;
	}
	/**
	 * 获得页面定义
	 */
	public function pageGet_action($mpid, $shop, $order) {
		// current user
		$user = $this->getUser($mpid);
		// order
		$order = $this->model('app\merchant\order')->byId($order);
		if (false === $order) {
			return new \ResponseError('订单不存在');
		}
		// page
		$page = $this->model('app\merchant\page')->byType('pay', $shop);
		if (empty($page)) {
			return new \ResponseError('没有获得订单支付页定义');
		}
		$page = $page[0];

		$params = array(
			'user' => $user,
			'page' => $page,
			'order' => $order,
		);

		return new \ResponseData($params);
	}
	/**
	 * 用积分进行支付
	 *
	 * @param string $mpid
	 * @param int $order
	 */
	public function coinOut_action($mpid, $shop, $order) {
		// current user
		$user = $this->getUser($mpid);
		// order
		$modelOrd = $this->model('app\merchant\order');
		$order = $modelOrd->byId($order);
		// 扣除用户的积分
		$modelCoin = $this->model('coin\log');
		$modelCoin->expense($mpid, 'app.merchant.order,' . $order->id . '.pay', $user->openid, $order->order_total_price);
		// 更新订单状态
		$rst = $modelOrd->update(
			'xxt_merchant_order',
			array(
				'order_status' => '2', // 已支付
			),
			"id='$order->id' and buyer_openid='user->openid'"
		);
		if ($rst != 1) {
			return new \RespnseError("更新订单信息失败");
		}

		return new \ResponseData('ok');
	}
	/**
	 * 获得调用微信支付jsapi的参数
	 *
	 * @param string $mpid
	 * @param int $order
	 */
	public function jsApiParametersGet_action($mpid, $order) {
		$user = $this->getUser($mpid);

		$order = $this->model('app\merchant\order')->byId($order);
		if (false === $order) {
			return new \ResponseError('订单不存在');
		}
		$products = array();
		$order->products = json_decode($order->products);
		foreach ($order->products as $prod) {
			$products[] = $prod->name;
		}
		$products = implode(',', $products);

		$notifyUrl = "http://" . $_SERVER['HTTP_HOST'];
		$notifyUrl .= "/rest/op/merchant/payok/notify";

		$tools = $this->model('mpproxy/WxPayJsApi');

		$wxPayConfig = new \WxPayConfig($mpid);
		$input = new \WxPayUnifiedOrder();

		$input->SetBody($products);
		$input->SetAttach("测试附加信息");
		$input->SetOut_trade_no($order->trade_no);
		$input->SetTotal_fee($order->order_total_price);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 600));
		$input->SetGoods_tag("测试标签");
		$input->SetNotify_url($notifyUrl);
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($user->openid);
		$order = \WxPayApi::unifiedOrder($mpid, $input);
		if ($order['result_code'] === 'FAIL') {
			return new \ResponseError($order['err_code_des']);
		}
		$jsApiParameters = $tools->GetJsApiParameters($mpid, $order);

		//获取共享收货地址js函数参数
		$editAddress = $tools->GetEditAddressParameters($mpid);

		$rsp = array(
			'jsApiParameters' => $jsApiParameters,
			'editAddress' => $editAddress,
		);

		return new \ResponseData($rsp);
	}
}