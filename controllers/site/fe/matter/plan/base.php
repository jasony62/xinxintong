<?php
namespace site\fe\matter\plan;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 计划活动
 */
class base extends \site\fe\matter\base {
	/**
	 *
	 */
	public function index_action($app) {
		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,siteid,title,entry_rule']);

		$this->checkEntryRule($oApp, true);

		if ($oApp) {
			\TPL::assign('title', $oApp->title);
		} else {
			\TPL::assign('title', '任务计划活动');
		}
		\TPL::output('/site/fe/matter/plan/main');
		exit;
	}
	/**
	 * 获得当前用户所属分组活动分组
	 */
	protected function getUserGroup($oApp) {
		$oEntryRule = $oApp->entry_rule;
		if (empty($oEntryRule->scope) || $oEntryRule->scope !== 'group' || empty($oEntryRule->group->id)) {
			return null;
		}

		$oUser = $this->who;
		$oGroup = new \stdClass;

		/* 限分组用户访问 */
		$oGroupApp = $oEntryRule->group;
		$oGroupUsr = $this->model('matter\group\player')->byUser($oGroupApp, $oUser->uid, ['fields' => 'round_id,round_title']);

		if (count($oGroupUsr)) {
			$oGroupUsr = $oGroupUsr[0];
			if (isset($oGroupApp->round->id)) {
				if ($oGroupUsr->round_id === $oGroupApp->round->id) {
					return $oGroupUsr;
				}
			} else {
				return $oGroupUsr;
			}
		}

		return null;
	}
	/**
	 * 检查登记活动进入规则
	 *
	 * @param object $oApp
	 * @param boolean $redirect
	 *
	 * @return string page 页面名称
	 *
	 */
	protected function checkEntryRule($oApp, $bRedirect = false) {
		$oUser = $this->who;
		$oEntryRule = $oApp->entry_rule;
		if (isset($oEntryRule->scope)) {
			if ($oEntryRule->scope === 'group') {
				$bMatched = false;
				/* 限分组用户访问 */
				if (isset($oEntryRule->group->id)) {
					$oGroupApp = $oEntryRule->group;
					$oGroupUsr = $this->model('matter\group\player')->byUser($oGroupApp, $oUser->uid, ['fields' => 'round_id,round_title']);
					if (count($oGroupUsr)) {
						$oGroupUsr = $oGroupUsr[0];
						if (isset($oGroupApp->round->id)) {
							if ($oGroupUsr->round_id === $oGroupApp->round->id) {
								$bMatched = true;
							}
						} else {
							$bMatched = true;
						}
					}
				}
				if (false === $bMatched) {
					$msg = '您目前不满足【' . $oApp->title . '】的进入规则，无法访问，请联系活动的组织者解决';
					if (true === $bRedirect) {
						$this->outputInfo($msg);
					} else {
						return [false, $msg];
					}
				}
			} else if ($oEntryRule->scope === 'member') {
				/* 限通讯录用户访问 */
				$bMatched = false;
				foreach ($oEntryRule->member as $schemaId => $rule) {
					/* 检查用户的信息是否完整，是否已经通过审核 */
					$modelMem = $this->model('site\user\member');
					if (empty($oUser->unionid)) {
						$aMembers = $modelMem->byUser($oUser->uid, ['schemas' => $schemaId]);
						if (count($aMembers) === 1) {
							$oMember = $aMembers[0];
							if ($oMember->verified === 'Y') {
								$bMatched = true;
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
									$bMatched = true;
									break;
								}
							}
						}
						if ($bMatched) {
							break;
						}
					}
					if (false === $bMatched) {
						if (true === $bRedirect) {
							$aMemberSchemas = array_keys(get_object_vars($oEntryRule->member));
							$this->gotoMember($oApp, $aMemberSchemas);
						} else {
							$msg = '您目前不满足【' . $oApp->title . '】的进入规则，无法访问，请联系活动的组织者解决';
							return [false, $msg];
						}
					}
				}
			} else if ($oEntryRule->scope === 'sns') {
				$bMatched = false;
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
								$bMatched = true;
								break;
							}
						}
					}
				}
				if (false === $bMatched) {
					$msg = '您目前不满足【' . $oApp->title . '】的进入规则，无法访问，请联系活动的组织者解决';
					if (true === $bRedirect) {
						if (!empty($oEntryRule->sns->wx->entry) && $oEntryRule->sns->wx->entry === 'Y') {
							$rst = $this->model('sns\wx\call\qrcode')->createOneOff($oApp->siteid, $oApp);
							if ($rst[0] === false) {
								$this->snsFollow($oApp->siteid, 'wx', $oApp);
							} else {
								$sceneId = $rst[1]->scene_id;
								$this->snsFollow($oApp->siteid, 'wx', false, $sceneId);
							}
						} else if (!empty($oEntryRule->sns->qy->entry) && $oEntryRule->sns->qy->entry === 'Y') {
							$this->snsFollow($oApp->siteid, 'qy', $oApp);
						} else if (!empty($oEntryRule->sns->yx->entry) && $oEntryRule->sns->yx->entry === 'Y') {
							$this->snsFollow($oApp->siteid, 'yx', $oApp);
						} else {
							$this->outputInfo($msg);
						}
					} else {
						return [false, $msg];
					}
				}
			}
		}

		return [true];
	}
}