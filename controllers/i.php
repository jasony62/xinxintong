<?php
/**
 * 用户邀请
 */
class i extends TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'index';

		return $rule_action;
	}
	/**
	 * 访问用户邀请页
	 *
	 * @param string $code 链接的编
	 *
	 */
	public function index_action($code = null) {
		if (empty($code)) {
			TPL::assign('title', APP_TITLE);
			TPL::output('site/fe/invite/entry');
			exit;
		}
		/**
		 * 检查邀请是否可用
		 */
		$modelInv = $this->model('invite');
		$oInvite = $modelInv->byCode($code);
		if (false === $oInvite) {
			TPL::assign('title', APP_TITLE);
			$this->outputError('指定编码【' . $code . '】的邀请不存在');
		}
		if (empty($oInvite->matter_id) || empty($oInvite->matter_type)) {
			TPL::assign('title', APP_TITLE);
			$this->outputError('指定编码【' . $code . '】的邀请不可用');
		}
		$modelMat = $this->model('matter\\' . $oInvite->matter_type);
		$oMatter = $modelMat->byId($oInvite->matter_id, ['fields' => 'siteid,id']);
		if (false === $oMatter) {
			$this->outputError('邀请访问的素材【' . $oInvite->matter_title . '】不存在');
		}

		/* 被邀请的用户 */
		$modelWay = $this->model('site\fe\way');
		$oInvitee = $modelWay->who($oInvite->matter_siteid);

		/* 如果当前用户已经被邀请过，就不再进行验证或记录日志 */
		$modelInvLog = $this->model('invite\log');
		$aInviteLogs = $modelInvLog->byUser($oMatter, $oInvitee->uid);
		if (0 === count($aInviteLogs)) {
			if (!empty($oInvite->require_code) && $oInvite->require_code === 'Y') {
				/* 需要邀请码 */
				if (!empty($_POST['inviteCode'])) {
					$inviteCode = $_POST['inviteCode'];
					$modelCode = $this->model('invite\code');
					$result = $modelCode->checkAndUse($oInvite, $inviteCode, $oInvitee);
					if (false === $result[0]) {
						$this->outputError($result[1]);
					}
					$oInviteLog = $result[1];
				} else {
					TPL::assign('title', empty($oInvite->matter_title) ? APP_TITLE : $oInvite->matter_title);
					TPL::output('site/fe/invite/code');
					exit;
				}
			} else {
				/* 记录访问日志 */
				$oInviteCode = new \stdClass;
				$oInviteCode->invite_id = $oInvite->id;
				$oInviteCode->id = 0;
				$oInviteCode->last_use_at = time();
				$oInviteLog = $modelInvLog->add($oInvite, $oInviteCode, $oInvitee);
			}
		} else {
			$oInviteLog = $aInviteLogs[0];
		}

		/* 更新邀请访问数据 */
		$modelInv->addInviterCount($oInvite);

		/**
		 * 设置访问控制，生成token
		 */
		$oAccessToken = $this->model('invite\token')->add($oInviteLog);

		$matterUrl = $modelMat->getEntryUrl($oMatter->siteid, $oMatter->id);
		$matterUrl .= strpos($matterUrl, '?') === false ? '?' : '&';
		$matterUrl .= 'inviteToken=' . $oAccessToken->token;

		$this->redirect($matterUrl);
	}
	/**
	 *
	 */
	protected function outputError($err, $title = '程序错误') {
		TPL::assign('title', $title);
		TPL::assign('body', $err);
		TPL::output('error');
		exit;
	}
}