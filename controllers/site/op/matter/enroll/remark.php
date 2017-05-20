<?php
namespace site\op\matter\enroll;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/*
 * 登记活动主控制器
 */
class remark extends \site\op\base {
/**
 * 返回一条登记记录的所有评论
 */
	public function list_action($ek, $schema = '', $page = 1, $size = 99) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$oUser = new \stdClass;

		$result = $this->model('matter\enroll\remark')->listByRecord($oUser, $ek, $schema, $page, $size);

		return new \ResponseData($result);
	}
}