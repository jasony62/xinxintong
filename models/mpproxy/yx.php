<?php
require_once dirname(__FILE__) . '/base.php';
/**
 * 易信公众号号代理类
 */
class yx_model extends mpproxy_base {
	/**
	 *
	 */
	private $yx_token;
	/**
	 *
	 * $mpid
	 */
	public function __construct($mpid) {
		parent::__construct($mpid);
	}
	/**
	 *
	 */
	public function reset($mpid) {
		parent::reset($mpid);
		unset($this->yx_token);
	}
	/**
	 * 加密/校验流程：
	 * 1. 将token、timestamp、nonce三个参数进行字典序排序
	 * 2. 将三个参数字符串拼接成一个字符串进行sha1加密
	 * 3. 开发者获得加密后的字符串可与signature对比，标识该请求来源于易信
	 *
	 * 若确认此次GET请求来自易信服务器，请原样返回echostr参数内容，则接入生效，否则接入失败。
	 */
	public function join($params, $setting = null) {
		$signature = $params['signature'];
		$timestamp = $params['timestamp'];
		$nonce = $params['nonce'];
		$echostr = $params['echostr'];

		if (empty($setting)) {
			$mpa = TMS_APP::G('mp\mpaccount');
			$p = array($mpa->token, $timestamp, $nonce);
			asort($p);
			$s = implode('', $p);
			$ss = sha1($s);
			if ($ss === $signature) {
				/**
				 * 断开连接
				 */
				TMS_APP::model()->update(
					'xxt_mpaccount',
					array('yx_joined' => 'N'),
					"yx_appid='$mpa->yx_appid' and yx_appsecret='$mpa->yx_appsecret'");
				/**
				 * 确认建立连接
				 */
				TMS_APP::model()->update(
					'xxt_mpaccount',
					array('yx_joined' => 'Y'),
					"mpid='$this->mpid'");

				return array(true, $echostr);
			} else {
				return array(false, 'failed');
			}
		} else {
			$p = array($setting->token, $timestamp, $nonce);
			asort($p);
			$s = implode('', $p);
			$ss = sha1($s);
			if ($ss === $signature) {
				/**
				 * 断开连接
				 */
				TMS_APP::model()->update(
					'xxt_site_yx',
					array('joined' => 'N'),
					"appid='$setting->appid' and appsecret='$setting->appsecret'"
				);
				/**
				 * 确认建立连接
				 */
				TMS_APP::model()->update(
					'xxt_site_yx',
					array('joined' => 'Y'),
					"siteid='$setting->siteid'"
				);

				return array(true, $echostr);
			} else {
				return array(false, 'failed');
			}
		}
	}
	/**
	 * 获得与公众平台进行交互的token
	 */
	public function accessToken($newAccessToken = false) {
		$whichToken = "yx_appid,yx_appsecret,yx_token,yx_token_expire_at";
		if ($newAccessToken === false) {
			if (isset($this->yx_token) && time() < $this->yx_token['expire_at'] - 60) {
				/**
				 * 在同一次请求中可以重用
				 */
				return array(true, $this->yx_token['value']);
			}
			/**
			 * 从数据库中获取之前保留的token
			 */
			$app = TMS_APP::model('mp\mpaccount')->byId($this->mpid, $whichToken);
			if (!empty($app->yx_token) && time() < (int) $app->yx_token_expire_at - 60) {
				/**
				 * 数据库中保存的token可用
				 */
				$this->yx_token = array(
					'value' => $app->yx_token,
					'expire_at' => $app->yx_token_expire_at,
				);
				return array(true, $app->yx_token);
			}
		} else {
			/**
			 * 从数据库中获取之前保留的token
			 */
			$app = TMS_APP::model('mp\mpaccount')->byId($this->mpid, $whichToken);
		}
		/**
		 * 重新获取token
		 */
		$url_token = "https://api.yixin.im/cgi-bin/token";
		$url_token .= "?grant_type=client_credential";
		$url_token .= "&appid=$app->yx_appid&secret=$app->yx_appsecret";
		$ch = curl_init($url_token);
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
		$token = json_decode($response);
		if (isset($token->errcode)) {
			return array(false, $token->errmsg);
		}

		/**
		 * 保存获得的token
		 */
		$u["yx_token"] = $token->access_token;
		$u["yx_token_expire_at"] = (int) $token->expires_in + time();

		TMS_APP::model()->update('xxt_mpaccount', $u, "mpid='$this->mpid'");

		$this->yx_token = array(
			'value' => $u["yx_token"],
			'expire_at' => $u["yx_token_expire_at"],
		);

		return array(true, $token->access_token);
	}
	/**
	 *
	 */
	public function oauthUrl($mpid, $redirect, $state = null, $scope = 'snsapi_base') {
		if (is_object($mpid)) {
			$appid = $mpid->appid;
		} else {
			$mpa = TMS_APP::model('mp\mpaccount')->byId($mpid, 'yx_appid');
			$appid = $mpa->yx_appid;
		}

		$oauth = "http://open.plus.yixin.im/connect/oauth2/authorize";
		$oauth .= "?appid=" . $appid;
		$oauth .= "&redirect_uri=" . urlencode($redirect);
		$oauth .= "&response_type=code";
		$oauth .= "&scope=" . $scope;
		!empty($state) && $oauth .= "&state=$state";
		$oauth .= "#yixin_redirect";

		return $oauth;
	}
	/**
	 * 获得openid
	 */
	public function getOAuthUser($code, $sns = null) {
		if (empty($sns)) {
			$mpa = TMS_APP::M('mp\mpaccount')->byId($this->mpid, "yx_appid,yx_appsecret");
			$appid = $mpa->yx_appid;
			$appsecret = $mpa->yx_appsecret;
		} else {
			$appid = $sns->appid;
			$appsecret = $sns->appsecret;
		}

		$cmd = "https://api.yixin.im/sns/oauth2/access_token";
		$params["appid"] = $appid;
		$params["secret"] = $appsecret;
		$params["code"] = $code;
		$params["grant_type"] = "authorization_code";

		$rst = $this->httpGet($cmd, $params, false, false);

		if ($rst[0] === false) {
			return $rst;
		}

		$openid = $rst[1]->openid;

		return array(true, $openid);
	}
	/**
	 *
	 */
	public function mobile2Openid($mobile) {
		$cmd = 'https://api.yixin.im/cgi-bin/user/valid';

		$rst = $this->httpGet($cmd, array('mobile' => $mobile));

		return $rst;
	}
	/**
	 * 获得所有的易信粉丝
	 */
	public function userGet($nextOpenid = '') {
		$cmd = 'https://api.yixin.im/cgi-bin/user/get';

		if (empty($nextOpenid)) {
			$params = array('next_openid' => $nextOpenid);
			$result = $this->httpGet($cmd, $params);
		} else {
			$result = $this->httpGet($cmd);
		}

		return $result;
	}
	/**
	 * 获得一个指定粉丝的信息
	 */
	public function userInfo($openid, $getGroup = false) {
		$cmd = 'https://api.yixin.im/cgi-bin/user/info';

		$params = array('openid' => $openid);
		/*user info*/
		$userRst = $this->httpGet($cmd, $params);
		if ($userRst[0] === false && strpos($userRst[1], 'json failed:') === 0) {
			$fan = new \stdClass;
			$json = str_replace(array('json failed:', '{', '}'), '', $userRst[1]);
			$data = explode(',', $json);
			foreach ($data as $pv) {
				$pv = explode(':', $pv);
				$p = str_replace('"', '', $pv[0]);
				$v = str_replace('"', '', $pv[1]);
				$fan->{$p} = $v;
			}
			$userRst[0] = true;
			$userRst[1] = $fan;
		} else if (empty($userRst[1])) {
			return array(false, 'empty openid:' . $openid);
		}
		/*group info*/
		if ($getGroup && $userRst[0]) {
			/**
			 * 获得粉丝的分组信息
			 */
			$cmd = 'https://api.yixin.im/cgi-bin/groups/getid';
			$posted = json_encode(array("openid" => $openid));
			$groupRst = $this->httpPost($cmd, $posted);
			if ($groupRst[0]) {
				$userRst[1]->groupid = $groupRst[1]->groupid;
			}
		}

		return $userRst;
	}
	/**
	 * 获得所有的易信粉丝分组
	 */
	public function groupsGet() {
		$cmd = 'https://api.yixin.im/cgi-bin/groups/get';

		$rst = $this->httpGet($cmd);

		return $rst;
	}
	/**
	 * 添加粉丝分组
	 *
	 * 同时在公众平台和本地添加
	 */
	public function groupsCreate($group) {
		/**
		 * 在公众平台上添加
		 */
		$cmd = 'https://api.yixin.im/cgi-bin/groups/create';
		$posted = json_encode(array('group' => $group));
		$rst = $this->httpPost($cmd, $posted);

		return $rst;
	}
	/**
	 * 更新粉丝分组的名称
	 *
	 * 同时修改公众平台的数据和本地数据
	 */
	public function groupsUpdate($group) {
		$cmd = "https://api.yixin.im/cgi-bin/groups/update";
		$posted = json_encode(array('group' => $group));
		$rst = $this->httpPost($cmd, $posted);

		return $rst;
	}
	/**
	 * 设置关注用户的分组
	 */
	public function groupsMembersUpdate($openid, $groupid) {
		$cmd = "https://api.yixin.im/cgi-bin/groups/members/update";
		$posted = json_encode(array("openid" => $openid, "to_groupid" => $groupid));
		$rst = $this->httpPost($cmd, $posted);

		return $rst;
	}
	/**
	 * 删除粉丝分组
	 *
	 * todo 标准接口中不支持
	 *
	 * 同时删除公众平台上的数据和本地数据
	 */
	public function groupsDelete($group) {
		$cmd = "https://api.yixin.im/cgi-bin/groups/delete";
		$posted = json_encode(array('group' => $group));
		$rst = $this->httpPost($cmd, $posted);

		return $rst;
	}
	/**
	 * upload menu.
	 */
	public function menuCreate($menu) {
		$cmd = 'https://api.yixin.im/cgi-bin/menu/create';

		$rst = $this->httpPost($cmd, $menu);

		return $rst;
	}
	/**
	 * upload menu.
	 */
	public function menuDelete() {
		$cmd = 'https://api.yixin.im/cgi-bin/menu/delete';

		$rst = $this->httpGet($cmd);

		return $rst;
	}
	/**
	 * 将图片上传到公众号平台
	 *
	 * $imageUrl
	 * $imageType
	 */
	public function mediaUpload($mediaUrl, $mediaType = 'image') {
		$tmpfname = $this->fetchUrl($mediaUrl);
		$uploaded['media'] = "@$tmpfname";
		/**
		 * upload image
		 */
		$cmd = 'https://api.yixin.im/cgi-bin/media/upload';
		$cmd .= "?type=$mediaType";

		$rst = $this->httpPost($cmd, $uploaded);
		if ($rst[0] === false) {
			return $rst;
		}

		$media_id = $rst[1]->media_id;

		return array(true, $media_id);
	}
	/**
	 * 向易信用户群发消息
	 */
	public function messageGroupSend($message) {
		$cmd = 'https://api.yixin.im/cgi-bin/message/group/send';

		$rst = $this->httpPost($cmd, $message);

		return $rst;
	}
	/**
	 * 发送客服消息
	 *
	 * $message
	 * $openid
	 */
	public function messageCustomSend($message, $openid, $urlencode = true) {
		$message['touser'] = $openid;
		$cmd = 'https://api.yixin.im/cgi-bin/message/custom/send';

		$posted = $urlencode ? \TMS_MODEL::toJson($message) : json_encode($message);

		$rst = $this->httpPost($cmd, $posted);

		return $rst;
	}
	/**
	 * 通过易信点对点接口向用户发送消息
	 *
	 * $mpid
	 * $message
	 * $openids
	 */
	public function messageSend($message, $openids) {
		is_string($openids) && $openids = implde(',', $openids);
		/**
		 * 发送消息
		 */
		$cmd = 'https://api.yixin.im/cgi-bin/message/send';

		foreach ($openids as $openid) {
			$message['touser'] = $openid;
			$posted = \TMS_MODEL::toJson($message);
			$rst = $this->httpPost($cmd, $posted);
			$rst[0] === false && $warning[] = array($openid => $rst[1]);
		}

		if (isset($warning)) {
			return array(false, $warning);
		}

		return array(true);
	}
	/**
	 * 创建一个二维码响应
	 *
	 * 易信的永久二维码最大值1000
	 */
	public function qrcodeCreate($scene_id, $oneOff = true, $expire = 1800) {
		/**
		 * 获去二维码的ticket
		 */
		$cmd = 'https://api.yixin.im/cgi-bin/qrcode/create';

		if ($oneOff) {
			$posted = array(
				"action_name" => "QR_SCENE",
				"action_info" => array(
					"expire_seconds" => $expire,
					"scene" => array("scene_id" => $scene_id),
				),
			);
		} else {
			$posted = array(
				"action_name" => "QR_LIMIT_SCENE",
				"action_info" => array(
					"scene" => array("scene_id" => $scene_id),
				),
			);
		}

		$posted = json_encode($posted);
		$rst = $this->httpPost($cmd, $posted);
		if (false === $rst[0]) {
			return $rst;
		}

		$ticket = $rst[1]->ticket;
		$pic = "https://api.yixin.im/cgi-bin/qrcode/showqrcode?ticket=$ticket";

		$d = array(
			'scene_id' => $scene_id,
			'pic' => $pic,
		);
		$oneOff && $d['expire_seconds'] = $expire;

		return array(true, (object) $d);
	}
	/**
	 * 向易信用户群发消息
	 */
	public function send2group($message) {
		$message = \TMS_MODEL::toJson($message);
		$rst = $this->messageGroupSend($message);

		return $rst;
	}
}