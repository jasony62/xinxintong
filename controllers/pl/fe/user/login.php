<?php
namespace pl\fe\user;
/**
 * 平台管理端用户登录
 */
class login extends \TMS_CONTROLLER {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array();
		$rule_action['actions'][] = 'index';
		$rule_action['actions'][] = 'do';

		return $rule_action;
	}
	/**
	 * 进入平台管理页面用户身份验证页面
	 */
	public function index_action() {
		$this->view_action('/pl/fe/user/login');
	}
	/**
	 * 只是进行用户身份的检查，并不解决页面跳转
	 */
	public function do_action() {
		$data = $this->getPostJson();

		$modelUsr = $this->model('mp\user');
		/*check*/
		$result = $modelUsr->validate($data->email, $data->password);
		if ($result[0] === false) {
			return new \ResponseError($result[1]);
		}
		$act = $result[1];

		return new \ResponseData($act->uid);
	}

}