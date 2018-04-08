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
	public function missions_action($site, $user) {
		$modelMisUser = $this->model('matter\mission\user');
		$missions = $modelMisUser->byUser($user, ['bySite' => $site, 'fields' => 'u.user_total_coin as apptotalcoin,u.modify_log,m.id,m.title']);

		foreach ($missions as $mission) {
			$mission->modify_log = json_decode($mission->modify_log);
		}

		return new \ResponseData($missions);
	}
	/*
	 *
	 */
	public function logs_action($site, $user, $matter = null, $groupByMatter = false, $page = null, $size = null) {
		$data = $this->userLogs($site, $user, $matter, $groupByMatter, $page, $size);

		return new \ResponseData($data);
	}
	/*
	 *
	 */
	private function userLogs($site, $user, $matter = null, $groupByMatter = false, $page = null, $size = null) {
		$model = $this->model();
		$q = [
			'c.matter_id,c.matter_type,c.matter_title,c.act,c.occur_at,c.delta,c.total',
			'xxt_coin_log c',
			"c.siteid = '{$site}' and c.userid = '{$user}'"
		];

		if (!empty($matter)) {
			$matter = explode(',', $matter);
			list($matterType, $matterId) = $matter;
			$q[2] .= " and matter_id = '{$matterId}' and matter_type = '{$matterType}'";
			switch ($matterType) {
				case 'enroll':
					$q[0] .= ',e.user_total_coin apptotalcoin';
					$q[1] .= ',xxt_enroll_user e';
					$q[2] .= " and e.userid = c.userid and e.rid = 'ALL'";
					break;
				case 'plan':
					$q[0] .= ',p.coin apptotalcoin';
					$q[1] .= ',xxt_plan_user p';
					$q[2] .= " and p.userid = c.userid";
					break;
			}
		}

		$p = ['o' => 'c.occur_at desc'];

		if ($groupByMatter) {
			$p['g'] = 'matter_id,matter_type';
		}

		if (!empty($page) && !empty($size)) {
			$p['r'] = ['o' => $page, 'l' => $size];
		}

		$logs = $model->query_objs_ss($q, $p);
		// 总数
		$q[0] = 'count(id)';
		$sum = $model->query_val_ss($q);

		$data = new \stdClass;
		$data->logs = $logs;
		$data->total = $sum;

		return $data;
	}
}