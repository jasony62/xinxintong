<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动标签
 */
class tag extends base {
	/**
	 *
	 */
	public function create_action($site, $app) {
		/* 登记活动定义 */
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ResponseError('指定的登记活动不存在，请检查参数是否正确');
		}

		$posted = $this->getPostJson();
		$user = $this->who;

		$newTags = $this->model('matter\enroll\tag')->add($oApp, $user, $posted);

		return new \ResponseData($newTags);
	}
}