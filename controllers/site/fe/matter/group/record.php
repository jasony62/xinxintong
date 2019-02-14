<?php
namespace site\fe\matter\group;

include_once dirname(__FILE__) . '/base.php';
/**
 * 成员
 */
class record extends base {
	/**
	 *
	 */
	public function list_action($team) {
		if (!$this->groupApp) {
			return new \ObjectNotFoundError();
		}

		$records = $this->model('matter\group\record')->byTeam($team, ['fields' => 'id,userid,draw_at,enroll_at,is_leader,nickname']);

		return new \ResponseData($records);
	}
}