<?php
namespace mp\user;

require_once dirname(dirname(__FILE__)) . '/mp_controller.php';
/**
 *
 */
class member extends \mp\mp_controller {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 * 设置页面参数
	 */
	public function view_action($path) {
		$features = $this->model('mp\mpaccount')->getFeature($this->mpid);
		\TPL::assign('can_member_card', $features->can_member_card);
		\TPL::assign('can_member_checkin', $features->can_member_checkin);
		parent::view_action($path);
	}
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/mp/user/members');
	}
	/**
	 * all members.
	 *
	 * $mpid
	 * $authid
	 * $dept
	 * $tag
	 *
	 * return member list|total|itemsSetting
	 */
	public function list_action($authid, $page = 1, $size = 30, $kw = null, $by = null, $dept = null, $tag = null, $contain = '') {
		$contain = explode(',', $contain);

		$w = "m.authapi_id=$authid and m.forbidden='N'";
		/**
		 * 子账号只能看到自己账号中数据
		 */
		$mpa = $this->getMpaccount();
		$mpa->asparent === 'N' && $w .= " and m.mpid='$this->mpid'";

		if (!empty($kw) && !empty($by)) {
			$w .= " and m.$by like '%$kw%'";
		}

		if (!empty($dept)) {
			$w .= " and m.depts like '%\"$dept\"%'";
		}

		if (!empty($tag)) {
			$w .= " and concat(',',m.tags,',') like '%,$tag,%'";
		}
		$result = array();
		$q = array(
			'm.*',
			'xxt_member m',
			$w,
		);
		$q2['o'] = 'm.create_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($members = $this->model()->query_objs_ss($q, $q2)) {
			$result['members'] = $members;
			if (in_array('total', $contain)) {
				$q[0] = 'count(*)';
				$total = (int) $this->model()->query_val_ss($q);
				$result['total'] = $total;
			}
		} else {
			$result['members'] = array();
			$result['total'] = 0;
		}
		if (in_array('memberAttrs', $contain)) {
			/**
			 * 0-5 注册用户的基本信息
			 */
			$setting = $this->model('user/authapi')->byId($authid, 'attr_mobile,attr_email,attr_name,extattr');
			/**
			 * 注册用户的其他属性，例如：会员卡号，会员积分
			 */
			//$features = $this->model('mp\mpaccount')->getFeature($this->mpid);
			//$setting->can_member_card = $features->can_member_card;
			//$setting->can_member_credits = $features->can_member_credits;
			/**
			 * 返回属性设置信息
			 */
			$result['attrs'] = $setting;
		}

		return new \ResponseData($result);
	}
	/**
	 * 直接创建一个认证用户
	 *
	 * $fid
	 * $authid
	 */
	public function create_action($fid, $authid) {
		$member = $this->getPostJson();

		$fan = $this->model('user/fans')->byId($fid);

		$attrs = $this->model('user/authapi')->byId($authid, 'attr_mobile,attr_email,attr_name,attr_password,extattr');
		/**
		 * 设置身份标识
		 */
		if ($attrs->attr_mobile[5] === '1') {
			$member->authed_identity = $member->mobile;
		} else if ($attrs->attr_email[5] === '1') {
			$member->authed_identity = $member->email;
		} else {
			return new \ResponseError('没有指定认证用户的唯一标识字段');
		}

		/**
		 * 设置缺省密码
		 */
		$attrs->attr_password[0] === '0' && $member->password = '123456';
		!isset($member->email_verified) && $member->email_verified = ($attrs->attr_email[4] === '1') ? 'N' : 'Y';

		$member->mpid = $this->mpid;
		$member->authapi_id = $authid;

		$rst = $this->model('user/member')->create($fid, $member, $attrs);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		$mid = $rst[1];
		$member = $this->model('user/member')->byId($mid);

		return new \ResponseData($member);
	}
	/**
	 * 更新成员数据
	 */
	public function update_action($mid) {
		$member = $this->model('user/member')->byId($mid, 'authapi_id');
		$attrs = $this->model('user/authapi')->byId($member->authapi_id, 'attr_mobile,attr_email,attr_name,extattr');

		$data = $this->getPostJson();
		/**
		 * 基本属性
		 */
		$emailVerified = (isset($data->email_verified) && $data->email_verified === 'Y') ? 'Y' : 'N';
		$newMember = array(
			'mobile' => empty($data->mobile) ? '' : $data->mobile,
			'email' => empty($data->email) ? '' : $data->email,
			'name' => empty($data->name) ? '' : $data->name,
			'email_verified' => $emailVerified,
			'verified' => (isset($data->verified) && $data->verified === 'Y') ? 'Y' : 'N',
		);
		if ($attrs->attr_mobile[5] === '1') {
			$newMember['authed_identity'] = $data->mobile;
		} else if ($attrs->attr_email[5] === '1') {
			$newMember['authed_identity'] = $data->email;
		}

		/**
		 * 扩展属性
		 */
		if (!empty($attrs->extattr)) {
			$extdata = array();
			foreach ($attrs->extattr as $ea) {
				if (!empty($data->extattr->{$ea->id})) {
					$extdata[urlencode($ea->id)] = urlencode($data->extattr->{$ea->id});
				} else {
					$extdata[urlencode($ea->id)] = '';
				}

			}
			$newMember['extattr'] = urldecode(json_encode($extdata));
		}

		$rst = $this->model()->update(
			'xxt_member',
			$newMember,
			"mpid='$this->mpid' and mid='$mid'"
		);
		/**
		 * 同步到企业号
		 */
		$mpapis = $this->model('mp\mpaccount')->getApis($this->mpid);
		if ($mpapis->qy_joined === 'Y') {
			$fan = $this->model('user/fans')->byMid($mid);
			$posted = array(
				'mobile' => empty($data->mobile) ? '' : $data->mobile,
				'email' => empty($data->email) ? '' : $data->email,
				'name' => empty($data->name) ? '' : $data->name,
			);
			if (!empty($data->extattr->position)) {
				$posted['position'] = $data->extattr->position;
			}

			if (!empty($attrs->extattr)) {
				$extdata = array();
				foreach ($attrs->extattr as $ea) {
					if ($ea->id === 'position') {
						continue;
					}

					$extdata[] = array(
						'name' => urlencode($ea->id),
						'value' => urlencode($data->extattr->{$ea->id}),
					);
				}
				$posted['extattr'] = array('attrs' => $extdata);
			}

			$rst = $this->model('mpproxy/qy', $this->mpid)->userUpdate($fan->openid, $posted);
			if ($rst[0] === false) {
				return new \ResponseError($rst[1]);
			}

		}

		return new \ResponseData($rst);
	}
	/**
	 * 跟新用户所属的部门
	 *
	 * $mid
	 *
	 * 返回更新后的部门字符串表示
	 */
	public function updateDepts_action($mid) {
		$u = $this->getPostJson();
		$depts = json_decode($u->depts);
		/**
		 * 同步到企业号通讯录
		 */
		$mpapis = $this->model('mp\mpaccount')->getApis($this->mpid);
		if ($mpapis->qy_joined === 'Y') {
			$rdepts = array();
			foreach ($depts as $dept) {
				$deptid = array_pop($dept);
				$extattr = $this->model('user/department')->byId($deptid, 'extattr');
				$extattr = json_decode($extattr->extattr);
				$rdepts[] = $extattr->id;
			}
			$fan = $this->model('user/fans')->byMid($mid);
			$rst = $this->model('mpproxy/qy', $this->mpid)->userUpdate($fan->openid, array('department' => $rdepts));
			if ($rst[0] === false) {
				return new \ResponseError($rst[1]);
			}

		}
		/**
		 * 更新本地数据
		 */
		$rst = $this->model()->update(
			'xxt_member',
			(array) $u,
			"mpid='$this->mpid' and mid='$mid'"
		);

		$sDepts = $this->model('user/department')->strUserDepts($u->depts);

		return new \ResponseData($sDepts);
	}
	/**
	 * 删除一个注册用户
	 *
	 * 不删除用户数据只是打标记
	 */
	public function remove_action($mid) {
		$rst = $this->model()->update(
			'xxt_member',
			array('forbidden' => 'Y'),
			"mid='$mid'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 添加的标签
	 *
	 * $fid
	 */
	public function addTags_action($mid) {
		$member = $this->model('user/member')->byId($mid);
		/**
		 * 指定的新标签
		 */
		$addeds = $this->getPostJson();
		/**
		 * 同步到企业号通讯录
		 */
		$mpapis = $this->model('mp\mpaccount')->getApis($this->mpid);
		if ($mpapis->qy_joined === 'Y') {
			foreach ($addeds as $add) {
				$extattr = $this->model('user/tag')->byId($add, 'extattr');
				$extattr = json_decode($extattr->extattr);
				$tagid = $extattr->tagid;
				$result = $this->model('mpproxy/qy', $this->mpid)->tagAddUser($tagid, array($member->ooid));
				if ($result[0] === false) {
					return new \ResponseError($result[1]);
				}

			}
		}
		/**
		 * 提交到本地
		 */
		$all = !empty($member->tags) ? array_merge(explode(',', $member->tags), $addeds) : $addeds;
		$all = implode(',', $all);
		$rst = $this->model()->update(
			'xxt_member',
			array('tags' => $all),
			"mpid='$this->mpid' and mid='$mid'"
		);

		return new \ResponseData($all);
	}
	/**
	 * 删除成员的标签
	 */
	public function delTags_action($mid, $tagid) {
		$member = $this->model('user/member')->byId($mid);
		/**
		 * 指定的新标签
		 */
		$mpapis = $this->model('mp\mpaccount')->getApis($this->mpid);
		if ($mpapis->qy_joined === 'Y') {
			$extattr = $this->model('user/tag')->byId($tagid, 'extattr');
			$extattr = json_decode($extattr->extattr);
			$exttagid = $extattr->tagid;
			$result = $this->model('mpproxy/qy', $this->mpid)->tagDeleteUser($exttagid, array($member->ooid));
			if ($result[0] === false) {
				return new \ResponseError($result[1]);
			}

		}
		/**
		 * 提交到本地
		 */
		$all = explode(',', $member->tags);
		$pos = array_search($tagid, $all);
		unset($all[$pos]);
		$all = implode(',', $all);
		$rst = $this->model()->update(
			'xxt_member',
			array('tags' => $all),
			"mpid='$this->mpid' and mid='$mid'"
		);

		return new \ResponseData($all);
	}
}
