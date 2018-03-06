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
	public function award(&$matter, &$actor, $act, $rules = []) {
		$oResult = new \stdClass;
		$oResult->actor = [];
		$oResult->creator = [];

		$modelMat = $this->model('matter\\' . $matter->type . '\coin');
		if (empty($rules)) {
			$rules = $modelMat->rulesByMatter($act, $matter);
		}
		foreach ($rules as $rule) {
			if ($rule->actor_delta) {
				$oResult->actor[] = $this->_award2User($matter, $actor, $act, (int) $rule->actor_delta);
			}
			if ($rule->creator_delta) {
				if ($creator = $modelMat->getCreator($matter)) {
					$oResult->creator[] = $this->_award2User($matter, $creator, $act, (int) $rule->creator_delta);
				}
			}
		}

		return $oResult;
	}
	/**
	 * 获得指定用户的最后一条日志
	 */
	private function &lastbyUser($userid) {
		$q = [
			'*',
			'xxt_coin_log',
			['userid' => $userid, 'last_row' => 'Y'],
		];
		$log = $this->query_obj_ss($q);

		return $log;
	}
	/**
	 * 给用户增加积分
	 *
	 * @param object $matter(siteid,id,type,title) 在哪个素材上执行的操作
	 * @param object $user(uid,nickname) 获得积分的用户
	 * @param string $act 执行的什么操作
	 */
	private function _award2User($matter, $user, $act, $delta, $payer = 'system', $transNo = '') {
		$current = time();
		$userid = isset($user->uid) ? $user->uid : $user->userid;
		// 最后一条积分记录
		if ($lastLog = $this->lastByUser($userid)) {
			$total = (int) $lastLog->total + $delta;
			$this->update('xxt_coin_log', ["last_row" => 'N'], "id={$lastLog->id}");
		} else {
			$total = $delta;
		}
		/*记录日志*/
		$log = new \stdClass;
		$log->siteid = $matter->siteid;
		$log->matter_type = isset($matter->type) ? $matter->type : '';
		$log->matter_id = isset($matter->id) ? $matter->id : '';
		$log->matter_title = isset($matter->title) ? $matter->title : '';
		$log->occur_at = $current;
		$log->act = $act;
		$log->payer = $payer;
		$log->userid = $userid;
		$log->nickname = $user->nickname;
		$log->delta = $delta;
		$log->total = $total;
		$log->last_row = 'Y';
		$log->trans_no = $transNo;

		$this->insert('xxt_coin_log', $log, false);

		/* 更新用户的积分汇总记录 */
		$userCoins = $this->model('site\user\account')->byId($userid, ['fields' => 'coin,coin_last_at,coin_day,coin_week,coin_month,coin_year']);
		if ($userCoins) {
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
			$sql .= " where uid='{$userid}'";
			$this->update($sql);
		}

		return $log;
	}
	/**
	 *
	 * 用户消费积分
	 *
	 * @param string $act
	 * @param object $user 获得积分的用户
	 * @param int $coin 获得的数额
	 *
	 */
	public function earn($act, $user, $coin) {
		$matter = new \stdClass;
		$matter->siteid = 'platform';
		$this->_award2User($matter, $user, $act, (int) $coin);
	}
	/**
	 *
	 * 用户消费积分
	 *
	 * @param string $act
	 * @param object $payer 付款人的平台账户
	 * @param int $coin 转账的数额
	 *
	 */
	public function pay($act, $payer, $coin) {
		$matter = new \stdClass;
		$matter->siteid = 'platform';
		$this->_award2User($matter, $payer, $act, -1 * (int) $coin);
	}
	/**
	 *
	 */
	public function bySite($siteId, $page, $size) {
		$result = new \stdClass;
		$q = [
			'act,occur_at,userid,nickname,delta,total',
			'xxt_coin_log',
			"siteid='siteId'",
		];
		/**
		 * 分页数据
		 */
		$q2 = [
			'o' => 'occur_at desc',
			'r' => [
				'o' => (($page - 1) * $size),
				'l' => $size,
			],
		];

		$result->logs = $this->query_objs_ss($q, $q2);

		$q[0] = 'count(*)';
		$result->total = $this->query_val_ss($q);

		return $result;
	}
	/**
	 *
	 */
	public function byMatter($matter, $page, $size) {
		$result = new \stdClass;
		$q = [
			'act,occur_at,userid,nickname,delta,total',
			'xxt_coin_log',
			"matter_type='{$matter->type}' and matter_id='{$matter->id}'",
		];
		/**
		 * 分页数据
		 */
		$q2 = [
			'o' => 'occur_at desc',
			'r' => [
				'o' => (($page - 1) * $size),
				'l' => $size,
			],
		];

		$result->logs = $this->query_objs_ss($q, $q2);

		$q[0] = 'count(*)';
		$result->total = $this->query_val_ss($q);

		return $result;
	}
	/**
	 * 站点用户给平台用户支付积分
	 *
	 * @param object $matter(siteid,id,type,title) 在哪个素材上进行的操作
	 * @param string $act 执行的什么操作
	 * @param object $payer(uid,nickname) 支付积分的用户
	 * @param object $payee(id,name) 获得积分的用户
	 * @param int $coin 支付的积分
	 *
	 */
	public function transfer2PlUser($matter, $act, $payer, $payee, $coin) {
		$transNo = md5($payer->uid . time() . $payee->id . $matter->id . $coin);

		$this->model('pl\coin\log')->_award2User($matter->siteid, $payee, $act, (int) $coin, $payer->uid, $matter, $transNo);

		$this->_award2User($matter, $payer, $act, -1 * (int) $coin, $payee->id, $transNo);

		return true;
	}
	/**
	 * 扣除积分
	 *
	 * @param object $matter 操作的素材
	 * @param object $actor 执行操作的人
	 * @param string $act 操作
	 *
	 */
	public function deduct(&$matter, &$actor, $act, $deductCoin) {
		$this->_award2User($matter, $actor, $act, -1 * (int) $deductCoin);

		return true;
	}
}