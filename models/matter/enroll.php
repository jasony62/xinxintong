<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 *
 */
class enroll_model extends app_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_enroll';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'enroll';
	}
	/**
	 *
	 */
	public function getEntryUrl($runningMpid, $id) {
		$url = "http://" . $_SERVER['HTTP_HOST'];
		$url .= "/rest/app/enroll";
		$url .= "?mpid=$runningMpid&aid=" . $id;

		return $url;
	}
}