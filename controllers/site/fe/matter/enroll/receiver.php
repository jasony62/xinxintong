<?php
namespace site\fe\matter\enroll;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 登记信息接收人
 */
class receiver extends \site\fe\matter\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 用户作为登记活动事件接收人
	 *
	 * @param string $site
	 * @param string $app
	 *
	 */
	public function join_action($site, $app) {
		$modelApp = $this->model('matter\enroll');
		$modelRev = $this->model('matter\enroll\receiver');

		$oApp = $modelApp->byId($app, array('cascaded' => 'Y'));
		$oUser = $this->getUser($oApp);

		if (!$this->afterSnsOAuth()) {
			if (false === $this->_snsOAuth($oUser, $oApp->siteid)) {
				$this->outputInfo('仅限关注用户访问');
			}
		}

		$uid = $oUser->uid;
		if (false === ($receiver = $modelRev->byUser($site, $uid))) {
			$nickname = $oUser->nickname;
			$modelRev->insert(
				'xxt_enroll_receiver',
				[
					'siteid' => $site,
					'aid' => $oApp->id,
					'join_at' => time(),
					'userid' => $uid,
					'nickname' => empty($nickname) ? '未知姓名' : $modelRev->escape($nickname),
				],
				false
			);
		}

		$this->outputInfo('操作成功');
	}
	/**
	 * 第三方社交帐号认证
	 *
	 * @param string $site
	 */
	private function _snsOAuth($oUser, $siteid) {
		if ($this->userAgent() === 'wx') {
			if (!isset($oUser->sns->wx)) {
				$modelWx = $this->model('sns\wx');
				if ($wxConfig = $modelWx->bySite($siteid)) {
					if ($wxConfig->joined === 'Y') {
						$this->snsOAuth($wxConfig, 'wx');
					}
				} else if ($wxConfig = $modelWx->bySite('platform')) {
					if ($wxConfig->joined === 'Y') {
						$this->snsOAuth($wxConfig, 'wx');
					}
				}
			}
			if (!isset($oUser->sns->qy)) {
				if ($qyConfig = $this->model('sns\qy')->bySite($siteid)) {
					if ($qyConfig->joined === 'Y') {
						$this->snsOAuth($qyConfig, 'qy');
					}
				}
			}
		} else if ($this->userAgent() === 'yx') {
			if (!isset($oUser->sns->yx)) {
				if ($yxConfig = $this->model('sns\yx')->bySite($siteid)) {
					if ($yxConfig->joined === 'Y') {
						$this->snsOAuth($yxConfig, 'yx');
					}
				}
			}
		}

		return false;
	}
}