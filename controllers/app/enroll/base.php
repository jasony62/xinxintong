<?php
namespace app\enroll;

include_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 登记活动
 */
class base extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	protected function canAccessObj($mpid, $aid, $member, $authapis, $act) {
		return $this->model('acl')->canAccessMatter($mpid, 'enroll', $aid, $member, $authapis);
	}
	/**
	 * 检查进入活动规则
	 */
	protected function checkEntryRule($mpid, $act, $user, $redirect = false) {
		if (!$this->getClientSrc() && empty($user->openid)) {
			/**
			 * 非易信、微信公众号打开，无法获得openid
			 */
			if ($act->access_control === 'Y' && !empty($act->authapis)) {
				/**
				 * 如果活动限认证用户访问
				 */
				$page = '$authapi_auth';
			} else {
				$page = $act->entry_rule->nonfan->entry;
			}
		} else {
			if (empty($user->fan)) {
				/**
				 * 非关注用户
				 */
				$page = $act->entry_rule->nonfan->entry;
			} else {
				if (isset($user->fan)) {
					/**
					 * 关注用户
					 */
					$page = $act->entry_rule->fan->entry;
				}
				if (isset($user->membersInAcl) && !empty($user->members)) {
					/**
					 * 认证用户不在白名单中
					 */
					$page = $act->entry_rule->member_outacl->entry;
				}
				if (!empty($user->membersInAcl) || (!isset($user->membersInAcl) && !empty($user->members))) {
					/**
					 * 白名单中的认证用户，或者，不限制白名单的认证用户
					 */
					$page = $act->entry_rule->member->entry;
				}
			}
		}
		switch ($page) {
		case '$authapi_outacl':
			$actAuthapis = explode(',', $act->authapis);
			$this->gotoOutAcl($mpid, $actAuthapis[0]);
			break;
		case '$authapi_auth':
			$actAuthapis = explode(',', $act->authapis);
			$this->gotoAuth($mpid, $actAuthapis, $user->openid, $redirect ? null : false);
			break;
		case '$mp_follow':
			$this->askFollow($mpid, $user->openid);
			break;
		}

		return $page;
	}
	/**
	 *
	 */
	protected function checkActionRule($mpid, $act, $user) {
		if (empty($user->fan)) {
			/**
			 * 非关注用户
			 */
			$rule = $act->entry_rule->nonfan->enroll;
		} else {
			if (isset($user->fan)) {
				/* 关注用户 */
				$rule = $act->entry_rule->fan->enroll;
			}
			if (isset($user->membersInAcl) && !empty($user->members)) {
				/* 认证用户不在白名单中 */
				$rule = $act->entry_rule->member_outacl->enroll;
			}
			if (!empty($user->membersInAcl) || (!isset($user->membersInAcl) && !empty($user->members))) {
				/* 白名单中的认证用户，或者，不限制白名单的认证用户，允许登记 */
				$rule = 'Y';
			}
		}
		switch ($rule) {
		case '$authapi_outacl':
			$actAuthapis = explode(',', $act->authapis);
			$this->gotoOutAcl($mpid, $actAuthapis[0]);
			break;
		case '$mp_follow':
			$this->askFollow($mpid, $user->openid);
			break;
		case '$authapi_auth':
			$this->gotoAuth($mpid, $act->authapis, $user->openid, false);
			break;
		}

		return $rule;
	}
}