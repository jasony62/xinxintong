<?php
namespace site\fe\matter\contribute;

require_once dirname(__FILE__) . '/base.php';
/**
 * 审核日志
 */
class reviewlog extends base {
	/**
	 * 待审核稿件
	 */
	public function list_action($site, $matterId, $matterType) {
		$logs = $this->model('matter\\' . $matterType)->reviewlogs($matterId);

		return new \ResponseData($logs);
	}
}