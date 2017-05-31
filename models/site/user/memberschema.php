<?php
namespace site\user;
/**
 * 自定义用户信息
 */
class memberschema_model extends \TMS_MODEL {
	/**
	 *
	 */
	private function &_queryBy($where, $fields = '*') {
		$q = array(
			$fields,
			'xxt_site_member_schema',
			$where,
		);

		$schemas = $this->query_objs_ss($q);

		foreach ($schemas as &$schema) {
			if (!empty($schema->extattr)) {
				$schema->extattr = json_decode($schema->extattr);
			}
			if (!empty($schema->page_code_name)) {
				$page = \TMS_APP::M('code\page')->lastPublishedByName(
					$schema->siteid,
					$schema->page_code_name,
					array('fields' => 'id,html,css,js')
				);
				$schema->page = $page;
			}
		}

		return $schemas;
	}
	/**
	 * 自定义用户信息
	 */
	public function &byId($id, $fields = '*') {
		$api = $this->_queryBy("id='$id'");

		$api = count($api) === 1 ? $api[0] : false;

		return $api;
	}
	/**
	 *
	 * @param string $siteId
	 * @param string $valid [null|Y|N]
	 */
	public function &bySite($siteId, $valid = null, $options = []) {
		$siteId = $this->escape($siteId);
		$where = "siteid='$siteId'";

		if (isset($valid)) {
			if ($valid === 'Y') {
				$where .= " and valid='Y'";
			} else if ($valid === 'N') {
				$where .= " and valid='N'";
			}
		}

		if (isset($options['atUserHome'])) {
			$where .= " and at_user_home='Y'";
		}

		if (isset($options['fields'])) {
			$schemas = $this->_queryBy($where, $options['fields']);
		} else {
			$schemas = $this->_queryBy($where);
		}

		return $schemas;
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
	/**
	 * 获得企业号通讯录同步数据用的自定义用户定义
	 *
	 * @param string $siteId;
	 */
	public function qyabSchemaBySite($siteId, $optons = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_site_member_schema',
			["siteid" => $siteId, "qy_ab" => 'Y'],
		];
		$schema = $this->query_obj_ss($q);

		return $schema;
	}
	/**
	 *
	 */
	public function getEntryUrl($siteId, $mschemaId) {
		$url = "http://" . $_SERVER['HTTP_HOST'];
		$url .= '/rest/site/fe/user/member';
		$url .= "?site={$siteId}&schema={$mschemaId}";

		return $url;
	}
}