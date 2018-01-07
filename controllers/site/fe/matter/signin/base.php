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
		return $this->model('matter\acl')->canAccessMatter($site, 'signin', $app, $member, $memberSchemas);
	}
	/**
	 * 检查签到活动进入规则
	 *
	 * 1、用户是否已经签到
	 * 2、用户未签到已登记
	 * 3、需确认用户身份
	 *
	 * @param object $app
	 * @param boolean $redirect
	 */
	protected function checkEntryRule($oApp, $redirect = false, &$oRound = null) {
		$oUser = $this->who;
		$entryRule = $oApp->entry_rule;
		$modelRec = $this->model('matter\signin\record');
		if ($signinLog = $modelRec->userSigned($oUser, $oApp, $oRound)) {
			/* 用户是否已经签到 */
			$signinRec = $modelRec->byId($signinLog->enroll_key, ['cascaded' => 'N']);
			if (!empty($oApp->enroll_app_id)) {
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
		} else {
			if (!empty($entryRule->scope)) {
				if ($entryRule->scope === 'member') {
					/* 限自定义用户参与 */
					foreach ($entryRule->member as $schemaId => $rule) {
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
				} elseif ($entryRule->scope === 'sns') {
					$aResult = $this->enterAsSns($oApp);
					$page = empty($aResult[1]->entry) ? $entryRule->other->entry : $aResult[1]->entry;
				} else {
					/* 不限用户来源，默认进入页面 */
					if (isset($entryRule->otherwise->entry)) {
						$page = $entryRule->otherwise->entry;
					}
				}
			}
		}

		/* 内置页面 */
		switch ($page) {
		case '$memberschema':
			$aMemberSchemas = [];
			foreach ($entryRule->member as $schemaId => $rule) {
				$aMemberSchemas[] = $schemaId;
			}
			if ($redirect) {
				/* 页面跳转 */
				$this->gotoMember($oApp, $aMemberSchemas);
			} else {
				/* 返回地址 */
				$this->gotoMember($oApp, $aMemberSchemas, false);
			}
			break;
		case '$mpfollow':
			if (isset($entryRule->sns->wx)) {
				/* 指定了签到轮次 */
				if (!empty($_GET['round'])) {
					$oApp->params = new \stdClass;
					$oApp->params->round = $_GET['round'];
				}
				$this->snsWxQrcodeFollow($oApp);
			} elseif (isset($entryRule->sns->qy)) {
				$this->snsFollow($oApp->siteid, 'qy', $oApp);
			} elseif (isset($entryRule->sns->yx)) {
				$this->snsFollow($oApp->siteid, 'yx', $oApp);
			}
			break;
		}

		return $page;
	}
}