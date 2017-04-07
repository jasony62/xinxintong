<?php
namespace site\op\matter\enroll;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 * 登记活动报表
 */
class report extends \site\op\base {
	/**
	 * 返回视图
	 */
	public function index_action($app) {
		if (!$this->checkAccessToken()) {
			header('HTTP/1.0 500 parameter error:accessToken is invalid.');
			die('没有获得有效访问令牌！');
		}

		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);

		\TPL::assign('title', $app->title);
		\TPL::output('/site/op/matter/enroll/report');
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
	private function _getResult($site, $appId, $rid = null, $renewCache = 'Y') {
		$current = time();
		$model = $this->model();
		$rid = $model->escape($rid);
		if ($renewCache === 'Y') {
			/* 上一次保留统计结果的时间 */
			$q = [
				'create_at',
				'xxt_enroll_record_stat',
				["aid" => $appId, 'rid' => $rid],
			];
			$q2 = ['r' => ['o' => 0, 'l' => 1]];
			$last = $model->query_objs_ss($q, $q2);
			/* 上次统计后的新登记记录数 */
			if (count($last) === 1) {
				$last = $last[0];
				$q = [
					'count(*)',
					'xxt_enroll_record',
					"aid='$appId' and enroll_at>={$last->create_at}",
				];
				if ($rid !== 'ALL' && !empty($rid)) {
					$q[2] .= " and rid = '$rid'";
				}
				
				$newCnt = (int) $model->query_val_ss($q);
			} else {
				$newCnt = 999;
			}
			// 如果更新的登记数据，重新计算统计结果
			if ($newCnt > 0) {
				$result = $this->model('matter\enroll\record')->getStat($appId, $rid);
				// 保存统计结果
				$model->delete(
					'xxt_enroll_record_stat',
					['aid' => $appId, 'rid' => $rid]
				);
				foreach ($result as $id => $stat) {
					foreach ($stat['ops'] as $op) {
						$r = [
							'siteid' => $site,
							'aid' => $appId,
							'create_at' => $current,
							'id' => $id,
							'title' => $stat['title'],
							'v' => $op->v,
							'l' => $op->l,
							'c' => $op->c,
							'rid' => $rid,
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
					['aid' => $appId, 'rid' => $rid],
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
			$result = $this->model('matter\enroll\record')->getStat($appId, $rid);
		}

		return $result;
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
	public function get_action($site, $app, $rid = null, $renewCache = 'Y') {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$result = new \stdClass;

		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);

		if(empty($rid)) {
			if ($activeRound = $this->model('matter\enroll\round')->getActive($app)) {
				$rid = $activeRound->rid;
			}
		}
		$stat = $this->_getResult($site, $app->id, $rid, $renewCache);

		$result->app = $app;
		$result->stat = $stat;

		return new \ResponseData($result);
	}
}