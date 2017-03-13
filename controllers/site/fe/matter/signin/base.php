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
	 * 检查签到活动进入规则
	 *
	 * 1、用户是否已经签到
	 * 2、用户未签到已登记
	 * 3、需确认用户身份
	 *
	 * @param object $user
	 * @param string $siteId
	 * @param object $app
	 * @param boolean $redirect
	 */
	protected function checkEntryRule($siteId, &$app, $redirect = false, &$round = null) {
		$user = $this->who;
		$entryRule = $app->entry_rule;
		$modelRec = $this->model('matter\signin\record');
		if ($signinLog = $modelRec->userSigned($user, $siteId, $app, $round)) {
			/* 用户是否已经签到 */
			$signinRec = $modelRec->byId($signinLog->enroll_key, ['cascaded' => 'N']);
			if (!empty($app->enroll_app_id)) {
				/* 需要验证登记信息 */
				if ($signinRec->verified === 'Y') {
					if (isset($entryRule->success->entry)) {
						$page = $entryRule->success->entry;
					}
				} else {
					if (isset($entryRule->fail->entry)) {
						$page = $entryRule->fail->entry;
					}
				}
			} else {
				if (isset($entryRule->success->entry)) {
					$page = $entryRule->success->entry;
				}
			}
		} elseif (isset($entryRule->scope) && $entryRule->scope === 'member') {
			/* 限自定义用户参与 */
			foreach ($entryRule->member as $schemaId => $rule) {
				if (isset($user->members->{$schemaId})) {
					$page = $rule->entry;
					break;
				} else {
					$page = $entryRule->other->entry;
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
			!isset($page) && $page = $entryRule->other->entry;
		} else {
			/* 不限用户来源，默认进入页面 */
			if (isset($entryRule->otherwise->entry)) {
				$page = $entryRule->otherwise->entry;
			}
		}
		/* 如果没有设置对应的页面 */
		if (empty($page)) {}

		/* 内置页面 */
		switch ($page) {
		case '$memberschema':
			$aMemberSchemas = [];
			foreach ($entryRule->member as $schemaId => $rule) {
				$aMemberSchemas[] = $schemaId;
			}
			if ($redirect) {
				/* 页面跳转 */
				$this->gotoMember($siteId, $aMemberSchemas, $user->uid);
			} else {
				/* 返回地址 */
				$this->gotoMember($siteId, $aMemberSchemas, $user->uid, false);
			}
			break;
		case '$mpfollow':
			if (isset($entryRule->sns->wx)) {
				$this->snsFollow($siteId, 'wx', $app);
			} elseif (isset($entryRule->sns->qy)) {
				$this->snsFollow($siteId, 'qy', $app);
			} elseif (isset($entryRule->sns->yx)) {
				$this->snsFollow($siteId, 'yx', $app);
			}
			break;
		}

		return $page;
	}
}