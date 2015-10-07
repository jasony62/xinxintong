<?php
namespace op\merchant;

require_once $_SERVER['DOCUMENT_ROOT'] . "/lib/wxpay/WxPay.Api.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/wxpay/WxPay.Notify.php';
/**
 *
 */
class PayNotifyCallBack extends \WxPayNotify {
	//公众平台ID
	protected $mpid = false;
	//查询订单
	public function Queryorder($mpid, $transaction_id) {
		\TMS_APP::M('log')->log('debug', 'pay-Queryorder', '0');
		$input = new \WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = \WxPayApi::orderQuery($mpid, $input);
		if (array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS") {
			return true;
		}
		\TMS_APP::M('log')->log('debug', 'pay-Queryorder', 'ok');
		return false;
	}

	//重写回调处理函数
	public function NotifyProcess($data, &$msg) {
		\TMS_APP::M('log')->log('debug', 'pay-notify', '0');

		$notfiyOutput = array();

		if (!array_key_exists("transaction_id", $data)) {
			$msg = "输入参数不正确";
			\TMS_APP::M('log')->log('debug', 'pay-notify', $msg);
			return false;
		}
		//查询订单，判断订单真实性
		$mpid = $this->getMpid($data);
		if (!$this->Queryorder($mpid, $data["transaction_id"])) {
			$msg = "订单查询失败";
			\TMS_APP::M('log')->log('debug', 'pay-notify', $msg);
			return false;
		}
		//更新订单支付信息
		$trans_id = $data['transaction_id'];
		$trade_no = $data['out_trade_no'];

		$model = \TMS_APP::model();
		$rst = $model->update(
			'xxt_merchant_order',
			array('trans_id' => $trans_id),
			"trade_no='$trade_no'"
		);
		if ($rst != 1) {
			$msg = "更新订单信息失败";
			return false;
		}

		\TMS_APP::M('log')->log('debug', 'pay-notify', 'ok');

		return true;
	}
	//
	public function getMpid($data) {
		if (!empty($this->mpid)) {
			return $this->mpid;
		}

		$tradeNo = $data['out_trade_no'];
		$order = \TMS_APP::M('app\merchant\order')->byTradeNo($tradeNo);
		if ($order === false) {
			throw new \WxPayException('订单不存在');
		}

		$mpid = $order->mpid;

		return $mpid;
	}
}
/**
 * 支付
 */
class pay extends \TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 支付通知
	 */
	public function notify_action() {
		$notify = new PayNotifyCallBack();
		$notify->Handle(false);
	}
}