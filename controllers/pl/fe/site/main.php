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
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'][] = 'index';

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/frame');
		exit;
	}
	/**
	 * 新建团队
	 */
	public function plan_action() {
		\TPL::output('/pl/fe/site/plan');
		exit;
	}
	/**
	 * 创建团队
	 */
	public function create_action() {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSite = $this->model('site');
		$oProto = $this->getPostJson();

		$siteName = isset($oProto->name) ? $oProto->name : $oUser->name . '的团队';
		$siteSummary = isset($oProto->summary) ? $oProto->summary : '';

		$oNewSite = new \stdClass;
		$oNewSite->name = $modelSite->escape($siteName);
		$oNewSite->summary = $modelSite->escape($siteSummary);
		$oNewSite->id = $modelSite->create($oUser, $oNewSite);

		/* 记录操作日志 */
		$oMatter = new \stdClass;
		$oMatter->id = $oNewSite->id;
		$oMatter->type = 'site';
		$oMatter->title = $siteName;
		$this->model('matter\log')->matterOp($oNewSite->id, $oUser, $oMatter, 'C');

		/* 添加到团队的访问控制列表 */
		$modelAdm = $this->model('site\admin');
		$oAdmin = new \stdClass;
		$oAdmin->uid = $oUser->id;
		$oAdmin->ulabel = $modelAdm->escape($oUser->name);
		$oAdmin->urole = 'O';
		$rst = $modelAdm->add($oUser, $oNewSite->id, $oAdmin);

		return new \ResponseData($oNewSite);
	}
	/**
	 * 删除团队
	 * 只允许团队的创建者删除团队
	 * 不实际删除团队，只是打标记
	 */
	public function remove_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$log = $this->model('matter\log');
		/**
		 * 做标记
		 */
		$rst = $log->update(
			'xxt_site',
			['state' => 0],
			['id' => $site, 'creater' => $oUser->id]
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$oFilter = $this->getPostJson();
		$q = [
			"*",
			"xxt_site",
			"creater = '{$oUser->id}' and state = 0",
		];

		if (!empty($oFilter->byTitle)) {
			$q[2] .= " and name like '%" . $model->escape($oFilter->byTitle) . "%'";
		}

		$q2 = ['o' => 'create_at desc'];
		$rst = $model->query_objs_ss($q, $q2);

		return new \ResponseData($rst);
	}
	/**
	 * 恢复
	 */
	public function recover_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$log = \TMS_APP::M('matter\log');
		//恢复团队
		$rst = $log->update(
			'xxt_site',
			['state' => 1],
			['id' => $site, 'creater' => $oUser->id]
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSite = $this->model('site');
		if (false === ($oSite = $modelSite->byId($site))) {
			return new \ObjectNotFoundError();
		}
		/* 团队首页地址 */
		$oSite->homeUrl = 'http://' . APP_HTTP_HOST . '/rest/site/home?site=' . $oSite->id;

		/* 检查当前用户的角色 */
		$oSite->uid = $oUser->id;
		if ($oUser->id === $oSite->creater) {
			$oSite->yourRole = 'O';
		} else {
			if ($admin = $this->model('site\admin')->byUid($oSite->id, $oUser->id)) {
				$oSite->yourRole = $admin->urole;
			}
		}
		if (isset($oSite->yourRole)) {
			if (!empty($oSite->home_carousel)) {
				$oSite->home_carousel = json_decode($oSite->home_carousel);
			}
			/* 团队群二维码 */
			if (!empty($oSite->home_qrcode_group)) {
				$oSite->home_qrcode_group = json_decode($oSite->home_qrcode_group);
			}

			return new \ResponseData($oSite);
		} else {
			$basic = new \stdClass;
			$basic->name = $oSite->name;
			$basic->creater_name = $oSite->creater_name;
			$basic->create_at = $oSite->create_at;

			return new \ResponseData($basic);
		}
	}
	/**
	 * 有权管理的团队
	 */
	public function list_action() {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$filter = $this->getPostJson();
		$modelSite = $this->model('site');

		$options = array();
		if (!empty($filter->bySite)) {
			$options['bySite'] = $modelSite->escape($filter->bySite);
		}
		if (!empty($filter->byTitle)) {
			$options['byTitle'] = $modelSite->escape($filter->byTitle);
		}

		$mySites = $modelSite->byUser($oUser->id, $options);

		return new \ResponseData($mySites);
	}
	/**
	 * 允许公开访问的团队
	 */
	public function publicList_action($page = 1, $size = 8) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelHome = $this->model('site\home');
		$result = $modelHome->atHome(['page' => ['at' => $page, 'size' => $size]]);
		if ($result->total) {
			$modelSite = $this->model('site');
			$mySites = $modelSite->byUser($oUser->id);
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSite = $this->model('site');
		$mySites = $modelSite->byUser($oUser->id);
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelSite = $this->model('site');
		if (false === ($oSite = $modelSite->byId($site))) {
			return new \ObjectNotFoundError();
		}

		$filter = $this->getPostJson();
		$options = [];
		if (!empty($filter->nickname)) {
			$options['byNickname'] = $filter->nickname;
		}

		if ($category === 'client') {
			$result = $modelSite->subscriber($oSite->id, $page, $size, $options);
		} else if ($category === 'friend') {
			$result = $modelSite->friendBySite($oSite->id, $page, $size);
		} else {
			return new \ParameterError('category');
		}

		return new \ResponseData($result);
	}
	/**
	 * 已经关注过的团队发布的消息
	 */
	public function matterList_action($site = null, $page = 1, $size = 10) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$result = new \stdClass;

		$modelSite = $this->model('site');
		$mySiteIds = [];
		if (empty($site)) {
			$mySites = $modelSite->byUser($oUser->id);
			foreach ($mySites as $mySite) {
				$mySiteIds[] = $mySite->id;
			}
		} else {
			$mySiteIds[] = $site;
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
		$wxOptions = ['fields' => 'title,joined,can_qrcode '];
		if (($wx = $modelWx->bySite($site, $wxOptions)) && $wx->joined === 'Y') {
			$sns['wx'] = $wx;
		} else if (($wx = $modelWx->bySite('platform', $wxOptions)) && $wx->joined === 'Y') {
			$sns['wx'] = $wx;
		}

		$yxOptions = ['fields' => 'title,joined,can_qrcode'];
		if ($yx = $this->model('sns\yx')->bySite($site, $yxOptions)) {
			if ($yx->joined === 'Y') {
				$sns['yx'] = $yx;
			}
		}

		if ($qy = $this->model('sns\qy')->bySite($site, ['fields' => 'title,joined'])) {
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSite = $this->model('site');
		$modelSite->setOnlyWriteDbConn(true);

		$nv = $this->getPostJson();
		foreach ($nv as $n => $v) {
			if ($n === 'home_carousel') {
				$nv->{$n} = json_encode($v);
			} else if ($n === 'home_qrcode_group') {
				$nv->{$n} = $modelSite->escape($modelSite->toJson($v));
			}
		}
		$rst = $modelSite->update(
			'xxt_site',
			$nv,
			"id='$site'"
		);
		/*记录操作日志*/
		$matter = $modelSite->byId($site, ['fields' => 'id,name as title']);
		$matter->type = 'site';
		$this->model('matter\log')->matterOp($site, $oUser, $matter, 'U');

		return new \ResponseData($rst);
	}
	/**
	 *
	 *
	 * @param string $resType
	 * @param int 标签的分类
	 */
	public function applyToHome_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelHome = $this->model('site\home');
		$site = $this->model('site')->byId($site);
		if ($site === false) {
			return new \ObjectNotFoundError();
		}

		$reply = $modelHome->putSite($site, $oUser);

		return new \ResponseData($reply);
	}
	/**
	 * 创建团队首页页面
	 */
	public function pageCreate_action($site, $page, $template = 'basic') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$site = $this->model('site')->byId($site);

		$data = $this->_makePage($site, $page, $template);

		$code = $this->model('code\page')->create($site->id, $oUser->id, $data);

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
		if (false === ($oUser = $this->accountUser())) {
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
		$templateDir = file_exists(TMS_APP_TEMPLATE . '/pl/fe/site/page/' . $page) ? TMS_APP_TEMPLATE : TMS_APP_TEMPLATE_DEFAULT;
		$templateDir .= '/pl/fe/site/page/' . $page;

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