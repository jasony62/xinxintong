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
		$oMschema = $this->model('site\user\memberschema')->byId($schema, ['fields' => 'id,siteid,title,valid,is_wx_fan,is_qy_fan']);
		if ($oMschema === false || $oMschema->valid === 'N') {
			die('访问的对象不存在或已不可用');
		}

		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->requireSnsOAuth($oMschema->siteid);
		}

		if ($oMschema->is_wx_fan === 'Y' || $oMschema->is_qy_fan === 'Y') {
			$cookieUser = $this->who;
			$modelSiteUser = $this->model('site\user\account');
			$siteUser = $modelSiteUser->byId($cookieUser->uid);

			$oMschema2 = new \stdClass;
			$oMschema2->type = 'mschema';
			$oMschema2->id = $oMschema->id;

			/* 保存页面来源 */
			if (empty($this->myGetCookie("_{$oMschema->siteid}_mauth_t"))) {
				if (isset($_SERVER['HTTP_REFERER'])) {
					$referer = $_SERVER['HTTP_REFERER'];
					if (!empty($referer) && !in_array($referer, array('/'))) {
						if (false === strpos($referer, '/fe/user/member')) {
							$referer = $modelSiteUser->encrypt($referer, 'ENCODE', $oMschema->siteid);
							$this->mySetCookie("_{$oMschema->siteid}_mauth_t", $referer, time() + 600);
						}
					}
				}
			}

			if ($oMschema->is_wx_fan === 'Y') {
				$bFollowed = false;
				if (!empty($siteUser->wx_openid)) {
					$modelWx = $this->model('sns\wx');
					if (($wxConfig = $modelWx->bySite($oMschema->siteid)) && $wxConfig->joined === 'Y') {
						$snsSiteId = $oMschema->siteid;
					} else {
						$snsSiteId = 'platform';
					}
					$modelSnsUser = $this->model('sns\wx\fan');
					if ($modelSnsUser->isFollow($snsSiteId, $siteUser->wx_openid)) {
						$bFollowed = true;
					}
				}
				if (false === $bFollowed) {
					$rst = $this->model('sns\wx\call\qrcode')->createOneOff($oMschema->siteid, $oMschema2);
					if ($rst[0] === false) {
						$this->snsFollow($oMschema->siteid, 'wx', $oMschema);
					} else {
						$sceneId = $rst[1]->scene_id;
						$this->snsFollow($oMschema->siteid, 'wx', false, $sceneId);
					}
				}
			}
			if ($oMschema->is_qy_fan === 'Y') {
				if (empty($siteUser->qy_openid)) {
					$this->snsFollow($oMschema->siteid, 'qy');
				} else {
					$modelSnsUser = $this->model('sns\qy\fan');
					if (false === $modelSnsUser->isFollow($oMschema->siteid, $siteUser->qy_openid)) {
						$this->snsFollow($oMschema->siteid, 'qy', $oMschema2);
					}
				}
			}
		}
		\TPL::assign('title', $oMschema->title);
		\TPL::output('/site/fe/user/member');
		exit;
	}
	/**
	 * 获得用户在指定通讯录中的内容
	 */
	public function get_action($schema) {
		$oMschema = $this->model('site\user\memberschema')->byId($schema);
		if ($oMschema === false) {
			return new \ObjectNotFoundError();
		}
		/* 已填写的用户信息 */
		$modelMem = $this->model('site\user\member');
		$oUser = clone $this->who;
		$oUser->members = new \stdClass;
		if (!empty($oUser->unionid)) {
			$oRegAccount = $this->model('account')->byId($oUser->unionid, ['fields' => 'nickname,email']);
			$oUser->login = $oRegAccount;
			$oUser->login->uname = $oUser->login->email;
			unset($oUser->login->email);
		} else {
			/**
			 * 利用微信公众号信息判断是否用户已经注册
			 */
			if ($oMschema->is_wx_fan === 'Y') {
				if (!empty($oUser->sns->wx->openid)) {
					$oSiteUserByOpenid = $this->model('site\user\account')->byPrimaryOpenid($oMschema->siteid, 'wx', $oUser->sns->wx->openid);
					if ($oSiteUserByOpenid && !empty($oSiteUserByOpenid->unionid)) {
						$oRegAccount = $this->model('account')->byId($oSiteUserByOpenid->unionid, ['fields' => 'nickname,email']);
						$oUser->login = $oRegAccount;
						$oUser->login->uname = $oUser->login->email;
						$oUser->login->byWxOpenid = 'Y';
					}
				}
			}
		}
		$oMember = $modelMem->byUser($oUser->uid, ['schemas' => $schema]);
		if (count($oMember) > 1) {
			return new \ResponseError('数据错误，当前用户已经绑定多个联系人信息，请检查');
		}
		if (count($oMember) === 1) {
			$oMember = $oMember[0];
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

		$oMschema = $this->model('site\user\memberschema')->byId($schema, ['fields' => 'siteid,id,title,attr_mobile,attr_email,attr_name,ext_attrs,auto_verified,require_invite']);
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
			// 设置为通过或等待审核
			$oNewMember->verified = $oMschema->auto_verified === 'Y' ? 'Y' : 'P';
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

		// 如果通讯录被分组活动绑定，并且设置了自动更新用户，需要更新
		if ($oNewMember->verified === 'Y') {
			$modelMem->syncToGroupPlayer($oMschema->id, $oNewMember);
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

		$oMschema = $this->model('site\user\memberschema')->byId($schema, ['fields' => 'siteid,id,title,attr_mobile,attr_email,attr_name,ext_attrs,auto_verified']);
		if ($oMschema === false) {
			return new \ObjectNotFoundError();
		}

		$oMember = $this->getPostJson();
		/* 检查数据合法性。根据用户填写的自定义信息，找回数据。 */
		$modelMem = $this->model('site\user\member')->setOnlyWriteDbConn(true);
		if (false === ($oFound = $modelMem->findMember($oMember, $oMschema, false))) {
			return new \ParameterError('找不到匹配的联系人信息');
		}
		if ($oFound->userid !== $siteUser->uid) {
			return new \ResponseError('指定的用户信息错误，和当前登录用户不一致');
		}

		/* 更新用户信息 */
		$oMember->verified = $oMschema->auto_verified === 'Y' ? 'Y' : 'P';

		$oMember->identity = $oFound->identity;
		$rst = $modelMem->modify($oMschema, $oFound->id, $oMember);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		$oFound = $modelMem->byId($oFound->id);

		/* 绑定当前站点用户 */
		$modelWay = $this->model('site\fe\way');
		$modelWay->bindMember($oMschema->siteid, $oFound);

		// 如果通讯录被分组活动绑定，并且设置了自动更新用户，需要更新
		if ($oFound->verified === 'Y') {
			$modelMem->syncToGroupPlayer($oFound->schema_id, $oFound);
		}

		return new \ResponseData($oFound);
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
			$oMschema = $this->model('site\user\memberschema')->byId($schema, ['fields' => 'passed_url']);
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