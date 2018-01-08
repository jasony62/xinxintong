<?php
namespace site\user;
/**
 * 自定义用户信息
 */
class member_model extends \TMS_MODEL {
	/**
	 * 获取自定义用户信息
	 */
	public function &byId($id, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_site_member',
			['id' => $id, 'forbidden' => 'N'],
		];
		if ($member = $this->query_obj_ss($q)) {
			if (!empty($member->extattr)) {
			}
		}

		return $member;
	}
	/**
	 * 获取自定义用户信息
	 *
	 * @param string $userid
	 *
	 */
	public function &byUser($userid, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_site_member',
			"userid='$userid' and forbidden='N'",
		];
		isset($options['schemas']) && $q[2] .= " and schema_id in (" . $options['schemas'] . ")";

		$members = $this->query_objs_ss($q);

		return $members;
	}
	/**
	 * 获得注册用户关联的通讯录用户
	 */
	public function byUnionid($siteId, $mschemaId, $unionid) {
		$modelAcnt = $this->model('site\user\account');
		$aUnionUsers = $modelAcnt->byUnionid($unionid, ['siteid' => $siteId, 'fields' => 'uid']);
		$oMembers = [];
		foreach ($aUnionUsers as $oUnionUser) {
			$aMembers = $this->byUser($oUnionUser->uid, ['schemas' => $mschemaId]);
			if (count($aMembers) === 1) {
				$oMember = $aMembers[0];
				if ($oMember->verified === 'Y') {
					$oMembers[] = $oMember;
				}
			}
		}
		return $oMembers;
	}
	/**
	 * 获取自定义用户信息
	 *
	 * @param string $userid
	 *
	 */
	public function &byMschema($mschemaId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_site_member',
			['schema_id' => $mschemaId, 'forbidden' => 'N'],
		];

		$members = $this->query_objs_ss($q);

		return $members;
	}
	/**
	 * 创建通讯录联系人
	 */
	public function create($userid, &$oMschema, &$data) {
		// 结束数据库读写分离带来的问题
		$this->setOnlyWriteDbConn(true);

		if (empty($userid)) {
			return [false, '仅支持对站点用户进行认证'];
		}
		if (isset($data->id)) {
			return [false, '参数中包含了无法处理的信息'];
		}

		$aExisted = $this->byUser($userid, ['schemas' => $oMschema->id]);
		if (count($aExisted)) {
			return [false, '当前用户已经绑定了联系人信息'];
		}

		is_array($data) && $data = (object) $data;

		$create_at = time();
		$data->siteid = $oMschema->siteid;
		$data->userid = $userid;
		$data->schema_id = $oMschema->id;
		$data->create_at = $create_at;
		$data->modify_at = $create_at;
		/**
		 * todo 应该支持使用扩展属性作为唯一标识
		 */
		if ($oMschema->attr_mobile[5] === '1' && isset($data->mobile)) {
			if ($oMschema->attr_mobile[4] === '1') {
				/*检查手机号*/
			}
			$data->identity = $data->mobile;
		} else if ($oMschema->attr_email[5] === '1' && isset($data->email)) {
			$data->identity = $data->email;
		}
		/**
		 * 扩展属性
		 */
		if (!empty($oMschema->extattr)) {
			$extdata = array();
			foreach ($oMschema->extattr as $ea) {
				if (isset($data->extattr->{$ea->id})) {
					$extdata[$ea->id] = urlencode($data->extattr->{$ea->id});
					unset($data->{$ea->id});
				}
			}
			$data->extattr = urldecode(json_encode($extdata));
		} else {
			$data->extattr = '{}';
		}

		$id = $this->insert('xxt_site_member', $data, true);
		$member = $this->byId($id);

		return array(true, $member);
	}
	/**
	 * 在应用中创建通讯录联系人
	 */
	public function createByApp(&$oMschema, $userid, $oNewMember) {
		// 结束数据库读写分离带来的问题
		$this->setOnlyWriteDbConn(true);

		if (empty($oMschema)) {
			return array(false, '没有指定用户自定义接口');
		}

		is_array($oNewMember) && $oNewMember = (object) $oNewMember;
		$oNewMember->siteid = $oMschema->siteid;
		$create_at = time();
		$oNewMember->userid = $userid;
		$oNewMember->schema_id = $oMschema->id;
		$oNewMember->create_at = $create_at;
		$oNewMember->modify_at = $create_at;

		if ($errMsg = $this->rejectAuth($oNewMember, $oMschema)) {
			return array(false, $errMsg);
		}
		/* 用户的邮箱需要验证，将状态设置为等待验证的状态 */
		$oNewMember->email_verified = ($oMschema->attr_email[4] === '1') ? 'N' : 'Y';
		/**
		 * todo 应该支持使用扩展属性作为唯一标识
		 */
		if ($oMschema->attr_mobile[5] === '1' && isset($oNewMember->mobile)) {
			if ($oMschema->attr_mobile[4] === '1') {
				/*检查手机号*/
			}
			$oNewMember->identity = $oNewMember->mobile;
		} else if ($oMschema->attr_email[5] === '1' && isset($oNewMember->email)) {
			$oNewMember->identity = $oNewMember->email;
		} else {
			return array(false, '无法获得用户身份标识信息');
		}
		/* 扩展属性 */
		if (!empty($oNewMember->extattr)) {
			$extdata = array();
			foreach ($oNewMember->extattr as $ek => $ev) {
				$extdata[$ek] = urlencode($ev);
			}
			$oNewMember->extattr = urldecode(json_encode($extdata));
		} else {
			$oNewMember->extattr = '{}';
		}
		/* 验证状态 */
		$oNewMember->verified = $oMschema->auto_verified;

		$id = $this->insert('xxt_site_member', $oNewMember, true);
		$oNewMember = $this->byId($id);

		return array(true, $oNewMember);
	}
	/**
	 * 更新通讯录联系人
	 */
	public function modify(&$oMschema, $memberId, &$oNewMember) {
		if (empty($memberId)) {
			return array(false, '没有指定认证用户MID');
		}
		if ($errMsg = $this->rejectAuth($oNewMember, $oMschema)) {
			return array(false, $errMsg);
		}
		is_array($oNewMember) && $oNewMember = (object) $oNewMember;
		/**
		 * 用户的邮箱需要验证，将状态设置为等待验证的状态
		 */
		$oNewMember->email_verified = ($oMschema->attr_email[4] === '1') ? 'N' : 'Y';
		/**
		 * todo 应该支持使用扩展属性作为唯一标识
		 */
		if ($oMschema->attr_mobile[5] === '1' && isset($oNewMember->mobile)) {
			if ($oMschema->attr_mobile[4] === '1') {
				/*检查手机号*/
			}
			$identity = $oNewMember->mobile;
			if (isset($oNewMember->verified) && $oNewMember->verified === 'Y') {
				if (isset($oNewMember->identity) && $oNewMember->identity !== $identity) {
					return [false, '通讯录信息已通过审核，不可更改唯一标识(手机号)'];
				}
			}
			$oNewMember->identity = $identity;
		} else if ($oMschema->attr_email[5] === '1' && isset($oNewMember->email)) {
			$identity = $oNewMember->email;
			if (isset($oNewMember->verified) && $oNewMember->verified === 'Y') {
				if (isset($oNewMember->identity) && $oNewMember->identity !== $identity) {
					return [false, '通讯录信息已通过审核，不可更改唯一标识(邮箱)'];
				}
			}
			$oNewMember->identity = $identity;
		}
		/**
		 * 扩展属性
		 */
		if (!empty($oNewMember->extattr)) {
			$oNewMember->extattr = $this->toJson($oNewMember->extattr);
		} else {
			$oNewMember->extattr = '{}';
		}
		/* 验证状态 */
		$oNewMember->verified = isset($oNewMember->verified) ? $oNewMember->verified : $oMschema->auto_verified;
		$oNewMember->modify_at = time();

		$this->update('xxt_site_member', $oNewMember, ['id' => $memberId]);

		return array(true);
	}
	/**
	 * 判断当前用户信息是否有效
	 *
	 * @param object $member
	 * @param object $oMschema
	 *
	 * $attrs array 用户认证信息定义
	 *  0:hidden,1:mandatory,2:unique,3:immuatable,4:verification,5:identity
	 *
	 * return
	 *  若不合法，返回描述原因的字符串
	 *  合法返回false
	 */
	public function rejectAuth(&$member, &$oMschema) {
		if (isset($member->mobile) && $oMschema->attr_mobile[2] === '1') {
			/**
			 * 检查手机号的唯一性
			 */
			$mobile = $member->mobile;
			$q = [
				'id',
				'xxt_site_member',
				"schema_id={$member->schema_id} and forbidden='N' and mobile='$mobile'",
			];
			/* 不是当前用户自己 */
			!empty($member->id) && $q[2] .= " and id<>'{$member->id}'";
			$members = $this->query_objs_ss($q);
			if (count($members) > 0) {
				if (empty($oMschema->title)) {
					return '手机号已经存在，不允许重复登记！';
				} else {
					return '手机号已经在【' . $oMschema->title . '】中存在，不允许重复登记！';
				}
			}
		}
		if (isset($member->email) && $oMschema->attr_email[2] === '1') {
			/**
			 * 检查邮箱的唯一性
			 */
			$email = $member->email;
			$q = [
				'id',
				'xxt_site_member',
				"schema_id={$member->schema_id} and forbidden='N' and email='$email'",
			];
			/* 不是当前用户自己 */
			!empty($member->id) && $q[2] .= " and id<>'{$member->id}'";

			$members = $this->query_objs_ss($q);
			if (count($members) > 0) {
				if (empty($oMschema->title)) {
					return '邮箱已经存在，不允许重复登记！';
				} else {
					return '邮箱已经【' . $oMschema->title . '】中，不允许重复登记！';
				}
			}
		}

		return false;
	}
	/**
	 * 根据提交的认证信息，查找已经存在认证用户
	 *
	 * @param object $member
	 * @param object $oMschema
	 *
	 * $items array 用户认证信息定义
	 * 0:hidden,1:mandatory,2:unique,3:immuatable,4:verification,5:identity
	 */
	public function findMember(&$member, &$oMschema, $options = array()) {
		if (isset($member->mobile) && $oMschema->attr_mobile[5] === '1') {
			/**
			 * 手机号唯一
			 */
			$identity = $member->mobile;
		} else if (isset($member->email) && $oMschema->attr_email[5] === '1') {
			/**
			 * 邮箱唯一
			 */
			$identity = $member->email;
		}
		if (isset($identity)) {
			$fields = isset($options['fields']) ? $options['fields'] : '*';
			$q = [
				$fields,
				'xxt_site_member',
				"schema_id={$oMschema->id} and forbidden='N' and identity='$identity'",
			];
			$found = $this->query_obj_ss($q);
		}

		return !empty($found) ? $found : false;
	}
	/**
	 *
	 */
	public function &search($siteId, $identity, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_site_member',
			"siteid='$siteId' and forbidden='N' and (mobile='$identity' or email='$identity')",
		);
		$members = $this->query_objs_ss($q);

		return $members;
	}
}