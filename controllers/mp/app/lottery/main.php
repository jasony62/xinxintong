<?php
namespace mp\app\lottery;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *
 */
class main extends \mp\app\app_base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'lottery';
	}
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/mp/app/lottery');
	}
	/**
	 * 返回转盘抽奖活动数据
	 */
	public function get_action($lid = null, $src = null) {
		$uid = \TMS_CLIENT::get_client_uid();
		if ($lid) {
			/**
			 * one
			 */
			$r = $this->model('app\lottery')->byId($lid, '*', array('award', 'task'));
			$r->url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/lottery?mpid=$r->mpid&lid=$lid";
			$r->preactivitydone_url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/lottery/preactiondone?mpid=$r->mpid&lid=$lid";
			/**
			 * acl
			 */
			$r->acl = $this->model('acl')->byMatter($this->mpid, 'lottery', $lid);

			return new \ResponseData($r);
		} else {
			/**
			 * list
			 */
			$q = array('*', 'xxt_lottery');
			if ($src === 'p') {
				$pmpid = $this->getParentMpid();
				$q[2] = "mpid='$pmpid'";
			} else {
				$q[2] = "mpid='$this->mpid'";
			}

			$q2['o'] = 'create_at desc';

			$r = $this->model()->query_objs_ss($q, $q2);

			return new \ResponseData($r);
		}
	}
	/**
	 * 获得转盘设置信息
	 */
	public function plate_action($lid) {
		$q = array(
			'*',
			'xxt_lottery_plate',
			"lid='$lid'",
		);
		$p = $this->model()->query_obj_ss($q);

		return new \ResponseData($p);
	}
	/**
	 * 创建一个轮盘抽奖活动
	 *
	 * 自动生成没有奖励的奖项
	 * 自动将转盘各个槽位的奖项设置为没有奖励的缺省奖项
	 */
	public function create_action() {
		$uid = \TMS_CLIENT::get_client_uid();
		$mpa = $this->model('mp\mpaccount')->getFeatures($this->mpid, 'heading_pic');

		$lid = uniqid();
		$current = time();

		$newone['mpid'] = $this->mpid;
		$newone['id'] = $lid;
		$newone['title'] = '新抽奖活动';
		$newone['creater'] = \TMS_CLIENT::get_client_uid();
		//$newone['creater_name'] = $account->nickname;
		$newone['create_at'] = $current;
		$newone['pic'] = $mpa->heading_pic;
		$newone['start_at'] = $current;
		$newone['end_at'] = $current + 86400;
		$newone['nonfans_alert'] = "请先关注公众号，再参与抽奖！";
		$newone['nochance_alert'] = "您的抽奖机会已经用光了，下次再来试试吧！";
		/**
		 * 创建定制页
		 */
		$codeModel = $this->model('code/page');
		$page = $codeModel->create($uid);
		$data = array(
			'html' => '<button ng-click="play()">开始</button>',
			'css' => '#pattern button{width:100%;font-size:1.2em;padding:.5em 0}',
			'js' => '',
		);
		$codeModel->modify($page->id, $data);
		$newone['page_id'] = $page->id;

		$this->model()->insert('xxt_lottery', $newone, false);
		/**
		 * default award
		 */
		$aid = uniqid();
		$award['mpid'] = $this->mpid;
		$award['lid'] = $lid;
		$award['aid'] = $aid;
		$award['title'] = '谢谢参与';
		$award['prob'] = 100;
		$award['type'] = 0;
		$this->model()->insert('xxt_lottery_award', $award, false);
		/**
		 * plate
		 */
		$plate['mpid'] = $this->mpid;
		$plate['lid'] = $lid;
		for ($i = 0; $i < 12; $i++) {
			$plate["a$i"] = $aid;
		}
		$this->model()->insert('xxt_lottery_plate', $plate, false);

		return new \ResponseData($lid);
	}
	/**
	 * 更新轮盘抽奖的基本设置信息
	 */
	public function update_action($lid) {
		$nv = (array) $this->getPostJson();

		$keys = array_keys($nv);
		foreach ($keys as $k) {
			if (in_array($k, array('nonfans_alert', 'nochance_alert', 'nostart_alert', 'hasend_alert', 'preactivity', 'extra_css', 'extra_ele', 'extra_js'))) {
				$nv[$k] = $this->model()->escape($nv[$k]);
			}

		}
		$rst = $this->model()->update('xxt_lottery', $nv, "id='$lid'");

		return new \ResponseData($rst);
	}
	/**
	 * 添加奖项
	 */
	public function addAward_action($lid, $mpid) {
		$a = array(
			'mpid' => $mpid,
			'lid' => $lid,
			'aid' => uniqid(),
			'title' => '新增奖项',
			'pic' => '',
			'type' => 0,
			'quantity' => 0,
			'prob' => 0,
		);
		$this->model()->insert('xxt_lottery_award', $a, false);

		return new \ResponseData($a);
	}
	/**
	 * 设置奖项的属性
	 *
	 * $aid award's id.
	 */
	public function setAward_action($aid) {
		$nv = $this->getPostJson();

		if (isset($nv->description)) {
			$nv->description = $this->model()->escape($nv->description);
		} else if (isset($nv->greeting)) {
			$nv->greeting = $this->model()->escape($nv->greeting);
		}

		$rst = $this->model()->update('xxt_lottery_award', (array) $nv, "aid='$aid'");

		return new \ResponseData($rst);
	}
	/**
	 * 删除奖项
	 *
	 * 如果已经有人中奖，就不允许删除奖项
	 */
	public function delAward_action($aid) {
		/**
		 * 检查是否已经有中奖记录
		 */
		$q = array(
			'count(*)',
			'xxt_lottery_log',
			"aid='$aid'",
		);
		$cnt = $this->model()->query_val_ss($q);
		if ($cnt > 0) {
			return new \ComplianceError('已经有中奖记录，奖项不允许被删除！');
		}

		$rst = $this->model()->delete('xxt_lottery_award', "aid='$aid'");

		return new \ResponseData($rst);
	}
	/**
	 * 设置转盘槽位的奖项
	 */
	public function setPlate_action($lid) {
		$r = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_lottery_plate',
			(array) $r,
			"lid='$lid'"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function pageSet_action($lid, $pageid, $pattern) {
		$codeModel = $this->model('code/page');
		$page = $codeModel->byId($pageid);
		$data = array(
			'html' => file_get_contents(dirname(__FILE__) . '/pattern/' . $pattern . '.html'),
			'css' => file_get_contents(dirname(__FILE__) . '/pattern/' . $pattern . '.css'),
			'js' => file_get_contents(dirname(__FILE__) . '/pattern/' . $pattern . '.js'),
		);
		$rst = $codeModel->modify($page->id, $data);

		return new \ResponseData($rst);
	}
	/**
	 * 抽奖结果列表
	 */
	public function result_action($lid, $startAt = null, $endAt = null, $page = 1, $size = 30, $award = null, $assocAct = null) {
		$r = $this->model('app\lottery')->byId($lid, 'access_control');
		if ($r->access_control === 'Y') {
			$q = array(
				'l.mid,m.name,m.mobile,m.email,l.draw_at,a.title award_title,l.takeaway',
				'xxt_lottery_log l,xxt_lottery_award a,xxt_member m',
				"l.lid='$lid' and l.mid=m.mid and m.forbidden='N' and l.aid=a.aid",
			);
		} else {
			/**
			 * 参与抽奖的用户不一定是关注用户，所以粉丝表里不一定有对应的记录
			 */
			$q = array(
				"f.nickname,l.openid,l.draw_at,a.title award_title,l.takeaway",
				"xxt_lottery_log l left join xxt_lottery_award a on l.aid=a.aid left join xxt_fans f on f.mpid='$this->mpid' and l.openid=f.openid",
				"l.lid='$lid'",
			);
		}
		/**
		 * 指定时间范围
		 */
		if ($startAt !== null && $endAt !== null) {
			$q[2] .= " and l.draw_at>=$startAt and l.draw_at<=$endAt";
		}

		/**
		 * 指定奖项
		 */
		if (!empty($award)) {
			$q[2] .= " and l.aid='$award'";
		}

		/**
		 * 排序和分页
		 */
		$q2['o'] = 'draw_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;

		$result = $this->model()->query_objs_ss($q, $q2);
		/**
		 * 总数
		 */
		$q[0] = 'count(*)';
		$amount = $this->model()->query_val_ss($q);
		/**
		 * 从关联活动中获得登记数据
		 * todo 有可能实现批量获取吗？
		 */
		if (!empty($assocAct)) {
			foreach ($result as &$r) {
				/**
				 * 获取数据
				 */
				$sql = 'select c.name,c.value';
				$sql .= ' from xxt_enroll_record_data c, xxt_enroll_record e';
				$sql .= " where e.aid='$assocAct' and c.enroll_key=e.enroll_key";
				$sql .= " and e.openid='$r->openid'";
				$cusdata = $this->model()->query_objs($sql);
				/**
				 * 组合数据
				 */
				foreach ($cusdata as $cd) {
					$r->assoc->{$cd->name} = $cd->value;
				}

			}
			if (isset($cusdata)) {
				$assocDef = array();
				foreach ($cusdata as $cd) {
					$assocDef[] = $cd->name;
				}

			}
		}

		return new \ResponseData(array($result, $amount, isset($assocDef) ? $assocDef : array()));
	}
	/**
	 * 抽奖情况统计
	 */
	public function stat_action($lid) {
		$q = array(
			'a.aid,a.title,count(*) number',
			'xxt_lottery_award a,xxt_lottery_log l',
			"l.lid='$lid' and a.lid=l.lid and a.aid=l.aid",
		);
		$q2 = array(
			'g' => 'a.aid',
			'o' => 'a.prob',
		);

		$stat = $this->model()->query_objs_ss($q, $q2);

		return new \ResponseData($stat);
	}
	/**
	 * 删除一条抽奖活动数据
	 * todo 是否应该更新奖品数量？
	 */
	public function removeRoll_action($lid, $openid = '', $mid = '') {
		if (!empty($openid)) {
			$whichuser = "lid='$lid' and openid='$openid'";
		} else if (!empty($mid)) {
			$whichuser = "lid='$lid' and mid='$mid'";
		} else {
			die('invalid parameters.');
		}

		$this->model()->delete(
			'xxt_lottery_task_log',
			$whichuser
		);

		$rst = $this->model()->delete(
			'xxt_lottery_log',
			$whichuser
		);

		return new \ResponseData($rst);
	}
	/**
	 * 清空抽奖活动数据
	 */
	public function clean_action($lid) {
		$rst = $this->model('app\lottery')->clean($lid);

		return new \ResponseData($rst);
	}
	/**
	 * 给所有未中奖的用户增加一次抽奖机会
	 */
	public function addChance_action($lid) {
		/**
		 * 获得所有未中奖的用户
		 */
		$q = array(
			'l.mid,l.openid',
			'xxt_lottery_log l,xxt_lottery_award a',
			"l.lid='$lid' and l.aid=a.aid and l.last='Y' and a.type=0",
		);
		$award['quantity'] = 1;
		$losers = $this->model()->query_objs_ss($q);
		foreach ($losers as $loser) {
			$this->model('app\lottery')->earnPlayAgain($lid, $loser->mid, $loser->openid, $award);
		}

		return new \ResponseData(count($losers));
	}
}
