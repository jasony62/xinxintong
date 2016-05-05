<?php
namespace pl\fe\matter\lottery;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 抽奖活动控制器
 */
class task extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/lottery/frame');
		exit;
	}
	/**
	 * 添加任务
	 */
	public function add_action($site, $app) {
		$posted = $this->getPostJson();

		$taskId = uniqid();
		$newTask = array(
			'siteid' => $site,
			'lid' => $app,
			'tid' => $taskId,
			'siteid' => $site,
			'title' => isset($posted->title) ? $posted->title : '',
			'task_type' => $posted->task_type,
			'task_name' => $posted->task_name,
			'task_params' => '{}',
			'description' => '',
		);
		$this->model()->insert('xxt_lottery_task', $newTask, false);
		$newTask['task_params'] = new \stdClass;

		return new \ResponseData($newTask);
	}
	/**
	 *
	 */
	public function update_action($site, $app, $task) {
		$posted = $this->getPostJson();

		$model = $this->model();
		$newTask = array();
		isset($posted->title) && $newTask['title'] = $posted->title;
		isset($posted->task_params) && $newTask['task_params'] = $model->toJson($posted->task_params);
		isset($posted->description) && $newTask['description'] = $posted->description;
		$rst = $model->update(
			'xxt_lottery_task',
			$newTask,
			"siteid='$site' and tid='$task'"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function remove_action($site, $app, $task) {
		$rst = $this->model()->delete(
			'xxt_lottery_task',
			"siteid='$site' and tid='$task'"
		);

		return new \ResponseData($rst);
	}
}