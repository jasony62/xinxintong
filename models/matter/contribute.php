<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 *
 */
class contribute_model extends app_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_contribute';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'contribute';
	}
	/**
	 *
	 */
	public function getEntryUrl($runningMpid, $id) {
		$url = "http://" . $_SERVER['HTTP_HOST'];
		$url .= "/rest/app/contribute";
		$url .= "?mpid=$runningMpid&entry=contribute," . $id;

		return $url;
	}
	/**
	 * $siteId
	 */
	public function &bySite($siteId) {
		$q = array(
			'*',
			'xxt_contribute c',
			"siteid='$siteId' and (state=1 or state=2)",
		);
		$q2 = array('o' => 'create_at desc');

		$cs = $this->query_objs_ss($q, $q2);

		return $cs;
	}
	/**
	 * 文稿编辑
	 */
	public function &editors($siteId, $appId, $role) {
		/**
		 * 直接指定
		 */
		$q = array(
			'c.id,c.identity,c.idsrc,c.label',
			'xxt_contribute_user c',
			"c.siteid='$siteId' and c.cid='$appId' and role='$role'",
		);
		$acls = $this->query_objs_ss($q);

		return $acls;
	}
}