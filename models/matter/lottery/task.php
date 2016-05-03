<?php
namespace matter\lottery;
/**
 *
 */
class task_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id, $fields = '*') {
		$q = array(
			$fields,
			'xxt_lottery_task',
			"aid='$id'",
		);
		$task = $this->query_obj_ss($q);

		return $task;
	}
	/**
	 *
	 */
	public function &byApp($lid, $fields = '*') {
		$q = array(
			$fields,
			'xxt_lottery_task',
			"lid='$lid'",
		);
		$tasks = $this->query_objs_ss($q);

		return $tasks;
	}
	/**
	 * 给指定用户创建任务
	 */
	public function &getTaskByUser(&$user, $lid, $taskId) {
		$task = false;
		/**
		 * 一个活动可能产生多个任务，只有最新创建的任务是有效任务
		 */
		$q = array(
			'id,tid,finished,create_at',
			'xxt_lottery_task_log',
			"lid='$lid' and tid='$taskId' and userid='{$user->uid}'",
		);
		$q2 = array(
			'o' => 'create_at desc',
			'r' => array('o' => 0, 'l' => 1),
		);
		$tasks = $this->query_objs_ss($q, $q2);
		if (count($tasks) === 1) {
			$task = $tasks[0];
		}

		return $task;
	}
	/**
	 * 给指定用户创建任务
	 */
	public function addTask4User(&$user, $lid, $taskId) {
		$t = array();
		$t['lid'] = $lid;
		$t['userid'] = $user->uid;
		$t['nickname'] = $user->nickname;
		$t['create_at'] = time();
		$t['tid'] = $taskId;

		$t['id'] = $this->insert('xxt_lottery_task_log', $t, true);

		return (object) $t;
	}
	/**
	 * 检查用户任务是否完成
	 */
	public function checkUserTask(&$user, $lid, &$lotTask, &$userTask) {
		/**
		 * 有任务，没有完成
		 */
		if ($lotTask->task_type === 'sns_share') {
			/**
			 * 检查是否分享了好友
			 * 没有对分享的时间点进行检查
			 */
			$q = array(
				'count(*)',
				'xxt_log_matter_share',
				"userid='{$user->uid}' and (share_to='F' or share_to='T') and matter_type='lottery' and matter_id='$lid' and share_at>{$userTask->create_at}",
			);
			if ($lotTask->task_params->shareCount <= (int) $this->query_val_ss($q)) {
				/**
				 * 修改任务状态
				 */
				$this->update(
					'xxt_lottery_task_log',
					array('finished' => 'Y'),
					"id={$userTask->id}"
				);
				return true;
			}
		}

		return false;
	}
}