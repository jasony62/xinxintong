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
		$q = array(
			$fields,
			'xxt_site_member',
			"id='$id' and forbidden='N'",
		);
		$member = $this->query_obj_ss($q);

		return $member;
	}
	/**
	 * 获取自定义用户信息
	 */
	public function &byUser($siteId, $userid, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_site_member',
			"userid='$userid' and forbidden='N'",
		);
		isset($options['schemas']) && $q[2] .= " and schema_id in (" . $options['schemas'] . ")";
		$members = $this->query_objs_ss($q);

		return $members;
	}
	/**
	 * 创建一个自定义用户
	 *
	 * 自定义用户首先必须是站点用户
	 *
	 * $userid 站点用户id
	 * $data
	 * $schema
	 */
	public function create($siteId, $userid, &$schema, &$data) {
		if (empty($userid)) {
			return array(false, '仅支持对站点用户进行认证');
		}

		is_array($data) && $data = (object) $data;

		$create_at = time();
		$data->siteid = $siteId;
		$data->userid = $userid;
		$data->schema_id = $schema->id;
		$data->create_at = $create_at;
		/**
		 * todo 应该支持使用扩展属性作为唯一标识
		 */
		if ($schema->attr_mobile[5] === '1' && isset($data->mobile)) {
			if ($schema->attr_mobile[4] === '1') {
				/*检查手机号*/
			}
			$data->identity = $data->mobile;
		} else if ($schema->attr_email[5] === '1' && isset($data->email)) {
			$data->identity = $data->email;
		}
		/**
		 * 扩展属性
		 */
		if (!empty($schema->extattr)) {
			$extdata = array();
			foreach ($schema->extattr as $ea) {
				if (isset($data->{$ea->id})) {
					$extdata[$ea->id] = urlencode($data->{$ea->id});
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
	 * 创建认证用户
	 */
	public function createByApp($siteId, &$schema, $userid, $member) {
		if (empty($siteId)) {
			return array(false, '没有指定站点');
		}

		if (empty($schema)) {
			return array(false, '没有指定用户自定义接口');
		}

		is_array($member) && $member = (object) $member;
		$member->siteid = $siteId;
		$create_at = time();
		$member->userid = $userid;
		$member->schema_id = $schema->id;
		$member->create_at = $create_at;

		if ($errMsg = $this->rejectAuth($member, $schema)) {
			return array(false, $errMsg);
		}
		/* 用户的邮箱需要验证，将状态设置为等待验证的状态 */
		$member->email_verified = ($schema->attr_email[4] === '1') ? 'N' : 'Y';
		/**
		 * todo 应该支持使用扩展属性作为唯一标识
		 */
		if ($schema->attr_mobile[5] === '1' && isset($member->mobile)) {
			if ($schema->attr_mobile[4] === '1') {
				/*检查手机号*/
			}
			$member->identity = $member->mobile;
		} else if ($schema->attr_email[5] === '1' && isset($member->email)) {
			$member->identity = $member->email;
		} else {
			return array(false, '无法获得用户身份标识信息');
		}
		/* 扩展属性 */
		if (!empty($member->extattr)) {
			$extdata = array();
			foreach ($member->extattr as $ek => $ev) {
				$extdata[$ek] = urlencode($ev);
			}
			$member->extattr = urldecode(json_encode($extdata));
		} else {
			$member->extattr = '{}';
		}

		$id = $this->insert('xxt_site_member', $member, true);

		$member = $this->byId($id);

		return array(true, $member);
	}
	/**
	 * 更新认证用户
	 *
	 * 要求认证用户首先必须是关注用户
	 *
	 * $siteId
	 * $fid 关注用户id
	 * $data
	 * $attrs
	 */
	public function modify($siteId, &$schema, $memberId, &$member) {
		if (empty($siteId)) {
			return array(false, '没有指定SITEID');
		}
		if (empty($memberId)) {
			return array(false, '没有指定认证用户MID');
		}
		if ($errMsg = $this->rejectAuth($member, $schema)) {
			return array(false, $errMsg);
		}
		is_array($member) && $member = (object) $member;
		/**
		 * 用户的邮箱需要验证，将状态设置为等待验证的状态
		 */
		$member->email_verified = ($schema->attr_email[4] === '1') ? 'N' : 'Y';
		/**
		 * todo 应该支持使用扩展属性作为唯一标识
		 */
		if ($schema->attr_mobile[5] === '1' && isset($member->mobile)) {
			if ($schema->attr_mobile[4] === '1') {
				/*检查手机号*/
			}
			$member->identity = $member->mobile;
		} else if ($schema->attr_email[5] === '1' && isset($member->email)) {
			$member->identity = $member->email;
		}
		/**
		 * 扩展属性
		 */
		if (!empty($member->extattr)) {
			$member->extattr = $this->toJson($member->extattr);
		} else {
			$member->extattr = '{}';
		}

		$this->update('xxt_site_member', $member, "id='$memberId'");

		return array(true);
	}
	/**
	 * 获得指定成员的部门
	 */
	public function getDepts($mid, $depts = '') {
		if (empty($depts)) {
			$member = $this->byId($mid, 'depts');
			$depts = $member->depts;
		}
		if (empty($depts) || $depts === '[]') {
			return array();
		}

		$ids = array();
		$depts = json_decode($depts);
		foreach ($depts as $ds) {
			$ids = array_merge($ids, $ds);
		}

		$ids = implode(',', $ids);
		$q = array(
			'distinct id,name',
			'xxt_member_department',
			"id in ($ids)",
		);
		$q2 = array('o' => 'fullpath');

		$depts = $this->query_objs_ss($q, $q2);

		return $depts;
	}
	/**
	 *
	 * $mid
	 * $tags ids
	 * $type
	 *
	 */
	public function getTags($mid, $tags = '', $type = 0) {
		if (empty($tags)) {
			$member = $this->byId($mid, 'tags');
			$tags = $member->tags;
		}
		if (empty($tags)) {
			return array();
		}

		$q = array(
			'distinct id,name',
			'xxt_member_tag',
			"type=$type and id in ($tags)",
		);
		$tags = $this->query_objs_ss($q);

		return $tags;
	}
	/**
	 * 判断当前用户信息是否有效
	 *
	 * $member
	 * $attrs array 用户认证信息定义
	 *  0:hidden,1:mandatory,2:unique,3:immuatable,4:verification,5:identity
	 *
	 * return
	 *  若不合法，返回描述原因的字符串
	 *  合法返回false
	 */
	public function rejectAuth(&$member, &$schema) {
		if (isset($member->mobile) && $schema->attr_mobile[2] === '1') {
			/**
			 * 检查手机号的唯一性
			 */
			$mobile = $member->mobile;
			$q = array(
				'1',
				'xxt_site_member',
				"schema_id={$member->schema_id} and forbidden='N' and mobile='$mobile'",
			);
			/* 不是当前用户自己 */
			!empty($member->id) && $q[2] .= " and id!='{$member->id}'";
			if ('1' === $this->query_val_ss($q)) {
				return '手机号已经存在，不允许重复登记！';
			}
		}
		if (isset($member->email) && $schema->attr_email[2] === '1') {
			/**
			 * 检查邮箱的唯一性
			 */
			$email = $member->email;
			$q = array(
				'1',
				'xxt_site_member',
				"schema_id={$member->schema_id} and forbidden='N' and email='$email'",
			);
			/* 不是当前用户自己 */
			!empty($member->id) && $q[2] .= " and id!='{$member->id}'";
			if ('1' === $this->query_val_ss($q)) {
				return '邮箱已经存在，不允许重复登记！';
			}
		}

		return false;
	}
	/**
	 * 根据提交的认证信息，查找已经存在认证用户
	 *
	 * $member
	 * $items array 用户认证信息定义
	 * 0:hidden,1:mandatory,2:unique,3:immuatable,4:verification,5:identity
	 */
	public function findMember(&$member, &$schema, $options = array()) {
		if (isset($member->mobile) && $schema->attr_mobile[5] === '1') {
			/**
			 * 手机号唯一
			 */
			$identity = $member->mobile;
		} else if (isset($member->email) && $schema->attr_email[5] === '1') {
			/**
			 * 邮箱唯一
			 */
			$identity = $member->email;
		}
		if (isset($identity)) {
			$fields = isset($options['fields']) ? $options['fields'] : '*';
			$q = array(
				$fields,
				'xxt_site_member',
				"schema_id={$schema->id} and forbidden='N' and identity='$identity'",
			);
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
			'xxt_member',
			"mpid='$siteId' and forbidden='N' and (mobile='$identity' or email='$identity')",
		);
		$members = $this->query_objs_ss($q);

		return $members;
	}
}