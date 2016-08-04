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
	 *
	 */
	public function detail_action() {
		$this->view_action('/mp/app/lottery/detail');
	}
	/**
	 *
	 */
	public function plate_action() {
		$this->view_action('/mp/app/lottery/detail');
	}
	/**
	 *
	 */
	public function page_action() {
		$this->view_action('/mp/app/lottery/detail');
	}
	/**
	 *
	 */
	public function result_action() {
		$this->view_action('/mp/app/lottery/detail');
	}
	/**
	 * 返回转盘抽奖活动数据
	 *
	 * @param string $lottery ID
	 */
	public function get_action($lottery) {
		$lot = $this->model('app\lottery')->byId($lottery, '*', array('award', 'task'));
		/*acl*/
		$lot->acl = $this->model('acl')->byMatter($this->mpid, 'lottery', $lottery);

		return new \ResponseData($lot);
	}
	/**
	 * 返回转盘抽奖活动数据
	 */
	public function list_action($src = null) {
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
	/**
	 * 获得转盘设置信息
	 */
	public function plateGet_action($lid) {
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
		$mpa = $this->model('mp\mpaccount')->getFeature($this->mpid, 'heading_pic');

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
		$newone['pretaskdesc'] = "请设置前置任务";
		/**
		 * 创建定制页
		 */
		$codeModel = $this->model('code\page');
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
	 *
	 * @param string $lottery ID
	 */
	public function update_action($lottery) {
		$nv = (array) $this->getPostJson();

		$keys = array_keys($nv);
		foreach ($keys as $k) {
			if (in_array($k, array('nonfans_alert', 'nochance_alert', 'nostart_alert', 'hasend_alert', 'pretaskdesc'))) {
				$nv[$k] = $this->model()->escape($nv[$k]);
			}

		}
		$rst = $this->model()->update('xxt_lottery', $nv, "id='$lottery'");

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
		$codeModel = $this->model('code\page');
		if ($pageid) {
			$page = $codeModel->byId($pageid);
		} else {
			/**
			 * 创建定制页
			 */
			$uid = \TMS_CLIENT::get_client_uid();
			$page = $codeModel->create($uid);
			$this->model()->update('xxt_lottery', array('page_id' => $page->id), "id='$lid'");
		}
		$data = array(
			'html' => file_get_contents(dirname(__FILE__) . '/pattern/' . $pattern . '.html'),
			'css' => file_get_contents(dirname(__FILE__) . '/pattern/' . $pattern . '.css'),
			'js' => file_get_contents(dirname(__FILE__) . '/pattern/' . $pattern . '.js'),
		);
		$rst = $codeModel->modify($page->id, $data);

		return new \ResponseData($rst);
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