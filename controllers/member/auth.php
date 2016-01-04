<?php
namespace member;

require_once dirname(dirname(__FILE__)) . '/member_base.php';
/**
 * 应用内用户身份认证
 *
 * 要求认证用户必须关联一个关注用户
 *
 * 参见:member_authapis
 */
class auth extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 打开认证页面
	 *
	 * $mpid
	 * $authid
	 * $openid
	 *
	 * 打开认证页，完成认证不一定意味着通过认证，可能还需要发送验证邮件或短信验证码
	 *
	 * 如果公众号支持OAuth，那么应该优先使用OAuth获得openid
	 * 只有在无法通过OAuth获得openid时才完全信任直接传入的openid
	 * 直接传入的openid不一定可靠
	 *
	 * 因为微信中OAuth不能在iframe中执行，所以需要在一开始进入页面的时候就执行OAuth，不能等到认证时再执行
	 * 所以只有在无法获得之前页面取得OAuth时，认证页面才做OAuth
	 *
	 */
	public function index_action($mpid, $authid, $openid = '', $code = null) {
		$this->doAuth($mpid, $code, $openid);
		\TPL::output('/member/auth');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($mpid, $authid) {
		$params = array();

		$api = $this->model('user/authapi')->byId($authid);
		$params['api'] = $api;
		/*属性信息*/
		$attrs = array(
			'mobile' => $api->attr_mobile,
			'email' => $api->attr_email,
			'name' => $api->attr_name,
			'password' => $api->attr_password,
			'extattrs' => $api->extattr,
		);
		$params['attrs'] = $attrs;
		/*已经认证过的用户身份*/
		$user = $this->getUser($mpid);
		if (!empty($user->openid)) {
			$member = $this->model('user/member')->byOpenid($mpid, $user->openid, '*', $authid);
			$params['authedMember'] = $member;
		}

		return new \ResponseData($params);
	}
	/**
	 * 提交用户身份认证信息
	 *
	 * $mpid running mpid.
	 * $authid
	 *
	 * 支持记录的内容
	 * 姓名，手机号，邮箱
	 * 每项内容的设置
	 * 隐藏(0)，必填(1)，唯一(2)，不可更改(3)，需要验证(4)，身份标识(5)
	 * 0:hidden,1:mandatory,2:unique,3:immuatable,4:verification,5:identity
	 *
	 */
	public function doAuth_action($mpid, $authid) {
		$user = $this->getUser($mpid, array('verbose' => array('fan' => 'Y')));
		if (empty($user->openid)) {
			return new \ResponseError('无法获得当前用户的openid');
		}

		$member = $this->getPostJson();

		if (isset($member->password2)) {
			unset($member->password2);
		}
		$member->mpid = $mpid;
		$member->authapi_id = $authid;
		/**
		 * get auth settings.
		 */
		$attrs = $this->model('user/authapi')->byId($authid, 'attr_mobile,attr_email,attr_name,attr_password,extattr');
		/**
		 * check auth data.
		 */
		if ($err_msg = $this->model('user/member')->rejectAuth($member, $attrs)) {
			return new \ParameterError($err_msg);
		}
		/**
		 * 用户的邮箱需要验证，将状态设置为等待验证的状态
		 */
		$member->email_verified = ($attrs->attr_email[4] === '1') ? 'N' : 'Y';
		/**
		 * todo 应该支持使用扩展属性作为唯一标识
		 */
		if ($attrs->attr_mobile[5] === '1' && isset($member->mobile)) {
			/**
			 * 手机号作为唯一标识
			 */
			if ($attrs->attr_mobile[4] === '1') {
				$mobile = $member->mobile;
				$mpa = $this->model('mp\mpaccount')->getApis($mpid);
				if ('yx' !== $mpa->mpsrc || 'yx' !== $this->getClientSrc()) {
					return new \ResponseError('目前仅支持在易信客户端中验证手机号');
				}

				if ('N' == $mpa->yx_checkmobile) {
					return new \ResponseError('仅支持在开通了手机验证接口的公众号中验证手机号');
				}
				$rst = $this->model('mpproxy/yx', $mpid)->mobile2Openid($mobile);
				if ($rst[0] === false) {
					//return new \ResponseError("验证手机号失败【{$rst[1]}】");
					return new \ResponseError("请输入注册易信时使用的手机号码");
				}
				if ($fan->openid !== $rst[1]->openid) {
					return new \ResponseError("您输入的手机号与注册易信用户时的提供手机号不一致");
				}
			}
			$member->authed_identity = $member->mobile;
		} else if ($attrs->attr_email[5] === '1' && isset($member->email)) {
			/**
			 * 邮箱作为唯一标识
			 */
			$member->authed_identity = $member->email;
		} else {
			return new \ResponseError('无法获得身份标识信息');
		}
		/**
		 * 添加新的认证用户
		 */
		$rst = $this->model('user/member')->create($user->fan->fid, $member, $attrs);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		$mid = $rst[1];
		// log
		$this->model('log')->writeMemberAuth($mpid, $user->openid, $mid);
		/**
		 * 验证邮箱真实性
		 */
		$attrs->attr_email[4] === '1' && $this->_sendVerifyEmail($mpid, $member->email);
		/**
		 * 在cookie中记录认证用户的身份信息
		 */
		$this->setCookie4Member($mpid, $authid, $mid);
		/**
		 * 记录和访客用户的关系
		 */
		$vid = $this->getVisitorId($mpid);
		$this->model()->update(
			'xxt_visitor',
			array('fid' => $user->fan->fid),
			"mpid='$mpid' and vid='$vid'"
		);

		return new \ResponseData($mid);
	}
	/**
	 * 重新进行用户身份验证
	 */
	public function doReauth_action($mpid, $authid) {
		$member = $this->getPostJson();

		$member->authapi_id = $authid;
		/**
		 * get auth settings.
		 */
		$attrs = $this->model('user/authapi')->byId($authid, 'attr_mobile,attr_email,attr_name,attr_password');
		if (false === ($mid = $this->model('user/member')->findMember($member, $attrs))) {
			return new \ParameterError('找不到匹配的认证用户');
		}
		/**
		 * 是否需要进行验证
		 * 目前仅支持邮箱验证
		 */
		if ($attrs->attr_email[4] === '1') {
			$this->model()->update(
				'xxt_member',
				array('verified' => 'N', 'email_verified' => 'N'),
				"mid='$mid'"
			);
			$this->_sendVerifyEmail($mpid, $member->email);
		}
		if ($attrs->attr_mobile[4] === '1') {
			$this->model()->update(
				'xxt_member',
				array('verified' => 'N', 'mobile_verified' => 'N'),
				"mid='$mid'"
			);
		}
		/**
		 * 在cookie中记录认证用户的身份信息
		 */
		$this->setCookie4Member($mpid, $authid, $mid);

		return new \ResponseData($mid);
	}
	/**
	 * 认证完成后的回调地址
	 * 进行用户身份绑定
	 * 生成cookie的唯一标识，这个值可以逆向解开
	 *
	 * $mpid
	 * $authid
	 * $mid 需要告知当前用户的唯一标识
	 */
	public function passed_action($mpid, $authid, $mid) {
		if ($target = $this->myGetCookie("_{$mpid}_mauth_t")) {
			/**
			 * 调用认证前记录的
			 */
			$this->mySetcookie("_{$mpid}_mauth_t", '');
			$target = $this->model()->encrypt($target, 'DECODE', $mpid);
			$this->redirect($target);
		} else {
			/**
			 * 认证成功后的缺省页面
			 */
			$params = array(
				'mpid' => $mpid,
				'authid' => $authid,
			);
			\TPL::assign('params', $params);
			$this->view_action('/member/authed');
		}
	}
	/**
	 * 发送验证邮件
	 *
	 * $email 在一个公众账号内是唯一的
	 */
	private function _sendVerifyEmail($mpid, $email) {
		$mp = $this->model('mp\mpaccount')->byId($mpid, 'name');
		$subject = $mp->name . "用户身份验证";

		/**
		 * store token.
		 */
		$access_token = md5(uniqid($email) . mt_rand());
		$i['token'] = $access_token;
		$i['create_at'] = time();
		$i['data'] = json_encode(array($mpid, $email));
		$this->model()->insert('xxt_access_token', $i);

		$url = "http://" . $_SERVER['HTTP_HOST'];
		$url .= "/rest/member/auth/emailpassed?token=$access_token";

		$content = "<p>欢迎关注【" . $mp->name . "】</p>";
		$content .= "<p></p>";
		$content .= "<p>为了向您更好地供个性化服务，请点击下面的链接完成用户身份验证。</p>";
		$content .= "<p></p>";
		$content .= "<p><a href='$url'>完成身份验证</a></p>";

		if (true !== ($msg = $this->sendEmail($mpid, $subject, $content, $email))) {
			return $msg;
		}

		return true;
	}
	/**
	 * 绑定的邮件是否已经通过验证
	 *
	 * //todo 认证信息真实性验证
	 */
	public function emailpassed_action($token) {
		$q = array('data', 'xxt_access_token', "token='$token'");
		$data = $this->model()->query_val_ss($q);
		if ($data === false) {
			die('非法访问被拒绝！');
		}

		$data = json_decode($data);
		/**
		 * update user state.
		 */
		$u = array();
		$u['verified'] = 'Y';
		$u['email_verified'] = 'Y';
		$this->model()->update(
			'xxt_member',
			$u,
			"mpid='{$data[0]}' and forbidden='N' and email='{$data[1]}'"
		);
		/**
		 * remove token.
		 */
		$this->model()->delete('xxt_access_token', "token='$token'");

		// todo 邮件验证的信息应该允许定制
		\TPL::output('emailpassed');
	}
	/**
	 * 给当前用户发送验证邮件
	 * 当前用户的信息通过cookie获取
	 *
	 * $mpid
	 *
	 */
	public function sendVerifyEmail_action($mpid) {
		// todo 需要指定认证接口
		$aAuthapis = array();
		$authapi = $this->model('user/authapi')->byUrl($mpid, '/rest/member/auth', 'authid,url');
		$aAuthapis[] = $authapi->authid;
		$members = $this->getCookieMember($mpid, $aAuthapis);
		if (empty($members)) {
			die('parameter invalid.');
		}

		//$member = $this->model('user/member')->byId($mid, 'email');
		$member = $members[0];

		$this->_sendVerifyEmail($mpid, $member->authed_identity);

		return new \ResponseData('success');
	}
	/**
	 * 返回组织机构组件
	 */
	public function memberSelector_action($authid) {
		$addon = array(
			'js' => '/views/default/member/memberSelector.js',
			'view' => "/rest/member/auth/organization?authid=$authid",
		);
		return new \ResponseData($addon);
	}
	/**
	 *
	 */
	public function organization_action($authid) {
		$this->view_action('/member/memberSelector');
	}
	/**
	 * 检查指定用户是否在acl列表中
	 *
	 * $authid
	 * $uid
	 */
	public function checkAcl_action($authid, $uid) {
		$q = array(
			'*',
			'xxt_member',
			"authapi_id=$authid and authed_identity='$uid' and forbidden='N'",
		);
		$members = $this->model()->query_objs_ss($q);
		if (empty($members)) {
			return new \ResponseError('指定的认证用户不存在');
		}

		$acls = $this->getPostJson();
		foreach ($members as $member) {
			foreach ($acls as $acl) {
				switch ($acl->idsrc) {
				case 'D':
					$depts = json_decode($member->depts);
					if (!empty($depts)) {
						$aDepts = array();
						foreach ($depts as $ds) {
							$aDepts = array_merge($aDepts, $ds);
						}

						if (in_array($acl->identity, $aDepts)) {
							return new \ResponseData('passed');
						}

					}
					break;
				case 'T':
					$aMemberTags = explode(',', $member->tags);
					$aIdentity = explode(',', $acl->identity);
					$aIntersect = array_intersect($aIdentity, $aMemberTags);
					if (count($aIntersect) === count($aIdentity)) {
						return new \ResponseData('passed');
					}

					break;
				case 'M':
					if ($member->mid === $acl->identity) {
						return new \ResponseData('passed');
					}

					break;
				case 'DT':
					$depts = json_decode($member->depts);
					if (!empty($depts)) {
						$aMemberDepts = array();
						foreach ($depts as $ds) {
							$aMemberDepts = array_merge($aMemberDepts, $ds);
						}

						$aMemberTags = explode(',', $member->tags);
						/**
						 * 第一个是部门，后面是标签，需要同时匹配
						 */
						$aIdentity = explode(',', $acl->identity);
						if (in_array($aIdentity[0], $aMemberDepts)) {
							unset($aIdentity[0]);
							$aIntersect = array_intersect($aIdentity, $aMemberTags);
							if (count($aIntersect) === count($aIdentity)) {
								return new \ResponseData('passed');
							}

						}
					}
					break;
				}
			}
		}

		return new \ResponseError('no matched');
	}
	/**
	 * 将内部组织结构数据全量导入到企业号通讯录
	 *
	 * $mpid
	 * $authid
	 */
	public function import2Qy_action($mpid, $authid) {
		return new \ResponseError('not support');
	}
	/**
	 * 将内部组织结构数据增量导入到企业号通讯录
	 *
	 * $mpid
	 * $authid
	 */
	public function sync2Qy_action($mpid, $authid) {
		return new \ResponseError('not support');
	}
	/**
	 * 从企业号通讯录同步用户数据
	 *
	 * $authid
	 * $pdid 父部门id
	 *
	 */
	public function syncFromQy_action($mpid, $authid, $pdid = 1) {
		if (!($authapi = $this->model('user/authapi')->byId($authid))) {
			return new \ResponseError('未设置内置认证接口，无法同步通讯录');
		}

		$mp = $this->model('mp\mpaccount')->byId($mpid, 'qy_joined');
		if (!$mp && $mp->qy_joined !== 'Y') {
			return new \ResponseError('未与企业号连接，无法同步通讯录');
		}
		$timestamp = time(); // 进行同步操作的时间戳
		$qyproxy = $this->model('mpproxy/qy', $mpid);
		$model = $this->model();
		$modelDept = $this->model('user/department');
		/**
		 * 同步部门数据
		 */
		$mapDeptR2L = array(); // 部门的远程ID和本地ID的映射
		$result = $qyproxy->departmentList($pdid);
		if ($result[0] === false) {
			return new \ResponseError($result[1]);
		}

		$rootDepts = array(); // 根部门
		$rdepts = $result[1]->department;
		foreach ($rdepts as $rdept) {
			$pid = $rdept->parentid == 0 ? 0 : isset($mapDeptR2L[$rdept->parentid]['id']) ? $mapDeptR2L[$rdept->parentid]['id'] : 0;
			if ($pid === 0) {
				$rootDepts[] = $rdept;
			}
			$rdeptName = $rdept->name;
			unset($rdept->name);
			/**
			 * 如果已经同步过，更新数据和时间戳；否则创建新本地数据
			 */
			$q = array(
				'id,fullpath,sync_at',
				'xxt_member_department',
				"mpid='$mpid' and extattr like '%\"id\":$rdept->id,%'",
			);
			if (!($ldept = $model->query_obj_ss($q))) {
				$ldept = $modelDept->create($mpid, $authid, $pid, null);
			}
			$model->update(
				'xxt_member_department',
				array(
					'pid' => $pid,
					'sync_at' => $timestamp,
					'name' => $rdeptName,
					'extattr' => json_encode($rdept),
				),
				"mpid='$mpid' and id=$ldept->id"
			);
			$mapDeptR2L[$rdept->id] = array('id' => $ldept->id, 'path' => $ldept->fullpath);
		}
		/**
		 * 清空同步不存在的部门
		 */
		$this->model()->delete(
			'xxt_member_department',
			"mpid='$mpid' and sync_at<" . $timestamp
		);
		/**
		 * 同步部门下的用户
		 */
		foreach ($rootDepts as $rootDept) {
			$result = $qyproxy->userList($rootDept->id, 1);
			if ($result[0] === false) {
				return new \ResponseError($result[1]);
			}
			$users = $result[1]->userlist;
			foreach ($users as $user) {
				$q = array(
					'mid,fid,sync_at',
					'xxt_member',
					"mpid='$mpid' and openid='$user->userid'",
				);
				if (!($luser = $model->query_obj_ss($q))) {
					$this->createQyFan($mpid, $user, $authid, $timestamp, $mapDeptR2L);
				} else if ($luser->sync_at < $timestamp) {
					$this->updateQyFan($mpid, $luser->fid, $user, $authid, $timestamp, $mapDeptR2L);
				}
			}
		}
		/**
		 * 清空没有同步的粉丝数据
		 */
		$model->delete(
			'xxt_fans',
			"mpid='$mpid' and fid in (select fid from xxt_member where mpid='$mpid' and sync_at<" . $timestamp . ")"
		);
		/**
		 * 清空没有同步的成员数据
		 */
		$model->delete(
			'xxt_member',
			"mpid='$mpid' and sync_at<" . $timestamp
		);
		/**
		 * 同步标签
		 */
		$result = $qyproxy->tagList();
		if ($result[0] === false) {
			return new \ResponseError($result[1]);
		}
		$tags = $result[1]->taglist;
		foreach ($tags as $tag) {
			$q = array(
				'id,sync_at',
				'xxt_member_tag',
				"mpid='$mpid' and extattr like '{\"tagid\":$tag->tagid}%'",
			);
			if (!($ltag = $model->query_obj_ss($q))) {
				$t = array(
					'mpid' => $mpid,
					'sync_at' => $timestamp,
					'name' => $tag->tagname,
					'authapi_id' => $authid,
					'extattr' => json_encode(array('tagid' => $tag->tagid)),
				);
				$memberTagId = $model->insert('xxt_member_tag', $t, true);
			} else {
				$memberTagId = $ltag->id;
				$t = array(
					'sync_at' => $timestamp,
					'name' => $tag->tagname,
				);
				$this->model()->update(
					'xxt_member_tag',
					$t,
					"mpid='$mpid' and id=$ltag->id"
				);
			}
			/**
			 * 建立标签和成员、部门的关联
			 */
			$result = $qyproxy->tagUserList($tag->tagid);
			if ($result[0] === false) {
				return new \ResponseError($result[1]);
			}
			$tagUsers = $result[1]->userlist;
			foreach ($tagUsers as $user) {
				$q = array(
					'sync_at,tags',
					'xxt_member',
					"mpid='$mpid' and openid='$user->userid'",
				);
				if ($memeber = $model->query_obj_ss($q)) {
					if (empty($memeber->tags)) {
						$memeber->tags = $memberTagId;
					} else {
						$memeber->tags .= ',' . $memberTagId;
					}
					$model->update(
						'xxt_member',
						array('tags' => $memeber->tags),
						"mpid='$mpid' and openid='$user->userid'"
					);
				}
			}
		}
		/**
		 * 清空已有标签
		 */
		$model->delete(
			'xxt_member_tag',
			"mpid='$mpid' and sync_at<" . $timestamp
		);

		$model->update(
			'xxt_member_authapi',
			array('sync_from_qy_at' => time()),
			"authid=$authid"
		);

		$rst = array(
			isset($rdepts) ? count($rdepts) : 0,
			isset($users) ? count($users) : 0,
			isset($tags) ? count($tags) : 0,
			$timestamp,
		);

		return new \ResponseData($rst);
	}
}