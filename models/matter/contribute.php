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
	public function getEntryUrl($siteId, $id, $ver = 'NEW') {
		if ($ver === 'OLD') {
			$url = "http://" . $_SERVER['HTTP_HOST'];
			$url .= "/rest/app/contribute";
			$url .= "?mpid=$siteId&entry=contribute," . $id;
		} else {
			$url = "http://" . $_SERVER['HTTP_HOST'];
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

		$q2['o']='id asc'; 
		      
		$acls = $this->query_objs_ss($q,$q2);

		return $acls;
	}
	/**
	 *获得用户绑定的sns信息
	 */
	public function &getSnsName($site,$mid)
	{
		$sql="SELECT y.openid AS yx,w.openid AS wx,q.openid AS qy
			  FROM xxt_site_member m 
			  LEFT JOIN xxt_site_wxfan w ON m.userid=w.userid
			  LEFT JOIN xxt_site_qyfan q ON m.userid=q.userid
    		  LEFT JOIN xxt_site_yxfan y ON m.userid=y.userid
			  WHERE m.siteid='$site' and m.id='$mid'";
		$r=$this->query_obj($sql);
		$member=(array)$r;
		$member=array_filter($member);
		$type=array_keys($member);
		return $type[0];
	}
}