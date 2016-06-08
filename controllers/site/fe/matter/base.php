<?php
namespace site\fe\matter;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *
 */
class base extends \site\fe\base {
	/**
	 * 打开页面时设置客户端的标识，用户在后续的操作中判断调用是否来源于之前的客户端
	 */
	protected function _setAgentEnter($matterId) {
		/* user */
		$user = $this->who;
		/* set key */
		$_SESSION['AGENTENTER_' . $matterId . '_' . $user->uid] = time();
	}
	/**
	 * 判断调用是否来源于之前的客户端
	 */
	protected function _isAgentEnter($matterId) {
		/* user */
		$user = $this->who;
		/* set key */
		return isset($_SESSION['AGENTENTER_' . $matterId . '_' . $user->uid]);
	}
	/**
	 * 跳转到用户认证页
	 */
	protected function gotoMember($siteId, $aMemberSchemas, $userid, $targetUrl = null) {
		is_string($aMemberSchemas) && $aMemberSchemas = explode(',', $aMemberSchemas);
		/**
		 * 如果不是注册用户，要求先进行认证
		 */
		if (count($aMemberSchemas) === 1) {
			$schema = $this->model('site\user\memberschema')->byId($aMemberSchemas[0], 'id,url');
			strpos($schema->url, 'http') === false && $authUrl = 'http://' . $_SERVER['HTTP_HOST'];
			$authUrl .= $schema->url;
			$authUrl .= "?site=$siteId";
			!empty($userid) && $authUrl .= "&userid=$userid";
			$authUrl .= "&schema=" . $aMemberSchemas[0];
		} else {
			/**
			 * 让用户选择通过那个认证接口进行认证
			 */
			$authUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/rest/site/fe/user/member/schemaOptions';
			$authUrl .= "?site=$siteId";
			!empty($userid) && $authUrl .= "&userid=$userid";
			$authUrl .= "&schema=" . implode(',', $aMemberSchemas);
		}
		/**
		 * 返回身份认证页
		 */
		if ($targetUrl === false) {
			/**
			 * 直接返回认证地址
			 * todo angular无法自动执行初始化，所以只能返回URL，由前端加载页面
			 */
			$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
			header($protocol . ' 401 Unauthorized');
			die("$authUrl");
		} else {
			/**
			 * 跳转到认证接口
			 */
			if (empty($targetUrl)) {
				$targetUrl = $this->getRequestUrl();
			}
			/**
			 * 将跳转信息保存在cookie中
			 */
			$targetUrl = $this->model()->encrypt($targetUrl, 'ENCODE', $siteId);
			$this->mySetCookie("_{$siteId}_mauth_t", $targetUrl, time() + 300);
			$this->redirect($authUrl);
		}
	}
	/**
	 * 解决老版本的数据迁移问题
	 */
	private function __upgradeCookieMembers($siteId, $userid, $aMemberSchemas) {
		$model = $this->model();
		$members = array();
		/* members */
		foreach ($aMemberSchemas as $authid) {
			if ($encoded = $this->myGetCookie("_{$siteId}_{$authid}_member")) {
				if (!isset($cookiekey)) {
					/* get cookie key */
					$q = array('creater', 'xxt_mpaccount', "mpid='$siteId'");
					if (!($mpCreater = $model->query_val_ss($q))) {
						return false;
					}
					$cookiekey = md5($siteId . $mpCreater);
				}
				if ($mid = $model->encrypt($encoded, 'DECODE', $cookiekey)) {
					/**
					 * 检查数据库中是否有匹配的记录
					 */
					$q = array(
						'authed_identity',
						'xxt_member',
						"authapi_id=$authid and mid='$mid' and forbidden='N'",
					);
					if ($memberOld = $model->query_obj_ss($q)) {
						$q = array(
							'*',
							'xxt_site_member',
							"siteid='{$siteId}' and schema_id=$authid and identity='{$memberOld->authed_identity}'",
						);
						if ($member = $model->query_obj_ss($q)) {
							$model->update('xxt_site_member', array('userid' => $userid), "id='{$member->id}'");
							$member->userid = $userid;
							$members[] = $member;
						}
					}
				}
				/* 清除原有的cookie */
				$this->mySetCookie("_{$siteId}_{$authid}_member", '', time() - 86400);
			}
		}
		return $members;
	}
	/**
	 *
	 */
	private function __upgradeOldMembers($siteId, $userid, $openid) {
		$model = $this->model();
		$members = array();
		$q = array(
			'authed_identity,authapi_id',
			'xxt_member',
			"mpid='$siteId' and forbidden='N' and openid='$openid'",
		);
		if ($memberOlds = $model->query_objs_ss($q)) {
			foreach ($memberOlds as $memberOld) {
				$q = array(
					'*',
					'xxt_site_member',
					"siteid='{$siteId}' and schema_id='{$memberOld->authapi_id}' and identity='{$memberOld->authed_identity}' and forbidden='N'",
				);
				if ($member = $model->query_obj_ss($q)) {
					$model->update('xxt_site_member', array('userid' => $userid), "id='{$member->id}'");
					$member->userid = $userid;
					$members[] = $member;
				}
			}
		}

		return $members;
	}
	/**
	 * 访问控制设置
	 *
	 * 检查当前用户是否为认证用户
	 * 检查当前用户是否在白名单中
	 *
	 * 如果用户没有认证，跳转到认证页
	 *
	 */
	protected function accessControl($siteId, $objId, $memberSchemas, $userid, &$obj, $targetUrl = null) {
		$aMemberSchemas = explode(',', $memberSchemas);
		$members = array();
		foreach ($aMemberSchemas as $memberSchema) {
			if (isset($this->who->members->{$memberSchema})) {
				$members[] = $this->who->members->{$memberSchema};
			}
		}
		if (empty($members)) {
			$members = $this->model('site\user\member')->byUser($siteId, $userid, array('schemas' => $memberSchemas));
		}
		if (empty($members)) {
			/* 处理版本迁移数据 */
			$members = $this->__upgradeCookieMembers($siteId, $userid, $aMemberSchemas);
			if (empty($members)) {
				if ($this->userAgent() === 'wx') {
					if (isset($this->who->sns->wx)) {
						$openid = $this->who->sns->wx->openid;
					} else if (isset($this->who->sns->qy)) {
						$openid = $this->who->sns->qy->openid;
					}
				} else if ($this->userAgent() === 'yx') {
					if (isset($this->who->sns->yx)) {
						$openid = $this->who->sns->yx->openid;
					}
				}
				if (isset($openid)) {
					$members = $this->__upgradeOldMembers($siteId, $userid, $openid);
				}
			}
		}
		if (empty($members)) {
			/**
			 * 如果不是认证用户，先进行认证
			 */
			$this->gotoMember($siteId, $aMemberSchemas, $userid, $targetUrl);
		} else {
			$model = $this->model();
			$passed = false;
			foreach ($members as $member) {
				if ($this->canAccessObj($siteId, $objId, $member, $memberSchemas, $obj)) {
					/**
					 * 检查用户是否通过了验证
					 */
					$q = array(
						'verified',
						'xxt_site_member',
						"siteid='$siteId' and id='$member->id'",
					);
					if ('Y' !== $model->query_val_ss($q)) {
						$r = $this->model('site\user\memberschema')->getNotpassStatement($member->schema_id, $siteId);
						$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
						header($protocol . ' 401 Unauthorized');
						\TPL::assign('title', '访问控制未通过');
						\TPL::assign('body', $r);
						\TPL::output('error');
						exit;
					}
					$passed = true;
					break;
				}
			}
			!$passed && $this->gotoOutAcl($siteId, $member->schema_id);

			return $member;
		}
	}
}