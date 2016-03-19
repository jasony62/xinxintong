<?php
require_once dirname(dirname(dirname(__FILE__))) . '/lib/wxqy/WXBizMsgCrypt.php';
require_once dirname(__FILE__) . '/base.php';
/**
 * 企业号代理类
 */
class qy_model extends mpproxy_base {
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
		unset($this->qy_token);
	}
	/**
	 * 对接企业号
	 */
	public function join($params) {
		$msg_signature = $params['msg_signature'];
		$timestamp = $params['timestamp'];
		$nonce = $params['nonce'];
		$echostr = $params['echostr'];

		$logger = TMS_APP::M('log');
		$mpa = TMS_APP::G('mp\mpaccount');

		$sEchoStr = '';
		$wxcpt = new WXBizMsgCrypt($mpa->token, $mpa->qy_encodingaeskey, $mpa->qy_corpid);
		$errCode = $wxcpt->VerifyURL($msg_signature, $timestamp, $nonce, $echostr, $sEchoStr, $logger);

		if ($errCode == 0) {
			/**
			 * 如果存在，断开公众号原有连接
			 */
			TMS_APP::model()->update(
				'xxt_mpaccount',
				array('qy_joined' => 'N'),
				"qy_corpid='$mpa->qy_corpid' and qy_secret='$mpa->qy_secret'");
			/**
			 * 确认建立连接
			 */
			TMS_APP::model()->update(
				'xxt_mpaccount',
				array('qy_joined' => 'Y'),
				"mpid='$this->mpid'");

			return array(true, $sEchoStr);
		} else {
			return array(false, $errCode);
		}
	}
	/**
	 *
	 */
	public function DecryptMsg($params, $data) {
		$mpa = TMS_APP::G('mp\mpaccount');

		$msg_signature = $params['msg_signature'];
		$timestamp = $params['timestamp'];
		$nonce = $params['nonce'];
		$sMsg = "";
		$wxcpt = new WXBizMsgCrypt($mpa->token, $mpa->qy_encodingaeskey, $mpa->qy_corpid);
		$errCode = $wxcpt->DecryptMsg($msg_signature, $timestamp, $nonce, $data, $sMsg);

		if ($errCode != 0) {
			return array(false, $errCode);
		}

		return array(true, $sMsg);
	}
	/**
	 * 获得与公众平台进行交互的token
	 */
	public function accessToken($newAccessToken = false) {
		/**
		 * 不重用之前保留的access_token
		 */
		$whichToken = "qy_corpid,qy_secret,qy_token,qy_token_expire_at";
		if ($newAccessToken === false) {
			if (isset($this->qy_token) && time() < $this->qy_token['expire_at'] - 60) {
				/**
				 * 在同一次请求中可以重用
				 */
				return array(true, $this->qy_token['value']);
			}
			/**
			 * 从数据库中获取之前保留的token
			 */
			$app = TMS_APP::model('mp\mpaccount')->byId($this->mpid, $whichToken);
			if (!empty($app->qy_token) && time() < (int) $app->qy_token_expire_at - 60) {
				/**
				 * 数据库中保存的token可用
				 */
				$this->qy_token = array(
					'value' => $app->qy_token,
					'expire_at' => $app->qy_token_expire_at,
				);
				return array(true, $app->qy_token);
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
		$url_token = "https://qyapi.weixin.qq.com/cgi-bin/gettoken";
		$url_token .= "?corpid=$app->qy_corpid&corpsecret=$app->qy_secret";
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
		$u["qy_token"] = $token->access_token;
		$u["qy_token_expire_at"] = 7200 + time();
		TMS_APP::model()->update('xxt_mpaccount', $u, "mpid='$this->mpid'");

		$this->qy_token = array(
			'value' => $u["qy_token"],
			'expire_at' => $u["qy_token_expire_at"],
		);

		return array(true, $token->access_token);
	}
	/**
	 * 获得微信JSSDK签名包
	 *
	 * $mpid
	 */
	public function getJssdkSignPackage($url) {
		$mpa = TMS_APP::M('mp\mpaccount')->byId($this->mpid, 'qy_corpid');

		$rst = $this->getJsApiTicket();
		if ($rst[0] === false) {
			return $rst;
		}

		$jsapiTicket = $rst[1];

		$timestamp = time();
		$nonceStr = $this->createNonceStr();
		// 这里参数的顺序要按照 key 值 ASCII 码升序排序
		$string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
		$signature = sha1($string);

		$signPackage = array(
			"appId" => $mpa->qy_corpid,
			"nonceStr" => $nonceStr,
			"timestamp" => $timestamp,
			"url" => $url,
			"signature" => $signature,
			"rawString" => $string,
		);

		$js = "signPackage={appId:'{$signPackage['appId']}'";
		$js .= ",nonceStr:'{$signPackage['nonceStr']}'";
		$js .= ",timestamp:'{$signPackage['timestamp']}'";
		$js .= ",url:'{$signPackage['url']}'";
		$js .= ",signature:'{$signPackage['signature']}'}";

		return array(true, $js);
	}
	/**
	 *
	 */
	private function createNonceStr($length = 16) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}

		return $str;
	}
	/**
	 *
	 */
	public function getJsapiTicket() {
		$mpa = TMS_APP::M('mp\mpaccount')->byId($this->mpid, 'wx_jsapi_ticket,wx_jsapi_ticket_expire_at');

		if (!empty($mpa->wx_jsapi_ticket) && time() < $mpa->wx_jsapi_ticket_expire_at - 60) {
			return array(true, $mpa->wx_jsapi_ticket);
		}

		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket";
		$rst = $this->httpGet($cmd);
		if ($rst[0] === false) {
			return $rst[1];
		}

		$ticket = $rst[1];

		TMS_APP::model()->update(
			'xxt_mpaccount',
			array(
				'wx_jsapi_ticket' => $ticket->ticket,
				'wx_jsapi_ticket_expire_at' => time() + $ticket->expires_in,
			),
			"mpid='$this->mpid'"
		);

		return array(true, $ticket->ticket);
	}
	/**
	 *
	 */
	public function oauthUrl($mpid, $redirect, $state = null, $scope = 'snsapi_base') {
		if (is_object($mpid)) {
			$appid = $mpid->appid;
		} else {
			$mpa = TMS_APP::model('mp\mpaccount')->byId($mpid, 'qy_corpid');
			$appid = $mpa->qy_corpid;
		}

		$oauth = "https://open.weixin.qq.com/connect/oauth2/authorize";
		$oauth .= "?appid=" . $appid;
		$oauth .= "&redirect_uri=" . urlencode($redirect);
		$oauth .= "&response_type=code";
		$oauth .= "&scope=" . $scope;
		!empty($state) && $oauth .= "&state=$state";
		$oauth .= "#wechat_redirect";

		return $oauth;
	}
	/**
	 * 换取userid
	 */
	public function getOAuthUser($code) {
		$mpa = TMS_APP::M('mp\mpaccount')->byId($this->mpid, "qy_agentid");

		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo";
		$params["code"] = $code;
		$params["agentid"] = $mpa->qy_agentid;

		$rst = $this->httpGet($cmd, $params);

		if ($rst[0] === false) {
			return $rst;
		}

		$openid = $rst[1]->UserId;

		return array(true, $openid);
	}
	/**
	 *
	 * $userId
	 * $data
	 *  name
	 *  mobile
	 *  eamil
	 *  position
	 *  department
	 *  extattr
	 */
	public function userCreate($userId, $data) {
		is_array($data) && $data = (object) $data;

		$posted = array(
			'userid' => $userId,
		);

		!empty($data->name) && $posted['name'] = urlencode($data->name);
		!empty($data->mobile) && $posted['mobile'] = $data->mobile;
		!empty($data->email) && $posted['email'] = $data->email;
		!empty($data->position) && $posted['position'] = urlencode($data->position);
		!empty($data->department) && $posted['department'] = $data->department;
		!empty($data->extattr) && $posted['extattr'] = $data->extattr;

		$posted = urldecode(json_encode($posted));

		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/create";
		$rst = $this->httpPost($cmd, $posted);

		return $rst;
	}
	/**
	 *
	 * $userId
	 *
	 * $data
	 *  name
	 *  mobile
	 *  eamil
	 *  position
	 *  department
	 *  extattr
	 *
	 */
	public function userUpdate($userId, $data) {
		is_array($data) && $data = (object) $data;

		$posted = array(
			'userid' => $userId,
		);

		!empty($data->name) && $posted['name'] = urlencode($data->name);
		!empty($data->mobile) && $posted['mobile'] = $data->mobile;
		!empty($data->email) && $posted['email'] = $data->email;
		!empty($data->position) && $posted['position'] = urlencode($data->position);
		!empty($data->department) && $posted['department'] = $data->department;
		!empty($data->extattr) && $posted['extattr'] = $data->extattr;

		$posted = urldecode(json_encode($posted));

		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/update";
		$rst = $this->httpPost($cmd, $posted);

		return $rst;
	}
	/**
	 *
	 * $userId
	 *
	 */
	public function userDelete($userId) {
		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/delete";
		$rst = $this->httpGet($cmd, array('userid' => $userId));

		return $rst;
	}
	/**
	 *
	 * $userId
	 */
	public function userGet($userId) {
		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/get";
		$rst = $this->httpGet($cmd, array('userid' => $userId));

		return $rst;
	}
	/**
	 *
	 * $deptId
	 * $fetchChild
	 * $status
	 */
	public function userSimpleList($deptId, $fetchChild = 1, $status = 0) {
		$params = array(
			'department_id' => $deptId,
			'fetch_child' => $fetchChild,
			'status' => $status,
		);
		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/simplelist";
		$rst = $this->httpGet($cmd, $params);

		return $rst;
	}
	/**
	 * 获得用户列表
	 *
	 * $departmentId 获取的部门id
	 * $fetchChild 1/0：是否递归获取子部门下面的成员
	 * $status 0获取全部员工，1获取已关注成员列表，2获取禁用成员列表，4获取未关注成员列表。status可叠加
	 */
	public function userList($departmentId, $fetchChild = 0, $status = 0) {
		$params = array(
			'department_id' => $departmentId,
			'fetch_child' => $fetchChild,
			'status' => $status,
		);
		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/list";
		$result = $this->httpGet($cmd, $params);

		return $result;
	}
	/**
	 *
	 * $name
	 * $parentid
	 * $order
	 */
	public function departmentCreate($name, $parentid, $order, $id = null) {
		$newDept = array(
			'name' => urlencode($name),
			'parentid' => $parentid,
			'order' => $order,
		);
		!empty($id) && $newDept['id'] = $id;

		$posted = urldecode(json_encode($newDept));
		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/department/create";
		$result = $this->httpPost($cmd, $posted);

		return $result;
	}
	/**
	 * $id
	 */
	public function departmentDelete($id) {
		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/department/delete";
		$result = $this->httpGet($cmd, array('id' => $id));

		return $result;
	}
	/**
	 *
	 */
	public function departmentUpdate($id, $name = null, $pid = null) {
		$updated = array('id' => $id);
		!empty($name) && $updated['name'] = urlencode($name);
		!empty($pid) && $updated['parentid'] = $pid;
		$posted = urldecode(json_encode($updated));

		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/department/update";
		$result = $this->httpPost($cmd, $posted);

		return $result;
	}
	/**
	 * 获得部门列表
	 */
	public function departmentList($pdid = null) {
		$params = array();
		$pdid && $params['id'] = $pdid;

		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/department/list";
		$result = $this->httpGet($cmd, $params);

		return $result;
	}
	/**
	 * 获得标签列表
	 */
	public function tagList() {
		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/list";
		$result = $this->httpGet($cmd);

		return $result;
	}
	/**
	 * $name
	 */
	public function tagCreate($name) {
		$posted = urldecode(json_encode(array(
			'tagname' => urlencode($name),
		)));

		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/create";
		$result = $this->httpPost($cmd, $posted);

		return $result;
	}
	/**
	 *
	 * $tagid
	 * $name
	 */
	public function tagUpdate($tagid, $name) {
		$posted = urldecode(json_encode(array(
			'tagid' => $tagid,
			'tagname' => urlencode($name),
		)));

		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/update";
		$result = $this->httpPost($cmd, $posted);

		return $result;
	}
	/**
	 *
	 * $tagid
	 */
	public function tagDelete($tagid) {
		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/delete";
		$result = $this->httpGet($cmd, array('tagid' => $tagid));

		return $result;
	}
	/**
	 * $tagid
	 * $userlist
	 */
	public function tagAddUser($tagid, $userlist) {
		$posted = json_encode(array(
			'tagid' => $tagid,
			'userlist' => $userlist,
		));

		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/addtagusers";
		$result = $this->httpPost($cmd, $posted);

		return $result;
	}
	/**
	 * $tagid
	 * $userlist
	 */
	public function tagDeleteUser($tagid, $userlist) {
		$posted = json_encode(array(
			'tagid' => $tagid,
			'userlist' => $userlist,
		));

		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/deltagusers";
		$result = $this->httpPost($cmd, $posted);

		return $result;
	}
	/**
	 * 获得标签下的用户列表
	 *
	 * $tagid
	 */
	public function tagUserList($tagid) {
		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/get";
		$result = $this->httpGet($cmd, array('tagid' => $tagid));

		return $result;
	}
	/**
	 * upload menu.
	 */
	public function menuCreate($menu) {
		$app = TMS_APP::M('mp\mpaccount')->byId($this->mpid, 'qy_agentid');
		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/menu/create?agentid=$app->qy_agentid";

		$rst = $this->httpPost($cmd, $menu);

		return $rst;
	}
	/**
	 * upload menu.
	 */
	public function menuDelete() {
		$app = TMS_APP::M('mp\mpaccount')->byId($this->mpid, 'qy_agentid');
		$cmd = "https://qyapi.weixin.qq.com/cgi-bin/menu/delete";

		$rst = $this->httpGet($cmd, array('agentid' => $app->qy_agentid));

		return $rst;
	}
	/**
	 * 向企业号用户发送消息
	 *
	 * $mpid
	 * $message
	 */
	public function messageSend($message, $encoded = false) {
		$mpa = TMS_APP::M('mp\mpaccount')->byId($this->mpid, 'qy_agentid');

		$cmd = 'https://qyapi.weixin.qq.com/cgi-bin/message/send';
		$message['agentid'] = $mpa->qy_agentid;

		$posted = TMS_MODEL::toJson($message);

		$rst = $this->httpPost($cmd, $posted);

		return $rst;
	}
	/**
	 * 获得下载媒体文件的链接
	 *
	 * $mediaid
	 */
	public function mediaGetUrl($mediaId) {
		$rst = $this->accessToken();
		if ($rst[0] === false) {
			return $rst[1];
		}

		$url = 'https://qyapi.weixin.qq.com/cgi-bin/media/get';
		$url .= "?access_token={$rst[1]}";
		$url .= "&media_id=$mediaId";

		return array(true, $url);
	}
}