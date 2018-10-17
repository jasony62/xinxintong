<?php
namespace site\fe\matter\enroll;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 登记活动
 */
class base extends \site\fe\matter\base {
	/**
	 *
	 */
	const AppFields = 'id,state,siteid,title,summary,pic,assigned_nickname,open_lastroll,can_coin,count_limit,data_schemas,start_at,end_at,entry_rule,action_rule,mission_id,read_num,scenario,share_friend_num,share_timeline_num,use_mission_header,use_mission_footer,use_site_header,use_site_footer,enrolled_entry_page,group_app_id,enroll_app_id,repos_config,rank_config,scenario_config,round_cron,mission_id,sync_mission_round';

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 获得当前用户的完整信息
	 * 1、活动中指定的用户昵称
	 * 2、用户在活动中所属的分组
	 */
	protected function getUser($oApp, $oEnrolledData = null) {
		$modelUsr = $this->model('matter\enroll\user');
		$oUser = $modelUsr->detail($oApp, $this->who, $oEnrolledData);

		return $oUser;
	}
	/**
	 * 检查登记活动参与规则
	 *
	 * @param object $oApp
	 * @param boolean $redirect
	 *
	 */
	protected function checkEntryRule($oApp, $bRedirect = false, $oUser = null) {
		if (!isset($oApp->entryRule->scope)) {
			return [true];
		}
		if (empty($oUser)) {
			$oUser = $this->getUser($oApp);
		}

		$oEntryRule = $oApp->entryRule;
		$oScope = $oEntryRule->scope;

		if (isset($oScope->register) && $oScope->register === 'Y') {
			$checkRegister = $this->checkRegisterEntryRule($oUser);
			if ($checkRegister[0] === false) {
				if (true === $bRedirect) {
					$this->gotoAccess();
				} else {
					$msg = '未检测到您的注册信息，不满足【' . $oApp->title . '】的参与规则，请登陆后再尝试操作。';
					return [false, $msg];
				}
			}
		}
		if (isset($oScope->member) && $oScope->member === 'Y') {
			if (empty($oEntryRule->optional->member) || $oEntryRule->optional->member !== 'Y') {
				if (!isset($oEntryRule->member)) {
					$msg = '需要填写通讯录信息，请联系活动的组织者解决。';
					if (true === $bRedirect) {
						$this->outputInfo($msg);
					} else {
						return [false, $msg];
					}
				}
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
						$msg = '您【ID:' . $oUser->uid . '】没有填写通讯录信息，不满足【' . $oApp->title . '】的参与规则，无法访问，请联系活动的组织者解决。';
						return [false, $msg];
					}
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
			if (empty($oEntryRule->optional->group) || $oEntryRule->optional->group !== 'Y') {
				$bMatched = false;
				/* 限分组用户访问 */
				if (isset($oEntryRule->group->id)) {
					$oGroupApp = $this->model('matter\group')->byId($oEntryRule->group->id, ['fields' => 'id,state,title']);
					if (false === $oGroupApp || $oGroupApp->state !== '1') {
						$msg = '【' . $oApp->title . '】指定的分组活动不可访问，请联系活动的组织者解决。';
						if (true === $bRedirect) {
							$this->outputInfo($msg);
						} else {
							return [false, $msg];
						}
					}
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
				if (false === $bMatched) {
					$msg = '您【ID:' . $oUser->uid . '】目前的分组，不满足【' . $oApp->title . '】的参与规则，无法访问，请联系活动的组织者解决。';
					if (true === $bRedirect) {
						$this->outputInfo($msg);
					} else {
						return [false, $msg];
					}
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
	protected function checkEntryRule2($oApp, $oUser = null) {
		$oResult = new \stdClass;
		$oResult->passed = 'Y';

		if (isset($oApp->entryRule->scope)) {
			if (empty($oUser)) {
				$oUser = $this->getUser($oApp);
			}
			$oEntryRule = $oApp->entryRule;
			if (isset($oEntryRule->scope->register) && $oEntryRule->scope->register === 'Y') {
				$checkRegister = $this->checkRegisterEntryRule($oUser);
				if ($checkRegister[0] === false) {
					$oResult->passed = 'N';
					$oResult->scope = 'register';
				}
			}
			if ($oResult->passed === 'Y' && isset($oEntryRule->scope->member) && $oEntryRule->scope->member === 'Y') {
				if (empty($oEntryRule->optional->member) || $oEntryRule->optional->member !== 'Y') {
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
						$oResult->passed = 'N';
						$oResult->scope = 'member';
						$oResult->member = $oEntryRule->member;
					}
				}
			}
			if ($oResult->passed === 'Y' && isset($oEntryRule->scope->sns) && $oEntryRule->scope->sns === 'Y') {
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
					$oResult->passed = 'N';
					$oResult->scope = 'sns';
					$oResult->sns = $oEntryRule->sns;
				}
			}
		}

		return $oResult;
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