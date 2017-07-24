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
		$q = [
			$fields,
			'xxt_site_member_schema',
			$where,
		];

		$schemas = $this->query_objs_ss($q);
		if (count($schemas)) {
			//$modelCp = $this->model('code\page');
			foreach ($schemas as &$schema) {
				if ($schema->type === 'inner') {
					if (isset($schema->siteid) && isset($schema->id)) {
						$schema->fullUrl = 'http://' . APP_HTTP_HOST . $schema->url . '?site=' . $schema->siteid . '&schema=' . $schema->id;
					}
				}
				if (!empty($schema->extattr)) {
					$schema->extattr = json_decode($schema->extattr);
				}
				// if (!empty($schema->page_code_name)) {
				// 	$page = $modelCp->lastPublishedByName(
				// 		$schema->siteid,
				// 		$schema->page_code_name,
				// 		['fields' => 'id,html,css,js']
				// 	);
				// 	$schema->page = $page;
				// }
				$templateDir = TMS_APP_TEMPLATE . '/pl/fe/site/memberschema';
				$page = new \stdClass;
				$page->html = file_get_contents($templateDir . '/basic.html');
				$page->css = file_get_contents($templateDir . '/basic.css');
				$page->js = file_get_contents($templateDir . '/basic.js');
				$schema->page = $page;
			}
		}

		return $schemas;
	}
	/**
	 * 自定义用户信息
	 */
	public function &byId($id, $fields = '*') {
		$oMschema = $this->_queryBy("id='$id'");

		$oMschema = count($oMschema) === 1 ? $oMschema[0] : false;

		return $oMschema;
	}
	/**
	 * 通讯录概况
	 */
	public function overview($schemaId) {
		$q = [
			'id,matter_id,matter_type,title,require_invite,is_qy_fan,is_wx_fan,is_yx_fan,attr_mobile,attr_email,attr_name,extattr',
			'xxt_site_member_schema',
			['id' => $schemaId],
		];
		if ($oMschema = $this->query_obj_ss($q)) {
			$oMschema->extattr = empty($oMschema->extattr) ? new \stdClass : json_decode($oMschema->extattr);
			$count = new \stdClass;
			$q = [
				'count(*)',
				'xxt_site_member',
				['schema_id' => $schemaId, 'forbidden' => 'N', 'verified' => 'Y'],
			];
			$count->verified = $this->query_val_ss($q);

			$q[2]['verified'] = 'N';
			$count->unverified = $this->query_val_ss($q);

			$oMschema->count = $count;
		}

		return $oMschema;
	}
	/**
	 * 获得团队下的通讯录
	 *
	 * @param string $siteId
	 * @param string $valid [null|Y|N]
	 */
	public function &bySite($siteId, $valid = null, $options = []) {
		$onlyMatter = isset($options['onlyMatter']) ? $options['onlyMatter'] : 'Y';

		$siteId = $this->escape($siteId);
		$where = "siteid='$siteId'";

		if (isset($valid)) {
			if ($valid === 'Y') {
				$where .= " and valid='Y'";
			} else if ($valid === 'N') {
				$where .= " and valid='N'";
			}
		}
		if (isset($options['matter'])) {
			$oMatter = $options['matter'];
			if ($onlyMatter === 'Y') {
				$where .= ' and (matter_id = \'' . $oMatter->id . '\' and matter_type=\'' . $oMatter->type . '\')';
			} else {
				$oMatter = $this->model('matter\\' . $oMatter->type)->byId($oMatter->id, ['cascaded' => 'N']);
				$where .= ' and (';
				$where .= ' (matter_id = \'' . $oMatter->id . '\' and matter_type=\'' . $oMatter->type . '\')';
				$where .= ' or matter_id=\'\''; // 团队下的通讯录
				if ($oMatter->type !== 'mission' && !empty($oMatter->mission_id)) {
					$where .= ' or (matter_id=\'' . $oMatter->mission_id . '\' and matter_type=\'mission\')'; // 项目下的通讯录
				}
				$where .= ')';
			}
		} else {
			/* 直接属于团队下的通讯录 */
			$where .= ' and matter_id = \'\'';
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
		$url = "http://" . APP_HTTP_HOST;
		$url .= '/rest/site/fe/user/member';
		$url .= "?site={$siteId}&schema={$mschemaId}";

		return $url;
	}
}