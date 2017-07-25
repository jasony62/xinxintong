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
	public function list_action($site, $page = 1, $size = 10) {
		$user = $this->who;
		if (!isset($user->unionid)) {
			return new \ResponseError('请登录后再进行该操作');
		}

		$q = [
			'id,batch_id,data,status',
			'xxt_log_tmplmsg_detail',
			["siteid" => $this->siteId, "userid" => $user->uid],
		];
		$q2 = ['o' => 'id desc'];
		!empty($page) && !empty($size) && $q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];

		$result = new \stdClass;
		$modelBat = $this->model('matter\tmplmsg\batch');
		$logs = $modelBat->query_objs_ss($q, $q2);
		if (count($logs)) {
			foreach ($logs as &$log) {
				if (!empty($log->data)) {
					$log->data = json_decode($log->data);
				}
				if ($log->batch_id) {
					if ($log->batch = $modelBat->byId($log->batch_id, ['fields' => 'create_at,remark,params'])) {
						if(!empty($log->batch->params)){
							$log->batch->params = json_decode($log->batch->params);
							if(!empty($log->batch->params->url)){
								$log->batch->remark .= "\n<a href = " . $log->batch->params->url . ">查看详情</a>";
							}
						}
					}
				}
			}
		}
		$result->logs = $logs;

		$q[0] = 'count(*)';
		$result->total = $modelBat->query_val_ss($q);

		return new \ResponseData($result);
	}
	/**
	 * 查看未读通知发送日志
	 *
	 * @param string $sendTo 发送渠道
	 */
	public function uncloseList_action($site, $page = 1, $size = 10) {
		$user = $this->who;
		if (!isset($user->unionid)) {
			return new \ResponseError('请登录后再进行该操作');
		}

		$modelTmplBat = $this->model('matter\tmplmsg\batch');

		$q = [
			'*',
			'xxt_log_tmplmsg_detail',
			['siteid' => $site, 'userid' => $user->uid, 'close_at' => 0],
		];
		$q2 = ['o' => 'id desc'];
		!empty($page) && !empty($size) && $q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];

		$logs = $modelTmplBat->query_objs_ss($q, $q2);
		$result = new \stdClass;
		foreach ($logs as &$log) {
			if (!empty($log->data)) {
				$log->data = json_decode($log->data);
			}
			if ($batch = $modelTmplBat->byId($log->batch_id)) {
				if(!empty($batch->params)){
					$batch->params = json_decode($batch->params);
					if(!empty($batch->params->url)){
						$batch->remark .= "\n<a href = " . $batch->params->url . ">查看详情</a>";
					}
				}
			}
			$log->batch = $batch;
		}
		$result->logs = $logs;

		$q[0] = 'count(*)';
		$result->total = $modelTmplBat->query_val_ss($q);

		return new \ResponseData($result);
	}
	/**
	 * 关闭未读通知
	 * 只允许自己关闭
	 *
	 * @param int $id 通知日志id
	 *
	 */
	public function close_action($site, $id) {
		$user = $this->who;
		if (!isset($user->unionid)) {
			return new \ResponseError('请登录后再进行该操作');
		}

		$model = $this->model();
		$q = [
			'*',
			'xxt_log_tmplmsg_detail',
			['id' => $id],
		];
		$log = $model->query_obj_ss($q);
		if (false === $log) {
			return new \ObjectNotFoundError();
		}
		if ($log->userid !== $user->uid) {
			return new \ResponseError('没有关闭通知的权限');
		}
		$rst = $model->update('xxt_log_tmplmsg_detail', ['close_at' => time()], ['id' => $id]);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function count_action($site) {
		$user = $this->who;
		if (!isset($user->unionid)) {
			return new \ResponseError('请登录后再进行该操作');
		}

		$model = $this->model();
		$q = [
			'count(*)',
			'xxt_log_tmplmsg_detail',
			['siteid' => $site, 'userid' => $user->uid, 'close_at' => 0],
		];

		$count = $model->query_val_ss($q);

		return new \ResponseData($count);
	}
}