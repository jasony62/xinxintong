<?php
namespace app\lottery;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 抽奖活动引擎
 */
class main extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	protected function canAccessObj($mpid, $lottery, $member, $authapis, $lot) {
		return $this->model('acl')->canAccessMatter($mpid, 'lottery', $lottery, $member, $authapis);
	}
	/**
	 * 获得轮盘抽奖活动的页面或定义
	 *
	 * $mpid
	 * $lottery 抽奖活动id
	 * $shareby 谁做的分享
	 */
	public function index_action($mpid, $lottery, $shareby = '', $pretaskdone = 'N', $mocker = null, $code = null) {
		empty($mpid) && $this->outputError('没有指定当前运行的公众号');
		empty($lottery) && $this->outputError('抽奖活动id为空');

		$model = $this->model('app\lottery');
		$lot = $model->byId($lottery);
		$current = time();
		/**
		 * start?
		 */
		if ($current < $lot->start_at) {
			\TPL::assign('title', $lot->title);
			\TPL::assign('body', empty($lot->nostart_alert) ? '活动未开始' : $lot->nostart_alert);
			\TPL::output('info');
			exit;
		}
		/**
		 * end?
		 */
		if ($current > $lot->end_at) {
			\TPL::assign('title', $lot->title);
			\TPL::assign('body', empty($lot->hasend_alert) ? '活动已结束' : $lot->hasend_alert);
			\TPL::output('info');
			exit;
		}

		$openid = $this->doAuth($mpid, $code, $mocker);

		$this->afterOAuth($mpid, $lottery, $shareby, $pretaskdone);
	}
	/**
	 * 返回页面信息
	 */
	private function afterOAuth($mpid, $lottery, $shareby = null, $pretaskdone = 'N') {
		$model = $this->model('app\lottery');
		$lot = $model->byId($lottery);
		/**
		 * 当前访问用户
		 */
		$user = $this->getUser($mpid);
		/**
		 * 访问控制
		 */
		if ($lot->access_control === 'Y') {
			$this->accessControl($mpid, $lot->id, $lot->authapis, $user->openid, $lßot);
		}
		/**
		 * 记录前置活动执行状态
		 */
		if ($lot->pretask === 'N') {
			$this->logRead($mpid, $user, $lot->id, 'lottery', $lot->title, $shareby);
		} else if ($pretaskdone === 'Y') {
			if ($lot->pretaskcount === 'F') {
				$expire = (int) $lot->end_at;
				$this->mySetCookie("_{$lottery}_pretask", 'done', $expire);
			} else {
				$this->mySetCookie("_{$lottery}_pretask", 'done');
			}
			$this->logRead($mpid, $user, $lot->id, 'lottery', $lot->title, $shareby);
		}

		\TPL::assign('title', $lot->title);
		\TPL::output('/app/lottery/play');
		exit;
	}
	/**
	 * 抽奖活动定义
	 */
	public function get_action($mpid, $lottery) {
		/* user */
		$user = $this->getUser($mpid);
		/**/
		$mid = null;
		$params = new \stdClass;
		$params->user = $user;
		/**
		 * 抽奖活动定义
		 */
		$model = $this->model('app\lottery');
		$lot = $model->byId($lottery, '*', array('award', 'plate'));
		/**
		 * 处理前置活动
		 */
		if ($lot->pretask === 'Y') {
			$state = $this->myGetCookie("_{$lottery}_pretask");
			$lot->_pretaskstate = $state === 'done' ? 'done' : 'pending';
			if ($lot->pretaskcount === 'E') {
				$this->mySetCookie("_{$lottery}_pretask", '', time() - 86400);
			}
		}
		/**
		 *
		 */
		$params->logs = $model->getLog($lottery, $mid, $user->openid, true);
		$params->leftChance = $model->getChance($lottery, $mid, $user->openid);
		$params->lottery = $lot;

		$page = $this->model('code\page')->byId($lot->page_id);
		$params->page = $page;

		return new \ResponseData($params);
	}
	/**
	 * 最近的获奖者清单
	 */
	public function winnersList_action($lottery) {
		$winners = $this->model('app\lottery')->getWinners($lottery);

		return new \ResponseData($winners);
	}
	/**
	 * 进行抽奖
	 * @param string $mpid
	 * @param string $lottery 抽奖互动
	 * @param string $enrollKey 关联的登记活动的登记记录
	 */
	public function play_action($mpid, $lottery, $enrollKey = null) {
		$user = $this->getUser($mpid);
		$model = $this->model('app\lottery');
		/**
		 * define data.
		 */
		$lot = $model->byId($lottery, '*', array('award', 'plate'));
		/**
		 * 如果仅限关注用户参与，获得openid
		 */
		$openid = $user->openid;
		if ($lot->fans_only === 'Y') {
			if (empty($openid)) {
				return new \ResponseData(null, 302, $lot->nonfans_alert);
			}
			$q = array(
				'count(*)',
				'xxt_fans',
				"mpid='$mpid' and openid='$openid' and unsubscribe_at=0",
			);
			if (1 !== (int) $this->model()->query_val_ss($q)) {
				return new \ResponseData(null, 302, $lot->nonfans_alert);
			}
		}
		/**
		 * 如果不能获得一个确定的身份信息，就无法将抽奖结果和用户关联
		 * 因此无法确定用户身份时，就不允许进行抽奖
		 */
		if (empty($openid) && empty($mid)) {
			return new \ComplianceError('无法确定您的身份信息，不能参与抽奖！');
		}
		/**
		 * 如果仅限会员参与，获得用户身份信息
		 */
		if ($lot->access_control === 'Y') {
			$aAuthapis = explode(',', $lot->authapis);
			$members = $this->authenticate($mpid, $aAuthapis, false);
			$mid = $members[0]->mid;
		} else {
			$mid = null;
		}
		/**
		 * 是否完成了指定内置任务
		 */
		if ($task = $model->hasTask($lottery, $mid, $openid)) {
			return new \ResponseData(null, 301, $task->description);
		}
		/**
		 * 还有参加抽奖的机会吗？
		 */
		if (false === $model->canPlay($lottery, $mid, $openid, true)) {
			return new \ResponseData(null, 301, $lot->nochance_alert);
		}
		/**
		 * 抽奖
		 */
		list($selectedSlot, $selectedAwardID, $myAward) = $this->_drawAward($lot);

		if (empty($myAward)) {
			return new \ResponseData(null, 301, '对不起，没有奖品了！');
		}
		/**
		 * 领取非实体奖品
		 */
		if ($myAward['type'] == 1 || $myAward['type'] == 2 || $myAward['type'] == 3) {
			$model->acceptAward($lottery, $mid, $openid, $myAward);
		}
		/**
		 * 返回奖项信息
		 */
		foreach ($lot->awards as $a) {
			if ($a->aid === $myAward['aid']) {
				$myAward2 = $a;
				break;
			}
		}
		/**
		 * record result
		 */
		$modelLog = $this->model('app\lottery\log');
		$log = $modelLog->add($mpid, $lottery, $openid, $myAward2, $enrollKey);
		/**
		 * 检查剩余的机会
		 */
		$chance = $model->getChance($lot->id, $mid, $openid);
		/**
		 * 清理冗余数据
		 */
		unset($myAward2->prob);
		unset($myAward2->quantity);

		$result = array('slot' => $selectedSlot, 'leftChance' => $chance, 'award' => $myAward2, 'log' => $log);

		return new \ResponseData($result);
	}
	/**
	 * 记录兑奖地址
	 */
	public function prize_action($mpid) {
		$log = $this->getPostJson();

		$user = $this->getUser($mpid);

		$rst = $this->model()->update(
			'xxt_lottery_log',
			array('prize_url' => $log->url),
			"id='$log->logid'"
		);

		return new \ResponseData('ok');
	}
	/**
	 * 返回当前用户获得的奖品
	 */
	public function myawards_action($mpid, $lottery) {
		$model = $this->model('app\lottery');
		/**
		 * 抽奖活动定义
		 */
		$r = $model->byId($lottery, 'access_control,authapis', array('award'));
		/**
		 * is member?
		 */
		$mid = null;
		if ($r->access_control) {
			$aAuthapis = explode(',', $r->authapis);
			if ($members = $this->getCookieMember($mpid, $aAuthapis)) {
				$mid = $members[0]->mid;
			}

		}

		$fan = $this->getCookieOAuthUser($mpid);

		$myAwards = $model->getLog($lottery, $mid, $fan->openid, true);

		return new \ResponseData($myAwards);
	}
	/**
	 * 抽取奖品
	 * 奖品必须还有剩余
	 *
	 * @param object $lot
	 */
	private function _drawAward(&$lot) {
		/**
		 * arrange relateion between award and plate's slots.
		 */
		$awards = array();
		foreach ($lot->awards as $a) {
			/**
			 * 奖品的抽中概率为0，或者已经没有剩余的奖品，奖项就不再参与抽奖
			 * 由于周期性抽奖，有可能改变奖品的数量，因此周期性抽奖即使没有奖品了也要允许抽
			 */
			if ((int) $a->prob === 0 || ($a->period === 'A' && $a->type == 99 && ((int) $a->takeaway >= (int) $a->quantity))) {
				continue;
			}
			$awards[$a->aid] = array(
				'aid' => $a->aid,
				'prob' => $a->prob,
				'type' => $a->type,
				'taskid' => $a->taskid,
				'period' => $a->period,
				'quantity' => $a->quantity,
			);
		}
		/**
		 * 没有可用的奖品了
		 */
		if (empty($awards)) {
			return false;
		}
		/**
		 * 指定了奖品槽位情况下，计算每个可用的奖项所在位置，并跳过无效的奖品
		 */
		if ($lot->plate->size > 0) {
			for ($i = 0; $i < $lot->plate->size; $i++) {
				if (isset($awards[$lot->plate->{"a$i"}])) {
					$awards[$lot->plate->{"a$i"}]['pos'][] = $i;
				}
			}
			/**
			 * 清除不在槽位中的奖项
			 */
			foreach ($awards as $k => $a) {
				if (!isset($a['pos'])) {
					unset($awards[$k]);
				}
			}
		} else {
			$pos = 0;
			foreach ($awards as $k => &$a) {
				$a['pos'] = array($pos);
				$lot->plate->{"a$pos"} = $a['aid'];
				$pos++;
			}
		}
		/**
		 * 按照概率从低到高排列奖品
		 */
		uasort($awards, function ($a, $b) {
			if ((int) $a['prob'] === (int) $b['prob']) {
				return 0;
			}
			return ((int) $a['prob'] < (int) $b['prob']) ? -1 : 1;
		});
		/**
		 * 计算位置和奖品
		 */
		$limit = 10;
		while ($limit--) {
			$selectedSlot = $this->_getAwardPos($awards);
			$selectedAwardID = $lot->plate->{"a$selectedSlot"};
			$myAward = $awards[$selectedAwardID];
			if ($myAward['type'] == 99) {
				$current = time();
				/**
				 * 如果抽奖周期是天，当前用户的抽奖时间和最近一次领取奖品的时间不是同一天
				 * 那么先重置奖品领取信息
				 */
				if ($myAward['period'] === 'D') {
					$cdate = getdate($current);
					$ztime = mktime(0, 0, 0, (int) $cdate['mon'], (int) $cdate['mday'], (int) $cdate['year']);
					$sql = "update xxt_lottery_award";
					$sql .= " set takeaway=0,takeaway_at=0";
					$sql .= " where aid='$selectedAwardID' and takeaway_at<$ztime";
					$this->model()->update($sql);
				}
				/**
				 * 如果是实物奖品，更新被领取的奖品数量
				 * 如果没有剩余的奖品就重新抽奖
				 */
				$sql = "update xxt_lottery_award";
				$sql .= " set takeaway=takeaway+1,takeaway_at=$current";
				$sql .= " where aid='$selectedAwardID' and quantity>takeaway";
				$success = $this->model()->update($sql);
				if (1 === (int) $success) {
					break;
				} else {
					unset($awards[$selectedAwardID]);
				}
			} else {
				$success = 1;
				break;
			}
		}
		if ((int) $success !== 1) {
			die('can not get an award, please set a default award.');
		}

		return array($selectedSlot, $selectedAwardID, $myAward);
	}
	/**
	 * 获得抽中奖品所在的位置
	 */
	private function _getAwardPos(&$proArr) {
		$awardPos = null;
		/**
		 * 概率数组的总概率
		 */
		$proSum = 0;
		foreach ($proArr as $award) {
			$proSum += (int) $award['prob'];
		}
		/**
		 * 概率数组循环
		 */
		$randNum = mt_rand(1, $proSum);
		foreach ($proArr as $award) {
			if ($randNum <= (int) $award['prob']) {
				/**
				 * 在奖品的概率范围内
				 */
				if (count($award['pos']) === 1) {
					/**
					 * 只有一个位置可选
					 */
					$awardPos = $award['pos'][0];
				} else {
					/**
					 * 随机挑选一个位置
					 */
					$i = mt_rand(0, count($award['pos']) - 1);
					$awardPos = $award['pos'][$i];
				}
				break;
			} else {
				/**
				 * 缩小范围
				 */
				$randNum -= (int) $award['prob'];
			}
		}
		return $awardPos;
	}
}