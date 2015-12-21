<?php
namespace app\enroll;

class lottery_model extends \TMS_MODEL {
	/**
	 * 参加抽奖活动的人
	 */
	public function &players($aid, $rid, $hasData = 'N') {
		$result = array(array(), array());
		$w = "e.aid='$aid' and e.state=1";
		$w .= " and not exists(select 1 from xxt_enroll_lottery l where e.enroll_key=l.enroll_key)";
		$q = array(
			'e.id,e.enroll_key,e.nickname,e.openid,e.enroll_at,signin_at,e.tags',
			'xxt_enroll_record e',
			$w,
		);
		$q2['o'] = 'e.enroll_at desc';
		/**
		 * 获得填写的登记数据
		 */
		if (($players = $this->query_objs_ss($q, $q2)) && !empty($players)) {
			/**
			 * 获得自定义数据的值
			 */
			foreach ($players as &$player) {
				$qc = array(
					'name,value',
					'xxt_enroll_record_data',
					"enroll_key='$player->enroll_key'",
				);
				$cds = $this->query_objs_ss($qc);
				foreach ($cds as $cd) {
					if ($cd->name === 'member') {
						$player->{$cd->name} = json_decode($cd->value);
					} else {
						$player->{$cd->name} = $cd->value;
					}
				}
			}
			/**
			 * 删除没有填写报名信息数据
			 */
			if ($hasData === 'Y') {
				$players2 = array();
				foreach ($players as $p2) {
					if (empty($p2->name) && empty($p2->mobile)) {
						continue;
					}
					$players2[] = $p2;
				}
				$result[0] = $players2;
			} else {
				$result[0] = $players;
			}
		}
		/**
		 * 已经抽中的人
		 */
		$q = array(
			'l.*',
			'xxt_enroll_lottery l',
			"l.aid='$aid' and round_id='$rid'",
		);
		$q2 = array('o' => 'draw_at');
		if ($winners = $this->query_objs_ss($q, $q2)) {
			/**
			 * 获得自定义数据的值
			 */
			foreach ($winners as &$w) {
				$qc = array(
					'name,value',
					'xxt_enroll_record_data',
					"enroll_key='$w->enroll_key'",
				);
				$cds = $this->query_objs_ss($qc);
				foreach ($cds as $cd) {
					if ($cd->name === 'member') {
						$w->{$cd->name} = json_decode($cd->value);
					} else {
						$w->{$cd->name} = $cd->value;
					}
				}
			}
			$result[1] = $winners;
		}

		return $result;
	}
	/**
	 * 获得抽奖的轮次
	 * @param string $aid
	 * @param array $options
	 */
	public function &rounds($aid, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_enroll_lottery_round',
			"aid='$aid'",
		);
		$rounds = $this->query_objs_ss($q);

		return $rounds;
	}
	/**
	 * 活动中奖名单
	 */
	public function &winners($aid, $rid = null) {
		/**
		 * 已经抽中的人
		 */
		$q = array(
			'l.*,r.title',
			'xxt_enroll_lottery l,xxt_enroll_lottery_round r',
			"l.aid='$aid' and l.round_id=r.round_id",
		);
		if (!empty($rid)) {
			$q[2] .= " and l.round_id='$rid'";
		}

		$q2 = array('o' => 'l.round_id,l.draw_at');
		if ($winners = $this->query_objs_ss($q, $q2)) {
			/**
			 * 获得自定义数据的值
			 */
			foreach ($winners as &$w) {
				$qc = array(
					'name,value',
					'xxt_enroll_record_data',
					"enroll_key='$w->enroll_key'",
				);
				$cds = $this->query_objs_ss($qc);
				foreach ($cds as $cd) {
					if ($cd->name === 'member') {
						$w->{$cd->name} = json_decode($cd->value);
					} else {
						$w->{$cd->name} = $cd->value;
					}
				}
			}
		}

		return $winners;
	}
}