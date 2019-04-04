<?php
namespace sns\dev189;

require_once dirname(dirname(__FILE__)) . '/proxybase.php';
require_once dirname(__FILE__) . '/config.php';
/**
 * 微信公众号代理类
 */
class proxy_model extends \sns\proxybase {
	//
	private $appid;
	//
	private $appsecret;
	//
	private $accessToken;
	//
	private $getAccessTokenUrl = DEV_GET_ACCESSTOKEN_URL;
	//
	private $authloginUrl = DEV_AUTH_LOGIN_URL;
	//
	private $authUserinfoUrl = DEV_AUTH_USERINFO_URL;
	/**
	 *
	 * $siteid
	 */
	public function __construct($config) {
		parent::__construct($config);
		$this->appid = $config->appid;
		$this->appsecret = $config->appsecret;
	}
	/**
	 *
	 */
	public function reset($config) {
		parent::reset($cnfig);
		unset($this->accessToken);
	}
	/**
	 * 获得与公众平台进行交互的token
	 */
	public function accessToken($newAccessToken = false) {
		if ($newAccessToken === false) {
			if (!empty($this->accessToken) && time() < $this->accessToken['expire_at'] - 60) {
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
				$this->accessToken = [
					'value' => $this->config->access_token,
					'expire_at' => $this->config->access_token_expire_at,
				];
				return [true, $this->config->access_token];
			}
		}
		/**
		 * 重新获取token
		 */
		if (empty($this->appid) || empty($this->appsecret)) {
			return [false, '能力开放平台参数为空'];
		}

		// 鉴权
		$authorization = base64_encode($this->appid . ':' . $this->appsecret);
		$header = [];
		$header[] = "Content-type: application/x-www-form-urlencoded; charset=UTF-8";
		$header[] = "Authorization: Basic " . $authorization;
		$response = $this->_curlPost($this->getAccessTokenUrl, $header);
		if ($response[0] === false) {
			return $response;
		}
		$token = $response[1];
		/**
		 * 保存获得的token
		 */
		$u = [];
		$u["access_token"] = $token->access_token;
		$u["access_token_expire_at"] = (int) $token->expires_in + time();

		\TMS_APP::model()->update('xxt_account_third', $u, "id='{$this->config->id}'");

		$this->accessToken = [
			'value' => $u["access_token"],
			'expire_at' => $u["access_token_expire_at"],
		];

		return [true, $token->access_token];
	}
	/**
	 *
	 */
	public function oauthUrl($redirect, $state = null) {
		$oauth = $this->authloginUrl;
		$oauth .= "?access_token=" . $this->accessToken()[1];
		$oauth .= "&redirect_url=" . $redirect;
		!empty($state) && $oauth .= "&state=$state";

		return $oauth;
	}
	/**
	 * 获得第三方应用用户信息
	 */
	public function userInfoByCode($code) {
		/* 获得用户的openid */
		$cmd = $this->authUserinfoUrl;
		$params["code"] = $code;
		$params["access_token"] = $this->accessToken()[1];
		$rst = $this->httpGet($cmd, $params, false, false);
		if ($rst[0] === false) {
			return $rst;
		}
		$user = $rst[1];
		if ($user->returnCode != '0') {
			return [false, json_encode($user)];	
		}

		if (isset($user->custName)) {
			$user->nickname = \TMS_APP::model()->cleanEmoji($user->custName, true);
		}

		return [true, $user];
	}
	/**
	 *
	*/
	private function _curlPost($url, $header = [], $posted = '') {
		$ch = curl_init(); //初始化curl
		curl_setopt($ch, CURLOPT_URL, $url); //设置链接
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //设置是否返回信息
		curl_setopt($ch, CURLOPT_REFERER, 1);
		curl_setopt($ch, CURLOPT_POST, 1); //设置为POST方式
		if (!empty($header)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}
		if (!empty($posted)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $posted);
		}

		if (false === ($response = curl_exec($ch))) {
			$err = curl_error($ch);
			curl_close($ch);
			return [false, $err];
		}
		if (empty($response)) {
			$info = curl_getinfo($ch);
			curl_close($ch);
			$this->model('log')->log('error', 'dev_accessToken: response is empty.', json_encode($info));
			return [false, 'response for getting accessToken is empty'];
		} else {
			curl_close($ch);
		}
		$data = json_decode($response);
		if (isset($data->returnCode) && $data->returnCode != 0) {
			return [false, $response];
		}

		return [true, $data];
	}
}