<?php
namespace sns;
/**
 * 公众号代理类的基类
 */
class proxybase {
	/**
	 * 社交平台配置信息
	 */
	protected $config;

	public function __construct($config) {
		$this->config = $config;
	}
	/**
	 *
	 */
	public function reset($config) {
		$this->config = $config;
	}
	/**
	 * 从易信公众号获取信息
	 *
	 * 需要提供token的请求
	 */
	protected function httpGet($cmd, $params = null, $newAccessToken = false, $appendAccessToken = true) {
		$url = $cmd;
		if ($appendAccessToken) {
			$token = $this->accessToken($newAccessToken);
			if ($token[0] === false) {
				return $token;
			}
			$url .= false == strpos($url, '?') ? '?' : '&';
			$url .= "access_token={$token[1]}";
		}
		if (!empty($params)) {
			false == strpos($url, '?') && $url .= '?';
			$url .= '&' . http_build_query($params);
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		if (false === ($response = curl_exec($ch))) {
			$err = curl_error($ch);
			curl_close($ch);
			return array(false, $err);
		}
		curl_close($ch);

		$response = preg_replace("/\\\\\w|\x{000f}/", '', $response);
		$result = json_decode($response);
		if (isset($result->errcode)) {
			if ($result->errcode == 40014) {
				return $this->httpGet($cmd, $params, true);
			}
			if ($result->errcode !== 0) {
				return array(false, $result->errmsg . "($result->errcode)");
			}
		} else if (empty($result)) {
			if (strpos($response, '{') === 0) {
				return array(false, 'json failed:' . $response);
			} else {
				return array(false, $response);
			}
		}

		return array(true, $result);
	}
	/**
	 * 提交信息到公众号平台
	 */
	protected function httpPost($cmd, $posted, $newAccessToken = false) {
		$token = $this->accessToken($newAccessToken);
		if ($token[0] === false) {
			return $token;
		}

		$url = $cmd;
		$url .= false == strpos($url, '?') ? '?' : '&';
		$url .= "access_token=" . $token[1];

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $posted);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		if (false === ($response = curl_exec($ch))) {
			$err = curl_error($ch);
			curl_close($ch);
			return array(false, $err);
		}
		curl_close($ch);

		$response = preg_replace("/\\\\\w|\x{000f}/", '', $response);
		$rst = json_decode($response);
		if (isset($rst->errcode)) {
			if ($rst->errcode == 40014) {
				return $this->httpPost($cmd, $posted, true);
			}
			if ($rst->errcode !== 0) {
				return array(false, $rst->errmsg . "($rst->errcode)");
			}
		} else if (empty($rst)) {
			if (strpos($response, '{') === 0) {
				return array(false, 'json failed:' . $response);
			} else {
				return array(false, $response);
			}
		}

		return array(true, $rst);
	}
	/**
	 * 将url的数据抓取到本地并保存在临时文件中返回
	 *
	 * $url
	 */
	public function fetchUrl($url) {
		/**
		 * 下载文件
		 */
		$ext = 'jpg';
		$urlContent = file_get_contents($url);
		$responseInfo = $http_response_header;
		foreach ($responseInfo as $loop) {
			if (strpos($loop, "Content-disposition") !== false) {
				$disposition = trim(substr($loop, 21));
				$filename = explode(';', $disposition);
				$filename = array_pop($filename);
				$filename = explode('=', $filename);
				$filename = array_pop($filename);
				$filename = str_replace('"', '', $filename);
				$filename = explode('.', $filename);
				$ext = array_pop($filename);
				break;
			}
		}
		/**
		 * 临时文件
		 */
		if (defined('SAE_TMP_PATH')) {
			$tmpfname2 = SAE_TMP_PATH . uniqid() . '.' . $ext;
			file_put_contents($tmpfname2, $urlContent);
		} else {
			$tmpfname = tempnam('', '');
			$tmpfname2 = $tmpfname . '.' . $ext;
			rename($tmpfname, $tmpfname2);
			$handle = fopen($tmpfname2, "w");
			fwrite($handle, $urlContent);
			fclose($handle);
		}

		return $tmpfname2;
	}
}