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
	public function submit_action($site, $app, $rid = '', $page, $record = null, $topic = null) {
		if (empty($app) || empty($page) || (empty($record) && empty($topic))) {
			return new \ParameterError();
		}

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			$this->outputError('指定的登记活动不存在，请检查参数是否正确');
		}
		$oUser = $this->getUser($oApp);
		$oPosted = $this->getPostJson();
		if (empty($oPosted)) {
			return new \ParameterError();
		}

		if (empty($rid)) {
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$rid = $activeRound->rid;
			}
		}

		$results = $this->model('matter\enroll\analysis')->submit($site, $oApp, $rid, $oUser, $oPosted, $page, $record, $topic);

		return new \ResponseData($results);
	}
}