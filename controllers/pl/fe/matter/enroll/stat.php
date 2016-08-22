<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 *
 */
class stat extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 统计登记信息
	 *
	 * 只统计single/multiple类型的数据项
	 *
	 * return
	 * name => array(l=>label,c=>count)
	 *
	 */
	public function get_action($site, $app, $renewCache = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$current = time();
		$model = $this->model();
		if ($renewCache === 'Y') {
			/* 上一次保留统计结果的时间 */
			$q = [
				'create_at',
				'xxt_enroll_record_stat',
				["aid" => $app],
			];
			$q2 = ['r' => ['o' => 0, 'l' => 1]];
			$last = $model->query_objs_ss($q, $q2);
			/* 上次统计后的新登记记录数 */
			if (count($last) === 1) {
				$last = $last[0];
				$q = [
					'count(*)',
					'xxt_enroll_record',
					"aid='$app' and enroll_at>={$last->create_at}",
				];
				$newCnt = (int) $model->query_val_ss($q);
			} else {
				$newCnt = 999;
			}
			// 如果更新的登记数据，重新计算统计结果
			if ($newCnt > 0) {
				$result = $this->model('matter\enroll\record')->getStat($app);
				// 保存统计结果
				$model->delete(
					'xxt_enroll_record_stat',
					"aid='$app'"
				);
				foreach ($result as $id => $stat) {
					foreach ($stat['ops'] as $op) {
						$r = [
							'siteid' => $site,
							'aid' => $app,
							'create_at' => $current,
							'id' => $id,
							'title' => $stat['title'],
							'v' => $op->v,
							'l' => $op->l,
							'c' => $op->c,
						];
						$model->insert('xxt_enroll_record_stat', $r);
					}
				}
			} else {
				/* 从缓存中获取统计数据 */
				$result = [];
				$q = [
					'id,title,v,l,c',
					'xxt_enroll_record_stat',
					"aid='$app'",
				];
				$cached = $model->query_objs_ss($q);
				foreach ($cached as $data) {
					if (empty($result[$data->id])) {
						$item = [
							'id' => $data->id,
							'title' => $data->title,
							'ops' => [],
						];
						$result[$data->id] = $item;
					}
					$op = [
						'v' => $data->v,
						'l' => $data->l,
						'c' => $data->c,
					];
					$result[$data->id]['ops'][] = $op;
				}
			}
		} else {
			$result = $this->model('matter\enroll\record')->getStat($app);
		}

		return new \ResponseData($result);
	}
}