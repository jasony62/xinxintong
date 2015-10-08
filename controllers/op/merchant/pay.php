<?php
namespace op\merchant;

require_once $_SERVER['DOCUMENT_ROOT'] . "/lib/wxpay/WxPay.Api.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/wxpay/WxPay.Notify.php';
/**
 *
 */
class PayNotifyCallBack extends \WxPayNotify {
	//控制器
	private $ctrl;
	//公众平台ID
	private $mpid = false;
	//
	public function __construct($ctrl) {
		$this->ctrl = $ctrl;
	}
	//查询订单
	public function Queryorder($mpid, $transaction_id) {
		//\TMS_APP::M('log')->log('debug', 'pay-Queryorder', '0');
		$input = new \WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = \WxPayApi::orderQuery($mpid, $input);
		if (array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS") {
			return true;
		}
		//\TMS_APP::M('log')->log('debug', 'pay-Queryorder', 'ok');
		return false;
	}

	//重写回调处理函数
	public function NotifyProcess($data, &$msg) {
		//\TMS_APP::M('log')->log('debug', 'pay-notify', '0');
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

		//\TMS_APP::M('log')->log('debug', 'pay-notify', 'ok');

		//通知客服
		$mpid = $this->getMpid($data);
		$order = \TMS_APP::model('app\merchant\order')->byTradeNo($trade_no);
		$this->ctrl->notify($mpid, $order);

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
		$notify = new PayNotifyCallBack($this);
		$notify->Handle(false);
	}
	/**
	 * 通知客服有新订单
	 */
	public function notify($mpid, $order) {
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

		$txt = urlencode("订单已支付，");
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