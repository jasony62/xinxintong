<?php
namespace site\fe;

require_once dirname(__FILE__) . '/base.php';
/**
 * 站点首页
 */
class main extends base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		$site = $this->model('site')->byId($this->siteId);
		\TPL::assign('title', $site->name);
		\TPL::output('/site/fe/main');
		exit;
	}
	/**
	 *
	 */
	public function get_action() {
		$site = $this->model('site')->byId($this->siteId, ['fields' => 'id,name,summary,heading_pic,creater,creater_name']);

		return new \ResponseData($site);
	}
	/**
	 * 站点首页页面定义
	 */
	public function pageGet_action() {
		$site = $this->model('site')->byId($this->siteId);
		$page = $this->model('code\page')->byId($site->home_page_id);

		$param = array(
			'page' => $page,
		);

		return new \ResponseData($param);
	}
	/**
	 * 获得站点自定义用户定义
	 *
	 */
	public function memberSchemalist_action() {
		$modelSchema = $this->model('site\user\memberschema');

		$schemas = $modelSchema->bySite($this->siteId, 'Y');

		return new \ResponseData($schemas);
	}
	/**
	 * 执行定时任务
	 */
	public function timer_action() {
		/**
		 * 查找匹配的定时任务
		 */
		$tasks = $this->model('site\timer')->tasksByTime();
		/**
		 * 记录日志
		 */
		foreach ($tasks as $task) {
			$rsp = $task->exec();
			$log = array(
				'siteid' => $task->siteid,
				'task_id' => $task->id,
				'occur_at' => time(),
				'result' => json_encode($rsp),
			);
			$this->model()->insert('xxt_log_timer', $log, true);
		}

		return new \ResponseData(count($tasks));
	}
}