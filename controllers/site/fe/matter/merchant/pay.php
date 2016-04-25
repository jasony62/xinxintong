<?php
namespace site\fe\matter\merchant;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 支付
 */
class pay extends \site\fe\matter\base {
	/**
	 * 进入支付页
	 *
	 * 要求当前用户必须是认证用户
	 *
	 * @param string $site mpid'id
	 * @param int $shop shop'id
	 * @param int $order order'id
	 *
	 */
	public function index_action($site, $shop, $order, $payby = null, $mocker = null, $code = null) {
		/*当前访问用户*/
		$openid = $this->doAuth($site, $code, $mocker);
		/*页面信息*/
		if ($payby === null) {
			$order = $this->model('matter\merchant\order')->byId($order);
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
	public function pageGet_action($site, $shop, $order) {
		// current user
		$user = $this->getUser($site);
		// order
		$order = $this->model('matter\merchant\order')->byId($order);
		if (false === $order) {
			return new \ResponseError('订单不存在');
		}
		// page
		$page = $this->model('matter\merchant\page')->byType('pay', $shop);
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
	 * @param string $site
	 * @param int $order
	 */
	public function coinOut_action($site, $shop, $order) {
		// current user
		$user = $this->getUser($site);
		// order
		$modelOrd = $this->model('matter\merchant\order');
		$order = $modelOrd->byId($order);
		// 扣除用户的积分
		$modelCoin = $this->model('coin\log');
		$modelCoin->expense($site, 'app.merchant.order,' . $order->id . '.pay', $user->openid, $order->order_total_price);
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
	 * @param string $site
	 * @param int $order
	 */
	public function jsApiParametersGet_action($site, $order) {
		$user = $this->getUser($site);

		$order = $this->model('matter\merchant\order')->byId($order);
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

		$wxPayConfig = new \WxPayConfig($site);
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
		$order = \WxPayApi::unifiedOrder($site, $input);
		if ($order['result_code'] === 'FAIL') {
			return new \ResponseError($order['err_code_des']);
		}
		$jsApiParameters = $tools->GetJsApiParameters($site, $order);

		//获取共享收货地址js函数参数
		$editAddress = $tools->GetEditAddressParameters($site);

		$rsp = array(
			'jsApiParameters' => $jsApiParameters,
			'editAddress' => $editAddress,
		);

		return new \ResponseData($rsp);
	}
}