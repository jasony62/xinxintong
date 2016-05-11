<?php
/**
 *
 */
class authapi_model extends TMS_MODEL {
	/**
	 *
	 */
	private function &queryBy($where, $fields = '*') {
		$q = array(
			$fields,
			'xxt_member_authapi',
			$where,
		);

		!($apis = $this->query_objs_ss($q)) && $apis = array();

		foreach ($apis as &$api) {
			if (!empty($api->extattr)) {
				$api->extattr = json_decode($api->extattr);
			}
			if ($api->auth_code_id != 0) {
				$page = \TMS_APP::M('code\page')->byId($api->auth_code_id, 'html,css,js');
				$api->page = $page;
			}
		}

		return $apis;
	}
	/**
	 * 认证接口定义
	 */
	public function &byId($authid, $fields = '*') {
		$api = $this->queryBy("authid=$authid");

		$api = count($api) === 1 ? $api[0] : false;

		return $api;
	}
	/**
	 *
	 */
	public function byUrl($mpid, $url, $fields = '*') {
		$api = $this->queryBy("mpid='$mpid' and url='$url'");

		$api = count($api) === 1 ? $api[0] : false;

		return $api;
	}
	/**
	 * 获得定义的认证接口
	 *
	 * $mpid
	 * $valid [null|Y|N]
	 */
	public function &byMpid($mpid, $valid = null, $own = 'Y') {
		$where = "mpid='$mpid'";
		!empty($valid) && $where .= " and valid='$valid'";

		$apis = $this->queryBy($where);

		if ($own === 'N') {
			$pmp = \TMS_APP::M('mp\mpaccount')->byId($mpid);
			if (!empty($pmp->parent_mpid)) {
				$papis = $this->byMpid($pmp->parent_mpid, $valid);
			}

			if (!empty($papis)) {
				if (!empty($apis)) {
					$apis = array_merge($papis, $apis);
				} else {
					$apis = $papis;
				}

			}
		}

		return $apis;
	}
	/**
	 * 进入用户身份认证页的说明
	 */
	public function getEntryStatement($authid, $mpid, $openid) {
		$authapi = $this->byId($authid, 'url,entry_statement');
		$r = $authapi->entry_statement;
		if (false !== strpos($r, '{{authapi}}')) {
			// auth page's url
			$url = "http://" . $_SERVER['HTTP_HOST'];
			$url .= $authapi->url;
			$url .= "?mpid=$mpid&authid=$authid&openid=$openid";
			// require auth reply
			$r = str_replace('{{authapi}}', $url, $authapi->entry_statement);
		}

		return $r;
	}
	/**
	 * 用户身份认证信息没有通过验证
	 *
	 * $authid
	 * $runningMpid
	 */
	public function getNotpassStatement($authid, $runningMpid, $openid = null) {
		$authapi = $this->byId($authid, 'url,notpass_statement');
		$r = $authapi->notpass_statement;
		if (false !== strpos($r, '{{authapi}}')) {
			// auth page's url
			$url = "http://" . $_SERVER['HTTP_HOST'];
			$url .= $authapi->url;
			$url .= "?mpid=$runningMpid&authid=$authid";
			if (!empty($openid)) {
				$url .= "&openid=$openid";
			}

			// require auth reply
			$r = str_replace('{{authapi}}', $url, $authapi->notpass_statement);
		}

		return $r;
	}
	/**
	 * 用户身份认证信息没有在白名单中
	 */
	public function getAclStatement($authid, $runningMpid, $openid = null) {
		$authapi = $this->byId($authid, 'url,acl_statement');
		$r = $authapi->acl_statement;
		if (false !== strpos($r, '{{authapi}}')) {
			// auth page's url
			$url = "http://" . $_SERVER['HTTP_HOST'];
			$url .= $authapi->url;
			$url .= "?mpid=$runningMpid&authid=$authid";
			if (!empty($openid)) {
				$url .= "&openid=$openid";
			}

			// require auth reply
			$r = str_replace('{{authapi}}', $url, $authapi->acl_statement);
		}

		return $r;
	}
}