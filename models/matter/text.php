<?php
namespace matter;

require_once dirname(__FILE__) . '/base.php';

class text_model extends base_model {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_text';
	}
	/**
	 * 返回进行推送的消息格式
	 *
	 * $runningMpid
	 * $id
	 */
	public function &forCustomPush($runningMpid, $id) {
		$text = $this->byId($id, 'content');

		$msg = array(
			'msgtype' => 'text',
			'text' => array('content' => $text->content),
		);

		return $msg;
	}
}