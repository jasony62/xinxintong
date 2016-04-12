<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动主控制器
 */
class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'enroll';
	}
	/**
	 * 返回视图
	 */
	public function index_action($site, $id) {
		$app = $this->model('matter\enroll')->byId($id);
		if ($app->state === '2') {
			$this->redirect('/rest/pl/fe/matter/enroll/running?site=' . $site . '&id=' . $id);
		} else {
			\TPL::output('/pl/fe/matter/enroll/frame');
			exit;
		}
	}
	/**
	 * 返回视图
	 */
	public function setting_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function running_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function event_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function preview_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function stat_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function coin_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回一个登记活动
	 */
	public function get_action($site, $id) {
		$uid = \TMS_CLIENT::get_client_uid();
		$a = $this->model('app\enroll')->byId($id);
		$a->uid = $uid;
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
		$a->channels = $this->model('matter\channel')->byMatter($id, 'enroll');
		/* acl */
		$a->acl = $this->model('acl')->byMatter($site, 'enroll', $id);
		/* 登记通知接收人 */
		$a->receiver = $this->model('acl')->enrollReceiver($site, $id);
		/* 获得的轮次 */
		if ($rounds = $this->model('app\enroll\round')->byApp($site, $id)) {
			!empty($rounds) && $a->rounds = $rounds;
		}

		return new \ResponseData($a);
	}
	/**
	 * 返回登记活动列表
	 *
	 * $src 是否来源于父账号，=p
	 */
	public function list_action($site, $page = 1, $size = 30) {
		$model = $this->model();
		$q = array(
			'a.*',
			'xxt_enroll a',
			"siteid='$site' and state<>0",
		);
		$q2['o'] = 'a.modify_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($a = $model->query_objs_ss($q, $q2)) {
			$result['apps'] = $a;
			$q[0] = 'count(*)';
			$total = (int) $model->query_val_ss($q);
			$result['total'] = $total;
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
	public function create_action($site, $scenario = null, $template = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$current = time();
		$site = $this->model('site')->byId($site, array('fields' => 'id,heading_pic'));

		$newapp = array();
		$id = uniqid();
		/*pages*/
		if (!empty($scenario) && !empty($template)) {
			$customConfig = $this->getPostJson();
			$config = $this->_addPageByTemplate($site->id, $id, $scenario, $template, $customConfig);
			$entryRule = $config->entryRule;
			if (isset($config->enrolled_entry_page)) {
				$newapp['enrolled_entry_page'] = $config->enrolled_entry_page;
			}
		} else {
			$entryRule = $this->_addBlankPage($site->id, $id);
		}
		if (empty($entryRule)) {
			return new \ResponseError('没有获得页面进入规则');
		}
		/*create app*/
		$newapp['siteid'] = $site->id;
		$newapp['id'] = $id;
		$newapp['title'] = '新登记活动';
		$newapp['pic'] = $site->heading_pic;
		$newapp['creater'] = $user->id;
		$newapp['creater_src'] = $user->src;
		$newapp['creater_name'] = $user->name;
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $user->name;
		$newapp['modify_at'] = $current;
		$newapp['entry_rule'] = json_encode($entryRule);
		isset($config) && $newapp['data_schemas'] = \TMS_MODEL::toJson($config->schema);
		$newapp['summary'] = '';
		$this->model()->insert('xxt_enroll', $newapp, false);
		$app = $this->model('matter\enroll')->byId($id);
		/*记录操作日志*/
		$app->type = 'enroll';
		$this->model('log')->matterOp($site->id, $user, $app, 'C');

		return new \ResponseData($app);
	}
	/**
	 *
	 * @param int $mission mission'is
	 */
	public function createByMission_action($site, $mission, $scenario = null, $template = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('mission');
		$mission = $modelMis->byId($mission);
		$current = time();

		/*create app*/
		$aid = uniqid();
		/*pages*/
		if (!empty($scenario) && !empty($template)) {
			$customConfig = $this->getPostJson();
			$config = $this->_addPageByTemplate($site, $aid, $scenario, $template, $customConfig);
			$entryRule = $config->entryRule;
			if (isset($config->enrolled_entry_page)) {
				$newapp['enrolled_entry_page'] = $config->enrolled_entry_page;
			}
		} else {
			$entryRule = $this->_addBlankPage($site, $aid);
		}
		$newapp['siteid'] = $site;
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
		isset($config) && $newapp['data_schemas'] = \TMS_MODEL::toJson($config->schema);
		$this->model()->insert('xxt_enroll', $newapp, false);

		$matter = $this->model('matter\enroll')->byId($aid);
		/*记录操作日志*/
		$matter->type = 'enroll';
		$this->model('log')->matterOp($site, $user, $matter, 'C');
		/*记录和任务的关系*/
		$modelMis->addMatter($user, $site, $mission->id, $matter);

		return new \ResponseData($matter);
	}
	/**
	 * 更新活动的属性信息
	 *
	 * @param string $aid
	 *
	 */
	public function update_action($site, $app) {
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

		$rst = $model->update('xxt_enroll', $nv, "id='$app'");
		/*记录操作日志*/
		if ($rst) {
			$app = $this->model('matter\\' . 'enroll')->byId($app, 'id,title,summary,pic');
			$app->type = 'enroll';
			$this->model('log')->matterOp($site, $user, $app, 'U');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 添加空页面
	 */
	private function _addBlankPage($siteId, $aid) {
		$current = time();
		$modelPage = $this->model('app\enroll\page');
		/* form page */
		$page = array(
			'title' => '登记信息页',
			'type' => 'I',
			'name' => 'z' . $current,
		);
		$page = $modelPage->add($siteId, $aid, $page);
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
		$modelPage->add($siteId, $aid, $page);

		return $entryRule;
	}
	/**
	 * 根据模板生成页面
	 *
	 * @param string $aid
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 */
	private function &_addPageByTemplate($siteId, $aid, $scenario, $template, $customConfig) {
		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/matter/enroll/scenario/' . $scenario . '/templates/' . $template;
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
			$ap = $modelPage->add($siteId, $aid, (array) $page);
			$data = array(
				'html' => file_get_contents($templateDir . '/' . $page->name . '.html'),
				'css' => file_get_contents($templateDir . '/' . $page->name . '.css'),
				'js' => file_get_contents($templateDir . '/' . $page->name . '.js'),
			);
			/*填充页面*/
			$matched = array();
			$pattern = '/<!-- begin: generate by schema -->.*<!-- end: generate by schema -->/s';
			if (preg_match($pattern, $data['html'], $matched)) {
				if (isset($customConfig->simpleSchema)) {
					$config->schema = $modelPage->schemaByText($customConfig->simpleSchema);
				}
				$html = $modelPage->htmlBySchema($config->schema, $matched[0]);
				$data['html'] = preg_replace($pattern, $html, $data['html']);
				$rst = $modelPage->update(
					'xxt_enroll_page',
					array('data_schemas' => \TMS_MODEL::toJson($config->schema)),
					"aid='$aid' and id={$ap->id}"
				);
			}
			$modelCode->modify($ap->code_id, $data);
		}

		return $config;
	}
}