<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动打分结果
 */
class marks extends base {
	/**
	 * 打分题汇总信息
	 */
	public function get_action($app, $rid = '', $gid = '') {
		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$rid = empty($rid) ? [] : explode(',', $rid);

		// 查询结果
		$modelRec = $this->model('matter\enroll\record');
		$oResult = $modelRec->score4Schema($oApp, $rid, $gid);

		return new \ResponseData($oResult);
	}
}