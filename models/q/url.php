<?php
namespace q;
/**
 * 短地址任务链接
 */
class url_model extends \TMS_MODEL {
	/**
	 * 生成任务
	 *
	 * @param string $siteId
	 * @param string $userId
	 * @param string $url
	 *
	 * @return string code
	 */
	public function add(&$user, $siteId, $url, $title = '') {
		$code = $this->genCode();

		$q = ['1', 'xxt_short_url', ['code' => $code, 'state' => 1]];

		while ('1' === $this->query_val_ss($q)) {
			$code = $this->genCode();
			$q[2] = ['code' => $code, 'state' => 1];
		}

		$item = [
			'code' => $code,
			'siteid' => $siteId,
			'creater' => $user->id,
			'creater_name' => $this->escape($user->name),
			'target_title' => $title,
			'target_url' => $url,
			'create_at' => time(),
		];

		$this->insert('xxt_short_url', $item, false);

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
	public function remove(&$user, $siteId, $url) {
		$rst = $this->delete(
			'xxt_short_url',
			["target_url" => $url]
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
	public function byUrl(&$user, $siteId, $url) {
		$q = [
			'code,create_at,password,expire_at,can_favor,target_title',
			'xxt_short_url',
			["target_url" => $url],
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
			'target_title,target_url,create_at,password,can_favor',
			'xxt_short_url',
			["code" => $code],
		];
		$task = $this->query_obj_ss($q);
		if ($task) {
			//$this->delete('xxt_short_url', ["code" => $code]);
			//if ($task->create_at + 300 < time()) {
			//	return false;
			//}

			return $task;
		}

		return false;
	}
	/**
	 * 任务访问链接
	 */
	public function urlByCode($code) {
		$url = APP_PROTOCOL . APP_HTTP_HOST;
		$url .= "/q/{$code}";

		return $url;
	}
	/**
	 * 根据链接获得短链接
	 *
	 * @param string $siteId
	 * @param string $url
	 */
	public function urlByUrl($siteId, $url) {
		$user = new \stdClass;
		$task = $this->byUrl($user, $siteId, $url);
		if (false === $task) {
			return false;
		}
		$qUrl = $this->urlByCode($task->code);

		return $qUrl;
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