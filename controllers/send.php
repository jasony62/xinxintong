<?php
require_once dirname(__FILE__) . '/xxt_base.php';
/**
 *
 */
class send extends xxt_base {
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 发送模板消息
	 *
	 * $mpid
	 * $templateid 模板消息id
	 *
	 * post
	 * --data object key:value 不包含大括号
	 * --openids array string
	 *
	 */
	public function tmplmsg_action($mpid, $templateid, $url = '') {
		$posted = $this->getPostJson();

		$data = $posted->data;
		$openids = $posted->openids;

		foreach ($openids as $openid) {
			$rst = $this->tmplmsgSendByOpenid($mpid, $templateid, $openid, $data, $url);
			if ($rst[0] === false) {
				return new \ResponseError($rst[1]);
			}
		}

		return new \ResponseData('ok');
	}
}