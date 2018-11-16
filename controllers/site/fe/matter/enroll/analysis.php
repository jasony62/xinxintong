<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记行为追踪
 */
class analysis extends base {
	/**
	 *
	 */
	public function submit_action($app, $page, $record = '', $topic = '', $rid = '') {
		if (empty($app) || empty($page)) {
			return new \ParameterError();
		}

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError('指定的记录活动不存在，请检查参数是否正确');
		}
		$oPosted = $this->getPostJson();
		if (empty($oPosted)) {
			return new \ParameterError();
		}

		$oUser = $this->getUser($oApp);
		if (empty($rid)) {
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$rid = $activeRound->rid;
			}
		}

		$oClient = new \stdClass;
		$oClient->agent = $_SERVER['HTTP_USER_AGENT'];
		$oClient->ip = $this->client_ip();

		$aResult = $this->model('matter\enroll\analysis')->submit($oApp, $rid, $oUser, $oPosted, $page, $record, $topic, $oClient);
		if ($aResult[0] === false) {
			return new \ResponseError($aResult[1]);
		}

		return new \ResponseData($aResult[1]);
	}
}