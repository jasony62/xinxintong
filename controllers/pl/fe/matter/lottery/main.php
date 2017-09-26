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
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$oPosted = $this->getPostJson();
		$q = [
			"*,'lottery' type",
			'xxt_lottery l',
			"siteid = '" . $model->escape($site) . "' and state in (1,2)",
		];
		if (!empty($oPosted->byTitle)) {
			$q[2] .= " and title like '%" . $model->escape($oPosted->byTitle) . "%'";
		}
		if (!empty($oPosted->byTags)) {
			foreach ($oPosted->byTags as $tag) {
				$q[2] .= " and matter_mg_tag like '%" . $model->escape($tag->id) . "%'";
			}
		}
		if (isset($oPosted->byStar) && $oPosted->byStar === 'Y') {
			$q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='lottery' and t.matter_id=l.id and userid='{$oUser->id}')";
		}

		$q2['o'] = 'create_at desc';

		$apps = $model->query_objs_ss($q, $q2);

		return new \ResponseData(['apps' => $apps, 'total' => count($apps)]);
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}

		$modelApp = $this->model('matter\lottery')->setOnlyWriteDbConn(true);

		$current = time();
		$oNewApp = new \stdClass;
		$oNewApp->siteid = $oSite->id;
		$oNewApp->title = '新抽奖活动';
		$oNewApp->pic = $oSite->heading_pic;
		$oNewApp->start_at = $current;
		$oNewApp->end_at = $current + 86400;
		$oNewApp->nonfans_alert = "请先关注公众号，再参与抽奖！";
		$oNewApp->nochance_alert = "您的抽奖机会已经用光了，下次再来试试吧！";
		$oNewApp->pretaskdesc = "请设置前置任务";
		/**
		 * 创建定制页
		 */
		$modelCode = $this->model('code\page');
		$page = $modelCode->create($oSite->id, $oUser->id);
		$data = array(
			'html' => '<button ng-click="play()">开始</button>',
			'css' => '#pattern button{width:100%;font-size:1.2em;padding:.5em 0}',
			'js' => '',
		);
		$modelCode->modify($page->id, $data);
		$oNewApp->page_id = $page->id;
		$oNewApp->page_code_name = $page->name;

		$oNewApp = $modelApp->create($oUser, $oNewApp);
		/**
		 * default award
		 */
		$aid = uniqid();
		$award['siteid'] = $oSite->id;
		$award['lid'] = $oNewApp->id;
		$award['aid'] = $aid;
		$award['title'] = '谢谢参与';
		$award['prob'] = 100;
		$award['type'] = 0;
		$modelApp->insert('xxt_lottery_award', $award, false);
		/**
		 * plate
		 */
		$plate['siteid'] = $oSite->id;
		$plate['lid'] = $oNewApp->id;
		for ($i = 0; $i < 12; $i++) {
			$plate["a$i"] = $aid;
		}
		$modelApp->insert('xxt_lottery_plate', $plate, false);

		/*记录操作日志*/
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');

		return new \ResponseData($oNewApp);
	}
	/**
	 *
	 * @param int $id mission'is
	 */
	public function createByMission_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oSite = $this->model('site')->byId($site, array('fields' => 'id,heading_pic'));
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}

		$modelApp = $this->model('matter\lottery')->setOnlyWriteDbConn(true);
		$modelMis = $this->model('matter\mission');

		/* lottery */
		$current = time();
		$oNewApp = new \stdClass;
		$oNewApp->siteid = $oSite->id;
		$oNewApp->title = '新抽奖活动';
		$oNewApp->pic = $oSite->heading_pic;
		$oNewApp->start_at = $current;
		$oNewApp->end_at = $current + 86400;
		$oNewApp->nonfans_alert = "请先关注公众号，再参与抽奖！";
		$oNewApp->nochance_alert = "您的抽奖机会已经用光了，下次再来试试吧！";
		$oNewApp->pretaskdesc = "请设置前置任务";
		/**
		 * 创建定制页
		 */
		$modelCode = $this->model('code\page');
		$page = $modelCode->create($oSite->id, $oUser->id);
		$data = [
			'html' => '<button ng-click="play()">开始</button>',
			'css' => '#pattern button{width:100%;font-size:1.2em;padding:.5em 0}',
			'js' => '',
		];
		$modelCode->modify($page->id, $data);
		$oNewApp->page_id = $page->id;

		$oNewApp = $modelApp->create($oUser, $oNewApp);
		/**
		 * default award
		 */
		$aid = uniqid();
		$award['siteid'] = $oSite->id;
		$award['lid'] = $oNewApp->id;
		$award['aid'] = $aid;
		$award['title'] = '谢谢参与';
		$award['prob'] = 100;
		$award['type'] = 0;
		$modelApp->insert('xxt_lottery_award', $award, false);
		/**
		 * plate
		 */
		$plate['siteid'] = $oSite->id;
		$plate['lid'] = $oNewApp->id;
		for ($i = 0; $i < 12; $i++) {
			$plate["a$i"] = $aid;
		}
		$modelApp->insert('xxt_lottery_plate', $plate, false);

		/*记录操作日志*/
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');

		return new \ResponseData($oNewApp);
	}
	/**
	 * 更新抽奖活动的基本设置信息
	 */
	public function update_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\lottery');
		$oMatter = $modelApp->byId($app, 'id,title,summary,pic,start_at,end_at,mission_id');
		if (false === $oMatter) {
			return new \ObjectNotFoundError();
		}

		$oUpdated = $this->getPostJson();
		foreach ($oUpdated as $k => $v) {
			if (in_array($k, ['nonfans_alert', 'nochance_alert', 'nostart_alert', 'hasend_alert', 'pretaskdesc'])) {
				$oUpdated->{$k} = $modelApp->escape($v);
			}
		}

		if ($oMatter = $modelApp->modify($oUser, $oMatter, $oUpdated)) {
			$this->model('matter\log')->matterOp($site, $oUser, $oMatter, 'U');
		}

		return new \ResponseData($oMatter);
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
	 * 删除一个活动
	 *
	 * @param string $site
	 * @param string $app
	 */
	public function remove_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelLot = $this->model('matter\lottery');
		$oApp = $modelLot->byId($app, 'siteid,id,title,summary,pic,creater');
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		if ($oApp->creater !== $oUser->id) {
			return new \ResponseError('没有删除数据的权限');
		}

		$rst = $modelLot->remove($oUser, $oApp, 'Recycle');

		return new \ResponseData($rst);
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