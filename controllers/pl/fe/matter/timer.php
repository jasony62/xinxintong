<?php
namespace pl\fe\matter;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 定时任务
 */
class timer extends \pl\fe\base {

	private $meterial; // 素材

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 * 事件的类型
	 */
	protected function getCallType() {
		return 'Timer';
	}
	/**
	 * get all text call.
	 */
	public function index_action() {
		\TPL::output('/mp/reply/timer');
		exit;
	}
	/**
	 * get all text call.
	 */
	public function get_action($site) {
		$modelTimer = $this->model('matter\timer');
		$timers = $modelTimer->bySite($site);

		foreach ($timers as &$timer) {
			$timer->matter = $modelTimer->getMatterInfoById($timer->matter_type, $timer->matter_id);
		}

		return new \ResponseData($timers);
	}
	/**
	 * 添加定时推送
	 */
	public function create_action($site) {
		$matter = $this->getPostJson();

		$timer = new \stdClass;
		$timer->matter_type = $matter->type;
		$timer->matter_id = $matter->id;
		$timer->siteid = $site;

		$modelTimer = $this->model('matter\timer');
		$id = $modelTimer->insert('xxt_timer_push', $timer, true);

		$timer = $modelTimer->byId($id);

		$timer->matter = $modelTimer->getMatterInfoById($matter->type, $matter->id);

		return new \ResponseData($timer);
	}
	/**
	 * 删除文本命令
	 */
	public function delete_action($id) {
		$rsp = $this->model()->delete('xxt_timer_push', ['id' => $id]);

		return new \ResponseData($rsp);
	}
	/**
	 * 更新属性信息
	 *
	 * $id
	 * $nv array 0:name,1:value
	 */
	public function update_action($site, $id) {
		$nv = $this->getPostJson();

		foreach ($nv as $n => $v) {
			if (in_array($n, array('min', 'hour', 'mday', 'mon', 'wday'))) {
				$v === '忽略' && $nv->{$n} = -1;
			}
		}

		$rst = $this->model()->update(
			'xxt_timer_push',
			(array) $nv,
			['siteid' => $site, 'id' => $id]
		);

		return new \ResponseData($rst);
	}
	/**
	 * 指定文本项的回复素材
	 */
	public function setreply_action($site, $id) {
		$reply = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_timer_push',
			array(
				'matter_type' => $reply->rt,
				'matter_id' => $reply->rid,
			),
			['siteid' => $site, 'id' => $id]
		);

		return new \ResponseData($rst);
	}
	/**
	 * 获得执行日志
	 */
	public function logGet_action($taskid) {
		$q = array(
			'*',
			'xxt_log_timer',
			['task_id' => $taskid]
		);
		$q2 = array('o' => 'occur_at desc');

		$logs = $this->model()->query_objs_ss($q, $q2);

		return new \ResponseData($logs);
	}
}
