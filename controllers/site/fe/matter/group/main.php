<?php
namespace site\fe\matter\group;

include_once dirname(__FILE__) . '/base.php';
/**
 * 活动
 */
class main extends base {
	/**
	 *
	 */
	public function index_action() {
		if (!$this->groupApp) {
			$this->outputError('分组活动不存在或不可用！');
		}
		\TPL::assign('title', $this->groupApp->title);
		\TPL::output('/site/fe/matter/group/main');
		exit;
	}
	/**
	 *
	 */
	public function get_action() {
		if (!$this->groupApp) {
			return new \ObjectNotFoundError();
		}

		return new \ResponseData($this->groupApp);
	}
}