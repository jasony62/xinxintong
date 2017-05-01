<?php
namespace pl\fe\site;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = [];

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/console');
		exit;
	}
	/**
	 * 创建团队
	 */
	public function create_action() {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$site['name'] = $user->name . '的团队';
		$site['creater'] = $user->id;
		$site['creater_name'] = $user->name;
		$site['create_at'] = time();

		$siteid = $this->model('site')->create($site);

		/* 添加到团队的访问控制列表 */
		$modelAdm = $this->model('site\admin');
		$admin = new \stdClass;
		$admin->uid = $user->id;
		$admin->ulabel = $user->name;
		$admin->urole = 'O';
		$rst = $modelAdm->add($user, $siteid, $admin);

		return new \ResponseData(['id' => $siteid, 'name' => $site['name']]);
	}
	/**
	 * 删除团队
	 * 只允许团队的创建者删除团队
	 * 不实际删除团队，只是打标记
	 */
	public function remove_action($site) {
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}

		$log = \TMS_APP::M('matter\log');
		/**
		 * 做标记
		 */
		$rst = $log->update(
			'xxt_site',
			['state' => 0],
			['id' => $site, 'creater' => $user->id]
		);
		if ($rst) {
			//工作台
			$log->update('xxt_log_matter_op', ['user_last_op' => 'N', 'operation' => 'Recycle'], ['siteid' => $site]);
			//项目
			$log->update('xxt_mission_acl', ['state' => 0], ['siteid' => $site]);
		}
		return new \ResponseData($rst);
	}
	/**
	 * 已删除的团队列表
	 *
	 */
	public function wasteList_action() {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->query_objs_ss(["*", "xxt_site", ['creater' => $user->id, 'state' => '0']], ['o' => 'create_at desc']);

		return new \ResponseData($rst);
	}
	/**
	 * 恢复
	 */
	public function recover_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$log = \TMS_APP::M('matter\log');
		//恢复团队
		$rst = $log->update(
			'xxt_site',
			['state' => 1],
			['id' => $site, 'creater' => $user->id]
		);
		//恢复素材
		if ($rst) {
			//工作台恢复
			$log->update('xxt_log_matter_op', ['user_last_op' => 'Y', 'operation' => 'Restore'], ['siteid' => $site]);
			//项目恢复
			$log->update('xxt_mission_acl', ['state' => 1], ['siteid' => $site]);
		}

		return new \ResponseData($rst);
	}
	/**
	 * 获取团队信息
	 */
	public function get_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSite = $this->model('site');
		if (false === ($site = $modelSite->byId($site))) {
			return new \ObjectNotFoundError();
		}
		$site->uid = $user->id;
		/* 检查当前用户的角色 */
		if ($user->id === $site->creater) {
			$site->yourRole = 'O';
		} else {
			if ($admin = $this->model('site\admin')->byUid($site->id, $user->id)) {
				$site->yourRole = $admin->urole;
			}
		}
		if (isset($site->yourRole)) {
			if (!empty($site->home_carousel)) {
				$site->home_carousel = json_decode($site->home_carousel);
			}

			return new \ResponseData($site);
		} else {
			$basic = new \stdClass;
			$basic->name = $site->name;
			$basic->creater_name = $site->creater_name;
			$basic->create_at = $site->create_at;

			return new \ResponseData($basic);
		}
	}
	/**
	 * 有权管理的团队
	 */
	public function list_action() {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$q = [
			'id,creater_name,create_at,name',
			'xxt_site s',
			"state=1 and (creater='{$user->id}' or exists(select 1 from xxt_site_admin sa where sa.siteid=s.id and uid='{$user->id}'))",
		];
		$q2 = ['o' => 'create_at desc'];

		$sites = $this->model()->query_objs_ss($q, $q2);

		return new \ResponseData($sites);
	}
	/**
	 * 允许公开访问的团队
	 */
	public function publicList_action($page = 1, $size = 8) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelHome = $this->model('site\home');
		$result = $modelHome->atHome(['page' => ['at' => $page, 'size' => $size]]);
		if ($result->total) {
			$modelSite = $this->model('site');
			$mySites = $modelSite->byUser($user->id);
			foreach ($result->sites as &$site) {
				foreach ($mySites as $mySite) {
					$rel = $modelSite->isFriend($site->siteid, $mySite->id);
					$site->_subscribed = !empty($rel->subscribe_at) ? 'Y' : 'N';
				}
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 当前用户已经关注过的团队
	 */
	public function friendList_action() {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSite = $this->model('site');
		$mySites = $modelSite->byUser($user->id);
		$mySiteIds = [];
		foreach ($mySites as $mySite) {
			$mySiteIds[] = $mySite->id;
		}
		$friendSites = [];
		$friends = $modelSite->byFriend($mySiteIds);
		foreach ($friends as $friend) {
			if ($friendSite = $modelSite->byId($friend->siteid, ['fields' => 'id,name,summary,heading_pic'])) {
				$friendSite->friend = $friend;
				$friendSites[] = $friendSite;
			}
		}

		return new \ResponseData($friendSites);
	}
	/**
	 * 指定站点的关注用户
	 *
	 * @param string $site site'id
	 * @param string $category 用户分类，client|team
	 *
	 */
	public function subscriberList_action($site, $category = 'client', $page = 1, $size = 30) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelSite = $this->model('site');
		if (false === ($oSite = $modelSite->byId($site))) {
			return new \ObjectNotFoundError();
		}
		if ($category === 'client') {
			$result = $modelSite->subscriber($oSite->id);
		} else if ($category === 'friend') {
			$result = $modelSite->friendBySite($oSite->id);
		} else {
			return new \ParameterError('category');
		}

		return new \ResponseData($result);
	}
	/**
	 * 已经关注过的团队发布的消息
	 */
	public function matterList_action($page = 1, $size = 10) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$result = new \stdClass;

		$modelSite = $this->model('site');
		$mySites = $modelSite->byUser($user->id);
		$mySiteIds = [];
		foreach ($mySites as $mySite) {
			$mySiteIds[] = $mySite->id;
		}
		$result = $modelSite->matterByFriend($mySiteIds, ['page' => ['at' => $page, 'size' => $size]]);

		return new \ResponseData($result);
	}
	/**
	 * 获得团队绑定的第三方公众号
	 */
	public function snsList_action($site) {
		$sns = array();

		$modelWx = $this->model('sns\wx');
		$wxOptions = ['fields' => 'joined,can_qrcode'];
		if (($wx = $modelWx->bySite($site, $wxOptions)) && $wx->joined === 'Y') {
			$sns['wx'] = $wx;
		} else if (($wx = $modelWx->bySite('platform', $wxOptions)) && $wx->joined === 'Y') {
			$sns['wx'] = $wx;
		}

		$yxOptions = ['fields' => 'joined,can_qrcode'];
		if ($yx = $this->model('sns\yx')->bySite($site, $yxOptions)) {
			if ($yx->joined === 'Y') {
				$sns['yx'] = $yx;
			}
		}

		if ($qy = $this->model('sns\qy')->bySite($site, ['fields' => 'joined'])) {
			if ($qy->joined === 'Y') {
				$sns['qy'] = $qy;
			}
		}

		if (empty($sns)) {
			return new \ResponseData(false);
		} else {
			return new \ResponseData($sns);
		}
	}
	/**
	 *
	 */
	public function update_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$nv = $this->getPostJson();
		foreach ($nv as $n => $v) {
			if ($n === 'home_carousel') {
				$nv->{$n} = json_encode($v);
			}
		}
		$rst = $this->model()->update(
			'xxt_site',
			$nv,
			"id='$site'"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 *
	 * @param string $resType
	 * @param int 标签的分类
	 */
	public function applyToHome_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelHome = $this->model('site\home');
		$site = $this->model('site')->byId($site);
		if ($site === false) {
			return new \ObjectNotFoundError();
		}

		$reply = $modelHome->putSite($site, $user);

		return new \ResponseData($reply);
	}
	/**
	 * 创建团队首页页面
	 */
	public function pageCreate_action($site, $page, $template = 'basic') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$site = $this->model('site')->byId($site);

		$data = $this->_makePage($site, $page, $template);

		$code = $this->model('code\page')->create($site->id, $user->id, $data);

		$rst = $this->model()->update(
			'xxt_site',
			array(
				$page . '_page_id' => $code->id,
				$page . '_page_name' => $code->name,
			),
			"id='{$site->id}'"
		);

		return new \ResponseData($code);
	}
	/**
	 * 根据模版重置引导关注页面
	 *
	 * @param int $codeId
	 */
	public function pageReset_action($site, $page, $template = 'basic') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$site = $this->model('site')->byId($site);

		$data = $this->_makePage($site, $page, $template);

		$rst = $this->model('code\page')->modify($site->{$page . '_page_id'}, $data);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	private function &_makePage($site, $page, $template) {
		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/site/page/' . $page;
		$data = array(
			'html' => file_get_contents($templateDir . '/' . $template . '.html'),
			'css' => file_get_contents($templateDir . '/' . $template . '.css'),
			'js' => file_get_contents($templateDir . '/' . $template . '.js'),
		);
		/*填充页面*/
		//$data['html'] = $this->_htmlBySite($site, $data['html']);

		return $data;
	}
	/**
	 *
	 */
	private function &_htmlBySite(&$site, $template) {
		if (defined('SAE_TMP_PATH')) {
			$tmpfname = tempnam(SAE_TMP_PATH, "template");
		} else {
			$tmpfname = tempnam(sys_get_temp_dir(), "template");
		}
		$handle = fopen($tmpfname, "w");
		fwrite($handle, $template);
		fclose($handle);
		$s = new \Savant3(array('template' => $tmpfname, 'exceptions' => true));
		$s->assign('site', $site);
		$html = $s->getOutput();
		unlink($tmpfname);

		return $html;
	}
	/**
	 *
	 */
	public function invite_action($code) {
		\TPL::output('/pl/fe/site/invite');
		exit;
	}
}