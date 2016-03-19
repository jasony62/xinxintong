<?php
namespace site\fe\matter\enroll;

include_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 登记活动数据定义
 */
class page extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 获得登记项定义
	 *
	 * @param string $mpid
	 * @param string $aid
	 * @param string $fromCache
	 * @param int $interval
	 *
	 * @return array
	 */
	public function schemaGet_action($mpid, $aid, $fromCache = 'N', $interval = 86400) {
		$interval = (int) $interval;
		if ($fromCache === 'Y' && $interval > 0) {
			$current = time();
			$model = $this->model();
			$q = array(
				'create_at,id,title,type,v,l',
				'xxt_enroll_record_schema',
				"aid='$aid'",
			);
			$cached = $model->query_objs_ss($q);
			if (count($cached) && $cached[0]->create_at >= $current - $interval) {
				/*从缓存中获取schema*/
				$schema = array();
				foreach ($cached as $data) {
					if (!isset($schema[$data->id])) {
						$schema[$data->id] = array(
							'id' => $data->id,
							'title' => $data->title,
							'type' => $data->type,
						);
						if ($data->type === 'multiple' || $data->type === 'single') {
							$schema[$data->id]['ops'] = array();
						}
					}
					$item = &$schema[$data->id];
					if (isset($item['ops'])) {
						$op = array(
							'v' => $data->v,
							'label' => $data->l,
						);
						$item['ops'][] = $op;
					}
				}
				$schema = array_values($schema);
			} else {
				$schema = $this->model('app\enroll\page')->schemaByApp($aid);
				/*更新缓存的schema*/
				$model->delete('xxt_enroll_record_schema', "aid='$aid'");
				foreach ($schema as $s) {
					if (isset($s['ops'])) {
						foreach ($s['ops'] as $op) {
							$r = array(
								'aid' => $aid,
								'create_at' => $current,
								'id' => $s['id'],
								'title' => $s['title'],
								'type' => $s['type'],
								'v' => $op['v'],
								'l' => $op['label'],
							);
							$model->insert('xxt_enroll_record_schema', $r);
						}
					} else {
						$r = array(
							'aid' => $aid,
							'create_at' => $current,
							'id' => $s['id'],
							'title' => $s['title'],
							'type' => isset($s['type']) ? $s['type'] : '',
							'v' => '',
							'l' => '',
						);
						$model->insert('xxt_enroll_record_schema', $r);
					}
				}
			}
		} else {
			$schema = $this->model('app\enroll\page')->schemaByApp($aid);
		}

		return new \ResponseData($schema);
	}
}