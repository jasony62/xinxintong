<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 给登记用户发送通知
 */
class notice extends main_base {
	/**
	 * 给记录活动的参与人发消息
	 *
	 * @param string $app app'id
	 * @param string $tmplmsg 模板消息id
	 *
	 */
	public function send_action($app, $tmplmsg, $rid = null) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFountError();
		}

		$modelEnlUsr = $this->model('matter\enroll\user');
		$oPosted = $this->getPostJson();

		if (isset($oPosted->criteria)) {
			// 筛选条件
			$oCriteria = $oPosted->criteria;
			!empty($oCriteria->rid) && $rid = $oCriteria->rid;
			$aOptions = [
				'rid' => $rid,
				'cascaded' => 'N',
			];
			!empty($oCriteria->onlyEnrolled) && $aOptions['onlyEnrolled'] = $oCriteria->onlyEnrolled;
			$enrollUsers = $modelEnlUsr->enrolleeByApp($oApp, '', '', $aOptions);
			$enrollers = $enrollUsers->users;
		} else if (isset($oPosted->users)) {
			// 直接指定
			$enrollers = $oPosted->users;
		}

		/* 发送消息 */
		if (count($enrollers)) {
			$params = $oPosted->message;
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
				$oReceiver = new \stdClass;
				isset($oUser->enroll_key) && $oReceiver->assoc_with = $oUser->enroll_key;
				$oReceiver->userid = $oUser->userid;
				$receivers[] = $oReceiver;
			}
			$oUser = $this->accountUser();
			$modelTmplBat = $this->model('matter\tmplmsg\batch');
			$oCreator = new \stdClass;
			$oCreator->uid = $oUser->id;
			$oCreator->name = $oUser->name;
			$oCreator->src = 'pl';
			$modelTmplBat->send($oApp->siteid, $tmplmsgId, $oCreator, $receivers, $params, ['send_from' => 'enroll:' . $oApp->id]);
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
		$q = [
			'*',
			'xxt_log_tmplmsg_detail',
			['batch_id' => $batch, 'msgid' => (object) ['op' => '<>', 'pat' => '']],
		];

		$logs = $modelTmplBat->query_objs_ss($q);
		$oResult = new \stdClass;
		$oResult->logs = $logs;

		/* 和登记记录进行关联 */
		if (count($logs)) {
			$modelAcnt = $this->model('site\user\account');
			$modelRec = $this->model('matter\enroll\record');
			$records = [];
			$records2 = [];
			foreach ($logs as $log) {
				$oSiteUser = $modelAcnt->byId($log->userid);
				if (empty($log->assoc_with)) {
					$record = new \stdClass;
					$record->userid = $log->userid;
					$record->nickname = $oSiteUser->nickname;
					$record->noticeStatus = $log->status;
					$records[] = $record;
					continue;
				}
				if (isset($records2[$log->assoc_with])) {
					$record = clone $records2[$log->assoc_with];
					$record->noticeStatus = $log->status;
					$records[] = $record;
					unset($record);
				} else if ($record = $modelRec->byId($log->assoc_with)) {
					$record->noticeStatus = $log->status;
					$records[] = $record;
					$records2[$log->assoc_with] = clone $record;
					unset($record);
				}
			}
			$oResult->records = $records;
		}

		return new \ResponseData($oResult);
	}
}