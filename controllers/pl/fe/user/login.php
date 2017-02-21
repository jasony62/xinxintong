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
		\TPL::output('/pl/fe/user/login');
		exit;
	}
	/**
	 * 只是进行用户身份的检查，并不解决页面跳转
	 */
	public function do_action() {
		$data = $this->getPostJson();

		if (empty($data->email)) {
			return new \ResponseError('邮箱不允许为空');
		}
		if (empty($data->password)) {
			return new \ResponseError('口令不允许为空');
		}

		$modelAct = $this->model('account');
		/* check */
		$result = $modelAct->validate($data->email, $data->password);
		if ($result->err_code != 0) {
			return $result;
		}
		$act = $result->data;

		return new \ResponseData($act->uid);
	}
}