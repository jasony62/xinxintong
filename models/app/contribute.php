<?php
namespace app;

require_once dirname(dirname(__FILE__)) . '/matter/contribute.php';
/**
 *
 */
class contribute_model extends \matter\contribute_model {
	/**
	 * $mpid
	 * $mid
	 */
	public function &byMpid($mpid, $mid = null) {
		$q = array(
			'*',
			'xxt_contribute c',
			"mpid='$mpid' and state=1",
		);
		$q2 = array('o' => 'create_at desc');

		$cs = $this->query_objs_ss($q, $q2);

		return $cs;
	}
	/**
	 *
	 */
	public function &userAcls($mpid, $cid, $role) {
		/**
		 * 直接指定
		 */
		$q = array(
			'c.id,c.identity,c.idsrc,c.label',
			'xxt_contribute_user c',
			"c.mpid='$mpid' and c.cid='$cid' and role='$role'",
		);
		$acls = $this->query_objs_ss($q);

		return $acls;
	}
}
