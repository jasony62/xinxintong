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
	 *
	 */
	public function index_action($app) {
		$app = $this->escape($app);
		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,siteid,title,entry_rule']);
		if (false === $oApp || $oApp->state !== '1') {
			$this->outputError('访问的对象不存在');
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
			$this->requireSnsOAuth($oApp);
		}

		/* 检查进入规则 */
		$this->checkEntryRule($oApp, true);

		\TPL::assign('title', $oApp->title);
		\TPL::output('/site/fe/matter/plan/main');
		exit;
	}
	/**
	 * 获得当前用户所属分组活动分组
	 */
	protected function getUserGroup($oApp) {
		$oEntryRule = $oApp->entryRule;
		if (empty($oEntryRule->scope->group) || $oEntryRule->scope->group !== 'Y' || empty($oEntryRule->group->id)) {
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
	 * 获得用户在活动中的昵称
	 */
	protected function getUserNickname($oApp) {

	}
	/**
	 * 检查登记活动进入规则
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
			$oResult = $this->enterAsMember($oApp);
			/**
			 * 限通讯录用户访问
			 * 如果指定的任何一个通讯录要求用户关注公众号，但是用户还没有关注，那么就要求用户先关注公众号，再填写通讯录
			 */
			if (false === $oResult[0]) {
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
					$msg = '您没有填写通讯录信息，不满足【' . $oApp->title . '】的进入规则，无法访问，请联系活动的组织者解决。';
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
						if (!empty($oEntryRule->group->round->id)) {
							if (isset($oGroupUsr->round_id)) {
								if ($oGroupUsr->round_id === $oEntryRule->group->round->id) {
									$bMatched = true;
								}
							}
						} else {
							$bMatched = true;
						}
					}
				}
			}
			if (false === $bMatched) {
				$msg = '您目前没有进行分组，不满足【' . $oApp->title . '】的进入规则，无法访问，请联系活动的组织者解决。';
				if (true === $bRedirect) {
					$this->outputInfo($msg);
				} else {
					return [false, $msg];
				}
			}
		}

		return [true];
	}
}