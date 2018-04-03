<?php
namespace sns\wxa;

require_once dirname(dirname(__FILE__)) . '/proxybase.php';
/**
 * 微信小程序代理类
 */
class proxy_model extends \sns\proxybase {
	/**
	 *
	 */
	private $accessToken;
	/**
	 *
	 * $siteid
	 */
	public function __construct($config) {
		parent::__construct($config);
	}
	/**
	 *
	 */
	public function reset($config) {
		parent::reset($cnfig);
		unset($this->accessToken);
	}
	/**
	 * 获得与小程序进行交互的token
	 */
	public function accessToken($newAccessToken = false) {
		if ($newAccessToken === false) {
			if (isset($this->accessToken) && time() < $this->accessToken['expire_at'] - 60) {
				/**
				 * 在同一次请求中可以重用
				 */
				return [true, $this->accessToken['value']];
			}
			/**
			 * 从数据库中获取之前保留的token
			 */
			if (!empty($this->config->access_token) && time() < (int) $this->config->access_token_expire_at - 60) {
				/**
				 * 数据库中保存的token可用
				 */
				$this->accessToken = array(
					'value' => $this->config->access_token,
					'expire_at' => $this->config->access_token_expire_at,
				);
				return [true, $this->config->access_token];
			}
		}
		/**
		 * 重新获取token
		 */
		if (empty($this->config->appid) || empty($this->config->appsecret)) {
			return array(false, '微信公众号参数为空');
		}

		$url_token = "https://api.weixin.qq.com/cgi-bin/token";
		$url_token .= "?grant_type=client_credential";
		$url_token .= "&appid={$this->config->appid}&secret={$this->config->appsecret}";
		$ch = curl_init($url_token);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		if (false === ($response = curl_exec($ch))) {
			$err = curl_error($ch);
			curl_close($ch);
			return array(false, $err);
		}
		if (empty($response)) {
			$info = curl_getinfo($ch);
			curl_close($ch);
			\TMS_APP::model('log')->log('error', 'accessToken: response is empty.', json_encode($info));
			return array(false, 'response for getting accessToken is empty');
		} else {
			curl_close($ch);
		}
		$token = json_decode($response);
		if (!is_object($token)) {
			return array(false, $response);
		}
		if (isset($token->errcode)) {
			\TMS_APP::model('log')->log('error', 'accessToken: response is error.', json_encode($token));
			return array(false, $token->errmsg);
		}
		/**
		 * 保存获得的token
		 */
		$u = [];
		$u["access_token"] = $token->access_token;
		$u["access_token_expire_at"] = (int) $token->expires_in + time();

		\TMS_APP::model()->update('xxt_site_wxa', $u, "siteid='{$this->config->siteid}'");

		$this->accessToken = array(
			'value' => $u["access_token"],
			'expire_at' => $u["access_token_expire_at"],
		);

		return array(true, $token->access_token);
	}
	/**
	 * 创建一个小程序码
	 */
	public function wxacodeCreate($page, $sceneId = null, $bLimited = false) {
		if ($bLimited) {
			$cmd = 'https://api.weixin.qq.com/wxa/getwxacode';
			$posted = [
				"path" => $page,
			];
		} else {
			$cmd = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit';
			$posted = [
				"scene" => $sceneId,
				"page" => $page,
			];
		}

		$posted = json_encode($posted);
		$rst = $this->httpPost($cmd, $posted, false, true);
		if (false === $rst[0]) {
			return $rst;
		}

		return [true, '123'];

		$rawResponse = $rst[1];
		if (strpos($rawResponse, '{') === 0) {
			$jsonResponse = json_decode($rawResponse);
			if ($jsonResponse) {
				if (isset($jsonResponse->errcode) && $jsonResponse->errcode !== 0 && !empty($jsonResponse->errmsg)) {
					return [false, $jsonResponse->errmsg];
				}
			}
		}

		return [true, $rawResponse];
	}
}