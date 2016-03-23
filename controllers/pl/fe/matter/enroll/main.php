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
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
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
	public function page_action() {
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
	public function record_action() {
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
		$q = array('a.*', 'xxt_enroll a');
		$q[2] = "siteid='$site' and state=1";
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
	public function create_action($site, $scenario = null, $template = null) {
		$user = $this->accountUser();
		$current = time();
		$site = $this->model('site')->byId($site, 'heading_pic');

		$newapp = array();
		$id = uniqid();
		/*pages*/
		if (!empty($scenario) && !empty($template)) {
			$customConfig = $this->getPostJson();
			$config = $this->_addPageByTemplate($id, $scenario, $template, $customConfig);
			$entryRule = $config->entryRule;
			if (isset($config->multi_rounds) && $config->multi_rounds === 'Y') {
				$this->_createRound($id);
				$newapp['multi_rounds'] = 'Y';
			}
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
		$newapp['summary'] = '';
		$this->model()->insert('xxt_enroll', $newapp, false);
		$app = $this->model('app\enroll')->byId($id);
		/*记录操作日志*/
		$app->type = 'enroll';
		$this->model('log')->matterOp($site->id, $user, $app, 'C');

		return new \ResponseData($app);
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
}