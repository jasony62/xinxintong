<?php
namespace site\fe\matter\enroll;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 记录活动
 */
class base extends \site\fe\matter\base {
	/**
	 *
	 */
	const AppFields = 'id,state,siteid,title,summary,pic,assigned_nickname,open_lastroll,can_coin,count_limit,data_schemas,start_at,end_at,entry_rule,action_rule,mission_id,read_num,scenario,share_friend_num,share_timeline_num,use_mission_header,use_mission_footer,use_site_header,use_site_footer,enrolled_entry_page,repos_config,rank_config,scenario_config,round_cron,mission_id,sync_mission_round';

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
			$aCheckResult = $this->checkRegisterEntryRule($oUser);
			if ($aCheckResult[0] === false) {
				if (true === $bRedirect) {
					$this->gotoAccess();
				} else {
					$msg = '未检测到您的注册信息，不满足【' . $oApp->title . '】的参与规则，请登陆后再尝试操作。';
					return [false, $msg];
				}
			}
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
					$oResult->passUrl = APP_PROTOCOL . APP_HTTP_HOST . '/rest/site/fe/user/access?site=' . $oApp->siteid;
				}
			}
			if ($oResult->passed === 'Y' && isset($oEntryRule->scope->member) && $oEntryRule->scope->member === 'Y') {
				if (empty($oEntryRule->optional->member) || $oEntryRule->optional->member !== 'Y') {
					$bMemberPassed = false;
					$aMemberSchemaIds = [];
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
						$aMemberSchemaIds[] = $schemaId;
					}
					if (!$bMemberPassed) {
						$siteId = $oApp->siteid;
						if (count($aMemberSchemaIds) === 1) {
							$schema = $this->model('site\user\memberschema')->byId($aMemberSchemaIds[0], ['fields' => 'id,url']);
							strpos($schema->url, 'http') === false && $authUrl = APP_PROTOCOL . APP_HTTP_HOST;
							$authUrl .= $schema->url;
							$authUrl .= "?site=$siteId";
							$authUrl .= "&schema=" . $aMemberSchemaIds[0];
						} else {
							/**
							 * 让用户选择通过那个认证接口进行认证
							 */
							$authUrl = APP_PROTOCOL . APP_HTTP_HOST . '/rest/site/fe/user/memberschema';
							$authUrl .= "?site=$siteId";
							$authUrl .= "&schema=" . implode(',', $aMemberSchemaIds);
						}
						$authUrl .= '&matter=' . $oApp->type . ',' . $oApp->id;

						$oResult->passed = 'N';
						$oResult->scope = 'member';
						$oResult->member = $oEntryRule->member;
						$oResult->passUrl = $authUrl;
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
					$oResult->passUrl = '/rest/site/fe/user/follow?site=' . $oApp->siteid . '&sns=wx';
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