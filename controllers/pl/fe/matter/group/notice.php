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
	public function send_action() {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRec = $this->model('matter\group\player');
		$posted = $this->getPostJson();
		if (empty($posted->app) || empty($posted->tmplmsg)) {
			return new \ResponseError('参数不完整');
		}
		$app = $modelRec->escape($posted->app);
		$tmplmsg = $modelRec->escape($posted->tmplmsg);
		
		$oApp = $this->model('matter\group')->byId($app);
		if (false === $oApp) {
			return new \ObjectNotFountError();
		}

		if (empty($posted->users)) {
			// 筛选条件
			$options = new \stdClass;
			isset($posted->tags) && $options->tags = $posted->tags;
			$users = $modelRec->byApp($oApp, $options)->players;
		} else {
			// 直接指定
			$users = $posted->users;
		}

		if (count($users)) {
			$params = $posted->message;
			$rst = $this->_notifyWithMatter($oApp->siteid, $app, $users, $tmplmsg, $params);
			if ($rst[0] === false) {
				return new \ResponseError($rst[1]);
			}
		}

		return new \ResponseData($users);
	}
	/**
	 * 给用户发送素材
	 */
	protected function _notifyWithMatter($siteId, $appId, &$users, $tmplmsgId, &$params) {
		if (count($users)) {
			$receivers = [];
			foreach ($users as $user) {
				if (empty($user->enroll_key)) {
					return array(false, '参数错误，缺少用户唯一标识');
				}
				$receiver = new \stdClass;
				$receiver->assoc_with = $user->enroll_key;
				$receiver->userid = $user->userid;
				$receivers[] = $receiver;
			}
			$user = $this->accountUser();
			$creater = new \stdClass;
			$creater->uid = $user->id;
			$creater->name = $user->name;
			$creater->src = 'pl';
			$modelTmplBat = $this->model('matter\tmplmsg\batch');
			$modelTmplBat->send($siteId, $tmplmsgId, $creater, $receivers, $params, ['send_from' => 'group:' . $appId]);
		}

		return array(true);
	}
	/**
	 * 查看通知发送日志
	 *
	 * @param int $batch 通知批次id
	 * @param string $app 分组活动的id
	 */
	public function logList_action($app, $batch) {
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
			$records2 = [];
			foreach ($logs as &$log) {
				if (isset($records2[$log->assoc_with])) {
					$record = clone $records2[$log->assoc_with];
					$record->noticeStatus = $log->status;
					$records[] = $record;
					unset($record);
				} else if ($record = $modelRec->byId($app, $log->assoc_with)) {
					$records2[$log->assoc_with] = clone $record;
					$record->noticeStatus = $log->status;
					$records[] = $record;
					unset($record);
				}
			}
			$result->records = $records;
		}

		return new \ResponseData($result);
	}
}