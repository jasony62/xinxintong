<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户收藏
 */
class message extends \site\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/message/main');
		exit;
	}
	/**
	 * 查看通知发送日志
	 *
	 * @param int $batch 通知批次id
	 */
	public function list_action($page = 1, $size = 10) {
		$user = $this->who;

		if(!empty($user->unionid)){
			$modelTmplBat = $this->model('matter\tmplmsg\batch');
			$q = [
				'*',
				'xxt_log_tmplmsg_pldetail',
				["userid" => $user->unionid],
			];
			$q2 = ['o' => 'id desc'];

			$logs = $modelTmplBat->query_objs_ss($q, $q2);
			$result = new \stdClass;
			foreach ($logs as &$log) {
				$batch = $modelTmplBat->byId($log->batch_id);
				$log->batch = $batch;
			}
			$result->logs = $logs;

			$q[0] = 'count(*)';
			$result->total = $modelTmplBat->query_val_ss($q);
		}else{
			$result = new \stdClass;
			$result->logs = new \stdClass;
			$result->total = 0;
		}

		return new \ResponseData($result);
	}
}