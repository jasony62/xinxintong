<?php
namespace mp\app\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *
 */
class main extends \mp\app\app_base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'enroll';
	}
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/mp/app/enroll');
	}
	/**
	 *
	 */
	public function detail_action() {
		$this->view_action('/mp/app/enroll/detail');
	}
	/**
	 *
	 */
	public function stat_action() {
		$this->view_action('/mp/app/enroll/detail');
	}
	/**
	 *
	 */
	public function accesslog_action() {
		$this->view_action('/mp/app/enroll/detail');
	}
	/**
	 * 返回一个登记活动
	 */
	public function get_action($aid) {
		$uid = \TMS_CLIENT::get_client_uid();
		$a = $this->model('app\enroll')->byId($aid);
		$a->uid = $uid;
		$a->url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/enroll?mpid=$this->mpid&aid=$aid";
		/**
		 * 活动签到回复消息
		 */
		if ($a->success_matter_type && $a->success_matter_id) {
			$m = $this->model('matter\base')->getMatterInfoById($a->success_matter_type, $a->success_matter_id);
			$a->successMatter = $m;
		}
		if ($a->failure_matter_type && $a->failure_matter_id) {
			$m = $this->model('matter\base')->getMatterInfoById($a->failure_matter_type, $a->failure_matter_id);
			$a->failureMatter = $m;
		}
		/* channels */
		$a->channels = $this->model('matter\channel')->byMatter($aid, 'enroll');
		/* acl */
		$a->acl = $this->model('acl')->byMatter($this->mpid, 'enroll', $aid);
		/* 登记通知接收人 */
		$a->receiver = $this->model('acl')->enrollReceiver($this->mpid, $aid);
		/* 获得的轮次 */
		if ($rounds = $this->model('app\enroll\round')->byApp($this->mpid, $aid)) {
			!empty($rounds) && $a->rounds = $rounds;
		}

		return new \ResponseData($a);
	}
	/**
	 * 返回登记活动列表
	 *
	 * $src 是否来源于父账号，=p
	 */
	public function list_action($src = null, $page = 1, $size = 30) {
		$q = array('a.*', 'xxt_enroll a');
		if ($src === 'p') {
			$pmpid = $this->getParentMpid();
			$q[2] = "mpid='$pmpid' and state=1";
		} else {
			$q[2] = "mpid='$this->mpid' and state=1";
		}
		$q2['o'] = 'a.modify_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($a = $this->model()->query_objs_ss($q, $q2)) {
			$result[] = $a;
			$q[0] = 'count(*)';
			$total = (int) $this->model()->query_val_ss($q);
			$result[] = $total;
			return new \ResponseData($result);
		}
		return new \ResponseData(array());
	}
	/**
	 * 创建一个空的登记活动
	 *
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 */
	public function create_action($scenario = null, $template = null) {
		$user = $this->accountUser();
		$current = time();
		$mpa = $this->model('mp\mpaccount')->getFeature($this->mpid, 'heading_pic');

		$newapp = array();
		$aid = uniqid();
		/*pages*/
		if (!empty($scenario) && !empty($template)) {
			$customConfig = $this->getPostJson();
			$config = $this->_addPageByTemplate($aid, $scenario, $template, $customConfig);
			$entryRule = $config->entryRule;
			if (isset($config->multi_rounds) && $config->multi_rounds === 'Y') {
				$this->_createRound($aid);
				$newapp['multi_rounds'] = 'Y';
			}
			if (isset($config->enrolled_entry_page)) {
				$newapp['enrolled_entry_page'] = $config->enrolled_entry_page;
			}
		} else {
			$entryRule = $this->_addBlankPage($aid);
		}
		if (empty($entryRule)) {
			return new \ResponseError('没有获得页面进入规则');
		}
		/*create app*/
		$newapp['mpid'] = $this->mpid;
		$newapp['id'] = $aid;
		$newapp['title'] = '新登记活动';
		$newapp['pic'] = $mpa->heading_pic;
		$newapp['creater'] = $user->id;
		$newapp['creater_src'] = $user->src;
		$newapp['creater_name'] = $user->name;
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $user->name;
		$newapp['modify_at'] = $current;
		$newapp['entry_rule'] = json_encode($entryRule);
		$newapp['summary'] = '';
		$this->model()->insert('xxt_enroll', $newapp, false);
		$app = $this->model('app\enroll')->byId($aid);
		/*记录操作日志*/
		$app->type = 'enroll';
		$this->model('log')->matterOp($this->mpid, $user, $app, 'C');

		return new \ResponseData($app);
	}
	/**
	 *
	 * @param int $id mission'is
	 */
	public function createByMission_action($id) {
		$modelMis = $this->model('mission');
		$mission = $modelMis->byId($id);
		$user = $this->accountUser();
		$current = time();

		/*create app*/
		$aid = uniqid();
		$entryRule = $this->_addBlankPage($aid);
		$newapp['mpid'] = $this->mpid;
		$newapp['id'] = $aid;
		$newapp['title'] = '新登记活动';
		$newapp['pic'] = $mission->pic;
		$newapp['creater'] = $user->id;
		$newapp['creater_src'] = $user->src;
		$newapp['creater_name'] = $user->name;
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $user->name;
		$newapp['modify_at'] = $current;
		$newapp['entry_rule'] = json_encode($entryRule);
		$newapp['summary'] = $mission->summary;
		$this->model()->insert('xxt_enroll', $newapp, false);

		$matter = $this->model('app\enroll')->byId($aid);
		/*记录操作日志*/
		$matter->type = 'enroll';
		$this->model('log')->matterOp($this->mpid, $user, $matter, 'C');
		/*记录和任务的关系*/
		$modelMis->addMatter($user, $this->mpid, $id, $matter);

		return new \ResponseData($matter);
	}
	/**
	 *
	 */
	private function _createRound($aid) {
		$roundId = uniqid();
		$round = array(
			'mpid' => $this->mpid,
			'aid' => $aid,
			'rid' => $roundId,
			'creater' => \TMS_CLIENT::get_client_uid(),
			'create_at' => time(),
			'title' => '轮次1',
			'state' => 1,
		);

		$this->model()->insert('xxt_enroll_round', $round, false);

		return true;
	}
	/**
	 * 根据模板生成页面
	 *
	 * @param string $aid
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 */
	private function _addPageByTemplate($aid, $scenario, $template, $customConfig) {
		$templateDir = dirname(__FILE__) . '/scenario/' . $scenario . '/templates/' . $template;
		$config = file_get_contents($templateDir . '/config.json');
		$config = preg_replace('/\t|\r|\n/', '', $config);
		$config = json_decode($config);
		$pages = $config->pages;
		if (empty($pages)) {
			return false;
		}
		$modelPage = $this->model('app\enroll\page');
		$modelCode = $this->model('code\page');
		foreach ($pages as $page) {
			$ap = $modelPage->add($this->mpid, $aid, (array) $page);
			$data = array(
				'html' => file_get_contents($templateDir . '/' . $page->name . '.html'),
				'css' => file_get_contents($templateDir . '/' . $page->name . '.css'),
				'js' => file_get_contents($templateDir . '/' . $page->name . '.js'),
			);
			//if ($page->type === 'I') {
			/*填充页面*/
			$matched = array();
			$pattern = '/<!-- begin: generate by schema -->.*<!-- end: generate by schema -->/s';
			if (preg_match($pattern, $data['html'], $matched)) {
				if (isset($customConfig->simpleSchema)) {
					$html = $modelPage->htmlBySimpleSchema($customConfig->simpleSchema, $matched[0]);
				} else {
					$html = $modelPage->htmlBySchema($config->schema, $matched[0]);
				}
				$data['html'] = preg_replace($pattern, $html, $data['html']);
			}
			//}
			$modelCode->modify($ap->code_id, $data);
		}

		return $config;
	}
	/**
	 * 添加空页面
	 */
	private function _addBlankPage($aid) {
		$current = time();
		$modelPage = $this->model('app\enroll\page');
		/* form page */
		$page = array(
			'title' => '登记信息页',
			'type' => 'I',
			'name' => 'z' . $current,
		);
		$page = $modelPage->add($this->mpid, $aid, $page);
		/*entry rules*/
		$entryRule = array(
			'otherwise' => array('entry' => $page->name),
			'member' => array('entry' => $page->name, 'enroll' => 'Y', 'remark' => 'Y'),
			'member_outacl' => array('entry' => $page->name, 'enroll' => 'Y', 'remark' => 'Y'),
			'fan' => array('entry' => $page->name, 'enroll' => 'Y', 'remark' => 'Y'),
			'nonfan' => array('entry' => '$mp_follow', 'enroll' => '$mp_follow'),
		);
		/* result page */
		$page = array(
			'title' => '查看结果页',
			'type' => 'V',
			'name' => 'z' . ($current + 1),
		);
		$modelPage->add($this->mpid, $aid, $page);

		return $entryRule;
	}
	/**
	 * 复制一个登记活动
	 *
	 * @param int $template
	 *
	 * @return object ResponseData
	 */
	public function createByOther_action($template) {
		$user = $this->accountUser();
		$current = time();
		$modelApp = $this->model('app\enroll');
		$modelPage = $this->model('app\enroll\page');
		$modelCode = $this->model('code\page');

		$template = $this->model('shop\shelf')->byId($template);
		$aid = $template->matter_id;
		$copied = $modelApp->byId($aid);
		$copied->title = $template->title;
		$copied->summary = $template->summary;
		$copied->pic = $template->pic;
		/**获得的基本信息*/
		$newaid = uniqid();
		$newapp = array();
		$newapp['mpid'] = $this->mpid;
		$newapp['id'] = $newaid;
		$newapp['creater'] = $user->id;
		$newapp['creater_src'] = $user->src;
		$newapp['creater_name'] = $user->name;
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $user->name;
		$newapp['modify_at'] = $current;
		$newapp['title'] = $copied->title . '（副本）';
		$newapp['pic'] = $copied->pic;
		$newapp['summary'] = $copied->summary;
		$newapp['public_visible'] = $copied->public_visible;
		$newapp['open_lastroll'] = $copied->open_lastroll;
		$newapp['can_signin'] = $copied->can_signin;
		$newapp['can_lottery'] = $copied->can_lottery;
		$newapp['tags'] = $copied->tags;
		$newapp['enrolled_entry_page'] = $copied->enrolled_entry_page;
		$newapp['receiver_page'] = $copied->receiver_page;
		$newapp['entry_rule'] = json_encode($copied->entry_rule);
		$this->model()->insert('xxt_enroll', $newapp, false);
		/**复制自定义页面*/
		if ($copied->pages) {
			foreach ($copied->pages as $ep) {
				$newPage = $modelPage->add($this->mpid, $newaid);
				$rst = $modelPage->update(
					'xxt_enroll_page',
					array('title' => $ep->title, 'name' => $ep->name),
					"aid='$newaid' and id=$newPage->id"
				);
				$data = array(
					'title' => $ep->title,
					'html' => $ep->html,
					'css' => $ep->css,
					'js' => $ep->js,
				);
				$modelCode->modify($newPage->code_id, $data);
			}
		}
		/**复制抽奖页内容*/
		if ($copied->can_lottery === 'Y' && $copied->lottery_page_id) {
			$lp = $modelCode->byId($copied->lottery_page_id);
			$code = $modelCode->create($user->id);
			$rst = $modelPage->update(
				'xxt_enroll',
				array('lottery_page_id' => $code->id),
				"id='$newaid'"
			);
			$data = array(
				'title' => $lp->title,
				'html' => $lp->html,
				'css' => $lp->css,
				'js' => $lp->js,
			);
			$modelCode->modify($code->id, $data);
			foreach ($lp->ext_js as $ejs) {
				$modelCode->insert('xxt_code_external', array('code_id' => $code->id, 'type' => 'J', 'url' => $ejs->url), false);
			}
			foreach ($lp->ext_css as $ecss) {
				$modelCode->insert('xxt_code_external', array('code_id' => $code->id, 'type' => 'C', 'url' => $ecss->url), false);
			}
		}
		$app = $modelApp->byId($newaid, array('cascaded' => 'N'));
		/*记录操作日志*/
		$app->type = 'enroll';
		$this->model('log')->matterOp($this->mpid, $user, $app, 'C');

		return new \ResponseData($app);
	}
	/**
	 * 复制一个登记活动
	 */
	public function copy_action($aid) {
		$user = $this->accountUser();
		$current = time();
		$modelApp = $this->model('app\enroll');
		$modelCode = $this->model('code\page');

		$copied = $modelApp->byId($aid);
		/**
		 * 获得的基本信息
		 */
		$newaid = uniqid();
		$newapp = array();
		$newapp['mpid'] = $this->mpid;
		$newapp['id'] = $newaid;
		$newapp['creater'] = $user->id;
		$newapp['creater_src'] = $user->src;
		$newapp['creater_name'] = $user->name;
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $user->name;
		$newapp['modify_at'] = $current;
		$newapp['title'] = $copied->title . '（副本）';
		$newapp['pic'] = $copied->pic;
		$newapp['summary'] = $copied->summary;
		$newapp['public_visible'] = $copied->public_visible;
		$newapp['open_lastroll'] = $copied->open_lastroll;
		$newapp['can_signin'] = $copied->can_signin;
		$newapp['can_lottery'] = $copied->can_lottery;
		$newapp['tags'] = $copied->tags;
		$newapp['enrolled_entry_page'] = $copied->enrolled_entry_page;
		$newapp['receiver_page'] = $copied->receiver_page;
		$newapp['entry_rule'] = json_encode($copied->entry_rule);
		if ($copied->mpid === $this->mpid) {
			$newapp['access_control'] = $copied->access_control;
			$newapp['authapis'] = $copied->authapis;
			$newapp['success_matter_type'] = $copied->success_matter_type;
			$newapp['success_matter_id'] = $copied->success_matter_id;
			$newapp['failure_matter_type'] = $copied->failure_matter_type;
			$newapp['failure_matter_id'] = $copied->failure_matter_id;
		}
		$this->model()->insert('xxt_enroll', $newapp, false);
		/**
		 * 复制自定义页面
		 */
		if ($copied->pages) {
			$modelPage = $this->model('app\enroll\page');
			foreach ($copied->pages as $ep) {
				$newPage = $modelPage->add($this->mpid, $newaid);
				$rst = $modelPage->update(
					'xxt_enroll_page',
					array('title' => $ep->title, 'name' => $ep->name),
					"aid='$newaid' and id=$newPage->id"
				);
				$data = array(
					'title' => $ep->title,
					'html' => $ep->html,
					'css' => $ep->css,
					'js' => $ep->js,
				);
				$modelCode->modify($newPage->code_id, $data);
			}
		}
		/*如果在同一个站点下复制，复制频道、接收人、ACL数据*/
		if ($copied->mpid === $this->mpid) {
			/**复制所属频道*/
			$sql = 'insert into xxt_channel_matter(channel_id,matter_id,matter_type,creater,creater_src,creater_name,create_at)';
			$sql .= " select channel_id,'$newaid','enroll','$user->id','A','$user->name',$current";
			$sql .= ' from xxt_channel_matter';
			$sql .= " where matter_id='$aid' and matter_type='enroll'";
			$this->model()->insert($sql, '', false);
			/**复制登记事件接收人*/
			$sql = 'insert into xxt_enroll_receiver(mpid,aid,identity,idsrc)';
			$sql .= " select '$this->mpid','$newaid',identity,idsrc";
			$sql .= ' from xxt_enroll_receiver';
			$sql .= " where aid='$aid'";
			$this->model()->insert($sql, '', false);
			/**复制ACL*/
			$sql = 'insert into xxt_matter_acl(mpid,matter_type,matter_id,identity,idsrc,label)';
			$sql .= " select '$this->mpid',matter_type,'$newaid',identity,idsrc,label";
			$sql .= ' from xxt_matter_acl';
			$sql .= " where matter_id='$aid'";
			$this->model()->insert($sql, '', false);
		}
		/**复制抽奖页内容*/
		if ($copied->can_lottery === 'Y' && $copied->lottery_page_id) {
			$lp = $modelCode->byId($copied->lottery_page_id);
			$code = $modelCode->create($user->id);
			$rst = $modelPage->update(
				'xxt_enroll',
				array('lottery_page_id' => $code->id),
				"id='$newaid'"
			);
			$data = array(
				'title' => $lp->title,
				'html' => $lp->html,
				'css' => $lp->css,
				'js' => $lp->js,
			);
			$modelCode->modify($code->id, $data);
			foreach ($lp->ext_js as $ejs) {
				$modelCode->insert('xxt_code_external', array('code_id' => $code->id, 'type' => 'J', 'url' => $ejs->url), false);
			}
			foreach ($lp->ext_css as $ecss) {
				$modelCode->insert('xxt_code_external', array('code_id' => $code->id, 'type' => 'C', 'url' => $ecss->url), false);
			}
		}
		$app = $modelApp->byId($newaid, array('cascaded' => 'N'));
		/*记录操作日志*/
		$app->type = 'enroll';
		$this->model('log')->matterOp($this->mpid, $user, $app, 'C');

		return new \ResponseData($app);
	}
	/**
	 * 更新活动的属性信息
	 *
	 * @param string $aid
	 *
	 */
	public function update_action($aid) {
		$model = $this->model();
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}
		/**
		 * 处理数据
		 */
		$nv = (array) $this->getPostJson();
		foreach ($nv as $n => $v) {
			if (in_array($n, array('entry_rule'))) {
				$nv[$n] = $model->escape(urldecode($v));
			} else if (in_array($n, array('data_schemas'))) {
				$nv[$n] = $model->toJson($v);
			}
		}
		$nv['modifier'] = $user->id;
		$nv['modifier_src'] = $user->src;
		$nv['modifier_name'] = $user->name;
		$nv['modify_at'] = time();

		$rst = $model->update('xxt_enroll', $nv, "id='$aid'");
		/*记录操作日志*/
		if ($rst) {
			$app = $this->model('matter\\' . 'enroll')->byId($aid, 'id,title,summary,pic');
			$app->type = 'enroll';
			$this->model('log')->matterOp($this->mpid, $user, $app, 'U');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 删除一个活动
	 *
	 * 如果没有报名数据，就将活动彻底删除
	 * 否则只是打标记
	 *
	 * @param string $aid
	 */
	public function remove_action($aid) {
		/*在删除数据前获得数据*/
		$app = $this->model('matter\\' . 'enroll')->byId($aid, 'id,title,summary,pic');
		/*删除和任务的关联*/
		$this->model('mission')->removeMatter($this->mpid, $aid, 'enroll');
		/*check*/
		$q = array(
			'count(*)',
			'xxt_enroll_record',
			"mpid='$this->mpid' and aid='$aid'",
		);
		if ((int) $this->model()->query_val_ss($q) > 0) {
			$rst = $this->model()->update(
				'xxt_enroll',
				array('state' => 0),
				"mpid='$this->mpid' and id='$aid'"
			);
		} else {
			$this->model()->delete(
				'xxt_enroll_lottery',
				"aid='$aid'"
			);
			$this->model()->delete(
				'xxt_enroll_lottery_round',
				"aid='$aid'"
			);
			$this->model()->delete(
				'xxt_enroll_receiver',
				"mpid='$this->mpid' and id='$aid'"
			);
			$this->model()->delete(
				'xxt_enroll_round',
				"mpid='$this->mpid' and id='$aid'"
			);
			$this->model()->delete(
				'xxt_code_page',
				"id in (select code_id from xxt_enroll_page where aid='$aid')"
			);
			$this->model()->delete(
				'xxt_enroll_page',
				"aid='$aid'"
			);
			$rst = $this->model()->delete(
				'xxt_enroll',
				"mpid='$this->mpid' and id='$aid'"
			);
		}
		/*记录操作日志*/
		$user = $this->accountUser();
		$app->type = 'enroll';
		$this->model('log')->matterOp($this->mpid, $user, $app, 'D');

		return new \ResponseData($rst);
	}
	/**
	 * 统计登记信息
	 *
	 * 只统计radio/checkbox类型的数据项
	 *
	 * return
	 * name => array(l=>label,c=>count)
	 *
	 */
	public function statGet_action($aid, $renewCache = 'N') {
		$result = $this->model('app\enroll')->getStat($aid);
		if ($renewCache === 'Y') {
			$model = $this->model();
			$model->delete('xxt_enroll_record_stat', "aid='$aid'");
			$current = time();
			foreach ($result as $id => $stat) {
				foreach ($stat['ops'] as $op) {
					$r = array(
						'aid' => $aid,
						'create_at' => $current,
						'id' => $id,
						'title' => $stat['title'],
						'v' => $op['v'],
						'l' => $op['l'],
						'c' => $op['c'],
					);
					$model->insert('xxt_enroll_record_stat', $r);
				}
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 活动签到成功回复
	 */
	public function setSuccessReply_action($aid) {
		$matter = $this->getPostJson();

		$ret = $this->model()->update(
			'xxt_enroll',
			array(
				'success_matter_type' => $matter->mt,
				'success_matter_id' => $matter->mid,
			),
			"mpid='$this->mpid' and id='$aid'"
		);

		return new \ResponseData($ret);
	}
	/**
	 * 活动签到失败回复
	 */
	public function setFailureReply_action($aid) {
		$matter = $this->getPostJson();

		$ret = $this->model()->update(
			'xxt_enroll',
			array(
				'failure_matter_type' => $matter->mt,
				'failure_matter_id' => $matter->mid,
			),
			"mpid='$this->mpid' and id='$aid'"
		);

		return new \ResponseData($ret);
	}
	/**
	 * 设置登记通知的接收人
	 */
	public function setEnrollReceiver_action($aid) {
		$receiver = $this->getPostJson();

		if (empty($receiver->identity)) {
			return new \ResponseError('没有指定用户的唯一标识');
		}

		if (isset($receiver->id)) {
			$u['identity'] = $receiver->identity;
			$rst = $this->model()->update(
				'xxt_enroll_receiver',
				$u,
				"id=$receiver->id"
			);
			return new \ResponseData($rst);
		} else {
			$i['mpid'] = $this->mpid;
			$i['aid'] = $aid;
			$i['identity'] = $receiver->identity;
			$i['idsrc'] = empty($receiver->idsrc) ? '' : $receiver->idsrc;
			$i['id'] = $this->model()->insert('xxt_enroll_receiver', $i, true);
			$i['label'] = empty($receiver->label) ? $receiver->identity : $receiver->label;

			return new \ResponseData($i);
		}
	}
	/**
	 * 删除登记通知的接收人
	 * $id
	 * $acl aclid
	 */
	public function delEnrollReceiver_action($acl) {
		$ret = $this->model()->delete(
			'xxt_enroll_receiver',
			"mpid='$this->mpid' and id=$acl"
		);

		return new \ResponseData($ret);
	}
}