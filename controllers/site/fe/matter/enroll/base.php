<?php
namespace site\fe\matter\enroll;

include_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 登记活动
 */
class base extends \site\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	protected function canAccessObj($site, $app, $member, $authapis, $app) {
		return $this->model('acl')->canAccessMatter($site, 'enroll', $app, $member, $authapis);
	}
	/**
	 * 检查进入登记活动规则
	 */
	protected function checkEntryRule($site, $app, $user, $redirect = false) {
		if (!in_array($this->userAgent(), array('wx', 'yx')) && empty($user->openid)) {
			/**
			 * 非易信、微信公众号打开，无法获得openid
			 */
			if ($app->access_control === 'Y' && !empty($app->authapis)) {
				/**
				 * 如果活动限认证用户访问
				 */
				$page = '$authapi_auth';
			} else {
				$page = $app->entry_rule->nonfan->entry;
			}
		} else {
			if (empty($user->sns)) {
				/**
				 * 非关注用户
				 */
				$page = $app->entry_rule->nonfan->entry;
			} else {
				$entryRule = $app->entry_rule;
				if (isset($entryRule->wxfan->entry) && isset($user->sns->wx)) {
					/**
					 * 微信公众号关注用户
					 */
					$page = $entryRule->wxfan->entry;
				} else if (isset($entryRule->qyfan->entry) && isset($user->sns->qy)) {
					/**
					 * 微信企业号关注用户
					 */
					$page = $entryRule->qyfan->entry;
				} else if (isset($entryRule->yxfan->entry) && isset($user->sns->yx)) {
					/**
					 * 易信公众号关注用户
					 */
					$page = $entryRule->yxfan->entry;
				}
				if (isset($user->membersInAcl) && !empty($user->members)) {
					/**
					 * 认证用户不在白名单中
					 */
					$page = $entryRule->member_outacl->entry;
				}
				if (!empty($user->membersInAcl) || (!isset($user->membersInAcl) && !empty($user->members))) {
					/**
					 * 白名单中的认证用户，或者，不限制白名单的认证用户
					 */
					$page = $entryRule->member->entry;
				}
			}
		}
		switch ($page) {
		case '$authapi_outacl':
			$appAuthapis = explode(',', $app->authapis);
			$this->gotoOutAcl($site, $appAuthapis[0]);
			break;
		case '$authapi_auth':
			$appAuthapis = explode(',', $app->authapis);
			$this->gotoAuth($site, $appAuthapis, $user->openid, $redirect ? null : false);
			break;
		case '$mp_follow':
			$this->askFollow($site, empty($user->openid) ? '' : $user->openid);
			break;
		}

		return $page;
	}
	/**
	 *
	 */
	protected function checkActionRule($site, $app, $user) {
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