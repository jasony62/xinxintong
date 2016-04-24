<?php
namespace site\fe\matter\link;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 链接
 */
class main extends \site\fe\matter\base {
	/**
	 *
	 */
	public function index_action($site, $id) {
		$link = $this->model('matter\link')->byIdWithParams($id);
		if ($link->fans_only === 'Y') {
			if (!$this->afterSnsOAuth()) {
				/* 检查是否需要第三方社交帐号OAuth */
				$this->_requireSnsOAuth($site);
			}
		}
		switch ($link->urlsrc) {
		case 0:
			$url = $link->url;
			if (preg_match('/^(http:|https:)/', $url) === 0) {
				$url = 'http://' . $url;
			}
			if ($link->method == 'GET') {
				if (isset($link->params)) {
					$url .= (strpos($url, '?') === false) ? '?' : '&';
					$url .= $this->_spliceParams($this->siteId, $link->params);
				}
				header("Location: $url");
			} elseif ($link->method == 'POST') {
				if (isset($link->params)) {
					$posted = $this->_spliceParams($this->siteId, $link->params);
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
			break;
		case 1:
			//require_once dirname(__FILE__) . '/page_news.php';
			//$page = new page_news((int) $link->url, $openid);
			break;
		case 2:
			$channelUrl = $this->model('matter\channel')->getEntryUrl($site, (int) $link->url);
			$this->redirect($channelUrl);
			break;
		}
	}
	/**
	 * 检查是否需要第三方社交帐号认证
	 * 检查条件：
	 * 0、应用是否设置了需要认证
	 * 1、站点是否绑定了第三方社交帐号认证
	 * 2、平台是否绑定了第三方社交帐号认证
	 * 3、用户客户端是否可以发起认证
	 *
	 * @param string $site
	 */
	private function _requireSnsOAuth($siteid) {
		if ($this->userAgent() === 'wx') {
			if (!isset($this->who->sns->wx)) {
				if ($wxConfig = $this->model('sns\wx')->bySite($siteid)) {
					if ($wxConfig->joined === 'Y') {
						$this->snsOAuth($wxConfig, 'wx');
					}
				}
			}
			if (!isset($this->who->sns->qy)) {
				if ($qyConfig = $this->model('sns\qy')->bySite($siteid)) {
					if ($qyConfig->joined === 'Y') {
						$this->snsOAuth($qyConfig, 'qy');
					}
				}
			}
		} else if ($this->userAgent() === 'yx') {
			if (!isset($this->who->sns->yx)) {
				if ($yxConfig = $this->model('sns\yx')->bySite($siteid)) {
					if ($yxConfig->joined === 'Y') {
						$this->snsOAuth($yxConfig, 'yx');
					}
				}
			}
		}

		return false;
	}
	/**
	 *
	 */
	private function _getSnsOpenid() {
		if ($this->userAgent() === 'wx') {
			if (isset($this->who->sns->wx)) {
				return $this->who->sns->wx->openid;
			}
			if (isset($this->who->sns->qy)) {
				return $this->who->sns->qy->openid;
			}
		} else if ($this->userAgent() === 'yx') {
			if (isset($this->who->sns->yx)) {
				return $this->who->sns->yx->openid;
			}
		}
		return '';
	}
	/**
	 * 拼接URL中的参数
	 */
	private function _spliceParams($siteId, &$params) {
		$pairs = array();
		foreach ($params as $p) {
			switch ($p->pvalue) {
			case '{{site}}':
				$v = $siteId;
				break;
			case '{{openid}}':
				$v = $this->_getSnsOpenid();
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