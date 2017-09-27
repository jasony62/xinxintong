<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 * 投稿活动
 */
class contribute_model extends app_base {
	/**
	 * 记录日志时需要的列
	 */
	const LOG_FIELDS = 'siteid,id,title';
	/**
	 *
	 */
	protected function table() {
		return 'xxt_contribute';
	}
	/**
	 *
	 */
	public function getEntryUrl($siteId, $id, $ver = 'NEW') {
		if ($ver === 'OLD') {
			$url = "http://" . APP_HTTP_HOST;
			$url .= "/rest/app/contribute";
			$url .= "?mpid=$siteId&entry=contribute," . $id;
		} else {
			$url = "http://" . APP_HTTP_HOST;
			$url .= "/rest/site/fe/matter/contribute";
			$url .= "?site={$siteId}&app={$id}";
		}

		return $url;
	}
	/**
	 *
	 * @param string $siteId
	 * @param string $appId
	 */
	public function &bySite($siteId, $appId = null) {
		$q = array(
			'*',
			'xxt_contribute c',
			"siteid='$siteId' and (state=1 or state=2)",
		);
		!empty($appId) && $q[2] .= " and id='$appId'";

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
			'c.id,c.identity,c.idsrc,c.label,c.level',
			'xxt_contribute_user c',
			"c.siteid='$siteId' and c.cid='$appId' and role='$role'",
		);

		$q2['o'] = 'id asc';

		$acls = $this->query_objs_ss($q, $q2);

		return $acls;
	}
}