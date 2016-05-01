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
	 */
	protected function checkEntryRule($site, $app, $user, $redirect = false) {
		$entryRule = $app->entry_rule;
		if (isset($entryRule->scope) && $entryRule->scope === 'member') {
			foreach ($entryRule->member as $schemaId => $rule) {
				if (isset($user->members->{$schemaId})) {
					$page = $rule->entry;
					break;
				}
			}
			!isset($page) && $page = '$memberschema';
		} else if (isset($entryRule->scope) && $entryRule->scope === 'sns') {
			foreach ($entryRule->sns as $snsName => $rule) {
				if (isset($user->sns->{$snsName})) {
					$page = $rule->entry;
					break;
				}
			}
			!isset($page) && $page = '$mpfollow';
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
				$aMemberSchemas[] = $schemaId;
			}
			if ($redirect) {
				/*页面跳转*/
				$this->gotoMember($site, $aMemberSchemas, $user->uid);
			} else {
				/*返回地址*/
				$this->gotoMember($site, $aMemberSchemas, $user->uid, false);
			}
			break;
		case '$mpfollow':
			if (isset($entryRule->sns->wx)) {
				$this->snsFollow($site, 'wx');
			} else if (isset($entryRule->sns->qy)) {
				$this->snsFollow($site, 'qy');
			} else if (isset($entryRule->sns->yx)) {
				$this->snsFollow($site, 'yx');
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