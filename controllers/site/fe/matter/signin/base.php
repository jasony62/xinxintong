<?php
namespace site\fe\matter\signin;

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
		return $this->model('acl')->canAccessMatter($site, 'signin', $app, $member, $memberSchemas);
	}
	/**
	 * 检查登记活动进入规则
	 *
	 * 1、用户是否已经签到
	 * 2、用户为签到已登记
	 * 3、需确认用户身份
	 */
	protected function checkEntryRule(&$user, $siteId, &$app, $redirect = false) {
		$entryRule = $app->entry_rule;
		$modelRec = $this->model('matter\signin\record');
		if ($modelRec->userSigned($user, $siteId, $app)) {
			/* 用户是否已经签到 */
			if (isset($entryRule->signed->entry)) {
				$page = $entryRule->signed->entry;
			}
		} else if ($modelRec->byUser($user, $siteId, $app, array('fields' => 'enroll_key', 'cascaded' => 'N'))) {
			/* 用户是否已经登记 */
			if (isset($entryRule->signed->entry)) {
				$page = $entryRule->enrolled->entry;
			}
		} else if (isset($entryRule->scope) && $entryRule->scope === 'member') {
			/* 限自定义用户参与 */
			foreach ($entryRule->member as $schemaId => $rule) {
				if (isset($user->members->{$schemaId})) {
					$page = $rule->entry;
					break;
				}
			}
			!isset($page) && $page = '$memberschema';
		} else if (isset($entryRule->scope) && $entryRule->scope === 'sns') {
			/* 限社交网站用户参与 */
			foreach ($entryRule->sns as $snsName => $rule) {
				if (isset($user->sns->{$snsName})) {
					$page = $rule->entry;
					break;
				}
			}
			!isset($page) && $page = '$mpfollow';
		} else {
			/* 其它情况 */
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
				$this->gotoMember($siteId, $aMemberSchemas, $user->uid);
			} else {
				/*返回地址*/
				$this->gotoMember($siteId, $aMemberSchemas, $user->uid, false);
			}
			break;
		case '$mpfollow':
			if (isset($entryRule->sns->wx)) {
				$this->snsFollow($siteId, 'wx');
			} else if (isset($entryRule->sns->qy)) {
				$this->snsFollow($siteId, 'qy');
			} else if (isset($entryRule->sns->yx)) {
				$this->snsFollow($siteId, 'yx');
			}
			break;
		}

		return $page;
	}
}