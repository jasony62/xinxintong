<?php
/**
 * 从移动端到PC端的任务码
 */
class task_model extends TMS_MODEL {
	/**
	 *
	 */
	public function addTask($siteId, $userid, $url) {
		$code = $this->genCode();

		$q = array('1', 'xxt_task', "code='$code'");

		while ('1' === $this->query_val_ss($q)) {
			$code = $this->genCode();
			$q[2] = "code='$code'";
		}

		$task = array(
			'code' => $code,
			'siteid' => $siteId,
			'userid' => $userid,
			'url' => $url,
			'create_at' => time(),
		);

		$this->insert('xxt_task', $task, false);

		return $code;
	}
	/**
	 *
	 */
	public function getTask($code) {
		$q = array(
			'siteid,userid,url,create_at',
			'xxt_task',
			"code='$code'",
		);
		$task = $this->query_obj_ss($q);
		if ($task) {
			$this->delete('xxt_task', "code='$code'");
			if ($task->create_at + 300 < time()) {
				return false;
			}

			return $task;
		}

		return false;
	}
	/**
	 * generate a 4bits code.
	 */
	private static function genCode() {
		$alpha_digits = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$alpha_digits_len = strlen($alpha_digits) - 1;

		$code = '';
		for ($i = 0; $i < 4; $i++) {
			$code .= $alpha_digits[mt_rand(0, $alpha_digits_len)];
		}

		return $code;
	}
}