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
			'a.siteid,a.uid userid,s.name,a.coin',
			'xxt_site_account a,xxt_site s',
			"a.unionid = '{$this->who->unionid}'  and a.siteid = s.id and s.state = 1 and is_reg_primary = 'Y' and s.id <> 'platform'"
		];

		$sites = $model->query_objs_ss($q);

		return new \ResponseData($sites);
	}
	/*
	 *
	 */
	public function missions_action($site, $user, $page = null, $size = null) {
		$model = $this->model('matter\mission\user');
		$options = [
			'bySite' => $site,
			'fields' => 'u.user_total_coin as apptotalcoin,u.modify_log,m.id matter_id,m.title matter_title'
		];

		$filter = $this->getPostJson();
		if (!empty($filter->byName)) {
			$options['byName'] = $model->escape($filter->byName);
		}
		if (!empty($page) && !empty($size)) {
			$options['at'] = ['page' => $page, 'size' => $size];
		}

		$data = $model->byUser($user, $options);
		foreach ($data->logs as $log) {
			$log->modify_log = json_decode($log->modify_log);
		}

		return new \ResponseData($data);
	}
	/*
	 *
	 */
	public function logs_action($site, $user, $matterType = null, $matterId = null, $page = null, $size = null) {
		$filter = $this->getPostJson();
		$options = [];
		if (!empty($filter->byName)) {
			$options['byName'] =$this->model()->escape($filter->byName);
		}
		$data = $this->userLogs($site, $user, $matterType, $matterId, $options, $page, $size);

		return new \ResponseData($data);
	}
	/*
	 *
	 */
	private function userLogs($site, $user, $matterType = null, $matterId = null, $options = [], $page = null, $size = null) {
		$model = $this->model();
		$q = [
			'c.matter_id,c.matter_type,c.matter_title,c.act,c.occur_at,c.delta,c.total',
			'xxt_coin_log c',
			"c.siteid = '{$site}' and c.userid = '{$user}'"
		];
		$p = [];

		if (!empty($options['byName'])) {
			$q[2] .= " and c.matter_title like '%" . $options['byName'] . "%'";
		}
		if (!empty($matterType)) {
			$q[2] .= " and c.matter_type = '{$matterType}'";
			if (!empty($matterId)) {
				$q[2] .= " and c.matter_id = '{$matterId}'";
			} else {
				$p['g'] = 'c.matter_id';
			}
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
		}

		$p['o'] = 'c.id desc';
		if (!empty($page) && !empty($size)) {
			$p['r'] = ['o' => ($page - 1), 'l' => $size];
		}
		$logs = $model->query_objs_ss($q, $p);

		// 总数
		if (!empty($matterType) && empty($matterId)) {
			$q[0] = 'c.id';
			$p = ['g' => 'matter_id'];
			$res = $model->query_objs_ss($q, $p);
			$sum = count($res);
		} else {
			$q[0] = 'count(c.id)';
			$sum = $model->query_val_ss($q);
		}

		$data = new \stdClass;
		$data->logs = $logs;
		$data->total = $sum;

		return $data;
	}
}