<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动用户
 */
class user extends base {
	/**
	 * 返回当前用户任务完成的情况
	 */
	public function task_action($app) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->who;
		$options = [];
		if ($oActiveRound = $this->model('matter\enroll\round')->getActive($oApp)) {
			$options['rid'] = $oActiveRound->rid;
		}
		$oEnrollee = $this->model('matter\enroll\user')->byId($oApp, $oUser->uid, $options);

		return new \ResponseData($oEnrollee);
	}
}