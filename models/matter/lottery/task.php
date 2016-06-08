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
	 * 获得抽奖活动的任务
	 */
	public function &byApp($lid, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_lottery_task',
			"lid='$lid'",
		);
		if (isset($options['task_type'])) {
			$q[2] .= " and task_type='{$options['task_type']}'";
		}
		$tasks = $this->query_objs_ss($q);
		foreach ($tasks as &$task) {
			$task->task_params = json_decode($task->task_params);
		}
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
	 * 检查用户任务完成情况
	 */
	public function checkUserTask(&$user, $lid, &$lotTask, &$userTask) {
		if ($lotTask->task_type === 'can_play') {
			if ($lotTask->task_name === 'sns_share') {
				/**
				 * 检查是否分享了好友
				 * 没有对分享的时间点进行检查
				 */
				$q = array(
					'count(*)',
					'xxt_log_matter_share',
					"userid='{$user->uid}' and (share_to='F' or share_to='T') and matter_type='lottery' and matter_id='$lid' and share_at>{$userTask->create_at}",
				);
				$shareCount = (int) $this->query_val_ss($q);
				if ($lotTask->task_params->shareCount <= $shareCount) {
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
		} else if ($lotTask->task_type === 'add_chance') {
			if ($lotTask->task_name === 'sns_share') {
				/**
				 * 检查是否分享了好友
				 * 没有对分享的时间点进行检查
				 */
				$q = array(
					'count(*)',
					'xxt_log_matter_share',
					"userid='{$user->uid}' and (share_to='F' or share_to='T') and matter_type='lottery' and matter_id='$lid' and share_at>{$userTask->create_at}",
				);
				$shareCount = (int) $this->query_val_ss($q);
				if ($shareCount > 0) {
					/**
					 * 增加抽奖次数
					 */
					//$this->earnChance($lid, $user->uid, $lotTask->task_params->chanceCount * $shareCount);
					$this->earnChance($lid, $user->uid, $lotTask->task_params->chanceCount);
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
		}

		return false;
	}
	/**
	 * 增加抽奖次数
	 */
	public function earnChance($lid, $userid, $times) {
		$sql = 'update xxt_lottery_log';
		$sql .= " set times_accumulated=times_accumulated-$times";
		$sql .= " where lid='$lid' and userid='$userid' and last='Y'";
		$rst = $this->update($sql);

		return $rst == '1';
	}
}