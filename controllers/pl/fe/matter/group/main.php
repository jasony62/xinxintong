<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 分组活动主控制器
 */
class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'group';
	}
	/**
	 * 返回视图
	 */
	public function index_action($site, $id) {
		$app = $this->model('matter\group')->byId($id);
		if ($app->state === '2') {
			$this->redirect('/rest/pl/fe/matter/group/running?site=' . $site . '&id=' . $id);
		} else {
			\TPL::output('/pl/fe/matter/group/frame');
			exit;
		}
	}
	/**
	 * 返回视图
	 */
	public function setting_action() {
		\TPL::output('/pl/fe/matter/group/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function running_action() {
		\TPL::output('/pl/fe/matter/group/frame');
		exit;
	}
	/**
	 * 返回一个分组活动
	 */
	public function get_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$app = $this->model('matter\\group')->byId($app);
		/*所属项目*/
		$app->mission = $this->model('matter\\mission')->byMatter($site, $app->id, 'group');

		return new \ResponseData($app);
	}
	/**
	 * 返回分组活动列表
	 */
	public function list_action($site, $page = 1, $size = 30) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();
		$q = array('g.*', 'xxt_group g');
		$q[2] = "siteid='$site' and state=1";
		$q2['o'] = 'g.modify_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($apps = $model->query_objs_ss($q, $q2)) {
			$result['apps'] = $apps;
			$q[0] = 'count(*)';
			$total = (int) $model->query_val_ss($q);
			$result['total'] = $total;
			return new \ResponseData($result);
		}
		return new \ResponseData(false);
	}
	/**
	 * 创建空的分组活动
	 *
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 */
	public function create_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$current = time();
		$site = $this->model('site')->byId($site, array('fields' => 'id,heading_pic'));

		$newapp = array();
		$id = uniqid();
		/*create app*/
		$newapp['siteid'] = $site->id;
		$newapp['id'] = $id;
		$newapp['title'] = '新分组活动';
		$newapp['pic'] = $site->heading_pic;
		$newapp['creater'] = $user->id;
		$newapp['creater_src'] = $user->src;
		$newapp['creater_name'] = $user->name;
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $user->name;
		$newapp['modify_at'] = $current;
		//$newapp['data_schemas'] = \TMS_MODEL::toJson($config->schema);
		$newapp['summary'] = '';
		$this->model()->insert('xxt_group', $newapp, false);
		$app = $this->model('matter\group')->byId($id);
		/*记录操作日志*/
		$app->type = 'group';
		$this->model('log')->matterOp($site->id, $user, $app, 'C');

		return new \ResponseData($app);
	}
	/**
	 *
	 * @param int $id mission'is
	 */
	public function createByMission_action($site, $mission) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('mission');
		$mission = $modelMis->byId($mission);
		$current = time();

		/*create app*/
		$aid = uniqid();
		//$entryRule = $this->_addBlankPage($site, $aid);
		$newapp['siteid'] = $site;
		$newapp['id'] = $aid;
		$newapp['title'] = '新分组活动';
		$newapp['pic'] = $mission->pic;
		$newapp['creater'] = $user->id;
		$newapp['creater_src'] = $user->src;
		$newapp['creater_name'] = $user->name;
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $user->name;
		$newapp['modify_at'] = $current;
		//$newapp['data_schemas'] = \TMS_MODEL::toJson($config->schema);
		$newapp['summary'] = $mission->summary;
		$this->model()->insert('xxt_group', $newapp, false);

		$matter = $this->model('matter\group')->byId($aid);
		/*记录操作日志*/
		$matter->type = 'group';
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
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}
		$model = $this->model();
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

		$rst = $model->update('xxt_group', $nv, "id='$app'");
		/*记录操作日志*/
		if ($rst) {
			$app = $this->model('matter\group')->byId($app, 'id,title,summary,pic');
			$app->type = 'group';
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
		$modelCode = $this->model('code\page');
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
	/**
	 * 从登记活动导入数据
	 */
	public function importByApp_action($site, $app) {
		$modelGrp = $this->model('matter\group');
		$modelPlayer = $this->model('matter\group\player');
		$posted = $this->getPostJson();
		$filter = $posted->filter;
		$source = $posted->source;

		if (!empty($source)) {
			$sourceApp = $this->model('matter\enroll')->byId($source);
			/* 导入活动定义 */
			$modelGrp->update(
				'xxt_group',
				array('data_schemas' => $sourceApp->data_schemas, 'tags' => $sourceApp->tags),
				"id='$app'"
			);
			/* 清空已有数据 */
			$modelPlayer->clean($app, true);
			/* 获取数据 */
			$modelRec = $this->model('matter\enroll\record');
			$eks = null;
			if (empty((array) $filter)) {
				$q = array(
					'enroll_key',
					'xxt_enroll_record',
					"aid='$source' and state=1",
				);
				$eks = $modelRec->query_vals_ss($q);
			} else {
				/* 根据过滤条件选择数据 */
				$q = array(
					'distinct enroll_key',
					'xxt_enroll_record_data',
					"aid='$source' and state=1",
				);
				foreach ($filter as $k => $v) {
					$w = "(name='$k' and ";
					$w .= "concat(',',value,',') like '%,$v,%'";
					$w .= ')';
					$q2 = $q;
					$q2[2] .= ' and ' . $w;
					$eks2 = $modelRec->query_vals_ss($q2);
					$eks = ($eks === null) ? $eks2 : array_intersect($eks, $eks2);
				}
			}
			/* 导入数据 */
			if (!empty($eks)) {
				$objGrp = $modelGrp->byId($app, array('cascaded' => 'N'));
				$options = array('cascaded' => 'Y');
				foreach ($eks as $ek) {
					$record = $modelRec->byId($ek, $options);
					$user = new \stdClass;
					$user->uid = $record->userid;
					$user->nickname = $record->nickname;
					$newek = $modelPlayer->enroll($site, $objGrp, $user);
					$modelPlayer->setData($user, $site, $objGrp, $newek, $record->data);
				}
			}
		}

		return new \ResponseData('ok');
	}
}