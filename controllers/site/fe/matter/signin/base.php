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
		$oEntryRule = $oApp->entry_rule;
		$modelRec = $this->model('matter\signin\record');
		if ($signinLog = $modelRec->userSigned($oUser, $oApp, $oRound)) {
			/* 用户是否已经签到 */
			$signinRec = $modelRec->byId($signinLog->enroll_key, ['cascaded' => 'N']);
			if (!empty($oApp->enroll_app_id)) {
				/* 需要验证登记信息 */
				if ($signinRec->verified === 'Y') {
					if (isset($oEntryRule->success->entry)) {
						$page = $oEntryRule->success->entry;
					}
				} else {
					if (isset($oEntryRule->fail->entry)) {
						$page = $oEntryRule->fail->entry;
					}
				}
			} else {
				if (isset($oEntryRule->success->entry)) {
					$page = $oEntryRule->success->entry;
				}
			}
		} else if (!empty($oEntryRule->scope) && $oEntryRule->scope !== 'none') {
			if ($oEntryRule->scope === 'member') {
				$aResult = $this->enterAsMember($oApp);
			} elseif ($oEntryRule->scope === 'sns') {
				$aResult = $this->enterAsSns($oApp);
			}
			if (true === $aResult[0]) {
				$page = isset($aResult[1]->entry) ? $aResult[1]->entry : '';
			} else {
				if (isset($oEntryRule->other->entry)) {
					$page = $oEntryRule->other->entry;
				} else {
					$page = $oEntryRule->scope === 'member' ? '$memberschema' : '$mpfollow';
				}
			}
		}

		if (empty($page) && isset($oEntryRule->otherwise->entry)) {
			/* 应用的默认页 */
			$page = $oEntryRule->otherwise->entry;
		}
		if (empty($page)) {
			return false;
		}

		/* 内置页面 */
		switch ($page) {
		case '$memberschema':
			$aMemberSchemas = array_keys(get_object_vars($oEntryRule->member));
			$this->gotoMember($oApp, $aMemberSchemas, $redirect ? null : false);
			break;
		case '$mpfollow':
			if (isset($oEntryRule->sns->wx)) {
				/* 指定了签到轮次 */
				if (!empty($_GET['round'])) {
					$oApp->params = new \stdClass;
					$oApp->params->round = $_GET['round'];
				}
				$this->snsWxQrcodeFollow($oApp);
			} elseif (isset($oEntryRule->sns->qy)) {
				$this->snsFollow($oApp->siteid, 'qy', $oApp);
			} elseif (isset($oEntryRule->sns->yx)) {
				$this->snsFollow($oApp->siteid, 'yx', $oApp);
			}
			break;
		}

		return $page;
	}
}