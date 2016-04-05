<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 *
 */
class group_model extends app_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_group';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'group';
	}
	/**
	 *
	 * $aid string
	 * $cascaded array []
	 */
	public function &byId($aid, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$q = array(
			$fields,
			'xxt_group',
			"id='$aid'",
		);
		if ($app = $this->query_obj_ss($q)) {
			if ($cascaded === 'Y') {
			}
		}

		return $app;
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
			$this->update('xxt_group', array('tags' => $tags), "id='$aid'");
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
				$this->update('xxt_group', array('tags' => $updated), "id='$aid'");
			}
		}
		return true;
	}
}