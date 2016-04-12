<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class matter extends \pl\fe\matter\base {
	/**
	 * 活的任务下的素材
	 *
	 * @param string $site
	 * @param int $id
	 */
	public function list_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$matters = $this->model('mission')->mattersById($site, $id);

		return new \ResponseData($matters);
	}
}