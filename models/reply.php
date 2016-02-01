<?php
class reply_model extends TMS_MODEL {
	/**
	 *
	 */
	private function baseUrl() {
		$url = "http://" . $_SERVER['HTTP_HOST'];
		//$port = $_SERVER["SERVER_PORT"];
		//$port != '80' && $port != '443'  && $url .= ':' . $port;
		return $url;
	}
	/**
	 * 获得父公众号的ID
	 */
	private function getParentMpid($mpid) {
		$q = array(
			'parent_mpid',
			'xxt_mpaccount',
			"mpid='$mpid'",
		);

		return $this->query_val_ss($q);
	}
	/**
	 * 根据menu key获得响应素材
	 *
	 * todo 代码的逻辑有问题，如果找不到回复信息怎么办？
	 *
	 * return array(素材的类型，素材的ID)
	 */
	public function menu_call($mpid, $key) {
		$q = array(
			'matter_type,matter_id,access_control,authapis',
			'xxt_call_menu',
			"mpid='$mpid' and menu_key='$key' and published='Y'",
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
	 * 根据scene_id获得响应素材
	 *
	 * return array(素材的类型，素材的ID)
	 */
	public function qrcode_call($mpid, $scene_id) {
		$q[] = 'id,matter_type,matter_id,expire_at';
		$q[] = 'xxt_call_qrcode';
		$q[] = "mpid='$mpid' and scene_id=$scene_id";

		$cr = $this->query_obj_ss($q);

		return $cr;
	}
	/**
	 * 查找文本消息回复
	 *
	 * 返回回复对象
	 *
	 */
	public function text_call($mpid, $text) {
		/**
		 * mappings of text call and reply
		 */
		$q = array(
			'id,keyword,match_mode,matter_type,matter_id,access_control,authapis',
			'xxt_call_text',
		);
		/**
		 * 如果存在父账号，优先从父账号中查找回复定义
		 */
		if ($pmpid = $this->getParentMpid($mpid)) {
			$q[2] = "mpid='$pmpid'";
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
			if ($reply) {
				return $reply;
			}

		}
		/**
		 * 当前账号
		 */
		$q[2] = "mpid='$mpid'";
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
	public function other_call($mpid, $name) {
		$p = array(
			'matter_type,matter_id',
			'xxt_call_other',
			"mpid='$mpid' and name='$name'",
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
	/**
	 * 拼接URL中的参数
	 */
	public function spliceParams($mpid, &$params, $mid = null, $openid = null) {
		$pairs = array();
		foreach ($params as $p) {
			switch ($p->pvalue) {
			case '{{mpid}}':
				$v = $mpid;
				break;
			case '{{openid}}':
				$v = $openid;
				break;
			case '{{authed_identity}}':
				if (empty($mid)) {
					$q = array(
						'authed_identity',
						'xxt_member m',
						"m.mpid='$mpid' and m.forbidden='N' and m.openid='$openid' and m.authapi_id=$p->authapi_id",
					);
				} else {
					$q = array(
						'authed_identity',
						'xxt_member',
						"mpid='$mpid' and m.forbidden='N' and mid='$mid' and authapi_id=$p->authapi_id",
					);
				}

				if (!($v = $this->query_val_ss($q))) {
					$v = '';
				}
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