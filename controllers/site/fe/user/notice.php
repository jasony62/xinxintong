<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户收到的通知
 */
class notice extends \site\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/notice/main');
		exit;
	}
	/**
	 *
	 *
	 */
	public function list_action($page = 1, $size = 10) {
		$user = $this->who;

		$q = [
			'id,batch_id,data,status',
			'xxt_log_tmplmsg_detail',
			["siteid" => $this->siteId, "userid" => $user->uid],
		];
		$q2 = ['r' => ['o' => ($page - 1) * $size, 'l' => $size]];

		$result = new \stdClass;
		$modelBat = $this->model('matter\tmplmsg\batch');
		$logs = $modelBat->query_objs_ss($q, $q2);
		if (count($logs)) {
			foreach ($logs as &$log) {
				if (!empty($log->data)) {
					$log->data = json_decode($log->data);
				}
				if ($log->batch_id) {
					$log->batch = $modelBat->byId($log->batch_id, ['fields' => 'create_at,remark']);
				}
			}
		}
		$result->logs = $logs;

		$q[0] = 'count(*)';
		$result->total = $modelBat->query_val_ss($q);

		return new \ResponseData($result);
	}
}