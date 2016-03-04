<?php
namespace mi;
/**
 * 链接
 */
class page_external extends matter_page_base {
	/**
	 *
	 */
	public function __construct(&$link, $openid) {
		$this->link = $link;
		parent::__construct($this->link, $openid);
	}
	/**
	 *
	 */
	public function output($runningMpid, $mid = null) {
		$url = $this->link->url;
		if (preg_match('/^(http:|https:)/', $url) === 0) {
			$url = 'http://' . $url;
		}
		if ($this->link->method == 'GET') {
			if (isset($this->link->params)) {
				$url .= (strpos($url, '?') === false) ? '?' : '&';
				$url .= \TMS_APP::M('reply')->spliceParams($runningMpid, $this->link->params, $mid, $this->openid);
			}
			header("Location: $url");
		} elseif ($this->link->method == 'POST') {
			if (isset($this->link->params)) {
				$posted = \TMS_APP::M('reply')->spliceParams($runningMpid, $this->link->params, $mid, $this->openid);
			}
			$ch = curl_init(); //初始化curl
			curl_setopt($ch, CURLOPT_URL, $url); //设置链接
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //设置是否返回信息
			//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_REFERER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 1); //设置返回的信息是否包含http头
			curl_setopt($ch, CURLOPT_POST, 1); //设置为POST方式
			if (!empty($posted)) {
				$header = array("Content-type: application/x-www-form-urlencoded");
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $posted);
			}
			$response = curl_exec($ch);
			if (curl_errno($ch)) {
				echo curl_error($ch);
			} elseif (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '302') {
				/**
				 * 页面跳转
				 */
				$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
				$header = substr($response, 0, $headerSize);
				$matched = array();
				if (preg_match('/Location:(.*)\r\n/i', $header, $matched)) {
					$location = $matched[1];
					header("Location: $location");
				} else {
					echo 'Parse header error!';
				}
			} else {
				/**
				 * 返回内容
				 */
				$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
				$body = substr($response, $headerSize);
				echo $body;
			}
			curl_close($ch);
			exit;
		}
	}
}