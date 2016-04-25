<?php
namespace site\op\matter\merchant;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
require_once TMS_APP_DIR . "/lib/wxpay/WxPay.Api.php";
require_once TMS_APP_DIR . '/lib/wxpay/WxPay.Notify.php';
/**
 *
 */
class PayNotifyCallBack extends \WxPayNotify {
	//控制器
	private $ctrl;
	//公众平台ID
	private $siteId = false;
	//
	public function __construct($ctrl) {
		$this->ctrl = $ctrl;
	}
	//查询订单
	public function Queryorder($siteId, $transaction_id) {
		//\TMS_APP::M('log')->log('debug', 'pay-Queryorder', '0');
		$input = new \WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = \WxPayApi::orderQuery($siteId, $input);
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
		$siteId = $this->getSiteId($data);
		if (!$this->Queryorder($siteId, $data["transaction_id"])) {
			$msg = "订单查询失败";
			\TMS_APP::M('log')->log('debug', 'pay-notify', $msg);
			return false;
		}
		/*更新订单支付信息*/
		$trans_id = $data['transaction_id'];
		$trade_no = $data['out_trade_no'];

		$model = \TMS_APP::model();
		$rst = $model->update(
			'xxt_merchant_order',
			array(
				'trans_id' => $trans_id,
				'order_status' => '2', // 已支付
			),
			"trade_no='$trade_no'"
		);
		if ($rst != 1) {
			$msg = "更新订单信息失败";
			return false;
		}
		//\TMS_APP::M('log')->log('debug', 'pay-notify', 'ok');
		//通知客服
		$siteId = $this->getSiteId($data);
		$order = \TMS_APP::model('matter\merchant\order')->byTradeNo($trade_no);
		$this->ctrl->notify($siteId, $order);

		return true;
	}
	//
	public function getSiteId($data) {
		if (!empty($this->siteId)) {
			return $this->siteId;
		}

		$tradeNo = $data['out_trade_no'];
		$order = \TMS_APP::M('matter\merchant\order')->byTradeNo($tradeNo);
		if ($order === false) {
			throw new \WxPayException('订单不存在');
		}

		$siteId = $order->siteid;

		return $siteId;
	}
}
/**
 * 用户支付完成
 */
class payok extends \xxt_base {
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
	 *
	 */
	public function test_action($siteId, $order) {
		$order = \TMS_APP::model('matter\merchant\order')->byId($order);
		$this->notify($siteId, $order);

		return new \ResponseData('ok');
	}
	/**
	 * 通知客服有新订单
	 */
	public function notify($siteId, $order) {
		/* 客服员工 */
		$staffs = $this->model('matter\merchant\shop')->staffAcls($siteId, $order->sid, 'c');
		if (empty($staffs)) {
			return false;
		}
		/**/
		$modelProd = $this->model('matter\merchant\product');
		$modelTmpl = $this->model('matter\tmplmsg');
		$products = json_decode($order->products);
		foreach ($products as $product) {
			$product = $modelProd->byId($product->id, array('cascaded' => 'Y'));
			$mapping = $modelTmpl->mappingById($product->catelog->pay_order_tmplmsg);
			if (false === $mapping) {
				return false;
			}
			/**/
			$tmplmsg = $modelTmpl->byId($mapping->msgid, array('cascaded' => 'Y'));
			if (empty($tmplmsg->params)) {
				return false;
			}
			/*构造消息数据*/
			$data = array();
			foreach ($mapping->mapping as $k => $p) {
				$v = '';
				switch ($p->src) {
				case 'product':
					if ($p->id === '__productName') {
						$v = $product->name;
					} else {
						$v = $product->propValue->{$p->id}->name;
					}
					break;
				case 'order':
					if ($p->id === '__orderSn') {
						$v = $order->trade_no;
					} else if ($p->id === '__orderState') {
						$v = '已付款';
					} else {
						$v = $order->extPropValue->{$product->catelog->id}->{$p->id};
					}
					break;
				case 'text':
					$v = $p->id;
					break;
				}
				$data[$k] = $v;
			}
			/**
			 * 如果设置了客户人员，向客服人员发消息
			 */
			$url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/op/merchant/order";
			$url .= "?mpid=" . $siteId;
			$url .= "&shop=" . $order->sid;
			$url .= "&order=" . $order->id;

			$modelFan = $this->model('user/fans');
			foreach ($staffs as $staff) {
				switch ($staff->idsrc) {
				case 'M':
					$fan = $modelFan->byMid($staff->identity);
					$this->tmplmsgSendByOpenid($siteId, $tmplmsg->id, $fan->openid, $data, $url);
					break;
				}
			}
		}

		return true;
	}
}