<?php
namespace site\user;
/**
 * 自定义用户信息
 */
class memberschema_model extends \TMS_MODEL {
	/**
	 *
	 */
	private function &_queryBy($where, $fields = '*', $cascaded = 'Y') {
		$q = [
			$fields,
			'xxt_site_member_schema',
			$where,
		];

		$schemas = $this->query_objs_ss($q);
		if (count($schemas)) {
			foreach ($schemas as $oSchema) {
				$oSchema->type = 'memberschema';
				$oAttrs = new \stdClass;
				foreach (['name', 'mobile', 'email'] as $prop) {
					if (isset($oSchema->{'attr_' . $prop})) {
						$oProp = new \stdClass;
						$oProp->hide = $oSchema->{'attr_' . $prop}[0] === '1';
						$oProp->required = $oSchema->{'attr_' . $prop}[1] === '1';
						$oProp->unique = $oSchema->{'attr_' . $prop}[2] === '1';
						$oProp->identity = $oSchema->{'attr_' . $prop}[5] === '1';
						$oAttrs->{$prop} = $oProp;
					}
				}
				$oSchema->attrs = $oAttrs;
				if (property_exists($oSchema, 'url') && isset($oSchema->siteid) && isset($oSchema->id)) {
					$oSchema->fullUrl = 'http://' . APP_HTTP_HOST . $oSchema->url . '?site=' . $oSchema->siteid . '&schema=' . $oSchema->id;
				}
				if (property_exists($oSchema, 'ext_attrs')) {
					$oSchema->extAttrs = empty($oSchema->ext_attrs) ? [] : json_decode($oSchema->ext_attrs);
					unset($oSchema->ext_attrs);
				}
				if (isset($oSchema->extattr) && !empty($oSchema->extattr)) {
					$oSchema->extattr = json_decode($oSchema->extattr);
				}
			}
		}

		return $schemas;
	}
	/**
	 * 自定义用户信息
	 */
	public function byId($id, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$cascaded = isset($aOptions['cascaded']) ? $aOptions['cascaded'] : 'Y';

		$oMschema = $this->_queryBy("id='$id'", $fields, $cascaded);

		$oMschema = count($oMschema) === 1 ? $oMschema[0] : false;

		return $oMschema;
	}
	/**
	 * 填加自定义联系人接口
	 * 自定义联系人接口只有在本地部署版本中才有效
	 */
	public function create($oSite, $oUser, $oConfig = null) {
		$oNewMschema = new \stdClass;
		$oNewMschema->siteid = $oSite->id;
		$oNewMschema->matter_id = isset($oConfig->matter_id) ? $oConfig->matter_id : '';
		$oNewMschema->matter_type = isset($oConfig->matter_type) ? $oConfig->matter_type : '';
		$oNewMschema->title = isset($oConfig->title) ? $this->escape($oConfig->title) : '新通讯录';
		$oNewMschema->valid = (isset($oConfig->valid) && $oConfig->valid === 'Y') ? 'Y' : 'N';
		$oNewMschema->creater = $oUser->id;
		$oNewMschema->create_at = time();
		$oNewMschema->url = TMS_APP_API_PREFIX . "/site/fe/user/member";
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
		$oNewMschema->type = 'memberschema';

		/* 作为项目中的活动 */
		if (!empty($oConfig->matter_type) && $oConfig->matter_type === 'mission') {
			$modelMis = $this->model('matter\mission');
			$modelMis->addMatter($oUser, $oSite->id, $oConfig->matter_id, $oNewMschema, ['is_public' => 'N']);
		}

		return $oNewMschema;
	}
	/**
	 * 恢复被删除的素材
	 */
	public function restore($oUser, $oMschema) {
		/* 恢复数据 */
		$rst = $this->update(
			'xxt_site_member_schema',
			['valid' => 'Y'],
			["id" => $oMschema->id]
		);

		/* 记录和项目的关系 */
		if (isset($oMschema->matter_type) && $oMschema->matter_type === 'mission' && !empty($oMschema->matter_id)) {
			$modelMis = $this->model('matter\mission');
			$modelMis->addMatter($oUser, $oMschema->siteid, $oMschema->matter_id, $oMschema);
		}

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oMschema->siteid, $oUser, $oMschema, 'Restore');

		return new \ResponseData($rst);
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