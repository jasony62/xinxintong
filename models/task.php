<?php
/**
 * 短地址任务链接
 */
class task_model extends TMS_MODEL {
	/**
	 * 生成任务
	 *
	 * @param string $siteId
	 * @param string $userId
	 * @param string $url
	 *
	 * @return string code
	 */
	public function addTask($siteId, $userid, $url) {
		$code = $this->genCode();

		$q = ['1', 'xxt_task', ["code" => $code]];

		while ('1' === $this->query_val_ss($q)) {
			$code = $this->genCode();
			$q[2] = "code='$code'";
		}

		$task = [
			'code' => $code,
			'siteid' => $siteId,
			'userid' => $userid,
			'url' => $url,
			'create_at' => time(),
		];

		$this->insert('xxt_task', $task, false);

		return $code;
	}
	/**
	 * 生成任务
	 *
	 * @param string $siteId
	 * @param string $userId
	 * @param string $url
	 *
	 * @return string code
	 */
	public function remove($siteId, $userid, $url) {
		$rst = $this->delete(
			'xxt_task',
			["url" => $url]
		);

		return $rst;
	}
	/**
	 * 根据链接获得任务
	 *
	 * @param string $siteId
	 * @param string $userId
	 * @param string $url
	 *
	 * @return string task
	 *
	 */
	public function byUrl($siteId, $userid, $url) {
		$q = [
			'code,create_at',
			'xxt_task',
			["url" => $url],
		];
		$task = $this->query_obj_ss($q);

		return $task;
	}
	/**
	 * 根据编码获得任务定义
	 *
	 * @param string $code
	 *
	 */
	public function byCode($code) {
		$q = [
			'siteid,userid,url,create_at',
			'xxt_task',
			["code" => $code],
		];
		$task = $this->query_obj_ss($q);
		if ($task) {
			//$this->delete('xxt_task', ["code" => $code]);
			//if ($task->create_at + 300 < time()) {
			//	return false;
			//}

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