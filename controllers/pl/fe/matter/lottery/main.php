<?php
namespace pl\fe\matter\lottery;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 抽奖活动主控制器
 */
class main extends \pl\fe\matter\base {
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
		\TPL::output('/pl/fe/matter/lottery/frame');
		exit;
	}
	/**
	 *
	 */
	public function setting_action() {
		\TPL::output('/pl/fe/matter/lottery/frame');
		exit;
	}
	/**
	 *
	 */
	public function running_action() {
		\TPL::output('/pl/fe/matter/lottery/frame');
		exit;
	}
	/**
	 * 返回转盘抽奖活动数据
	 *
	 * @param string $lottery ID
	 */
	public function get_action($site, $app) {
		$options = array(
			'fields' => '*',
			'cascaded' => array('award', 'task'),
		);
		$lot = $this->model('matter\lottery')->byId($app, $options);
		/*acl*/
		//$lot->acl = $this->model('acl')->byMatter($site, 'lottery', $lottery);

		return new \ResponseData($lot);
	}
	/**
	 * 抽奖活动
	 */
	public function list_action($site) {
		$q = array(
			'*',
			'xxt_lottery',
			"siteid='$site'",
		);
		$q2['o'] = 'create_at desc';

		$apps = $this->model()->query_objs_ss($q, $q2);

		return new \ResponseData($apps);
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
	public function create_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$lid = uniqid();
		$current = time();
		$site = $this->model('site')->byId($site, array('fields' => 'id,heading_pic'));

		$newone['siteid'] = $site->id;
		$newone['id'] = $lid;
		$newone['title'] = '新抽奖活动';
		$newone['creater'] = $user->id;
		$newapp['creater_src'] = $user->src;
		$newone['creater_name'] = $user->name;
		$newone['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $user->name;
		$newapp['modify_at'] = $current;
		$newone['pic'] = $site->heading_pic;
		$newone['start_at'] = $current;
		$newone['end_at'] = $current + 86400;
		$newone['nonfans_alert'] = "请先关注公众号，再参与抽奖！";
		$newone['nochance_alert'] = "您的抽奖机会已经用光了，下次再来试试吧！";
		$newone['pretaskdesc'] = "请设置前置任务";
		/**
		 * 创建定制页
		 */
		$modelCode = $this->model('code\page');
		$page = $modelCode->create($site, $user->id);
		$data = array(
			'html' => '<button ng-click="play()">开始</button>',
			'css' => '#pattern button{width:100%;font-size:1.2em;padding:.5em 0}',
			'js' => '',
		);
		$modelCode->modify($page->id, $data);
		$newone['page_id'] = $page->id;
		$newone['page_code_name'] = $page->name;

		$this->model()->insert('xxt_lottery', $newone, false);
		/**
		 * default award
		 */
		$aid = uniqid();
		$award['siteid'] = $site->id;
		$award['lid'] = $lid;
		$award['aid'] = $aid;
		$award['title'] = '谢谢参与';
		$award['prob'] = 100;
		$award['type'] = 0;
		$this->model()->insert('xxt_lottery_award', $award, false);
		/**
		 * plate
		 */
		$plate['siteid'] = $site->id;
		$plate['lid'] = $lid;
		for ($i = 0; $i < 12; $i++) {
			$plate["a$i"] = $aid;
		}
		$this->model()->insert('xxt_lottery_plate', $plate, false);
		$app = $this->model('matter\lottery')->byId($lid);
		/*记录操作日志*/
		$app->type = 'lottery';
		$this->model('log')->matterOp($site->id, $user, $app, 'C');

		return new \ResponseData($lid);
	}
	/**
	 *
	 * @param int $id mission'is
	 */
	public function createByMission_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('mission');
		$mission = $modelMis->byId($id);
		/* lottery */
		$lid = uniqid();
		$current = time();
		$site = $this->model('site')->byId($site, array('fields' => 'id,heading_pic'));

		$newone['siteid'] = $site->id;
		$newone['id'] = $lid;
		$newone['title'] = '新抽奖活动';
		$newone['creater'] = $user->id;
		$newapp['creater_src'] = $user->src;
		$newone['creater_name'] = $user->name;
		$newone['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $user->name;
		$newapp['modify_at'] = $current;
		$newone['pic'] = $site->heading_pic;
		$newone['start_at'] = $current;
		$newone['end_at'] = $current + 86400;
		$newone['nonfans_alert'] = "请先关注公众号，再参与抽奖！";
		$newone['nochance_alert'] = "您的抽奖机会已经用光了，下次再来试试吧！";
		$newone['pretaskdesc'] = "请设置前置任务";
		/**
		 * 创建定制页
		 */
		$modelCode = $this->model('code\page');
		$page = $modelCode->create($user->id);
		$data = array(
			'html' => '<button ng-click="play()">开始</button>',
			'css' => '#pattern button{width:100%;font-size:1.2em;padding:.5em 0}',
			'js' => '',
		);
		$modelCode->modify($page->id, $data);
		$newone['page_id'] = $page->id;

		$this->model()->insert('xxt_lottery', $newone, false);
		/**
		 * default award
		 */
		$aid = uniqid();
		$award['siteid'] = $site->id;
		$award['lid'] = $lid;
		$award['aid'] = $aid;
		$award['title'] = '谢谢参与';
		$award['prob'] = 100;
		$award['type'] = 0;
		$this->model()->insert('xxt_lottery_award', $award, false);
		/**
		 * plate
		 */
		$plate['siteid'] = $site->id;
		$plate['lid'] = $lid;
		for ($i = 0; $i < 12; $i++) {
			$plate["a$i"] = $aid;
		}
		$this->model()->insert('xxt_lottery_plate', $plate, false);
		$app = $this->model('matter\lottery')->byId($lid);
		/*记录操作日志*/
		$app->type = 'lottery';
		$this->model('log')->matterOp($site->id, $user, $app, 'C');
		/*记录和任务的关系*/
		$modelMis->addMatter($user, $site, $id, $app);

		return new \ResponseData($lid);
	}
	/**
	 * 更新抽奖活动的基本设置信息
	 */
	public function update_action($site, $app) {
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$nv = $this->getPostJson();

		foreach ($nv as $k => $v) {
			if (in_array($k, array('nonfans_alert', 'nochance_alert', 'nostart_alert', 'hasend_alert', 'pretaskdesc'))) {
				$nv->{$k} = $model->escape($v);
			}
		}
		$nv->modifier = $user->id;
		$nv->modifier_src = $user->src;
		$nv->modifier_name = $user->name;
		$nv->modify_at = time();

		$rst = $this->model()->update('xxt_lottery', $nv, "id='$app'");
		/*记录操作日志*/
		if ($rst) {
			$app = $this->model('matter\lottery')->byId($app, 'id,title,summary,pic');
			$app->type = 'lottery';
			$this->model('log')->matterOp($site, $user, $app, 'U');
		}

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
		$modelCode = $this->model('code\page');
		if ($pageid) {
			$page = $modelCode->byId($pageid);
		} else {
			/**
			 * 创建定制页
			 */
			$uid = \TMS_CLIENT::get_client_uid();
			$page = $modelCode->create($uid);
			$this->model()->update('xxt_lottery', array('page_id' => $page->id), "id='$lid'");
		}
		$data = array(
			'html' => file_get_contents(dirname(__FILE__) . '/pattern/' . $pattern . '.html'),
			'css' => file_get_contents(dirname(__FILE__) . '/pattern/' . $pattern . '.css'),
			'js' => file_get_contents(dirname(__FILE__) . '/pattern/' . $pattern . '.js'),
		);
		$rst = $modelCode->modify($page->id, $data);

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
	public function removeRoll_action($lid, $userid) {
		$this->model()->delete(
			'xxt_lottery_task_log',
			"lid='$lid' and userid='$userid'"
		);

		$rst = $this->model()->delete(
			'xxt_lottery_log',
			"lid='$lid' and userid='$userid'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 清空抽奖活动数据
	 */
	public function clean_action($lid) {
		$rst = $this->model('matter\lottery')->clean($lid);

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
			$this->model('matter\lottery')->earnPlayAgain($lid, $loser->mid, $loser->openid, $award);
		}

		return new \ResponseData(count($losers));
	}
}