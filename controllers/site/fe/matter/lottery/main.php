<?php
namespace site\fe\matter\lottery;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 抽奖活动
 */
class main extends \site\fe\matter\base {
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
	protected function canAccessObj($site, $app, $member, $authapis, $lot) {
		return $this->model('acl')->canAccessMatter($site, 'lottery', $app, $member, $authapis);
	}
	/**
	 * 获得抽奖活动的页面
	 *
	 * $site
	 * $app 抽奖活动id
	 * $shareby 谁做的分享
	 */
	public function index_action($site, $app, $shareby = '', $pretaskdone = 'N', $mocker = null, $code = null) {
		empty($site) && $this->outputError('没有指定当前运行的公众号');
		empty($app) && $this->outputError('抽奖活动id为空');

		$modelLot = $this->model('matter\lottery');
		$lot = $modelLot->byId($app);
		$current = time();
		/* start? */
		if ($current < $lot->start_at) {
			\TPL::assign('title', $lot->title);
			\TPL::assign('body', empty($lot->nostart_alert) ? '活动未开始' : $lot->nostart_alert);
			\TPL::output('info');
			exit;
		}
		/* end? */
		if ($current > $lot->end_at) {
			\TPL::assign('title', $lot->title);
			\TPL::assign('body', empty($lot->hasend_alert) ? '活动已结束' : $lot->hasend_alert);
			\TPL::output('info');
			exit;
		}
		/* 限制公众帐号关注用户参与 */
		if ($lot->fans_only === 'Y' || $lot->fans_enter_only === 'Y') {
			if (!$this->afterSnsOAuth()) {
				/* 检查是否需要第三方社交帐号OAuth */
				$this->_requireSnsOAuth($site, $lot);
			}
		}
		/* 给agent设置合法进入的key */
		$this->_setAgentEnter($lot->id);

		/* 返回页面内容 */
		\TPL::assign('title', $lot->title);
		\TPL::output('/site/fe/matter/lottery/play');
		exit;
	}
	/**
	 * 检查是否需要第三方社交帐号认证
	 * 检查条件：
	 * 0、应用是否设置了需要认证
	 * 1、站点是否绑定了第三方社交帐号认证
	 * 2、平台是否绑定了第三方社交帐号认证
	 * 3、用户客户端是否可以发起认证
	 *
	 * @param string $site
	 * @param object $app
	 */
	private function _requireSnsOAuth($siteid, &$app) {
		if ($this->userAgent() === 'wx') {
			if (!isset($this->who->sns->wx)) {
				if ($wxConfig = $this->model('sns\wx')->bySite($siteid)) {
					if ($wxConfig->joined === 'Y') {
						$this->snsOAuth($wxConfig, 'wx');
					}
				}
			}
			if (!isset($this->who->sns->qy)) {
				if ($qyConfig = $this->model('sns\qy')->bySite($siteid)) {
					if ($qyConfig->joined === 'Y') {
						$this->snsOAuth($qyConfig, 'qy');
					}
				}
			}
		} else if ($this->userAgent() === 'yx') {
			if (!isset($this->who->sns->yx)) {
				if ($yxConfig = $this->model('sns\yx')->bySite($siteid)) {
					if ($yxConfig->joined === 'Y') {
						$this->snsOAuth($yxConfig, 'yx');
					}
				}
			}
		}

		return false;
	}
	/**
	 * 抽奖活动定义
	 */
	public function get_action($site, $app) {
		/* user */
		$user = $this->who;
		/**
		 * 抽奖活动定义
		 */
		$modelLot = $this->model('matter\lottery');
		$lot = $modelLot->byId($app, array('cascaded' => array('award', 'plate')));
		/**
		 *
		 */
		$params = new \stdClass;
		$params->user = $user;
		$params->logs = $modelLot->getLog($lot->id, $user, true);
		$params->leftChance = $modelLot->getChance($lot, $user);
		$params->app = $lot;

		$page = $this->model('code\page')->lastPublishedByName($site, $lot->page_code_name);
		$params->page = $page;

		return new \ResponseData($params);
	}
	/**
	 * 最近的获奖者清单
	 */
	public function winnersList_action($app) {
		$winners = $this->model('matter\lottery')->getWinners($app);

		return new \ResponseData($winners);
	}
	/**
	 * 进行抽奖
	 *
	 * @param string $site
	 * @param string $app 抽奖互动
	 * @param string $enrollKey 关联的登记活动的登记记录
	 *
	 */
	public function play_action($site, $app, $enrollKey = null) {
		/* 检查请求是否从客户端发起 */
		if (!$this->_isAgentEnter($app)) {
			return new \ResponseError('请从指定客户端发起请求');
		}

		$user = $this->who;
		$modelLot = $this->model('matter\lottery');
		$modelTsk = $this->model('matter\lottery\task');
		$modelRst = $this->model('matter\lottery\result');
		/**
		 * define data.
		 */
		$lot = $modelLot->byId($app, array('cascaded' => array('award', 'plate')));
		/**
		 * 如果仅限关注用户参与，获得openid
		 */
		if ($lot->fans_only === 'Y') {
			$fan = $this->getSnsUser($site);
			if (empty($fan)) {
				return new \ResponseData(null, 302, $lot->nonfans_alert);
			}
		}
		/**
		 * 检查是否有奖励的抽奖机会
		 */
		$modelLot->freshEarnChance($lot, $user);
		/**
		 * 是否完成了前置任务
		 */
		$tasks = $modelTsk->byApp($lot->id, array('task_type' => 'can_play'));
		if (count($tasks)) {
			foreach ($tasks as $lotTask) {
				$userTask = $modelTsk->getTaskByUser($user, $lot->id, $lotTask->tid);
				if ($userTask === false) {
					/*创建任务*/
					$modelTsk->addTask4User($user, $lot->id, $lotTask->tid);
					return new \ResponseData(null, 301, $lotTask->description);
				}
				if ($userTask->finished === 'N') {
					if (false === $modelTsk->checkUserTask($user, $lot->id, $lotTask, $userTask)) {
						/*任务未完成*/
						return new \ResponseData(null, 301, $lotTask->description);
					}
				}
			}
		}
		/**
		 * 还有参加抽奖的机会吗？
		 */
		if (false === $modelRst->canPlay($lot, $user, true)) {
			return new \ResponseData(null, 301, $lot->nochance_alert);
		}
		/**
		 * 抽奖
		 */
		list($selectedSlot, $selectedAwardID, $myAward) = $this->_drawAward($lot, $user);

		if (empty($myAward)) {
			return new \ResponseData(null, 301, '对不起，没有奖品了！');
		}
		/**
		 * 领取非实体奖品
		 */
		if ($myAward['type'] == 1 || $myAward['type'] == 2 || $myAward['type'] == 3) {
			$modelRst->acceptAward($app, $user, $myAward);
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
		$log = $modelRst->add($site, $app, $user, $myAward2, $enrollKey);
		/**
		 * 检查剩余的机会
		 */
		$chance = $modelLot->getChance($lot, $user);
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
	public function prize_action($site) {
		$log = $this->getPostJson();

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
	public function myawards_action($site, $app) {
		$model = $this->model('app\lottery');
		/**
		 * 抽奖活动定义
		 */
		$r = $model->byId($app, 'access_control,authapis', array('award'));
		/**
		 * is member?
		 */
		$mid = null;
		if ($r->access_control) {
			$aAuthapis = explode(',', $r->authapis);
			if ($members = $this->getCookieMember($site, $aAuthapis)) {
				$mid = $members[0]->mid;
			}

		}

		$fan = $this->getCookieOAuthUser($site);

		$myAwards = $model->getLog($app, $mid, $fan->openid, true);

		return new \ResponseData($myAwards);
	}
	/**
	 * 抽取奖品
	 * 奖品必须还有剩余
	 *
	 * @param object $lot
	 */
	private function _drawAward(&$lot, &$user) {
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
			/*限制了奖项的中奖次数*/
			if ((int) $a->user_limit > 0) {
				$q = array(
					'count(*)',
					'xxt_lottery_log',
					"userid='$user->uid' and lid='$lot->id' and aid='$a->aid'",
				);
				$count = (int) $this->model()->query_val_ss($q);
				if ($count >= (int) $a->user_limit) {
					continue;
				}
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