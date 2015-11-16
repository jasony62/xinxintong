<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 *
 */
class lottery_model extends app_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_lottery';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'lottery';
	}
	/**
	 *
	 */
	public function getEntryUrl($runningMpid, $id) {
		$url = "http://" . $_SERVER['HTTP_HOST'];
		$url .= "/rest/app/lottery";
		$url .= "?mpid=$runningMpid&lottery=" . $id;

		return $url;
	}
}