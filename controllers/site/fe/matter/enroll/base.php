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
	 * 获得当前用户的完整信息
	 * 1、活动中指定的用户昵称
	 * 2、用户在活动中所属的分组
	 */
	protected function getUser($oApp, $oEnrolledData = null) {
		$oUser = clone $this->who;
		$oUser->members = new \stdClass;

		if (isset($oEnrolledData) && (isset($oApp->assignedNickname->valid) && $oApp->assignedNickname->valid === 'Y') && isset($oApp->assignedNickname->schema->id)) {
			/* 指定的用户昵称 */
			if (isset($oEnrolledData)) {
				$modelEnlRec = $this->model('matter\enroll\record');
				$oUser->nickname = $modelEnlRec->getValueBySchema($oApp->assignedNickname->schema, $oEnrolledData);
			}
		} else {
			/* 曾经用过的昵称 */
			$modelEnlUsr = $this->model('matter\enroll\user');
			$oEnlUser = $modelEnlUsr->byId($oApp, $oUser->uid, ['fields' => 'nickname']);
			if ($oEnlUser) {
				$oUser->nickname = $oEnlUser->nickname;
			} else {
				$modelEnl = $this->model('matter\enroll');
				$userNickname = $modelEnl->getUserNickname($oApp, $oUser);
				$oUser->nickname = $userNickname;
			}
		}
		$oEntryRule = $oApp->entryRule;
		if (isset($oEntryRule->scope)) {
			/* 用户所属的分组 */
			if ($oEntryRule->scope === 'group' && isset($oEntryRule->group->id)) {
				$modelGrpUsr = $this->model('matter\group\player');
				$oGrpMemb = $modelGrpUsr->byUser($oEntryRule->group, $oUser->uid, ['fields' => 'round_id', 'onlyOne' => true]);
				if ($oGrpMemb) {
					$oUser->group_id = $oGrpMemb->round_id;
				}
			}
			/* 用户通讯录数据 */
			if ($oEntryRule->scope === 'member' && isset($oEntryRule->member)) {
				$mschemaIds = array_keys(get_object_vars($oEntryRule->member));
				if (count($mschemaIds)) {
					$modelMem = $this->model('site\user\member');
					$oUser->members = new \stdClass;
					if (empty($oUser->unionid)) {
						$aMembers = $modelMem->byUser($oUser->uid, ['schemas' => implode(',', $mschemaIds)]);
						foreach ($aMembers as $oMember) {
							$oUser->members->{$oMember->schema_id} = $oMember;
						}
					} else {
						$modelAcnt = $this->model('site\user\account');
						$aUnionUsers = $modelAcnt->byUnionid($oUser->unionid, ['siteid' => $oApp->siteid, 'fields' => 'uid']);
						foreach ($aUnionUsers as $oUnionUser) {
							$aMembers = $modelMem->byUser($oUnionUser->uid, ['schemas' => implode(',', $mschemaIds)]);
							foreach ($aMembers as $oMember) {
								$oUser->members->{$oMember->schema_id} = $oMember;
							}
						}
					}
				}
			}
		}

		return $oUser;
	}
	/**
	 * 检查登记活动参与规则
	 *
	 * @param object $oApp
	 * @param boolean $redirect
	 *
	 */
	protected function checkEntryRule($oApp, $bRedirect = false) {
		if (!isset($oApp->entryRule->scope)) {
			return [true];
		}
		$oUser = $this->who;
		$oEntryRule = $oApp->entryRule;
		$oScope = $oEntryRule->scope;

		if (isset($oScope->member) && $oScope->member === 'Y') {
			$aResult = $this->enterAsMember($oApp);
			/**
			 * 限通讯录用户访问
			 * 如果指定的任何一个通讯录要求用户关注公众号，但是用户还没有关注，那么就要求用户先关注公众号，再填写通讯录
			 */
			if (false === $aResult[0]) {
				if (true === $bRedirect) {
					$aMemberSchemaIds = [];
					$modelMs = $this->model('site\user\memberschema');
					foreach ($oEntryRule->member as $mschemaId => $oRule) {
						$oMschema = $modelMs->byId($mschemaId, ['fields' => 'is_wx_fan', 'cascaded' => 'N']);
						if ($oMschema->is_wx_fan === 'Y') {
							$oApp2 = clone $oApp;
							$oApp2->entryRule = new \stdClass;
							$oApp2->entryRule->sns = (object) ['wx' => (object) ['entry' => 'Y']];
							$aResult = $this->checkSnsEntryRule($oApp2, $bRedirect);
							if (false === $aResult[0]) {
								return $aResult;
							}
						}
						$aMemberSchemaIds[] = $mschemaId;
					}
					$this->gotoMember($oApp, $aMemberSchemaIds);
				} else {
					$msg = '您没有填写通讯录信息，不满足【' . $oApp->title . '】的参与规则，无法访问，请联系活动的组织者解决。';
					return [false, $msg];
				}
			}
		}
		if (isset($oScope->sns) && $oScope->sns === 'Y') {
			$aResult = $this->checkSnsEntryRule($oApp, $bRedirect);
			if (false === $aResult[0]) {
				return $aResult;
			}
		}
		if (isset($oScope->group) && $oScope->group === 'Y') {
			$bMatched = false;
			/* 限分组用户访问 */
			if (isset($oEntryRule->group->id)) {
				$oGroupApp = $this->model('matter\group')->byId($oEntryRule->group->id, ['fields' => 'id,state,title']);
				if ($oGroupApp && $oGroupApp->state === '1') {
					$oGroupUsr = $this->model('matter\group\player')->byUser($oGroupApp, $oUser->uid, ['fields' => 'round_id,round_title']);
					if (count($oGroupUsr)) {
						$oGroupUsr = $oGroupUsr[0];
						if (isset($oEntryRule->group->round->id)) {
							if ($oGroupUsr->round_id === $oEntryRule->group->round->id) {
								$bMatched = true;
							}
						} else {
							$bMatched = true;
						}
					}
				}
			}
			if (false === $bMatched) {
				$msg = '您目前的分组，不满足【' . $oApp->title . '】的参与规则，无法访问，请联系活动的组织者解决。';
				if (true === $bRedirect) {
					$this->outputInfo($msg);
				} else {
					return [false, $msg];
				}
			}
		}

		// 默认进入页面的名称
		$page = isset($oEntryRule->otherwise->entry) ? $oEntryRule->otherwise->entry : null;

		return [true, $page];
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
		$result->passed = 'Y';

		$oUser = $this->who;
		$oEntryRule = $oApp->entryRule;
		//if (!isset($oEntryRule->scope) || $oEntryRule->scope === 'none' || $oEntryRule->scope === 'group') {
		/* 没有限制 */
		//	$result->passed = 'Y';
		//}
		if (isset($oEntryRule->scope)) {
			if ($oEntryRule->scope->member === 'Y') {
				$bMemberPassed = false;
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
									$bMemberPassed = true;
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
										$bMemberPassed = true;
										break;
									}
								}
							}
							if ($bMemberPassed) {
								break;
							}
						}
					}
				}
				if (!$bMemberPassed) {
					$result->passed = 'N';
					$result->scope = 'member';
					$result->member = $oEntryRule->member;
				}
			}
			if ($result->passed === 'Y' && $oEntryRule->scope->sns === 'Y') {
				$bSnsPassed = false;
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
								$bSnsPassed = true;
								break;
							}
						}
					}
				}
				if (!$bSnsPassed) {
					$result->passed = 'N';
					$result->scope = 'sns';
					$result->sns = $oEntryRule->sns;
				}
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