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
		$oGroupUsr = $this->model('matter\group\record')->byUser($oGroupApp, $oUser->uid, ['fields' => 'team_id,team_title']);

		if (count($oGroupUsr)) {
			$oGroupUsr = $oGroupUsr[0];
			if (isset($oGroupApp->team->id)) {
				if ($oGroupUsr->team_id === $oGroupApp->team->id) {
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
}