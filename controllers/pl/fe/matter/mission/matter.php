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
	 * @param int $id
	 */
	public function list_action($site, $id) {
		$matters = $this->model('mission')->mattersById($site, $id);

		return new \ResponseData($matters);
	}
}