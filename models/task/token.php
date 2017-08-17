<?php
namespace task;
/**
 * 令牌模式，只保存数据，由提取任务
 */
class token_model extends \TMS_MODEL {
	/**
	 * 创建任务
	 *
	 * @param string $siteId 发起任务的站点
	 * @param string $name 任务名称
	 * @param string $creater 创建人
	 * @param int $validity 有效期，单位：秒
	 * @param object $params
	 *
	 * @return string 任务码
	 */
	public function makeTask($siteId, &$oCreater, $name, &$params, $validity) {
		/**
		 * 生成任务码
		 */
		$code = $this->_genCode();
		$q = [
			'1',
			'xxt_task_token',
			"code='$code'",
		];
		while ('1' === $this->query_val_ss($q)) {
			$code = $this->_genCode();
			$q[2] = "code='$code'";
		}
		/**
		 * 创建任务
		 */
		$current = time();
		$task = [
			'siteid' => $siteId,
			'code' => $code,
			'name' => $name,
			'creater' => $oCreater->id,
			'creater_name' => $this->escape($oCreater->name),
			'create_at' => $current,
			'expire_at' => $current + $validity,
		];
		if (!empty($params)) {
			$task['params'] = $this->toJson($params);
		}

		$this->insert('xxt_task_token', $task, false);

		return $code;
	}
	/**
	 * 获得任务数据
	 *
	 * @param string $code
	 * @param array $options
	 *
	 * @return object 任务对象
	 */
	public function &taskByCode($code, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : 'siteid,params,create_at,creater_name';
		$current = time();
		$q = [
			$fields,
			'xxt_task_token',
			"code='" . $this->escape($code) . "' and expire_at>=$current",
		];
		if ($task = $this->query_obj_ss($q)) {
			if (!empty($task->params)) {
				$task->params = json_decode($task->params);
			}
		}

		return $task;
	}
	/**
	 * 关闭任务
	 *
	 * @param object $user 关闭任务的用户
	 * @param string $code 任务码
	 *
	 */
	public function closeTask(&$user, $code) {
		if ($task = $this->taskByCode($code, ['fields' => '*'])) {
			// 删除任务
			$this->delete('xxt_task_token', ["code" => $code]);
			/**
			 * 保存日志
			 */
			$task->disposer = $user->id;
			$task->disposer_name = $user->name;
			$task->dispose_at = time();
			if (!empty($task->params)) {
				$task->params = $this->toJson($task->params);
			}
			$this->insert('xxt_task_token_log', $task, false);

			return true;
		}

		return false;
	}
	/**
	 * generate a 4bits code.
	 */
	private static function _genCode() {
		$alpha_digits = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$alpha_digits_len = strlen($alpha_digits) - 1;

		$code = '';
		for ($i = 0; $i < 4; $i++) {
			$code .= $alpha_digits[mt_rand(0, $alpha_digits_len)];
		}

		return $code;
	}
}