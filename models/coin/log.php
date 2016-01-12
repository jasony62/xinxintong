<?php
namespace coin;
/**
 *
 */
class log_model extends \TMS_MODEL {
	/**
	 *
	 * @param string $mpid
	 * @param string $act
	 * @param string $from payer
	 * @param string $openid payee
	 */
	public function record($mpid, $act, $from, $openid) {
		$ruleAct = strtok($act, ':');
		if ($rule = \TMS_APP::model('coin\rule')->byMpid($mpid, $ruleAct)) {
			if ((int) $rule->delta > 0) {
				$current = time();
				$fans = \TMS_APP::model('coin\rule')->byOpenid($mpid, $openid, 'coin');
				$total = (int) $fans->total + (int) $rule->delta;
				/*更新总值*/
				$this->update("update set total=total+$rule->delta where mpid='$mpid' and openid='$openid'");
				/*记录日志*/
				$i['mpid'] = $mpid;
				$i['occur_at'] = $current;
				$i['act'] = $act;
				$i['payer'] = $from;
				$i['payee'] = $openid;
				$i['delta'] = $rule->delta;
				$i['total'] = $total;
				$this->insert('xxt_log', $i, false);
			}
		}

		return true;
	}
}