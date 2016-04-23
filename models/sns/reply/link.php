<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 链接回复
 *
 * 1、通常情况下链接作为一个卡片进行回复
 * 2、如果要求将链接的执行结果进行回复，就要先执行链接，将获得的结果作为文本回复
 *
 */
class link_model extends Reply {

	private $link_id;

	public function __construct($call, $link_id) {
		parent::__construct($call);
		$this->link_id = $link_id;
	}
	/**
	 *
	 */
	public function exec() {
		$modelLink = \TMS_APP::model('matter\link');
		$call = $this->call;
		$link = $modelLink->byIdWithParams($this->link_id);
		$link->type = 'link';
		$link->entryURL = $modelLink->getEntryUrl($call['siteid'], $this->link_id, $call['from_user'], $call);
		if ($link->return_data === 'Y') {
			/**
			 * 以文字的形式响应
			 */
			$rst = $this->output($link, $call['from_user']);
			$r = $this->textResponse($rst);
		} else {
			/**
			 * 以图文卡片的形式响应
			 */
			$r = $this->cardResponse($link);
		}
		die($r);
	}
	/**
	 * 获得执行链接的结果
	 */
	private function output($link, $openid) {
		$url = $link->url;
		if (preg_match('/^(http:|https:)/', $url) === 0) {
			$url = 'http://' . $url;
		}

		if (isset($link->params)) {
			$params = $this->_spliceParams($link->siteid, $link->params, $openid);
		}

		if ($link->method == 'GET' && isset($this->params)) {
			$url .= (strpos($url, '?') === false) ? '?' : '&';
			$url .= $params;
		}
		$ch = curl_init(); //初始化curl
		curl_setopt($ch, CURLOPT_URL, $url); //设置链接
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //设置是否返回信息
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_REFERER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1); //设置返回的信息是否包含http头
		if ($link->method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1); //设置为POST方式
			if (!empty($params)) {
				$header = array("Content-type: application/x-www-form-urlencoded");
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $posted);
			}
		}
		$response = curl_exec($ch);
		if (curl_errno($ch)) {
			$output = curl_error($ch);
		} else {
			/**
			 * 返回内容
			 */
			$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$output = substr($response, $headerSize);
		}
		curl_close($ch);

		return $output;
	}
	/**
	 * 拼接URL中的参数
	 */
	private function _spliceParams($siteId, &$params, $openid = '') {
		$pairs = array();
		foreach ($params as $p) {
			switch ($p->pvalue) {
			case '{{site}}':
				$v = $siteId;
				break;
			case '{{openid}}':
				$v = $openid;
				break;
			default:
				$v = $p->pvalue;
			}
			$pairs[] = "$p->pname=$v";
		}
		$spliced = implode('&', $pairs);

		return $spliced;
	}
}