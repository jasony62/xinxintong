<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 团队联系人用户
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
	public function index_action($schema) {
		$oSchema = $this->model('site\user\memberschema')->byId($schema, 'siteid,valid,is_wx_fan,is_yx_fan');
		if ($oSchema === false || $oSchema->valid === 'N') {
			return new \ObjectNotFoundError();
		}

		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->requireSnsOAuth($oSchema->siteid);
		}

		if ($oSchema->is_wx_fan === 'Y' || $oSchema->is_qy_fan === 'Y' || $oSchema->is_yx_fan === 'Y') {
			$cookieUser = $this->who;
			$modelSiteUser = $this->model('site\user\account');
			$siteUser = $modelSiteUser->byId($cookieUser->uid);

			$oMschema2 = new \stdClass;
			$oMschema2->type = 'mschema';
			$oMschema2->id = $schema;

			/* 保存页面来源 */
			if (empty($this->myGetCookie("_{$oSchema->siteid}_mauth_t"))) {
				if (isset($_SERVER['HTTP_REFERER'])) {
					$referer = $_SERVER['HTTP_REFERER'];
					if (!empty($referer) && !in_array($referer, array('/'))) {
						if (false === strpos($referer, '/fe/user/member')) {
							$referer = $modelSiteUser->encrypt($referer, 'ENCODE', $oSchema->siteid);
							$this->mySetCookie("_{$oSchema->siteid}_mauth_t", $referer, time() + 600);
						}
					}
				}
			}

			if ($oSchema->is_wx_fan === 'Y') {
				if (empty($siteUser->wx_openid)) {
					$this->snsFollow($oSchema->siteid, 'wx', $oMschema2);
				} else {
					$modelWx = $this->model('sns\wx');
					if (($wxConfig = $modelWx->bySite($oSchema->siteid)) && $wxConfig->joined === 'Y') {
						$snsSiteId = $oSchema->siteid;
					} else {
						$snsSiteId = 'platform';
					}
					$modelSnsUser = $this->model('sns\wx\fan');
					if (false === $modelSnsUser->isFollow($snsSiteId, $siteUser->wx_openid)) {
						$this->snsFollow($snsSiteId, 'wx', $oMschema2);
					}
				}
			}
			if ($oSchema->is_qy_fan === 'Y') {
				if (empty($siteUser->qy_openid)) {
					$this->snsFollow($oSchema->siteid, 'qy');
				} else {
					$modelSnsUser = $this->model('sns\qy\fan');
					if (false === $modelSnsUser->isFollow($oSchema->siteid, $siteUser->qy_openid)) {
						$this->snsFollow($oSchema->siteid, 'qy', $oMschema2);
					}
				}
			}
			if ($oSchema->is_yx_fan === 'Y') {
				if (empty($siteUser->yx_openid)) {
					$this->snsFollow($oSchema->siteid, 'yx');
				} else {
					$modelSnsUser = $this->model('sns\yx\fan');
					if (false === $modelSnsUser->isFollow($oSchema->siteid, $siteUser->yx_openid)) {
						$this->snsFollow($oSchema->siteid, 'yx', $oMschema2);
					}
				}
			}
		}

		\TPL::output('/site/fe/user/member');
		exit;
	}
	/**
	 * 获得自定义用户的定义
	 */
	public function schemaGet_action($site, $schema) {
		$params = array();

		$oSchema = $this->model('site\user\memberschema')->byId($schema);
		if ($oSchema === false) {
			return new \ResponseError('指定的自定义用户定义不存在');
		}
		$params['schema'] = $oSchema;
		/* 属性定义 */
		$attrs = [
			'mobile' => $oSchema->attr_mobile,
			'email' => $oSchema->attr_email,
			'name' => $oSchema->attr_name,
			'extattrs' => $oSchema->extattr,
		];
		$params['attrs'] = $attrs;

		/* 已填写的用户信息 */
		$modelMem = $this->model('site\user\member');
		$oUser = $this->who;
		if (isset($oUser->members) && isset($oUser->members->{$schema})) {
			unset($oUser->members->{$schema});
		}
		$oMember = $modelMem->byUser($oUser->uid, ['schemas' => $schema]);
		if (count($oMember) > 1) {
			return new \ResponseError('数据错误，当前用户已经绑定多个联系人信息，请检查');
		}
		if (count($oMember) === 1) {
			$oMember = $oMember[0];
			if (!isset($oUser->members)) {
				$oUser->members = new \stdClass;
			}
			$oUser->members->{$schema} = $oMember;
		}
		$params['user'] = $oUser;

		return new \ResponseData($params);
	}
	/**
	 * 获得自定义用户的定义
	 */
	public function get_action($site, $schema) {

		$oSchema = $this->model('site\user\memberschema')->byId($schema);
		if ($oSchema === false) {
			return new \ResponseError('指定的自定义用户定义不存在');
		}
		/* 已填写的用户信息 */
		$modelMem = $this->model('site\user\member');
		$oUser = $this->who;
		$oMember = $modelMem->byUser($oUser->uid, ['schemas' => $schema]);
		if (count($oMember) > 1) {
			return new \ResponseError('数据错误，当前用户已经绑定多个联系人信息，请检查');
		}
		if (count($oMember) === 1) {
			$oMember = $oMember[0];
			if (!isset($oUser->members)) {
				$oUser->members = new \stdClass;
			}
			$oUser->members->{$schema} = $oMember;
		}

		return new \ResponseData($oUser);
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
		$oNewMember = $this->getPostJson();
		if (!empty($oNewMember->id)) {
			return new \ResponseError('已经有对应通讯录联系人，不能重复创建');
		}

		$oMschema = $this->model('site\user\memberschema')->byId($schema, 'siteid,id,title,attr_mobile,attr_email,attr_name,extattr,auto_verified,require_invite');
		if ($oMschema === false) {
			return new \ObjectNotFoundError();
		}

		if ($oMschema->require_invite === 'Y') {
			if (empty($oNewMember->invite_code)) {
				return new \ResponseError('请提供邀请码');
			}
			if (strlen($oNewMember->invite_code) !== 6) {
				return new \ResponseError('请提供有效的邀请码');
			}
			if (false === $this->model('site\user\memberinvite')->useCode($oMschema->id, $oNewMember->invite_code)) {
				return new \ResponseError('邀请码不存在或者已经失效');
			}
		}

		$modelSiteUser = $this->model('site\user\account');
		$cookieUser = $this->who;
		$siteUser = $modelSiteUser->byId($cookieUser->uid);
		if ($siteUser === false || empty($siteUser->unionid)) {
			return new \ResponseError('请注册或登录后再填写通讯录联系人信息');
		}

		$modelWay = $this->model('site\fe\way');
		$modelMem = $this->model('site\user\member');
		$bindMembers = $modelMem->byUser($cookieUser->uid, ['schemas' => $oMschema->id]);
		if (count($bindMembers) > 1) {
			throw new \Exception('数据错误：1个用户绑定了同一个通讯录中的多个联系人信息');
		} else if (count($bindMembers) === 1) {
			$oNewMember = $bindMembers[0];
			$modelWay->bindMember($oMschema->siteid, $oNewMember);
			return new \ResponseError('用户信息已经存在，不能重复创建');
		}

		/* 给当前用户创建自定义用户信息 */
		$oNewMember->siteid = $oMschema->siteid;
		$oNewMember->schema_id = $oMschema->id;
		$oNewMember->unionid = $siteUser->unionid;
		/* check auth data */
		if ($errMsg = $modelMem->rejectAuth($oNewMember, $oMschema)) {
			return new \ParameterError($errMsg);
		}
		/* 验证状态 */
		if ($oMschema->require_invite === 'Y' && isset($oNewMember->invite_code)) {
			$oNewMember->verified = 'Y';
		} else {
			$oNewMember->verified = $oMschema->auto_verified;
		}
		/* 创建新的自定义用户 */
		$rst = $modelMem->create($siteUser->uid, $oMschema, $oNewMember);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		if ($oNewMember = $rst[1]) {
			/* 绑定当前站点用户 */
			$cookieUser = $modelWay->bindMember($oMschema->siteid, $oNewMember);
		} else {
			throw new \Exception('程序异常：无法创建自定义用户');
		}

		//记录站点活跃数
		$this->model('site\active')->add($oNewMember->siteid, $cookieUser, 0, 'creatMember');

		return new \ResponseData($oNewMember);
	}
	/**
	 * 重新进行用户身份验证
	 *
	 * @param int $schema 自定义用户信息定义的id
	 *
	 */
	public function doReauth_action($schema) {
		$modelSiteUser = $this->model('site\user\account');
		$cookieUser = $this->who;
		$siteUser = $modelSiteUser->byId($cookieUser->uid);
		if ($siteUser === false || $siteUser->is_reg_primary !== 'Y') {
			return new \ResponseError('请登录后再指定用户信息');
		}

		$oMschema = $this->model('site\user\memberschema')->byId($schema, 'siteid,id,title,attr_mobile,attr_email,attr_name,extattr,auto_verified');
		if ($oMschema === false) {
			return new \ObjectNotFoundError();
		}

		$member = $this->getPostJson();
		/* 检查数据合法性。根据用户填写的自定义信息，找回数据。 */
		$modelMem = $this->model('site\user\member');
		if (false === ($found = $modelMem->findMember($member, $oMschema, false))) {
			return new \ParameterError('找不到匹配的联系人信息');
		}
		if ($found->userid !== $siteUser->uid) {
			return new \ResponseError('指定的用户信息错误，和当前登录用户不一致');
		}

		/* 更新用户信息 */
		$member->verified = $found->verified;
		$member->identity = $found->identity;
		$rst = $modelMem->modify($oMschema, $found->id, $member);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		$found = $modelMem->byId($found->id);

		/* 绑定当前站点用户 */
		$modelWay = $this->model('site\fe\way');
		$modelWay->bindMember($oMschema->siteid, $found);

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
			$oMschema = $this->model('site\user\memberschema')->byId($schema, 'passed_url');
			/**
			 * 认证成功后的缺省页面
			 */
			if (!empty($oMschema->passed_url)) {
				$target = $oMschema->passed_url;
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

		$url = "http://" . APP_HTTP_HOST;
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