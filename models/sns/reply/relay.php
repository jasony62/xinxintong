<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 直接转发收到消息，并反馈
 */
class relay_model {

	public function __construct($call, $relayId) {
		$this->relayId = $relayId;
	}

	public function exec() {
		$relay = \TMS_APP::model('mp\relay')->byId($this->relayId);
		/**
		 * 公众平台发过来的原始数据
		 */
		$data = file_get_contents("php://input");
		$headerArr[] = 'Content-Type: text/xml; charset=utf-8';
		/**
		 * 转发数据
		 */
		$ch = curl_init($relay->url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		if (false === ($rsp = curl_exec($ch))) {
			$err = curl_error($ch);
			curl_close($ch);
			return array(false, $err);
		}
		curl_close($ch);

		die($rsp);
	}
}