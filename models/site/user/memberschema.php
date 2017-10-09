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
				$oPage = new \stdClass;
				$templateDir = TMS_APP_TEMPLATE . '/pl/fe/site/memberschema';
				if (file_exists($templateDir . '/basic.html')) {
					$oPage->html = file_get_contents($templateDir . '/basic.html');
				} else {
					$oPage->html = file_get_contents(TMS_APP_TEMPLATE_DEFAULT . '/pl/fe/site/memberschema/basic.html');
				}
				if (file_exists($templateDir . '/basic.css')) {
					$oPage->css = file_get_contents($templateDir . '/basic.css');
				} else {
					$oPage->css = file_get_contents(TMS_APP_TEMPLATE_DEFAULT . '/pl/fe/site/memberschema/basic.css');
				}
				if (file_exists($templateDir . '/basic.js')) {
					$oPage->js = file_get_contents($templateDir . '/basic.js');
				} else {
					$oPage->js = file_get_contents(TMS_APP_TEMPLATE_DEFAULT . '/pl/fe/site/memberschema/basic.js');
				}
				$schema->page = $oPage;
			}
		}

		return $schemas;
	}
	/**
	 * 根据模板创建缺省页面
	 */
	private function _pageCreate($oSite, $oUser, $template = 'basic') {
		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/site/memberschema';

		$data = [
			'html' => file_get_contents($templateDir . '/' . $template . '.html'),
			'css' => file_get_contents($templateDir . '/' . $template . '.css'),
			'js' => file_get_contents($templateDir . '/' . $template . '.js'),
		];

		$oCode = $this->model('code\page')->create($oSite->id, $oUser->id, $data);

		return $oCode;
	}
	/**
	 * 自定义用户信息
	 */
	public function &byId($id, $fields = '*') {
		$id = $this->escape($id);
		$oMschema = $this->_queryBy("id='$id'");

		$oMschema = count($oMschema) === 1 ? $oMschema[0] : false;

		return $oMschema;
	}
	/**
	 * 填加自定义联系人接口
	 * 自定义联系人接口只有在本地部署版本中才有效
	 */
	public function create($oSite, $oUser, $oConfig = null) {

		$oCode = $this->_pageCreate($oSite, $oUser);

		$oNewMschema = new \stdClass;
		$oNewMschema->siteid = $oSite->id;
		$oNewMschema->matter_id = isset($oConfig->matter_id) ? $oConfig->matter_id : '';
		$oNewMschema->matter_type = isset($oConfig->matter_type) ? $oConfig->matter_type : '';
		$oNewMschema->title = isset($oConfig->title) ? $this->escape($oConfig->title) : '新通讯录';
		$oNewMschema->type = 'inner';
		$oNewMschema->valid = (isset($oConfig->valid) && $oConfig->valid === 'Y') ? 'Y' : 'N';
		$oNewMschema->creater = $oUser->id;
		$oNewMschema->create_at = time();
		$oNewMschema->entry_statement = '无法确认您是否有权限进行该操作，请先完成【<a href="{{authapi}}">用户身份确认</a>】。';
		$oNewMschema->acl_statement = '您的身份识别信息没有放入白名单中，请与系统管理员联系。';
		$oNewMschema->notpass_statement = '您的邮箱还没有验证通过，若未收到验证邮件请联系系统管理员。若需要重发验证邮件，请先完成【<a href="{{authapi}}">用户身份确认</a>】。';
		$oNewMschema->url = TMS_APP_API_PREFIX . "/site/fe/user/member";
		$oNewMschema->code_id = $oCode->id;
		$oNewMschema->page_code_name = $oCode->name;
		$oNewMschema->attr_mobile = '011101'; // 必填，唯一，不可更改，身份标识
		$oNewMschema->attr_email = '001000';
		$oNewMschema->attr_name = '000000';
		$oNewMschema->require_invite = 'Y';
		$oNewMschema->auto_verified = 'Y';
		$oNewMschema->validity = 365;
		$oNewMschema->at_user_home = 'N';

		/* 默认要求已经开通的关注公众号 */
		$modelWx = $this->model('sns\wx');
		if (($wx = $modelWx->bySite($oSite->id, ['fields' => 'joined'])) && $wx->joined === 'Y') {
			$oNewMschema->is_wx_fan = 'Y';
		} else if (($wx = $modelWx->bySite('platform', ['fields' => 'joined'])) && $wx->joined === 'Y') {
			$oNewMschema->is_wx_fan = 'Y';
		} else {
			$oNewMschema->is_wx_fan = 'N';
		}
		if (($yx = $this->model('sns\yx')->bySite($oSite->id, ['fields' => 'joined'])) && $yx->joined === 'Y') {
			$oNewMschema->is_yx_fan = 'Y';
		} else {
			$oNewMschema->is_yx_fan = 'N';
		}

		$oNewMschema->id = $this->insert('xxt_site_member_schema', $oNewMschema, true);

		return $oNewMschema;
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
			$url = "http://" . APP_HTTP_HOST;
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
			$url = "http://" . APP_HTTP_HOST;
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
			$url = "http://" . APP_HTTP_HOST;
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
	/**
	 * 获取此通讯录有权导入的其它通讯录
	 */
	public function importSchema($site, $id) {
		if (($schema = $this->byId($id, "id,matter_id,matter_type,type")) === false) {
			return new \ResponseError('请检查参数设置');
		}

		$site = $this->escape($site);
		$id = $this->escape($id);
		//如果是项目通讯录从项目下的活动通讯录和项目所属团队通讯录中导入用户
		if ($schema->matter_type === 'mission' && $schema->matter_id !== '') {
			$qm = [
				'ms.id,ms.title,ms.matter_id,ms.matter_type,ms.create_at',
				'xxt_mission_matter m,xxt_site_member_schema ms',
				"m.mission_id = $schema->matter_id and ms.matter_type = m.matter_type and ms.matter_id = m.matter_id",
			];
			$qm2 = ['o' => 'ms.create_at desc'];
			$schemaMissionMatter = $this->query_objs_ss($qm, $qm2);
			//项目本身其它的通讯录
			$qmi = [
				'id,title,matter_id,matter_type,create_at',
				'xxt_site_member_schema',
				"matter_type = 'mission' and matter_id = '$schema->matter_id' and id <> $id",
			];
			$qmi2 = ['o' => 'create_at desc'];
			$schemaMission = $this->query_objs_ss($qmi, $qmi2);

			$schemaMatter = array_merge($schemaMissionMatter, $schemaMission);
		} else if ($schema->matter_type !== '' && $schema->matter_id !== '') {
			//查询活动所属项目的通讯录
			$qm = [
				'ms.id,ms.title,ms.matter_id,ms.matter_type,ms.create_at',
				'xxt_mission_matter m,xxt_site_member_schema ms',
				"m.matter_id = '$schema->matter_id' and m.matter_type = '$schema->matter_type' and ms.matter_id = m.mission_id and ms.matter_type = 'mission'",
			];
			$qm2 = ['o' => 'ms.create_at desc'];
			$schemaMissionMatter = $this->query_objs_ss($qm, $qm2);
			//查询活动其它通讯录
			$qma = [
				'id,title,matter_id,matter_type,create_at',
				'xxt_site_member_schema',
				"matter_id = '$schema->matter_id' and matter_type = '$schema->matter_type' and id <> $id",
			];
			$qma2 = ['o' => 'create_at desc'];
			$schemaApp = $this->query_objs_ss($qma, $qma2);

			$schemaMatter = array_merge($schemaMissionMatter, $schemaApp);
		} else {
			//团队下的所有活动和项目通讯录
			$qm = [
				'id,title,matter_id,matter_type,create_at',
				'xxt_site_member_schema',
				"siteid = '$site' and matter_id <> ''",
			];
			$qm2 = ['o' => 'create_at desc'];
			$schemaMatter = $this->query_objs_ss($qm, $qm2);
		}
		//获取所在团队的所有通讯录
		$qs = [
			'id,title,matter_id,matter_type,create_at',
			'xxt_site_member_schema',
			"siteid = '$site' and matter_id = '' and id <> $id",
		];
		$qs2 = ['o' => 'create_at desc'];
		$schemaSite = $this->query_objs_ss($qs, $qs2);

		$schemas = array_merge($schemaMatter, $schemaSite);
		//依照时间排序
		$sortAt = [];
		foreach ($schemas as $key => $val) {
			$sortAt[$key] = $val->create_at;
		}
		array_multisort($sortAt, SORT_DESC, $schemas);

		return $schemas;
	}
}