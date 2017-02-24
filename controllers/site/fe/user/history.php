<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户历史轨迹
 */
class history extends \site\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/history/main');
		exit;
	}
	/**
	 * 获得当前用户在指定站点参与的活动
	 *
	 * @param string $site site'id
	 * @param string $matterType
	 */
	public function appList_action($site, $matterType = 'enroll,signin') {
		$result = new \stdClass;

		$modelLog = $this->model('matter\log');
		$q = [
			'matter_id,matter_type,matter_title,operate_at',
			'xxt_log_user_matter',
			"siteid='" . $modelLog->escape($site) . "' and userid='" . $this->who->uid . "' and user_last_op='Y'",
		];
		if (!empty($matterType)) {
			$matterType = explode(',', $matterType);
			$matterType = "'" . implode("','", $matterType) . "'";
			$q[2] .= " and matter_type in (" . $matterType . ")";
		}

		$logs = $modelLog->query_objs_ss($q);

		$result->apps = $logs;

		return new \ResponseData($result);
	}
	/**
	 * 获得当前用户在指定站点参与的项目
	 *
	 * @param string $site
	 */
	public function missionList_action($site) {
		$result = new \stdClass;

		$modelLog = $this->model('matter\log');
		$q = [
			'distinct mission_id,mission_title',
			'xxt_log_user_matter',
			"siteid='" . $modelLog->escape($site) . "' and userid='" . $this->who->uid . "' and mission_id<>0",
		];
		$logs = $modelLog->query_objs_ss($q);

		$result->missions = $logs;

		return new \ResponseData($result);
	}
}