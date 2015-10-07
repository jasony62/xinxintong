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

		$this->view_action('/app/merchant/pay');
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

		$notifyUrl = "http://" . $_SERVER['HTTP_HOST'];
		$notifyUrl .= "/rest/op/merchant/pay/notify";
		$notifyUrl .= "?mpid=$mpid";

		$tools = $this->model('mpproxy/WxPayJsApi');

		$wxPayConfig = new \WxPayConfig($mpid);
		$input = new \WxPayUnifiedOrder();

		$input->SetBody("test");
		$input->SetAttach("test");
		$input->SetOut_trade_no($order->trade_no);
		$input->SetTotal_fee($order->order_total_price);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 600));
		$input->SetGoods_tag("test");
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