<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 *
 */
class notice extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/group/frame');
		exit;
	}
	/**
	 * 给分组活动的参与人发消息
	 *
	 * @param string $site site'id
	 * @param string $app app'id
	 * @param string $tmplmsg 模板消息id
	 *
	 */
	public function send_action($site, $app, $tmplmsg) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// $modelRec = $this->model('matter\signin');
		$modelRec = $this->model('matter\group\player');
		$site = $modelRec->escape($site);
		$app = $modelRec->escape($app);
		$posted = $this->getPostJson();
		$params = $posted->message;

		if (isset($posted->tags)) {
			// 筛选条件
			$app = $this->model('matter\group')->byId($app);
			$options = new \stdClass;
			$options->tags = $posted->tags;
			$participants = $modelRec->find($site, $app, $options);
		} else if (isset($posted->users)) {
			// 直接指定
			$participants = $posted->users;
		}

		if (count($participants)) {
			$rst = $this->notifyWithMatter($site, $app, $participants, $tmplmsg, $params);
			if ($rst[0] === false) {
				return new \ResponseError($rst[1]);
			}
		}

		return new \ResponseData($participants);
	}
	/**
	 * 给用户发送素材
	 */
	protected function notifyWithMatter($siteId, $appId, &$users, $tmplmsgId, &$params) {
		if (count($users)) {
			$receivers = [];
			foreach ($users as $user) {
				$receiver = new \stdClass;
				$receiver->assoc_with = $user->enroll_key;
				$receiver->userid = $user->userid;
				$receivers[] = $receiver;
			}
			$user = $this->accountUser();
			$modelTmplBat = $this->model('matter\tmplmsg\batch');
			$creater = new \stdClass;
			$creater->uid = $user->id;
			$creater->name = $user->name;
			$creater->src = 'pl';
			$modelTmplBat->send($siteId, $tmplmsgId, $creater, $receivers, $params, ['send_from' => 'group:' . $appId]);
		}

		return array(true);
	}
	/**
	 * 查看通知发送日志
	 *
	 * @param int $batch 通知批次id
	 * @param string $aid 分组活动的id
	 */
	public function logList_action($aid, $batch) {
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

		/* 和登记记录进行关联 */
		if (count($logs)) {
			$modelRec = $this->model('matter\group\player');
			$records = [];
			foreach ($logs as $log) {
				if (empty($log->assoc_with)) {
					continue;
				}
				if ($record = $modelRec->byId($aid, $log->assoc_with)) {
					$record->noticeStatus = $log->status;
					$records[] = $record;
				}
			}
			$result->records = $records;
		}

		return new \ResponseData($result);
	}
}