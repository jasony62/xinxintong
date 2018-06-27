<?php
namespace pl\fe\site\member;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 消息通知控制器
 */
class notice extends \pl\fe\base {
	/**
	 * 给联系人发送消息
	 *
	 * @param string $schema schema'id
	 * @param string $tmplmsg 模板消息id
	 *
	 */
	public function send_action() {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelSchmUsr = $this->model('site\user\member');
		$posted = $this->getPostJson();
		if (empty($posted->schema) || empty($posted->tmplmsg)) {
			return new \ResponseError('参数不完整');
		}
		$schema = $modelSchmUsr->escape($posted->schema);
		$tmplmsg = $modelSchmUsr->escape($posted->tmplmsg);

		$oSchema = $this->model('site\user\memberschema')->byId($schema);
		if (false === $oSchema) {
			return new \ObjectNotFountError();
		}


		if (empty($posted->users)) {
			$users = $modelSchmUsr->byMschema($schema);
		} else {
			// 直接指定
			$users = $posted->users;
		}
		/* 发送消息 */
		if (count($users)) {
			$params = $posted->message;
			$rst = $this->notifyWithMatter($oSchema, $users, $tmplmsg, $params);
			if ($rst[0] === false) {
				return new \ResponseError($rst[1]);
			}
		}

		return new \ResponseData($users);
	}
	/**
	 * 给用户发送素材
	 */
	protected function notifyWithMatter(&$oSchema, &$users, $tmplmsgId, &$params) {
		if (count($users)) {
			$receivers = [];
			foreach ($users as $user) {
				if (empty($user->id)) {
					return array(false, '参数错误，缺少用户唯一标识');
				}
				$receiver = new \stdClass;
				$receiver->userid = $user->userid;
				$receiver->assoc_with = $user->id;
				$receivers[] = $receiver;
			}
			$oUser = $this->accountUser();
			$creater = new \stdClass;
			$creater->uid = $oUser->id;
			$creater->name = $oUser->name;
			$creater->src = 'pl';
			$modelTmplBat = $this->model('matter\tmplmsg\batch');
			$modelTmplBat->send($oSchema->siteid, $tmplmsgId, $creater, $receivers, $params, ['send_from' => 'schema:' . $oSchema->id]);
		}

		return array(true);
	}
	/**
	 * 查看通知发送日志
	 *
	 * @param int $batch 通知批次id
	 * @param string $schema 分组活动的id
	 */
	public function logList_action($schema, $batch) {
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
			$modelMemb = $this->model('site\user\member');
			$records = [];
			foreach ($logs as &$log) {
				if (isset($records[$log->assoc_with])) {
					$log->record = $records[$log->assoc_with];
				} else if ($record = $modelMemb->byId($log->assoc_with)) {
					$log->record = $record;
					$records[$log->assoc_with] = $record;
				}
			}
		}

		return new \ResponseData($result);
	}
}