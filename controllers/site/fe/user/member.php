<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点自定义用户信息
 */
class member extends \site\fe\base {
	/**
	 * 打开填写站点自定义用户页面
	 *
	 * @param string $site
	 * @param int $schema 自定义信息的id
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
	public function index_action($site, $schema) {
		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->requireSnsOAuth($site);
		}
		\TPL::output('/site/fe/user/member');
		exit;
	}
	/**
	 * 获得页面定义
	 */
	public function pageGet_action($site, $schema) {
		$params = array();

		$schema = $this->model('site\user\memberschema')->byId($schema);
		if ($schema === false) {
			return new \ResponseError('指定的自定义用户定义不存在');
		}
		$params['schema'] = $schema;
		/* 属性定义 */
		$attrs = array(
			'mobile' => $schema->attr_mobile,
			'email' => $schema->attr_email,
			'name' => $schema->attr_name,
			'extattrs' => $schema->extattr,
		);
		$params['attrs'] = $attrs;

		/* 已填写的用户信息 */
		$params['user'] = $this->who;

		return new \ResponseData($params);
	}
	/**
	 * 提交站点自定义用户信息
	 * 站点自定义用户信息只能绑定到注册主访客账号
	 *
	 * @param int $schema 自定义用户信息定义的id
	 *
	 * 支持记录的内容
	 * 姓名，手机号，邮箱
	 * 每项内容的设置
	 * 隐藏(0)，必填(1)，唯一(2)，不可更改(3)，需要验证(4)，身份标识(5)
	 * 0:hidden,1:mandatory,2:unique,3:immuatable,4:verification,5:identity
	 *
	 */
	public function doAuth_action($schema) {
		$member = $this->getPostJson();
		if (!empty($member->id)) {
			return new \ResponseError('自定义用户信息已经存在，不能重复创建');
		}

		$modelSiteUser = $this->model('site\user\account');
		$cookieUser = $this->who;
		$siteUser = $modelSiteUser->byId($cookieUser->uid);
		if ($siteUser === false || $siteUser->is_reg_primary !== 'Y') {
			return new \ResponseError('请登录后再指定用户信息');
		}

		$modelMem = $this->model('site\user\member');

		$schema = $this->model('site\user\memberschema')->byId($schema, 'id,attr_mobile,attr_email,attr_name,extattr,auto_verified');
		$member->siteid = $this->siteId;
		$member->schema_id = $schema->id;
		/**
		 * check auth data.
		 */
		if ($errMsg = $modelMem->rejectAuth($member, $schema)) {
			return new \ParameterError($errMsg);
		}
		/**
		 * 用户的邮箱需要验证，将状态设置为等待验证的状态
		 */
		//$member->email_verified = ($schema->attr_email[4] === '1') ? 'N' : 'Y';
		/**
		 * todo 应该支持使用扩展属性作为唯一标识
		 */
		if ($schema->attr_mobile[5] === '1' && isset($member->mobile)) {
			/**
			 * 手机号作为唯一标识
			 */
			if ($schema->attr_mobile[4] === '1') {
				$mobile = $member->mobile;
				$mpa = $this->model('mp\mpaccount')->getApis($site);
				if ('yx' !== $mpa->mpsrc || 'yx' !== $this->getClientSrc()) {
					return new \ResponseError('目前仅支持在易信客户端中验证手机号');
				}

				if ('N' == $mpa->yx_checkmobile) {
					return new \ResponseError('仅支持在开通了手机验证接口的公众号中验证手机号');
				}
				$rst = $this->model('mpproxy/yx', $site)->mobile2Openid($mobile);
				if ($rst[0] === false) {
					//return new \ResponseError("验证手机号失败【{$rst[1]}】");
					return new \ResponseError("请输入注册易信时使用的手机号码");
				}
				if ($fan->openid !== $rst[1]->openid) {
					return new \ResponseError("您输入的手机号与注册易信用户时的提供手机号不一致");
				}
			}
			//$member->authed_identity = $member->mobile;
		} else {
			//return new \ResponseError('无法获得身份标识信息');
		}
		/* 验证状态 */
		$member->verified = $schema->auto_verified;
		/* 创建新的自定义用户 */
		$rst = $modelMem->create($this->siteId, $siteUser->uid, $schema, $member);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		$member = $rst[1];
		/* 绑定当前站点用户 */
		$modelWay = $this->model('site\fe\way');
		$modelWay->bindMember($this->siteId, $member);
		// log
		//$this->model('log')->writeMemberAuth($site, $siteUser->openid, $mid);
		/**
		 * 验证邮箱真实性
		 */
		//$attrs->attr_email[4] === '1' && $this->_sendVerifyEmail($site, $member->email);

		return new \ResponseData($member);
	}
	/**
	 * 重新进行用户身份验证
	 *
	 * @param int $schema 自定义用户信息定义的id
	 */
	public function doReauth_action($schema) {
		$modelSiteUser = $this->model('site\user\account');
		$cookieUser = $this->who;
		$siteUser = $modelSiteUser->byId($cookieUser->uid);
		if ($siteUser === false || $siteUser->is_reg_primary !== 'Y') {
			return new \ResponseError('请登录后再指定用户信息');
		}

		$schema = $this->model('site\user\memberschema')->byId($schema, 'id,attr_mobile,attr_email,attr_name,extattr');

		$member = $this->getPostJson();
		/* 检查数据合法性 */
		$modelMem = $this->model('site\user\member');
		if (false === ($found = $modelMem->findMember($member, $schema, false))) {
			return new \ParameterError('找不到匹配的认证用户');
		}
		if ($found->userid !== $siteUser->uid) {
			return new \ResponseError('指定的用户信息错误，和当前登录用户不一致');
		}

		/* 更新用户信息 */
		$modelMem->modify($this->siteId, $schema, $found->id, $member);
		$found = $modelMem->byId($found->id);
		/* 绑定当前站点用户 */
		$modelWay = $this->model('site\fe\way');
		$modelWay->bindMember($this->siteId, $found);

		return new \ResponseData($found);
	}
	/**
	 * 认证完成后的回调地址
	 * 进行用户身份绑定
	 * 生成cookie的唯一标识，这个值可以逆向解开
	 *
	 * $site
	 * $authid
	 */
	public function passed_action($site, $schema, $redirect = 'Y') {
		if ($target = $this->myGetCookie("_{$site}_mauth_t")) {
			/**
			 * 调用认证前记录的
			 */
			$this->mySetcookie("_{$site}_mauth_t", '');
			$target = $this->model()->encrypt($target, 'DECODE', $site);
			if ($redirect === 'Y') {
				$this->redirect($target);
			} else {
				return new \ResponseData($target);
			}
		} else {
			$schema = $this->model('site\user\memberschema')->byId($schema, 'passed_url');
			/**
			 * 认证成功后的缺省页面
			 */
			if (!empty($schema->passed_url)) {
				$target = $schema->passed_url;
			} else {
				$target = '/rest/site/fe/user?site=' . $site;
			}
			if ($redirect === 'Y') {
				$this->redirect($target);
			} else {
				return new \ResponseData($target);
			}
		}
	}
	/**
	 * 发送验证邮件
	 *
	 * $email 在一个公众账号内是唯一的
	 */
	private function _sendVerifyEmail($site, $email) {
		$mp = $this->model('mp\mpaccount')->byId($site, 'name');
		$subject = $mp->name . "用户身份验证";

		/**
		 * store token.
		 */
		$access_token = md5(uniqid($email) . mt_rand());
		$i['token'] = $access_token;
		$i['create_at'] = time();
		$i['data'] = json_encode(array($site, $email));
		$this->model()->insert('xxt_access_token', $i);

		$url = "http://" . $_SERVER['HTTP_HOST'];
		$url .= "/rest/member/auth/emailpassed?token=$access_token";

		$content = "<p>欢迎关注【" . $mp->name . "】</p>";
		$content .= "<p></p>";
		$content .= "<p>为了向您更好地供个性化服务，请点击下面的链接完成用户身份验证。</p>";
		$content .= "<p></p>";
		$content .= "<p><a href='$url'>完成身份验证</a></p>";

		if (true !== ($msg = $this->sendEmail($site, $subject, $content, $email))) {
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
	 * $site
	 *
	 */
	public function sendVerifyEmail_action($site) {
		// todo 需要指定认证接口
		$aAuthapis = array();
		$authapi = $this->model('user/authapi')->byUrl($site, '/rest/member/auth', 'authid,url');
		$aAuthapis[] = $authapi->authid;
		$members = $this->getCookieMember($site, $aAuthapis);
		if (empty($members)) {
			die('parameter invalid.');
		}

		//$member = $this->model('user/member')->byId($mid, 'email');
		$member = $members[0];

		$this->_sendVerifyEmail($site, $member->authed_identity);

		return new \ResponseData('success');
	}
	/**
	 * 返回组织机构组件
	 */
	public function memberSelector_action($id) {
		$addon = array(
			'js' => '/views/default/pl/fe/site/user/memberSelector.js',
			'view' => "/rest/site/fe/user/member/organization?site={$this->siteId}&id=$id",
		);
		return new \ResponseData($addon);
	}
	/**
	 *
	 */
	public function organization_action($id) {
		\TPL::output('/pl/fe/site/user/memberSelector');
		exit;
	}
	/**
	 * 检查指定用户是否在acl列表中
	 *
	 * $authid
	 * $uid
	 */
	public function checkAcl_action($schema, $uid) {
		$q = array(
			'*',
			'xxt_site_member',
			"schema_id=$schema and id='$uid' and forbidden='N'",
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
					if ($member->id === $acl->identity) {
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
	
}