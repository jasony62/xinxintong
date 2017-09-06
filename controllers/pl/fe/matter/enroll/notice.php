<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 给登记用户发送通知
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
	 * 给登记活动的参与人发消息
	 *
	 * @param string $site site'id
	 * @param string $app app'id
	 * @param string $tmplmsg 模板消息id
	 *
	 */
	public function send_action($site, $app, $tmplmsg, $rid = null) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFountError();
		}

		$modelRec = $this->model('matter\enroll\record');
		$site = $modelRec->escape($site);
		$posted = $this->getPostJson();
		$params = $posted->message;

		if (isset($posted->criteria)) {
			// 筛选条件
			$criteria = $posted->criteria;
			$options = [
				'rid' => $rid,
			];
			$enrollers = $modelRec->enrolleeByApp($oApp, $options, $criteria);
		} else if (isset($posted->users)) {
			// 直接指定
			$enrollers = $posted->users;
		}

		if (count($enrollers)) {
			$rst = $this->notifyWithMatter($oApp, $enrollers, $tmplmsg, $params);
			if ($rst[0] === false) {
				return new \ResponseError($rst[1]);
			}
		}

		return new \ResponseData($enrollers);
	}
	/**
	 * 给用户发送素材
	 */
	protected function notifyWithMatter(&$oApp, &$oUsers, $tmplmsgId, &$params) {
		if (count($oUsers)) {
			$receivers = [];
			foreach ($oUsers as $oUser) {
				$receiver = new \stdClass;
				isset($oUser->enroll_key) && $receiver->assoc_with = $oUser->enroll_key;
				$receiver->userid = $oUser->userid;
				$receivers[] = $receiver;
			}
			$oUser = $this->accountUser();
			$modelTmplBat = $this->model('matter\tmplmsg\batch');
			$creater = new \stdClass;
			$creater->uid = $oUser->id;
			$creater->name = $oUser->name;
			$creater->src = 'pl';
			$modelTmplBat->send($oApp->siteid, $tmplmsgId, $creater, $receivers, $params, ['send_from' => 'enroll:' . $oApp->id]);
		}

		return array(true);
	}
	/**
	 * 查看通知发送日志
	 *
	 * @param int $batch 通知批次id
	 */
	public function logList_action($batch) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmplBat = $this->model('matter\tmplmsg\batch');
		$batch = $modelTmplBat->escape($batch);
		$q = [
			'de.*,e.nickname',
			'xxt_log_tmplmsg_detail de,xxt_enroll_user e',
			"de.batch_id = $batch and e.userid = de.userid and e.rid = 'ALL'",
		];

		$logs = $modelTmplBat->query_objs_ss($q);
		$result = new \stdClass;
		$result->logs = $logs;

		/* 和登记记录进行关联 */
		if (count($logs)) {
			$modelRec = $this->model('matter\enroll\record');
			$records = [];
			foreach ($logs as $log) {
				if (empty($log->assoc_with)) {
					$record = new \stdClass;
					$record->userid = $log->userid;
					$record->nickname = $log->nickname;
					$record->noticeStatus = $log->status;
					$records[] = $record;
					continue;
				}
				if ($record = $modelRec->byId($log->assoc_with)) {
					$record->noticeStatus = $log->status;
					$records[] = $record;
				}
			}
			$result->records = $records;
		}

		return new \ResponseData($result);
	}
}