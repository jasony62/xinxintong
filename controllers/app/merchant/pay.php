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
	 * $mpid mpid'id
	 * $shop shop'id
	 * $sku sku'id
	 */
	public function index_action($mpid, $order, $mocker = null, $code = null) {
		/**
		 * 获得当前访问用户
		 */
		$openid = $this->doAuth($mpid, $code, $mocker);
		$this->afterOAuth($mpid, $order, $openid);
	}
	/**
	 * 返回页面
	 */
	public function afterOAuth($mpid, $orderId, $openid) {
		\TPL::output('/app/merchant/pay');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($mpid, $shop) {
		// current visitor
		$user = $this->getUser($mpid);
		// page
		$page = $this->model('app\merchant\page')->byType('pay', $shop);
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