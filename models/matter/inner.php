<?php
namespace matter;

require_once dirname(__FILE__) . '/base.php';

class inner_model extends base_model {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_inner';
	}
	/**
	 * 返回进行推送的消息格式
	 *
	 * $runningMpid
	 * $id
	 */
	public function &forCustomPush($runningMpid, $id) {
		die('not support');
	}
}