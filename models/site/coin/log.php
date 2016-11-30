<?php
namespace site\coin;
/**
 * 站点内积分日志
 */
class log_model extends \TMS_MODEL {
	/**
	 * 授予积分
	 *
	 * @param object $matter 操作的素材
	 * @param object $actor 执行操作的人
	 * @param string $act 操作
	 *
	 */
	public function award(&$matter, &$actor, $act) {
		$modelMat = $this->model('matter\\' . $matter->type . '\coin');
		$rules = $modelMat->rulesByMatter($act, $matter);
		foreach ($rules as $rule) {
			if ($rule->actor_delta) {
				$this->award2User($matter, $actor, $act, (int) $rule->actor_delta);
			}
			if ($rule->creator_delta) {
				if ($creator = $modelMat->getCreator($matter)) {
					$this->award2User($matter, $creator, $act, (int) $rule->creator_delta);
				}
			}
		}
		return true;
	}
	/**
	 * 获得指定用户的最后一条日志
	 */
	private function &lastbyUser($userid) {
		$q = [
			'*',
			'xxt_coin_log',
			"userid='$userid' and last_row='Y'",
		];
		$log = $this->query_obj_ss($q);

		return $log;
	}
	/**
	 * 给用户增加积分
	 */
	private function award2User($matter, $user, $act, $delta, $payer = 'system') {
		$current = time();
		// 最后一条积分记录
		if ($lastLog = $this->lastByUser($user->uid)) {
			$total = (int) $lastLog->total + $delta;
			$this->update('xxt_coin_log', ["last_row" => 'N'], "id={$lastLog->id}");
		} else {
			$total = $delta;
		}
		/*记录日志*/
		$log = new \stdClass;
		$log->siteid = $matter->siteid;
		$log->occur_at = $current;
		$log->act = $act;
		$log->payer = $payer;
		$log->userid = $user->uid;
		$log->nickname = $user->nickname;
		$log->delta = $delta;
		$log->total = $total;
		$log->last_row = 'Y';

		$this->insert('xxt_coin_log', $log, false);

		/* 更新用户的积分汇总记录 */
		$userCoins = $this->model('site\user\account')->byId($user->uid, ['fields' => 'coin,coin_last_at,coin_day,coin_week,coin_month,coin_year']);
		// 增量累计值
		$last = explode(',', date('Y,n,W,j', $userCoins->coin_last_at));
		$today = explode(',', date('Y,n,W,j', $current));
		if ($today[0] !== $last[0]) {
			$year = $month = $week = $day = $delta;
		} else {
			$year = (int) $userCoins->coin_year + $delta;
			if ($today[1] !== $last[1]) {
				$month = $week = $day = $delta;
			} else {
				$month = (int) $userCoins->coin_month + $delta;
				if ($today[2] !== $last[2]) {
					$week = $day = $delta;
				} else {
					$week = (int) $userCoins->coin_week + $delta;
					if ($today[3] !== $last[3]) {
						$day = $delta;
					} else {
						$day = (int) $userCoins->coin_day + $delta;
					}
				}
			}
		}
		// 更新汇总数据
		$sql = "update xxt_site_account set";
		$sql .= " coin={$total},coin_last_at={$current}";
		$sql .= ",coin_day={$day},coin_week={$week},coin_month={$month},coin_year={$year}";
		$sql .= " where uid='{$user->uid}'";
		$this->update($sql);

		return $log;
	}
}