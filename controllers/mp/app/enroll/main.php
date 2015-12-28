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
		$uid = \TMS_CLIENT::get_client_uid();
		$q = array('a.*', 'xxt_enroll a');
		if ($src === 'p') {
			$pmpid = $this->getParentMpid();
			$q[2] = "mpid='$pmpid' and state=1";
		} else {
			$q[2] = "mpid='$this->mpid' and state=1";
		}
		$q2['o'] = 'a.create_at desc';
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
		$account = \TMS_CLIENT::account();
		if ($account === false) {
			return new \ResponseTimeout();
		}
		$current = time();
		$uid = \TMS_CLIENT::get_client_uid();
		$mpa = $this->model('mp\mpaccount')->getSetting($this->mpid, 'heading_pic');

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
		$newapp['creater'] = $uid;
		$newapp['creater_src'] = 'A';
		$newapp['creater_name'] = $account->nickname;
		$newapp['create_at'] = time();
		$newapp['entry_rule'] = json_encode($entryRule);
		$this->model()->insert('xxt_enroll', $newapp, false);

		$app = $this->model('app\enroll')->byId($aid);

		return new \ResponseData($app);
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
		$modelCode = $this->model('code/page');
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
	 */
	public function copy_action($aid = null, $shopid = null) {
		$account = \TMS_CLIENT::account();
		if ($account === false) {
			return new \ResponseError('长时间未操作，请重新登陆！');
		}

		$uid = \TMS_CLIENT::get_client_uid();
		$uname = $account->nickname;
		$current = time();
		$enrollModel = $this->model('app\enroll');
		$codeModel = $this->model('code/page');

		if (!empty($aid)) {
			$copied = $enrollModel->byId($aid);
		} else if (!empty($shopid)) {
			$shopItem = $this->model('shop\shelf')->byId($shopid);
			$aid = $shopItem->matter_id;
			$copied = $enrollModel->byId($aid);
			$copied->title = $shopItem->title;
			$copied->summary = $shopItem->summary;
			$copied->pic = $shopItem->pic;
		} else {
			return new \ResponseError('没有指定要复制登记活动id');
		}
		/**
		 * 获得的基本信息
		 */
		$newaid = uniqid();
		$newact['mpid'] = $this->mpid;
		$newact['id'] = $newaid;
		$newact['creater'] = $uid;
		$newact['creater_src'] = 'A';
		$newact['creater_name'] = $uname;
		$newact['create_at'] = $current;
		$newact['title'] = $copied->title . '（副本）';
		$newact['pic'] = $copied->pic;
		$newact['summary'] = $copied->summary;
		$newact['public_visible'] = $copied->public_visible;
		$newact['open_lastroll'] = $copied->open_lastroll;
		$newact['can_signin'] = $copied->can_signin;
		$newact['can_lottery'] = $copied->can_lottery;
		$newact['tags'] = $copied->tags;
		$newact['enrolled_entry_page'] = $copied->enrolled_entry_page;
		$newact['receiver_page'] = $copied->receiver_page;
		$newact['entry_rule'] = json_encode($copied->entry_rule);
		if ($copied->mpid === $this->mpid) {
			$newact['access_control'] = $copied->access_control;
			$newact['authapis'] = $copied->authapis;
			$newact['success_matter_type'] = $copied->success_matter_type;
			$newact['success_matter_id'] = $copied->success_matter_id;
			$newact['failure_matter_type'] = $copied->failure_matter_type;
			$newact['failure_matter_id'] = $copied->failure_matter_id;
		}
		$this->model()->insert('xxt_enroll', $newact, false);
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
				$codeModel->modify($newPage->code_id, $data);
			}
		}
		if ($copied->mpid === $this->mpid) {
			/**
			 * 复制所属频道
			 */
			$sql = 'insert into xxt_channel_matter(channel_id,matter_id,matter_type,creater,creater_src,creater_name,create_at)';
			$sql .= " select channel_id,'$newaid','enroll','$uid','A','$uname',$current";
			$sql .= ' from xxt_channel_matter';
			$sql .= " where matter_id='$aid' and matter_type='enroll'";
			$this->model()->insert($sql, '', false);
			/**
			 * 复制登记事件接收人
			 */
			$sql = 'insert into xxt_enroll_receiver(mpid,aid,identity,idsrc)';
			$sql .= " select '$this->mpid','$newaid',identity,idsrc";
			$sql .= ' from xxt_enroll_receiver';
			$sql .= " where aid='$aid'";
			$this->model()->insert($sql, '', false);
			/**
			 * 复制ACL
			 */
			$sql = 'insert into xxt_matter_acl(mpid,matter_type,matter_id,identity,idsrc,label)';
			$sql .= " select '$this->mpid',matter_type,'$newaid',identity,idsrc,label";
			$sql .= ' from xxt_matter_acl';
			$sql .= " where matter_id='$aid'";
			$this->model()->insert($sql, '', false);
		}

		$act = $enrollModel->byId($newaid);

		return new \ResponseData($act);
	}
	/**
	 * 更新活动的属性信息
	 *
	 * @param string $aid
	 *
	 */
	public function update_action($aid) {
		$nv = (array) $this->getPostJson();
		foreach ($nv as $n => $v) {
			if (in_array($n, array('entry_rule'))) {
				$nv[$n] = $this->model()->escape(urldecode($v));
			}
		}

		$rst = $this->model()->update('xxt_enroll', $nv, "id='$aid'");

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
	public function statGet_action($aid) {
		$result = $this->model('app\enroll')->getStat($aid);

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