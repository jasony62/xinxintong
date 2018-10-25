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
	 * 检查签到活动进入规则
	 *
	 * 1、用户是否已经签到
	 * 2、用户未签到已登记
	 * 3、需确认用户身份
	 *
	 * @param object $oApp
	 * @param boolean $bRedirect
	 */
	protected function checkEntryRule($oApp, $bRedirect = false, &$oRound = null) {
		$oUser = $this->who;
		$oEntryRule = $oApp->entryRule;
		$oScope = isset($oEntryRule->scope) ? $oEntryRule->scope : new \stdClass;
		$modelRec = $this->model('matter\signin\record');
		if ($signinLog = $modelRec->userSigned($oUser, $oApp, $oRound)) {
			/* 用户是否已经签到 */
			$signinRec = $modelRec->byId($signinLog->enroll_key, ['cascaded' => 'N']);
			if (!empty($oApp->entryRule->enroll->id)) {
				/* 需要验证登记信息 */
				if ($signinRec->verified === 'Y') {
					if (isset($oEntryRule->success->entry)) {
						return [true, $oEntryRule->success->entry];
					}
				} else {
					if (isset($oEntryRule->fail->entry)) {
						return [true, $oEntryRule->fail->entry];
					}
				}
			} else {
				if (isset($oEntryRule->success->entry)) {
					return [true, $oEntryRule->success->entry];
				}
			}

			return [true];
		}

		foreach (['member', 'sns', 'group', 'enroll'] as $item) {
			if (isset($oScope->{$item}) && $oScope->{$item} === 'Y') {
				$aCheckResult = $this->{'check' . ucfirst($item) . 'EntryRule'}($oApp, $bRedirect);
				if (false === $aCheckResult[0]) {
					return $aCheckResult;
				}
			}
		}
		// 默认进入页面的名称
		$page = isset($oEntryRule->otherwise->entry) ? $oEntryRule->otherwise->entry : null;

		return [true, $page];
	}
}