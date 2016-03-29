<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动签到
 */
class signin extends base {
	/**
	 * 执行签到
	 */
	public function do_action($site, $app) {
		/* 登记应用 */
		$modelApp = \TMS_APP::model('matter\enroll');
		$app = $modelApp->byId($app);
		$user = $this->who;
		$data = $this->getPostJson();
		$modelRec = \TMS_APP::model('matter\enroll\record');
		$rst = $modelRec->signin($site, $app, $user, $data);
		/**
		 * 回复
		 */
		if ($rst) {
			if ($app->success_matter_type && $app->success_matter_id) {
				return array('matter_type' => $app->success_matter_type, 'matter_id' => $app->success_matter_id);
			} else {
				return new \ResponseData("活动【" . $app->title . "】已签到，已登记");
			}
		} else {
			if ($app->failure_matter_type && $app->failure_matter_id) {
				return array('matter_type' => $app->failure_matter_type, 'matter_id' => $app->failure_matter_id);
			} else {
				return new \ResponseData("活动【" . $app->title . "】已签到，未登记");
			}
		}
	}
}