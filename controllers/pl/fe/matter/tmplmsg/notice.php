<?php
namespace pl\fe\matter\tmplmsg;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 *
 */
class notice extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 已经发送过的通知
	 */
	public function list_action($sender) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmplBat = $this->model('matter\tmplmsg\batch');
		$q = [
			'*',
			'xxt_log_tmplmsg_batch',
			"send_from='" . $modelTmplBat->escape($sender) . "'",
		];
		$q2 = ['o' => 'create_at desc'];

		$batches = $modelTmplBat->query_objs_ss($q, $q2);

		$result = new \stdClass;
		$result->batches = $batches;
		$result->total = count($batches);

		return new \ResponseData($result);
	}
	/**
	 * 发送最后一条通知
	 */
	public function last_action($sender) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmplBat = $this->model('matter\tmplmsg\batch');
		$q = [
			'*',
			'xxt_log_tmplmsg_batch',
			"send_from='" . $modelTmplBat->escape($sender) . "'",
		];
		$q2 = ['o' => 'create_at desc', 'r' => ['o' => 0, 'l' => 1]];

		$batches = $modelTmplBat->query_objs_ss($q, $q2);
		if (count($batches) === 1) {
			$batch = $batches[0];
			if (!empty($batch->params)) {
				$batch->params = json_decode($batch->params);
			}
			return new \ResponseData($batch);
		} else {
			return new \ResponseData(false);
		}
	}
	/**
	 *
	 */
	public function detail_action($batch) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmplBat = $this->model('matter\tmplmsg\batch');
		$q = [
			'*',
			'xxt_log_tmplmsg_detail',
			["batch_id" => $batch],
		];

		$logs = $modelTmplBat->query_objs_ss($q);

		$result = new \stdClass;
		$result->logs = $logs;

		return new \ResponseData($result);
	}
}