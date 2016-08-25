<?php
namespace site\fe\matter\signin;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 登记活动
 */
class base extends \site\fe\matter\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = [];

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
		if ($signinLog = $modelRec->userSigned($user, $siteId, $app)) {
			/* 用户是否已经签到 */
			$signinRec = $modelRec->byId($signinLog->enroll_key, ['cascaded' => 'N']);
			if ($signinRec->verified === 'Y') {
				if (isset($entryRule->success->entry)) {
					$page = $entryRule->success->entry;
				}
			} else {
				if (isset($entryRule->fail->entry)) {
					$page = $entryRule->fail->entry;
				}
			}
		} elseif (isset($entryRule->scope) && $entryRule->scope === 'member') {
			/* 限自定义用户参与 */
			foreach ($entryRule->member as $schemaId => $rule) {
				if (isset($user->members->{$schemaId})) {
					$page = $rule->entry;
					break;
				}
			}
			!isset($page) && $page = '$memberschema';
		} elseif (isset($entryRule->scope) && $entryRule->scope === 'sns') {
			// 限社交网站用户参与
			foreach ($entryRule->sns as $snsName => $rule) {
				if (isset($user->sns->{$snsName})) {
					// 检查用户对应的公众号
					if ($snsName === 'wx') {
						$modelWx = $this->model('sns\wx');
						if (($wxConfig = $modelWx->bySite($siteId)) && $wxConfig->joined === 'Y') {
							$snsSiteId = $siteId;
						} else {
							$snsSiteId = 'platform';
						}
					} else {
						$snsSiteId = $siteId;
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
			!isset($page) && $page = '$mpfollow';
		}
		/* 其它情况 */
		if (empty($page)) {
			if (isset($entryRule->otherwise->entry)) {
				$page = $entryRule->otherwise->entry;
			}
		}
		/*内置页面*/
		switch ($page) {
		case '$memberschema':
			$aMemberSchemas = [];
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
			} elseif (isset($entryRule->sns->qy)) {
				$this->snsFollow($siteId, 'qy');
			} elseif (isset($entryRule->sns->yx)) {
				$this->snsFollow($siteId, 'yx');
			}
			break;
		}

		return $page;
	}
}