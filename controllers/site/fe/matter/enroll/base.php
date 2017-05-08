<?php
namespace site\fe\matter\enroll;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 登记活动
 */
class base extends \site\fe\matter\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * @param string $memberSchemas
	 */
	protected function canAccessObj($site, $appId, &$member, $memberSchemas, &$app) {
		return $this->model('acl')->canAccessMatter($site, 'enroll', $app, $member, $memberSchemas);
	}
	/**
	 * 检查登记活动进入规则
	 *
	 * @param string $siteId
	 * @param object $oApp
	 * @param boolean $redirect
	 *
	 * @return string page 页面名称
	 *
	 */
	protected function checkEntryRule($oApp, $redirect = false) {
		$user = $this->who;
		$entryRule = $oApp->entry_rule;
		if (isset($entryRule->scope) && $entryRule->scope === 'member') {
			/* 限自定义用户访问 */
			foreach ($entryRule->member as $schemaId => $rule) {
				if (!empty($rule->entry)) {
					if (isset($user->members->{$schemaId})) {
						/* 检查用户的信息是否完整，是否已经通过审核 */
						$oMember = $user->members->{$schemaId};
						$oMember = $this->model('site\user\member')->byId($oMember->id);
						if ($oMember && $oMember->verified === 'Y') {
							$page = $rule->entry;
							break;
						}
					}
				}
			}
			if (!isset($page)) {
				if (isset($entryRule->other->entry)) {
					$page = $entryRule->other->entry;
				} else {
					$page = '$memberschema';
				}
			}
		} else if (isset($entryRule->scope) && $entryRule->scope === 'sns') {
			foreach ($entryRule->sns as $snsName => $rule) {
				if (isset($user->sns->{$snsName})) {
					// 检查用户对应的公众号
					if ($snsName === 'wx') {
						$modelWx = $this->model('sns\wx');
						if (($wxConfig = $modelWx->bySite($oApp->siteid)) && $wxConfig->joined === 'Y') {
							$snsSiteId = $oApp->siteid;
						} else {
							$snsSiteId = 'platform';
						}
					} else {
						$snsSiteId = $oApp->siteid;
					}
					// 检查用户是否已经关注
					$snsUser = $user->sns->{$snsName};
					$modelSnsUser = $this->model('sns\\' . $snsName . '\fan');
					if ($modelSnsUser->isFollow($snsSiteId, $snsUser->openid)) {
						$page = $rule->entry;
						break;
					}
				}
			}
			!isset($page) && $page = $entryRule->other->entry;
		} else {
			if (isset($entryRule->otherwise->entry)) {
				$page = $entryRule->otherwise->entry;
			} else {
				$page = null;
			}
		}
		/*内置页面*/
		switch ($page) {
		case '$memberschema':
			$aMemberSchemas = array();
			foreach ($entryRule->member as $schemaId => $rule) {
				if (!empty($rule->entry)) {
					$aMemberSchemas[] = $schemaId;
				}
			}
			if ($redirect) {
				/*页面跳转*/
				$this->gotoMember($oApp->siteid, $aMemberSchemas, $user->uid);
			} else {
				/*返回地址*/
				$this->gotoMember($oApp->siteid, $aMemberSchemas, $user->uid, false);
			}
			break;
		case '$mpfollow':
			if (!empty($entryRule->sns->wx->entry)) {
				$this->snsFollow($oApp->siteid, 'wx', $oApp);
			} else if (!empty($entryRule->sns->qy->entry)) {
				$this->snsFollow($oApp->siteid, 'qy', $oApp);
			} else if (!empty($entryRule->sns->yx->entry)) {
				$this->snsFollow($oApp->siteid, 'yx', $oApp);
			}
			break;
		}

		return $page;
	}
	/**
	 *
	 */
	protected function checkActionRule($site, $app, $user) {
		return 'Y';
		if (empty($user->fan)) {
			/**
			 * 非关注用户
			 */
			$rule = $app->entry_rule->nonfan->enroll;
		} else {
			if (isset($user->fan)) {
				/* 关注用户 */
				$rule = $app->entry_rule->fan->enroll;
			}
			if (isset($user->membersInAcl) && !empty($user->members)) {
				/* 认证用户不在白名单中 */
				$rule = $app->entry_rule->member_outacl->enroll;
			}
			if (!empty($user->membersInAcl) || (!isset($user->membersInAcl) && !empty($user->members))) {
				/* 白名单中的认证用户，或者，不限制白名单的认证用户，允许登记 */
				$rule = 'Y';
			}
		}
		switch ($rule) {
		case '$authapi_outacl':
			$appAuthapis = explode(',', $app->authapis);
			$this->gotoOutAcl($site, $appAuthapis[0]);
			break;
		case '$mp_follow':
			$this->askFollow($site, $user->openid);
			break;
		case '$authapi_auth':
			$this->gotoAuth($site, $app->authapis, $user->openid, false);
			break;
		}

		return $rule;
	}
}