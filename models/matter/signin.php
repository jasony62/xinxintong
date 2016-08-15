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
				$app->rounds = \TMS_APP::M('matter\signin\round')->byApp($appId);
			}
		}

		return $app;
	}
	/**
	 * 返回签到活动列表
	 */
	public function &bySite($siteId, $page = 1, $size = 30) {
		$result = ['apps' => null, 'total' => 0];
		$q = [
			'*',
			'xxt_signin',
			"state<>0 and siteid='$siteId'",
		];
		$q2['o'] = 'modify_at desc';
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
	/**
	 * 返回签到活动列表
	 */
	public function &byMission($mission, $page = 1, $size = 30) {
		$result = ['apps' => null, 'total' => 0];
		$q = [
			'*',
			'xxt_signin',
			"state<>0 and mission_id='$mission'",
		];
		$q2['o'] = 'modify_at desc';
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
	/**
	 * 更新登记活动标签
	 */
	public function updateTags($aid, $tags) {
		if (empty($tags)) {
			return false;
		}

		$options = array('fields' => 'tags', 'cascaded' => 'N');
		$app = $this->byId($aid, $options);
		if (empty($app->tags)) {
			$this->update('xxt_signin', array('tags' => $tags), "id='$aid'");
		} else {
			$existent = explode(',', $app->tags);
			$checked = explode(',', $tags);
			$updated = array();
			foreach ($checked as $c) {
				if (!in_array($c, $existent)) {
					$updated[] = $c;
				}
			}
			if (count($updated)) {
				$updated = array_merge($existent, $updated);
				$updated = implode(',', $updated);
				$this->update('xxt_signin', array('tags' => $updated), "id='$aid'");
			}
		}

		return true;
	}
}