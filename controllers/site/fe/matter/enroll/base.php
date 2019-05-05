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
	const AppFields = 'id,state,siteid,title,summary,pic,assigned_nickname,open_lastroll,count_limit,data_schemas,start_at,end_at,entry_rule,action_rule,mission_id,read_num,scenario,share_friend_num,share_timeline_num,use_mission_header,use_mission_footer,use_site_header,use_site_footer,enrolled_entry_page,repos_config,rank_config,scenario_config,vote_config,round_cron,mission_id,sync_mission_round';

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
	 * 检查记录活动参与规则
	 *
	 * @param object $oApp
	 * @param boolean $bRedirect
	 * @param object $oUser
	 *
	 */
	protected function checkEntryRule($oApp, $bRedirect = false, $oUser = null, $page = null, $ek = null) {
		if (empty($oUser)) {
			$oUser = $this->getUser($oApp);
		}
		$aCheckResult = parent::checkEntryRule($oApp, $bRedirect, $oUser, $page);
		if (false === $aCheckResult[0]) {
			return $aCheckResult;
		}

		// 检查结果页权限，如未设置时只能提交者进入
		if ($page === 'result' && !empty($ek)) {
			if (empty($oApp->scenarioConfig->can_result_all) || $oApp->scenarioConfig->can_result_all !== 'Y') {
				// 记录提交者
				$modelRec = $this->model('matter\enroll\record');
				$oRecord = $modelRec->byId($ek, ['fields' => 'userid', 'state' => 1]);
				if ($oRecord === false) {
					if ($bRedirect === false) {
						return [false, '记录不存在或已删除'];
					} else {
						$this->outputInfo('记录不存在或已删除');
					}
				}
				if ($oRecord->userid !== $oUser->uid) {
					if (!empty($oUser->unionid)) {
						//查询注册账号绑定的所有账号
						$users = $this->model('site\user\account')->byUnionid($oUser->unionid, ['siteid' => $oApp->siteid, 'fields' => 'uid']);
						$userids = [];
						foreach ($users as $user) {
							$userids[] = $user->uid;
						}
						if (!in_array($oRecord->userid, $userids)) {
							if ($bRedirect === false) {
								return [false, '只能查看自己的记录'];
							} else {
								$this->outputInfo('只能查看自己的记录');
							}
						}
					} else {
						if ($bRedirect === false) {
							return [false, '只能查看自己的记录'];
						} else {
							$this->outputInfo('只能查看自己的记录');
						}
					}
				}
			}
		}

		// 默认进入页面的名称
		$page = isset($oApp->entryRule->otherwise->entry) ? $oApp->entryRule->otherwise->entry : null;

		return [true, $page];
	}
	/**
	 * 检查记录活动操作规则
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
					$oResult->passUrl = APP_PROTOCOL . APP_HTTP_HOST . '/rest/site/fe/user/follow?site=' . $oApp->siteid . '&sns=wx';
				}
			}
		}

		return $oResult;
	}
	/**
	 * 编辑用户的定义及用户列表
	 */
	protected function getEditorGroup($oApp) {
		$oEditor = null;
		$oActionRule = $oApp->actionRule;
		if (isset($oActionRule->role->editor->group) && isset($oActionRule->role->editor->nickname)) {
			$oEditor = new \stdClass;
			$oEditor->group = $oActionRule->role->editor->group;
			$oEditor->nickname = $oActionRule->role->editor->nickname;
			// 如果记录活动指定了编辑组需要获取，编辑组中所有的用户
			$modelGrpRec = $this->model('matter\group\record');
			$oGrpRecResult = $modelGrpRec->byApp($oApp->entryRule->group->id, ['roleTeamId' => $oEditor->group, 'fields' => 'role_teams,userid']);
			if (isset($oGrpRecResult->records)) {
				$oEditor->users = new \stdClass;
				foreach ($oGrpRecResult->records as $oRec) {
					$oEditor->users->{$oRec->userid} = $oRec->role_teams;
				}
			}
		}

		return $oEditor;
	}
	/**
	 * 设置记录的昵称
	 *
	 * @param mixed $oObj 记录|数据|评论|专题
	 */
	protected function setNickname($oObj, $oUser, $oEditorGrp) {
		/* 修改默认访客昵称 */
		if (isset($oObj->userid) && $oObj->userid === $oUser->uid) {
			$oObj->nickname = '我';
		} else if (isset($oObj->nickname) && preg_match('/用户[^\W_]{13}/', $oObj->nickname)) {
			$oObj->nickname = '访客';
		} else if (isset($oEditorGrp) && (empty($oUser->is_editor) || $oUser->is_editor !== 'Y')) {
			/* 设置编辑统一昵称 */
			if (!empty($oObj->group_id) && $oObj->group_id === $oEditorGrp->group) {
				$oObj->nickname = $oEditorGrp->nickname;
			} else if (isset($oEditorGrp->users) && isset($oEditorGrp->users->{$oObj->userid})) {
				// 记录提交者是否有编辑组角色
				$oObj->nickname = $oEditorGrp->nickname;
			}
		}

		return $oObj;
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