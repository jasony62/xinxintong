<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 自定义用户信息
 */
class member extends \site\fe\base {
	/**
	 * 打开认证页面
	 *
	 * $site
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
	public function index_action($site, $schema) {
		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->_requireSnsOAuth($site);
		}
		\TPL::output('/site/fe/user/member');
		exit;
	}
	/**
	 * 进入选择认证接口页
	 *
	 * 如果被访问的页面支持多个认证接口，要求用户选择一种认证接口
	 */
	public function schemaOptions_action($site, $schema, $userid = null) {
		$params = "siteid=$site";

		$modelSch = $this->model('site\user\memberschema');
		$aMemberSchemas = array();
		$aSchemaIds = explode(',', $schema);
		foreach ($aSchemaIds as $schemaId) {
			$schema = $modelSch->byId($schemaId, 'id,name,url');
			$schema->url .= "?siteid={$site}&schema={$schemaId}";
			$aMemberSchemas[] = $schema;
		}
		\TPL::assign('schemas', $aMemberSchemas);
		\TPL::output('/site/fe/user/schemaoptions');
		exit;
	}
	/**
	 * 检查是否需要第三方社交帐号认证
	 * 检查条件：
	 * 0、应用是否设置了需要认证
	 * 1、站点是否绑定了第三方社交帐号认证
	 * 2、平台是否绑定了第三方社交帐号认证
	 * 3、用户客户端是否可以发起认证
	 *
	 * @param string $site
	 */
	private function _requireSnsOAuth($siteid) {
		if ($this->userAgent() === 'wx') {
			if (!isset($this->who->sns->wx)) {
				if ($wxConfig = $this->model('sns\wx')->bySite($siteid)) {
					if ($wxConfig->joined === 'Y') {
						$this->snsOAuth($wxConfig, 'wx');
					}
				}
			}
			if (!isset($this->who->sns->qy)) {
				if ($qyConfig = $this->model('sns\qy')->bySite($siteid)) {
					if ($qyConfig->joined === 'Y') {
						$this->snsOAuth($qyConfig, 'qy');
					}
				}
			}
		} else if ($this->userAgent() === 'yx') {
			if (!isset($this->who->sns->yx)) {
				if ($yxConfig = $this->model('sns\yx')->bySite($siteid)) {
					if ($yxConfig->joined === 'Y') {
						$this->snsOAuth($yxConfig, 'yx');
					}
				}
			}
		}

		return false;
	}
	/**
	 *
	 */
	public function pageGet_action($site, $schema) {
		$params = array();

		$schema = $this->model('site\user\memberschema')->byId($schema);
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
		//if (isset($this->who->members) && isset($this->who->members->{$schema->id})) {
		//	$params['member'] = $this->who->members->{$schema->id};
		//}

		return new \ResponseData($params);
	}
	/**
	 * 提交用户身份认证信息
	 *
	 * $site running mpid.
	 * $authid
	 *
	 * 支持记录的内容
	 * 姓名，手机号，邮箱
	 * 每项内容的设置
	 * 隐藏(0)，必填(1)，唯一(2)，不可更改(3)，需要验证(4)，身份标识(5)
	 * 0:hidden,1:mandatory,2:unique,3:immuatable,4:verification,5:identity
	 *
	 */
	public function doAuth_action($schema) {
		$schema = $this->model('site\user\memberschema')->byId($schema, 'id,attr_mobile,attr_email,attr_name,extattr');

		$member = $this->getPostJson();
		$member->siteid = $this->siteId;
		$member->schema_id = $schema->id;
		/**
		 * check auth data.
		 */
		if ($errMsg = $this->model('site\user\member')->rejectAuth($member, $schema)) {
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
		$member->verified = 'Y';
		/* 创建新的自定义用户 */
		$user = $this->who;
		$rst = $this->model('site\user\member')->create($this->siteId, $user->uid, $schema, $member);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		$member = $rst[1];
		/* 绑定当前站点用户 */
		$modelWay = $this->model('site\fe\way');
		$modelWay->bindMember($this->siteId, $member);
		// log
		//$this->model('log')->writeMemberAuth($site, $user->openid, $mid);
		/**
		 * 验证邮箱真实性
		 */
		//$attrs->attr_email[4] === '1' && $this->_sendVerifyEmail($site, $member->email);
		/**
		 * 在cookie中记录认证用户的身份信息
		 */
		//$this->setCookie4Member($site, $authid, $mid);

		return new \ResponseData($member);
	}
	/**
	 * 重新进行用户身份验证
	 */
	public function doReauth_action($schema) {
		$schema = $this->model('site\user\memberschema')->byId($schema, 'id,attr_mobile,attr_email,attr_name,extattr');

		$member = $this->getPostJson();
		/* 检查数据合法性 */
		$modelMem = $this->model('site\user\member');
		if (false === ($found = $modelMem->findMember($member, $schema, false))) {
			return new \ParameterError('找不到匹配的认证用户');
		}
		/* 更新用户信息 */
		$modelMem->modify($this->siteId, $schema, $found->id, $member);
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
	/**
	 * 将内部组织结构数据全量导入到企业号通讯录
	 *
	 * $site
	 * $authid
	 */
	public function import2Qy_action($site, $authid) {
		return new \ResponseError('not support');
	}
	/**
	 * 将内部组织结构数据增量导入到企业号通讯录
	 *
	 * $site
	 * $authid
	 */
	public function sync2Qy_action($site, $authid) {
		return new \ResponseError('not support');
	}
	/**
	 * 从企业号通讯录同步用户数据
	 *
	 * $authid
	 * $pdid 父部门id
	 *
	 */
	public function syncFromQy_action($site, $authid, $pdid = 1) {
		if (!($authapi = $this->model('user/authapi')->byId($authid))) {
			return new \ResponseError('未设置内置认证接口，无法同步通讯录');
		}

		$mp = $this->model('mp\mpaccount')->byId($site, 'qy_joined');
		if (!$mp && $mp->qy_joined !== 'Y') {
			return new \ResponseError('未与企业号连接，无法同步通讯录');
		}
		$timestamp = time(); // 进行同步操作的时间戳
		$qyproxy = $this->model('mpproxy/qy', $site);
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
				"mpid='$site' and extattr like '%\"id\":$rdept->id,%'",
			);
			if (!($ldept = $model->query_obj_ss($q))) {
				$ldept = $modelDept->create($site, $authid, $pid, null);
			}
			$model->update(
				'xxt_member_department',
				array(
					'pid' => $pid,
					'sync_at' => $timestamp,
					'name' => $rdeptName,
					'extattr' => json_encode($rdept),
				),
				"mpid='$site' and id=$ldept->id"
			);
			$mapDeptR2L[$rdept->id] = array('id' => $ldept->id, 'path' => $ldept->fullpath);
		}
		/**
		 * 清空同步不存在的部门
		 */
		$this->model()->delete(
			'xxt_member_department',
			"mpid='$site' and sync_at<" . $timestamp
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
					"mpid='$site' and openid='$user->userid'",
				);
				if (!($luser = $model->query_obj_ss($q))) {
					$this->createQyFan($site, $user, $authid, $timestamp, $mapDeptR2L);
				} else if ($luser->sync_at < $timestamp) {
					$this->updateQyFan($site, $luser->fid, $user, $authid, $timestamp, $mapDeptR2L);
				}
			}
		}
		/**
		 * 清空没有同步的粉丝数据
		 */
		$model->delete(
			'xxt_fans',
			"mpid='$site' and fid in (select fid from xxt_member where mpid='$site' and sync_at<" . $timestamp . ")"
		);
		/**
		 * 清空没有同步的成员数据
		 */
		$model->delete(
			'xxt_member',
			"mpid='$site' and sync_at<" . $timestamp
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
				"mpid='$site' and extattr like '{\"tagid\":$tag->tagid}%'",
			);
			if (!($ltag = $model->query_obj_ss($q))) {
				$t = array(
					'mpid' => $site,
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
					"mpid='$site' and id=$ltag->id"
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
					"mpid='$site' and openid='$user->userid'",
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
						"mpid='$site' and openid='$user->userid'"
					);
				}
			}
		}
		/**
		 * 清空已有标签
		 */
		$model->delete(
			'xxt_member_tag',
			"mpid='$site' and sync_at<" . $timestamp
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