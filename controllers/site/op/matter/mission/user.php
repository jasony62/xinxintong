<?php
namespace site\op\matter\mission;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/*
 * 项目控制器
 */
class user extends \site\op\base {
	/**
	 * 获得项目的用户列表
	 *
	 * @param int $mission mission's id
	 */
	public function list_action($mission, $page = 1, $size = 30) {
		$mission = $this->model('matter\mission')->byId($mission, ['fields' => 'user_app_id,user_app_type']);
		if ($mission === false) {
			return new \ObjectNotFoundError();
		}

		$criteria = $this->getPostJson();
		$options = [
			'page' => $page,
			'size' => $size,
		];

		$modelUsr = $this->model('matter\mission\user');
		$result = $modelUsr->byMission($mission, $criteria, $options);
		if ($result[0] === false) {
			return new \ResponseError($result[1]);
		}

		return new \ResponseData($result[1]);
	}
}