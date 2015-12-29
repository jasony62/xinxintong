<?php
namespace message;

require_once dirname(dirname(__FILE__)) . '/xxt_base.php';
/**
 * 向公众号用户发送消息
 */
class main extends \xxt_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 发送待办事项图文消息
	 *
	 * 易信开通了点对点消息
	 * 微信企业号
	 *
	 * $mpid 对应的公众号ID
	 * $authid 用户身份认证接口ID
	 * $userKey 用户的业务身份标识
	 * $text 发送的卡片消息正文
	 */
	public function text_action($mpid, $authid, $userKey, $text) {
		/**
		 * 获取用户的openid
		 */
		$q = array(
			'm.openid',
			'xxt_member m',
			"m.authapi_id=$authid and m.forbidden='N' and m.authed_identity='$userKey'",
		);
		if (!($openid = $this->model()->query_val_ss($q))) {
			return new \ResponseError('无法确认指定的用户身份信息');
		}
		/**
		 * 拼装消息
		 */
		$msg = array(
			'msgtype' => 'text',
			'text' => array('content' => $text),
		);
		/**
		 * 推送消息
		 */
		$rst = $this->send2YxUserByP2p($mpid, $msg, array($openid));
		if (false === $rst[0]) {
			return new \ResponseError($rst[1]);
		} else {
			return new \ResponseData('ok');
		}
	}
}
