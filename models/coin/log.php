<?php
namespace coin;
/**
 *
 */
class log_model extends \TMS_MODEL {
	/**
	 * 记录积分获得日志
	 *
	 * @param string $mpid
	 * @param string $act
	 * @param string $from payer
	 * @param string $openid payee
	 */
	public function income($mpid, $act, $objId, $from, $openid) {
		$coin = 0;
		if (empty($mpid) || empty($act) || empty($openid) || empty($from)) {
			return $coin;
		}
		if ($rule = \TMS_APP::model('coin\rule')->byMpid($mpid, $act, $objId)) {
			if ((int) $rule->delta > 0) {
				$current = time();
				$fan = \TMS_APP::model('user/fans')->byOpenid($mpid, $openid, 'nickname,coin,coin_last_at,coin_day,coin_week,coin_month,coin_year');
				$coin = (int) $rule->delta;
				/*更新总值*/
				$total = (int) $fan->coin + $coin;
				/*记录日志*/
				$i['mpid'] = $mpid;
				$i['occur_at'] = $current;
				$i['act'] = $act;
				$i['payer'] = $from;
				$i['payee'] = $openid;
				$i['nickname'] = $fan->nickname;
				$i['delta'] = $coin;
				$i['total'] = $total;
				$this->insert('xxt_coin_log', $i, false);
				/*增量累计值*/
				$last = explode(',', date('Y,n,W,j', $fan->coin_last_at));
				$today = explode(',', date('Y,n,W,j', $current));
				if ($today[0] !== $last[0]) {
					$year = $month = $week = $day = $coin;
				} else {
					$year = $fan->coin_year + $coin;
					if ($today[1] !== $last[1]) {
						$month = $week = $day = $coin;
					} else {
						$month = $fan->coin_month + $coin;
						if ($today[2] !== $last[2]) {
							$week = $day = $coin;
						} else {
							$week = $fan->coin_week + $coin;
							if ($today[3] !== $last[3]) {
								$day = $coin;
							} else {
								$day = $fan->coin_day + $coin;
							}
						}
					}
				}
				/*更新数据*/
				$sql = "update xxt_fans set";
				$sql .= " coin=$total,coin_last_at=$current";
				$sql .= ",coin_day=$day,coin_week=$week,coin_month=$month,coin_year=$year";
				$sql .= " where mpid='$mpid' and openid='$openid'";
				$this->update($sql);
			}
		}

		return $coin;
	}
	/**
	 * 支出积分
	 *
	 * @param string $mpid
	 * @param string $openid
	 * @param int $count
	 *
	 */
	public function expense($mpid, $act, $openid, $count) {
		/*是否有足够的余额？*/
		/*记录日志*/
		$current = time();
		$i['mpid'] = $mpid;
		$i['occur_at'] = $current;
		$i['act'] = $act;
		$i['payer'] = $openid;
		$i['payee'] = 'sys'; //???
		$i['nickname'] = '';
		$i['delta'] = $count;
		$i['total'] = '0';
		$this->insert('xxt_coin_log', $i, false);
		/*更新总额*/
		$sql = "update xxt_fans set";
		$sql .= " coin=coin-" . $count;
		$sql .= " where mpid='$mpid' and openid='$openid'";
		$rst = $this->update($sql);

		return $rst;
	}
	/**
	 * 用户之间进行转账
	 *
	 * @param string $mpid
	 * @param string $payer 付款人，openid
	 * @param string $payee 首款人，openid
	 * @param string $coin 转账的数额
	 *
	 */
	public function transfer($mpid, $payer, $payee, $coin) {
		/*减付款人*/
		$sql = "update xxt_fans set";
		$sql .= " coin=coin-" . $coin;
		$sql .= " where mpid='$mpid' and openid='$payer'";
		$this->update($sql);
		/*转账日志*/
		$current = time();
		$payee = \TMS_APP::model('user/fans')->byOpenid($mpid, $payee, 'openid,nickname,coin,coin_last_at,coin_day,coin_week,coin_month,coin_year');
		/*更新总值*/
		$total = (int) $payee->coin + $coin;
		/*记录日志*/
		$i['mpid'] = $mpid;
		$i['occur_at'] = $current;
		$i['act'] = 'coin.transfer';
		$i['payer'] = $payer;
		$i['payee'] = $payee->openid;
		$i['nickname'] = $payee->nickname;
		$i['delta'] = $coin;
		$i['total'] = $total;
		$this->insert('xxt_coin_log', $i, false);
		/*增量累计值*/
		$last = explode(',', date('Y,n,W,j', $payee->coin_last_at));
		$today = explode(',', date('Y,n,W,j', $current));
		if ($today[0] !== $last[0]) {
			$year = $month = $week = $day = $coin;
		} else {
			$year = $payee->coin_year + $coin;
			if ($today[1] !== $last[1]) {
				$month = $week = $day = $coin;
			} else {
				$month = $payee->coin_month + $coin;
				if ($today[2] !== $last[2]) {
					$week = $day = $coin;
				} else {
					$week = $payee->coin_week + $coin;
					if ($today[3] !== $last[3]) {
						$day = $coin;
					} else {
						$day = $payee->coin_day + $coin;
					}
				}
			}
		}
		/*更新数据*/
		$sql = "update xxt_fans set";
		$sql .= " coin=$total,coin_last_at=$current";
		$sql .= ",coin_day=$day,coin_week=$week,coin_month=$month,coin_year=$year";
		$sql .= " where mpid='$mpid' and openid='$payee->openid'";
		$this->update($sql);
	}
}