<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 *
 */
class wall_model extends app_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_wall';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'wall';
	}
	/**
	 *
	 */
	public function getEntryUrl($runningMpid, $id) {
		$url = "http://" . $_SERVER['HTTP_HOST'];
		$url .= "/rest/app/wall";
		$url .= "?mpid=$runningMpid&wid=" . $id;

		return $url;
	}
}