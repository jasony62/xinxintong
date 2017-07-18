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
			$modelTmplBat = $this->model('matter\tmplmsg\plbatch');
			$q = [
				'*',
				'xxt_log_tmplmsg_pldetail',
				["userid" => $user->unionid],
			];
			$q2 = ['o' => 'id desc'];
			// 查询结果分页
			if (!empty($page) && !empty($size)) {
				$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
			}
			
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
	/**
	 * 查看未读通知发送日志
	 *
	 * @param string $sendTo 发送渠道
	 */
	public function uncloseList_action($page = 1, $size = 10, $sendTo = 'pl') {
		$user = $this->who;

		if(!empty($user->unionid)){
			$modelTmplBat = $this->model('matter\tmplmsg\plbatch');
			$q = [
				'*',
				'xxt_log_tmplmsg_pldetail',
				['userid' => $user->unionid, 'close_at' => 0, 'send_to' => $sendTo],
			];
			$q2 = ['o' => 'id desc'];
			// 查询结果分页
			if (!empty($page) && !empty($size)) {
				$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
			}

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
	/**
	 * 关闭未读通知
	 * 只允许自己关闭
	 *
	 * @param int $id 通知日志id
	 *
	 */
	public function close_action($id) {
		$user = $this->who;

		if(!empty($user->unionid)){
			$model = $this->model();
			$q = [
				'*',
				'xxt_log_tmplmsg_pldetail',
				['id' => $id],
			];
			$log = $model->query_obj_ss($q);
			if (false === $log) {
				return new \ObjectNotFoundError();
			}
			if ($log->userid !== $user->unionid) {
				return new \ResponseError('没有删除通知的权限');
			}
			$rst = $model->update('xxt_log_tmplmsg_pldetail', ['close_at' => time()], ['id' => $id]);
		}else{
			$result = new \stdClass;
			$result->logs = new \stdClass;
			$result->total = 0;
		}

		return new \ResponseData($rst);
	}
}