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
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();

		$oUser = $this->getUser($oApp);
		$oUser->creater_src = 'S';
		$newTags = $this->model('matter\enroll\tag')->add($oApp, $oUser, $oPosted);

		return new \ResponseData($newTags);
	}
}