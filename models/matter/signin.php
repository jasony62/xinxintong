<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 * 签到活动
 */
class signin_model extends app_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_signin';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'signin';
	}
	/**
	 *
	 */
	public function getEntryUrl($siteId, $id) {
		$url = "http://" . $_SERVER['HTTP_HOST'];
		$url .= "/rest/site/fe/matter/signin";
		$url .= "?site={$siteId}&app=" . $id;

		return $url;
	}
	/**
	 *
	 * $appId string
	 * $cascaded array []
	 */
	public function &byId($appId, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$q = array(
			$fields,
			'xxt_signin',
			"id='$appId'",
		);
		if ($app = $this->query_obj_ss($q)) {
			if (isset($app->entry_rule)) {
				$app->entry_rule = json_decode($app->entry_rule);
			}
			if ($cascaded === 'Y') {
				/* 页面 */
				$app->pages = \TMS_APP::M('matter\signin\page')->byApp($appId);
				/* 轮次 */
				$app->rounds = \TMS_APP::M('matter\signin\round')->byApp($app->siteid, $appId);
			}
		}

		return $app;
	}
	/**
	 * 返回签到活动列表
	 */
	public function &bySite($siteId, $page = 1, $size = 30, $mission = null) {
		$result = array();
		$q = array(
			'a.*',
			'xxt_signin a',
			"siteid='$siteId' and state<>0",
		);
		if (!empty($mission)) {
			$q[2] .= " and exists(select 1 from xxt_mission_matter where mission_id='$mission' and matter_type='signin' and matter_id=a.id)";
		}
		$q2['o'] = 'a.modify_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($apps = $this->query_objs_ss($q, $q2)) {
			$result['apps'] = $apps;
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result['total'] = $total;
		}

		return $result;
	}
}