<?php
namespace pl\fe\code;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 自定义代码管理
 */
class main extends \pl\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 检索页面
	 */
	public function index_action($site, $name) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		if (!empty($name)) {
			$page = $this->model('code\page')->lastByName($site, $name);
			\TPL::assign('page', $page);
			$this->view_action('/code/page');
		} else {
			$this->view_action('/code/main');
		}
	}
	/**
	 * 创建新页面
	 *
	 * @param string $site
	 */
	public function create_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$page = $this->model('code\page')->create($site, $user->id);

		return new \ResponseData($page);
	}
	/**
	 * 删除页面
	 */
	public function remove_action($id) {
		$rst = $this->model('code\page')->remove($id);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function update_action($id) {
		$nv = $this->getPostJson();

		foreach ($nv as $n => $v) {
			if (in_array($n, array('html', 'css', 'js'))) {
				$v = urldecode($v);
				$nv->$n = $this->model()->escape($v);
			}
		}

		$rst = $this->model()->update(
			'xxt_code_page',
			(array) $nv,
			"id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 给页面添加资源
	 *
	 * $id 页面ID
	 */
	public function addExternal_action($id) {
		$res = $this->getPostJson();

		$res->code_id = $id;
		$res->id = $this->model()->insert('xxt_code_external', (array) $res, true);

		return new \ResponseData($res);
	}
	/**
	 * $id 外部资源ID
	 */
	public function delExternal_action($id) {
		$rst = $this->model()->delete('xxt_code_external', "id=$id");

		return new \ResponseData($rst);
	}
}