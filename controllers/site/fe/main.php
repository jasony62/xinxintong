<?php
namespace site\fe;

require_once dirname(__FILE__) . '/base.php';
/**
 * 站点首页
 */
class main extends base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		$site = $this->model('site')->byId($this->siteId);
		\TPL::assign('title', $site->name);
		\TPL::output('/site/fe/main');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action() {
		$site = $this->model('site')->byId($this->siteId);
		$page = $this->model('code\page')->byId($site->home_page_id);

		$param = array(
			'page' => $page,
		);

		return new \ResponseData($param);
	}
}