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
	protected function _checkInviteToken($userid, $oMatter) {
		if (empty($_GET['inviteToken'])) {
			die('参数不完整，未通过邀请访问控制');
		}
		$inviteToken = $_GET['inviteToken'];

		$rst = $this->model('invite\token')->checkToken($inviteToken, $userid, $oMatter);
		if (false === $rst[0]) {
			die($rst[1]);
		}

		return true;
	}
	/**
	 * 检查是否需要第三方社交帐号认证
	 * 检查条件：
	 * 0、应用是否设置了需要认证
	 * 1、站点是否绑定了第三方社交帐号认证
	 * 2、平台是否绑定了第三方社交帐号认证
	 * 3、用户客户端是否可以发起认证
	 *
	 * @param string $site
	 * @param object $app
	 */
	private function _requireSnsOAuth($oApp) {
		/* 检查进入规则 */
		$this->requireSnsOAuth($oApp);
		/* 检查sns关联规则 */
		$oAssocSns = $oApp->assocSns;
		if ($this->userAgent() === 'wx') {
			if (!empty($oAssocSns->wx) && $oAssocSns->wx === 'Y') {
				if (!isset($this->who->sns->wx)) {
					$modelWx = $this->model('sns\wx');
					if (($wxConfig = $modelWx->bySite($oApp->siteid)) && $wxConfig->joined === 'Y') {
						$this->snsOAuth($wxConfig, 'wx');
					} else if (($wxConfig = $modelWx->bySite('platform')) && $wxConfig->joined === 'Y') {
						$this->snsOAuth($wxConfig, 'wx');
					}
				}
			}
			if (!empty($oAssocSns->qy) && $oAssocSns->qy === 'Y') {
				if (!isset($this->who->sns->qy)) {
					if ($qyConfig = $this->model('sns\qy')->bySite($oApp->siteid)) {
						if ($qyConfig->joined === 'Y') {
							$this->snsOAuth($qyConfig, 'qy');
						}
					}
				}
			}
		} else if ($this->userAgent() === 'yx' && !empty($oAssocSns->yx) && ($oAssocSns->yx === 'Y')) {
			if (!isset($this->who->sns->yx)) {
				if ($yxConfig = $this->model('sns\yx')->bySite($oApp->siteid)) {
					if ($yxConfig->joined === 'Y') {
						$this->snsOAuth($yxConfig, 'yx');
					}
				}
			}
		}

		return false;
	}
	/**
	 *
	 */
	public function index_action($app) {
		$app = $this->escape($app);
		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,siteid,title,entry_rule,assoc_sns']);

		if (false === $oApp || $oApp->state !== '1') {
			\TPL::outputError('访问的对象不存在');
			exit;
		}

		/* 检查是否必须通过邀请链接进入 */
		$oInvitee = new \stdClass;
		$oInvitee->id = $oApp->siteid;
		$oInvitee->type = 'S';
		$oInvite = $this->model('invite')->byMatter($oApp, $oInvitee, ['fields' => 'id,code,expire_at,state']);
		if ($oInvite && $oInvite->state === '1') {
			$this->_checkInviteToken($this->who->uid, $oApp);
		}

		/* 检查是否需要第三方社交帐号OAuth */
		if (!$this->afterSnsOAuth()) {
			$this->_requireSnsOAuth($oApp);
		}

		/* 检查进入规则 */
		$this->checkEntryRule($oApp, true);

		/* 检查关联sns规则 */
		$this->_checkAssocSns($oApp, true);

		\TPL::assign('title', $oApp->title);
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
				$oResult = $this->enterAsMember($oApp);
				/* 限通讯录用户访问 */
				if (false === $oResult[0]) {
					if (true === $bRedirect) {
						$aMemberSchemas = array_keys(get_object_vars($oEntryRule->member));
						$this->gotoMember($oApp, $aMemberSchemas);
					} else {
						$msg = '您目前不满足【' . $oApp->title . '】的进入规则，无法访问，请联系活动的组织者解决';
						return [false, $msg];
					}
				}
			} else if ($oEntryRule->scope === 'sns') {
				$aResult = $this->enterAsSns($oApp);
				if (false === $aResult[0]) {
					$msg = '您目前不满足【' . $oApp->title . '】的进入规则，无法访问，请联系活动的组织者解决';
					if (true === $bRedirect) {
						if (!empty($oEntryRule->sns->wx->entry) && $oEntryRule->sns->wx->entry === 'Y') {
							/* 通过邀请链接访问 */
							if (!empty($_GET['inviteToken'])) {
								$oApp->params = new \stdClass;
								$oApp->params->inviteToken = $_GET['inviteToken'];
							}
							$this->snsWxQrcodeFollow($oApp);
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
	/**
	 * 检查登记活动进入规则
	 *
	 * @param object $oApp
	 * @param boolean $redirect
	 *
	 */
	protected function _checkAssocSns($oApp, $bRedirect = false) {
		$oUser = $this->who;
		$oAssocSns = $oApp->assocSns;
		$bRequireCheck = false;
		$bMatched = false;
		foreach ($oAssocSns as $snsName => $valid) {
			if ($valid !== 'Y') {
				continue;
			}
			$bRequireCheck = true;
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
		if (true === $bRequireCheck && false === $bMatched) {
			$msg = '您目前不满足【' . $oApp->title . '】的进入规则，无法访问，请联系活动的组织者解决';
			if (true === $bRedirect) {
				if (!empty($oAssocSns->wx) && $oAssocSns->wx === 'Y') {
					/* 通过邀请链接访问 */
					if (!empty($_GET['inviteToken'])) {
						$oApp->params = new \stdClass;
						$oApp->params->inviteToken = $_GET['inviteToken'];
					}
					$rst = $this->model('sns\wx\call\qrcode')->createOneOff($oApp->siteid, $oApp);
					if ($rst[0] === false) {
						$this->snsFollow($oApp->siteid, 'wx', $oApp);
					} else {
						$sceneId = $rst[1]->scene_id;
						$this->snsFollow($oApp->siteid, 'wx', false, $sceneId);
					}
				} else if (!empty($oAssocSns->qy) && $oAssocSns->qy === 'Y') {
					$this->snsFollow($oApp->siteid, 'qy', $oApp);
				} else if (!empty($oAssocSns->yx) && $oAssocSns->yx === 'Y') {
					$this->snsFollow($oApp->siteid, 'yx', $oApp);
				} else {
					$this->outputInfo($msg);
				}
			} else {
				return [false, $msg];
			}
		}

		return [true];
	}
}