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
	 * 创建一个自定义用户
	 *
	 * 自定义用户首先必须是站点用户
	 *
	 * $userid 站点用户id
	 * $data
	 * $schema
	 */
	public function create($userid, $data, $schema) {
		if (empty($userid)) {
			return array(false, '仅支持对站点用户进行认证');
		}

		is_array($data) && $data = (object) $data;

		$create_at = time();
		$data->userid = $userid;
		$data->create_at = $create_at;
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
	 *
	 * 要求认证用户首先必须是关注用户
	 *
	 * $fid 关注用户id
	 * $data
	 * $attrs
	 */
	public function create2($mpid, $authid, $fid, $member) {
		if (empty($mpid)) {
			return array(false, '没有指定MPID');
		}

		if (empty($authid)) {
			return array(false, '没有指定用户认证接口ID');
		}

		if (empty($fid)) {
			return array(false, '仅支持对关注用户进行认证');
		}

		$fan = \TMS_APP::M('user/fans')->byId($fid);
		$member->mpid = $mpid;
		$member->authapi_id = $authid;

		$attrs = \TMS_APP::M('user/authapi')->byId($authid, 'attr_mobile,attr_email,attr_name,attr_password,extattr');
		if ($errMsg = $this->rejectAuth($member, $attrs)) {
			return array(false, $errMsg);
		}

		is_array($member) && $member = (object) $member;
		/**
		 * 用户的邮箱需要验证，将状态设置为等待验证的状态
		 */
		$member->email_verified = ($attrs->attr_email[4] === '1') ? 'N' : 'Y';
		/**
		 * todo 应该支持使用扩展属性作为唯一标识
		 */
		if ($attrs->attr_mobile[5] === '1' && isset($member->mobile)) {
			if ($attrs->attr_mobile[4] === '1') {
				$mobile = $member->mobile;
				$mpa = \TMS_APP::M('mp\mpaccount')->getApis($mpid);
				if ('yx' !== $mpa->mpsrc) {
					return array(false, '目前仅支持在易信客户端中验证手机号');
				}

				if ('N' == $mpa->yx_checkmobile) {
					return array(false, '仅支持在开通了手机验证接口的公众号中验证手机号');
				}

				$rst = \TMS_APP::M('mpproxy/yx', $mpid)->mobile2Openid($mobile);
				if ($rst[0] === false) {
					return array(false, "验证手机号失败【{$rst[1]}】");
				}

				if ($fan->openid !== $rst[1]->openid) {
					return array(false, "您输入的手机号与注册易信用户时的提供手机号不一致");
				}

			}
			$member->authed_identity = $member->mobile;
		} else if ($attrs->attr_email[5] === '1' && isset($member->email)) {
			$member->authed_identity = $member->email;
		} else {
			return array(false, '无法获得用户身份标识信息');
		}

		/**
		 * 处理访问口令
		 */
		/*if ($attrs->attr_password[0] === '0') {
			if (empty($member->password) || strlen($member->password) < 6)
			return array(false, '密码长度不符合要求');
			$salt = $this->gen_salt();
			$cpw = $this->compile_password($member->authed_identity, $member->password, $salt);
			$member->password = $cpw;
			$member->password_salt = $salt;
		*/

		$create_at = time();
		$mid = md5(uniqid() . $create_at); //member's id
		$member->mid = $mid;
		$member->fid = $fid;
		$member->openid = $fan->openid;
		$member->nickname = $fan->nickname;
		$member->create_at = $create_at;
		/**
		 * 扩展属性
		 */
		if (!empty($member->extattr)) {
			$extdata = array();
			foreach ($member->extattr as $ek => $ev) {
				$extdata[$ek] = urlencode($ev);
			}
			$member->extattr = urldecode(json_encode($extdata));
		} else {
			$member->extattr = '{}';
		}

		$this->insert('xxt_member', (array) $member, false);

		return array(true, $mid);
	}
	/**
	 * 更新认证用户
	 *
	 * 要求认证用户首先必须是关注用户
	 *
	 * $mpid
	 * $fid 关注用户id
	 * $data
	 * $attrs
	 */
	public function modify($mpid, $authid, $mid, $member) {
		if (empty($mpid)) {
			return array(false, '没有指定MPID');
		}
		if (empty($authid)) {
			return array(false, '没有指定用户认证接口ID');
		}
		if (empty($mid)) {
			return array(false, '没有指定认证用户MID');
		}
		$member->mid = $mid;
		$member->mpid = $mpid;
		$member->authapi_id = $authid;

		$attrs = \TMS_APP::M('user/authapi')->byId($authid, 'attr_mobile,attr_email,attr_name,attr_password,extattr');
		if ($errMsg = $this->rejectAuth($member, $attrs)) {
			return array(false, $errMsg);
		}

		is_array($member) && $member = (object) $member;
		/**
		 * 用户的邮箱需要验证，将状态设置为等待验证的状态
		 */
		$member->email_verified = ($attrs->attr_email[4] === '1') ? 'N' : 'Y';
		/**
		 * todo 应该支持使用扩展属性作为唯一标识
		 */
		if ($attrs->attr_mobile[5] === '1' && isset($member->mobile)) {
			if ($attrs->attr_mobile[4] === '1') {
				$mobile = $member->mobile;
				$mpa = \TMS_APP::M('mp\mpaccount')->getApis($mpid);
				if ('yx' !== $mpa->mpsrc) {
					return array(false, '目前仅支持在易信客户端中验证手机号');
				}
				if ('N' == $mpa->yx_checkmobile) {
					return array(false, '仅支持在开通了手机验证接口的公众号中验证手机号');
				}
				$rst = \TMS_APP::M('mpproxy/yx', $mpid)->mobile2Openid($mobile);
				if ($rst[0] === false) {
					return array(false, "验证手机号失败【{$rst[1]}】");
				}
				$fan = \TMS_APP::M('user/fans')->byMid($mid);
				if ($fan->openid !== $rst[1]->openid) {
					return array(false, "您输入的手机号与注册易信用户时的提供手机号不一致");
				}
			}
			$member->authed_identity = $member->mobile;
		} else if ($attrs->attr_email[5] === '1' && isset($member->email)) {
			$member->authed_identity = $member->email;
			//} else {
			//return array(false, '无法获得用户身份标识信息');
		}
		/**
		 * 处理访问口令
		 */
		/*if ($attrs->attr_password[0] === '0') {
			if (empty($member->password) || strlen($member->password) < 6)
			return array(false, '密码长度不符合要求');
			$salt = $this->gen_salt();
			$cpw = $this->compile_password($member->authed_identity, $member->password, $salt);
			$member->password = $cpw;
			$member->password_salt = $salt;
		*/
		/**
		 * 扩展属性
		 */
		if (!empty($member->extattr)) {
			$extdata = array();
			foreach ($member->extattr as $ek => $ev) {
				$extdata[$ek] = urlencode($ev);
			}
			$member->extattr = urldecode(json_encode($extdata));
		} else {
			$member->extattr = '{}';
		}

		$this->update('xxt_member', $member, "mid='$mid'");

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
	public function rejectAuth($member, $schema) {
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
				return '手机号已经存在，不允许重复登记！';
			}
		}

		return false;
	}
	/**
	 * 根据提交的认证信息，查找已经存在认证用户
	 *
	 * 要求认证用户必须关联一个关注用户
	 *
	 * $member
	 * $items array 用户认证信息定义
	 * 0:hidden,1:mandatory,2:unique,3:immuatable,4:verification,5:identity
	 */
	public function findMember($member, $attrs, $checkPassword = true) {
		if (isset($member->mobile) && $attrs->attr_mobile[5] === '1') {
			/**
			 * 手机号唯一
			 */
			$identity = $member->mobile;
		} else if (isset($member->email) && $attrs->attr_email[5] === '1') {
			/**
			 * 邮箱唯一
			 */
			$identity = $member->email;
		}
		if (isset($identity)) {
			$q = array(
				'mid,password,password_salt',
				'xxt_member',
				"authapi_id=$member->authapi_id and fid!='' and forbidden='N' and authed_identity='$identity'",
			);
			$found = $this->query_obj_ss($q);
			if (!empty($found)) {
				if ($checkPassword && $attrs->attr_password[0] === '0') {
					/**
					 * 检查口令
					 */
					$cpw = $this->compile_password($identity, $member->password, $found->password_salt);
					if ($cpw !== $found->password) {
						return false;
					}
				}
			}
		}

		return !empty($found) ? $found->mid : false;
	}
	/**
	 *
	 */
	public function &search($mpid, $identity, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_member',
			"mpid='$mpid' and forbidden='N' and (mobile='$identity' or email='$identity')",
		);
		$members = $this->query_objs_ss($q);

		return $members;
	}
}