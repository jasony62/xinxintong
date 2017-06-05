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
		$oUser = $this->who;
		$oEntryRule = $oApp->entry_rule;
		if (isset($oEntryRule->scope) && $oEntryRule->scope === 'member') {
			/* 限自定义用户访问 */
			foreach ($oEntryRule->member as $schemaId => $rule) {
				if (!empty($rule->entry)) {
					/* 检查用户的信息是否完整，是否已经通过审核 */
					$modelMem = $this->model('site\user\member');
					if (empty($oUser->unionid)) {
						$aMembers = $modelMem->byUser($oUser->uid, ['schemas' => $schemaId]);
						if (count($aMembers) === 1) {
							$oMember = $aMembers[0];
							if ($oMember->verified === 'Y') {
								$page = $rule->entry;
								break;
							}
						}
					} else {
						$modelAcnt = $this->model('site\user\account');
						$aUnionUsers = $modelAcnt->byUnionid($oUser->unionid, ['siteid' => $oApp->siteid, 'fields' => 'uid']);
						foreach ($aUnionUsers as $oUnionUser) {
							$aMembers = $modelMem->byUser($oUnionUser->uid, ['schemas' => $schemaId]);
							if (count($aMembers) === 1) {
								$oMember = $aMembers[0];
								if ($oMember->verified === 'Y') {
									$page = $rule->entry;
									break;
								}
							}
						}
						if (isset($page)) {
							break;
						}
					}
				}
			}
			if (!isset($page)) {
				if (isset($oEntryRule->other->entry)) {
					$page = $oEntryRule->other->entry;
				} else {
					$page = '$memberschema';
				}
			}
		} else if (isset($oEntryRule->scope) && $oEntryRule->scope === 'sns') {
			foreach ($oEntryRule->sns as $snsName => $rule) {
				if (isset($oUser->sns->{$snsName})) {
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
					if ($snsUser = $oUser->sns->{$snsName}) {
						$modelSnsUser = $this->model('sns\\' . $snsName . '\fan');
						if ($modelSnsUser->isFollow($snsSiteId, $snsUser->openid)) {
							$page = $rule->entry;
							break;
						}
					}
				}
			}
			!isset($page) && $page = $oEntryRule->other->entry;
		} else {
			if (isset($oEntryRule->otherwise->entry)) {
				$page = $oEntryRule->otherwise->entry;
			} else {
				$page = null;
			}
		}
		/*内置页面*/
		switch ($page) {
		case '$memberschema':
			$aMemberSchemas = array();
			foreach ($oEntryRule->member as $schemaId => $rule) {
				if (!empty($rule->entry)) {
					$aMemberSchemas[] = $schemaId;
				}
			}
			if ($redirect) {
				/*页面跳转*/
				$this->gotoMember($oApp->siteid, $aMemberSchemas, $oUser->uid);
			} else {
				/*返回地址*/
				$this->gotoMember($oApp->siteid, $aMemberSchemas, $oUser->uid, false);
			}
			break;
		case '$mpfollow':
			if (!empty($oEntryRule->sns->wx->entry)) {
				$this->snsFollow($oApp->siteid, 'wx', $oApp);
			} else if (!empty($oEntryRule->sns->qy->entry)) {
				$this->snsFollow($oApp->siteid, 'qy', $oApp);
			} else if (!empty($oEntryRule->sns->yx->entry)) {
				$this->snsFollow($oApp->siteid, 'yx', $oApp);
			}
			break;
		}

		return $page;
	}
}