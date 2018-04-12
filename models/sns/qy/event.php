<?php
namespace sns\qy;
/**
 * 微信公众号事件
 */
class event_model extends \TMS_MODEL {
	/**
	 *
	 */
	private function baseUrl() {
		$url = APP_PROTOCOL . APP_HTTP_HOST;
		//$port = $_SERVER["SERVER_PORT"];
		//$port != '80' && $port != '443'  && $url .= ':' . $port;
		return $url;
	}
	/**
	 * 根据menu key获得响应素材
	 *
	 * todo 代码的逻辑有问题，如果找不到回复信息怎么办？
	 *
	 * return array(素材的类型，素材的ID)
	 */
	public function menuCall($siteId, $key) {
		$q = array(
			'matter_type,matter_id',
			'xxt_call_menu_qy',
			"siteid='$siteId' and menu_key='$key' and published='Y'",
		);
		$q2['o'] = 'version desc';
		$q2['r']['o'] = '0';
		$q2['r']['l'] = '1';
		if ($cr = $this->query_objs_ss($q, $q2)) {
			return $cr[0];
		} else {
			return false;
		}
	}

	/**
	 * 查找文本消息回复
	 *
	 * 返回回复对象
	 *
	 */
	public function textCall($siteId, $text) {
		/**
		 * mappings of text call and reply
		 */
		$q = array(
			'id,keyword,match_mode,matter_type,matter_id',
			'xxt_call_text_qy',
			"siteid='$siteId'",
		);
		$mps = $this->query_objs_ss($q);
		/**
		 * match mapping.
		 */
		$reply = false;
		foreach ($mps as $mp) {
			if ($mp->match_mode == 'full' && $text == $mp->keyword) {
				$reply = $mp;
				break;
			} else if ($mp->match_mode == 'cmd' && preg_match('/^' . preg_quote($mp->keyword) . '.?/i', $text) === 1) {
				$reply = $mp;
				break;
			}
		}
		return $reply;
	}
	/**
	 * 关注回复素材
	 */
	public function otherCall($siteId, $name) {
		$p = array(
			'matter_type,matter_id',
			'xxt_call_other_qy',
			"siteid='$siteId' and name='$name'",
		);
		if ($reply = $this->query_obj_ss($p)) {
			if (empty($reply->matter_type) || empty($reply->matter_id)) {
				return false;
			} else {
				return $reply;
			}
		}
		return false;
	}
}