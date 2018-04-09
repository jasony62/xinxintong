<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户历史轨迹
 */
class coin extends \site\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/coin/main');

		exit;
	}
	/*
	 *
	 */
	public function sites_action() {
		if (!isset($this->who->unionid)) {
			return new \ResponseError('仅限登录用户操作');
		}

		$model = $this->model();
		$q = [
			'a.siteid,a.uid userid,s.name',
			'xxt_site_account a,xxt_site s',
			"a.unionid = '{$this->who->unionid}'  and a.siteid = s.id and s.state = 1 and is_reg_primary = 'Y' and s.id <> 'platform'"
		];

		$sites = $model->query_objs_ss($q);

		return new \ResponseData($sites);
	}
	/*
	 *
	 */
	public function matters_action($site, $user, $type = null) {
		// 获取获得积分的所有活动
		$model = $this->model();
		$q = [
			'matter_id,matter_type,matter_title',
			'xxt_coin_log',
			['siteid' => $site, 'userid' => $user]
		];
		if (!empty($type)) {
			$q[2]['matter_type'] = $type;
		}
		$p = ['g' => 'matter_id,matter_type'];
		
		$matters = $model->query_objs_ss($q, $p);
		
		return new \ResponseData($matters);
	}
	/*
	 *
	 */
	public function missions_action($site, $user, $page = null, $size = null) {
		$filter = $this->getPostJson();
		$model = $this->model();
		$options = [
			'bySite' => $site,
			'fields' => 'u.user_total_coin as total,u.modify_log,m.id,m.title matter_title'
		];
		if (!empty($filter->byName)) {
			$options['byName'] = $model->escape($filter->byName);
		}
		if (!empty($page) && !empty($size)) {
			$options['at'] = ['page' => $page, 'size' => $size];
		}

		$data = $model->missionByUser($user, $options);
		foreach ($data->logs as $log) {
			$modify_log = json_decode($log->modify_log);
			$log->modify_log = new \stdClass;
			$log->modify_log->occur_at = $modify_log->at;
			$log->modify_log->delta = $modify_log->coin;
			$log->modify_log->act = $modify_log->op;
		}

		return new \ResponseData($data);
	}
	/*
	 *
	 */
	private function missionByUser($userId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
			'xxt_mission_user u,xxt_mission m',
			"u.userid = '{$userId}' and u.mission_id = m.id and m.state = 1",
		);

		if (!empty($options['bySite'])) {
			$q['2'] .= " and u.siteid = '{$options['bySite']}'";
		}
		if (!empty($options['byName'])) {
			$q['2'] .= " and m.title like '%" . $options['byName'] . "%'";
		}

		$p = [];
		if (!empty($options['at'])) {
			$p['r'] = ['o' => ($options['at']['page'] - 1), 'l' => $options['at']['size']];
		}

		$model = $this->model();
		$missions = $model->query_objs_ss($q, $p);

		$data = new \stdClass;
		$data->logs = $missions;
		$q[0] = 'count(id)';
		$data->total = $model->query_val_ss($q);

		return $data;
	}
	/*
	 *
	 */
	public function logs_action($site, $user, $matterType = null, $matterId = null, $groupByMatter = false, $page = null, $size = null) {
		$filter = $this->getPostJson();
		$options = [];
		if (!empty($filter->byName)) {
			$options['byName'] =$this->model()->escape($filter->byName);
		}
		$data = $this->userLogs($site, $user, $matterType, $matterId, $groupByMatter, $options, $page, $size);

		return new \ResponseData($data);
	}
	/*
	 *
	 */
	private function userLogs($site, $user, $matterType = null, $matterId = null, $groupByMatter = false, $options = [], $page = null, $size = null) {
		$model = $this->model();
		if ($groupByMatter === false) {
			$q = [
				'c.matter_id,c.matter_type,c.matter_title,c.act,c.occur_at,c.delta,c.total',
				'xxt_coin_log c',
				"c.siteid = '{$site}' and c.userid = '{$user}'"
			];

			if (!empty($options['byName'])) {
				$q[2] .= " and c.matter_title like '%" . $options['byName'] . "%'";
			}
			if (!empty($matterType) && !empty($matterId)) {
				$q[2] .= " and c.matter_id = '{$matterId}' and c.matter_type = '{$matterType}'";
				switch ($matterType) {
					case 'enroll':
						$q[0] .= ',e.user_total_coin apptotalcoin';
						$q[1] .= ',xxt_enroll_user e';
						$q[2] .= " and e.aid = c.matter_id and e.userid = c.userid and e.rid = 'ALL'";
						break;
					case 'plan':
						$q[0] .= ',p.coin apptotalcoin';
						$q[1] .= ',xxt_plan_user p';
						$q[2] .= " and p.aid = c.matter_id and p.userid = c.userid";
						break;
				}
			} else if (!empty($matterType)) {
				$q[2] .= " and c.matter_type = '{$matterType}'";
			}

			$p = ['o' => 'c.id desc'];
		} else {
			// 以单个素材分组显示
			$from = " ( ";
			$from .= "select c.id,c.matter_id,c.matter_type,c.matter_title,c.act,c.occur_at,c.delta,c.total from xxt_coin_log c where c.siteid = '{$site}' and c.userid = '{$user}'";

			if (!empty($matterType)) {
				$from .= " and c.matter_type = '{$matterType}'";
			}
			if (!empty($options['byName'])) {
				$from .= " and c.matter_title like '%" . $options['byName'] . "%'";
			}
			
			$from .= " ORDER BY c.id desc ) tmp";

			$q = [
				'*',
				$from,
			];

			$p = ['o' => 'id desc', 'g' => 'matter_id,matter_type'];
		}

		if (!empty($page) && !empty($size)) {
			$p['r'] = ['o' => ($page - 1), 'l' => $size];
		}
		$logs = $model->query_objs_ss($q, $p);

		// 总数
		if ($groupByMatter === false) {
			$q[0] = 'count(c.id)';
			$sum = $model->query_val_ss($q);
		} else {
			$q = [
				'id',
				'xxt_coin_log',
				"siteid = '{$site}' and userid = '{$user}'"
			];
			if (!empty($matterType)) {
				$q[2] .= " and matter_type = '{$matterType}'";
			}
			$p = ['g' => 'matter_id,matter_type'];
			$res = $model->query_objs_ss($q, $p);
			$sum = count($res);
		}

		$data = new \stdClass;
		$data->logs = $logs;
		$data->total = $sum;

		return $data;
	}
}