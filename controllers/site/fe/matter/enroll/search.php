<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动标签
 */
class search extends base {
	/*
	 *
	 */
	public function moveUserSearch_action($site, $app, $search) {
		
		return false;
	}
	/*
	 *
	 */
	public function listUserSearch_action($site, $app, $page = 1, $size = 10) {
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError('指定的活动不存在或以删除');
		}

		$user = $this->getUser($oApp);
		$options = [];
		if (!empty($page) && !empty($size)) {
			$options['page'] = $page;
			$options['size'] = $size;
		}
		$searchs = $this->model('matter\enroll\search')->listUserSearch($oApp, $user, $options);

		return new \ResponseData($searchs);
	}
}