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
		return $this->model('matter\acl')->canAccessMatter($site, 'enroll', $app, $member, $memberSchemas);
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
					if (true === $bRedirect) {
						$this->outputInfo('您目前不满足【' . $oApp->title . '】的进入规则，无法访问，请联系活动的组织者解决');
					}
				}
				if (isset($oEntryRule->otherwise->entry)) {
					$page = $oEntryRule->otherwise->entry;
				} else {
					$page = null;
				}
			} else {
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
			$this->gotoMember($oApp, $aMemberSchemas, $bRedirect ? null : false);
			break;
		case '$mpfollow':
			if (!empty($oEntryRule->sns->wx->entry)) {
				/* 指定了登记轮次 */
				$aParams = [];
				if (!empty($_GET['rid'])) {
					$aParams['rid'] = $_GET['rid'];
				}
				/* 指定了进入页面 */
				if (!empty($_GET['page'])) {
					$aParams['page'] = $_GET['page'];
				}
				if (!empty($aParams)) {
					$oApp->params = $aParams;
				}
				$this->snsWxQrcodeFollow($oApp);
			} else if (!empty($oEntryRule->sns->qy->entry)) {
				$this->snsFollow($oApp->siteid, 'qy', $oApp);
			} else if (!empty($oEntryRule->sns->yx->entry)) {
				$this->snsFollow($oApp->siteid, 'yx', $oApp);
			}
			break;
		}

		return $page;
	}
	/**
	 * 检查登记活动操作规则
	 *
	 * @param object $oApp
	 *
	 * @return object
	 *
	 */
	protected function checkActionRule($oApp) {
		$result = new \stdClass;
		$result->passed = 'N';

		$oUser = $this->who;
		$oEntryRule = $oApp->entry_rule;
		if (!isset($oEntryRule->scope) || $oEntryRule->scope === 'none' || $oEntryRule->scope === 'group') {
			/* 没有限制 */
			$result->passed = 'Y';
		} else if ($oEntryRule->scope === 'member') {
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
								$result->passed = 'Y';
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
									$result->passed = 'Y';
									break;
								}
							}
						}
						if ($result->passed === 'Y') {
							break;
						}
					}
				}
			}
			if ($result->passed === 'N') {
				$result->scope = 'member';
				$result->member = $oEntryRule->member;
			}
		} else if ($oEntryRule->scope === 'sns') {
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
							$result->passed = 'Y';
							break;
						}
					}
				}
			}
			if ($result->passed === 'N') {
				$result->scope = 'sns';
				$result->sns = $oEntryRule->sns;
			}
		}

		return $result;
	}
	/**
	 * 返回全局的邀请关注页面（覆盖基类的方法）
	 */
	public function askFollow_action($site, $sns) {
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
		if (isset($referer)) {
			$oParams = new \stdClass;
			$urlQuery = parse_url($referer, PHP_URL_QUERY);
			$urlQuery = explode('&', $urlQuery);
			foreach ($urlQuery as $param) {
				list($k, $v) = explode('=', $param);
				$oParams->{$k} = $v;
			}
			if (isset($oParams->app)) {
				$oMatter = new \stdClass;
				$oMatter->id = $oParams->app;
				$oMatter->type = 'enroll';
				unset($oParams->app);
				if (isset($oParams->site)) {
					unset($oParams->site);
				}
				$oMatter->params = $oParams;
				$rst = $this->model('sns\\' . $sns . '\call\qrcode')->createOneOff($site, $oMatter);
				if ($rst[0] === false) {
					$this->snsFollow($site, $sns, $oMatter);
				} else {
					$sceneId = $rst[1]->scene_id;
					$this->snsFollow($site, $sns, false, $sceneId);
				}
			} else {
				$this->askFollow($site, $sns);
			}
		} else {
			$this->askFollow($site, $sns);
		}
	}
}