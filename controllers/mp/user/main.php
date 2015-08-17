<?php
namespace mp\user;

require_once dirname(dirname(__FILE__)) . '/mp_controller.php';

class main extends \mp\mp_controller {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';
		$rule_action['actions'][] = 'picker';

		return $rule_action;
	}
	/**
	 * 获得一个用户的完整信息
	 *
	 * $fid
	 * $openid
	 */
	public function index_action($fid = null, $openid = null) {
		$this->view_action('/mp/user/user');
	}
	/**
	 * 获得一个用户的完整信息
	 *
	 * $fid
	 * $openid
	 */
	public function get_action($fid = null, $openid = null) {
		// 关注用户信息
		if (!empty($fid)) {
			$fan = $this->model('user/fans')->byId($fid);
		} else if (!empty($openid)) {
			$fan = $this->model('user/fans')->byOpenid($this->mpid, $openid);
		} else {
			die('parameter error');
		}

		if (empty($fan)) {
			return new \ResponseError('无法获取关注用户信息');
		}

		$mm = $this->model('user/member');
		if ($members = $mm->byFanid($fan->fid)) {
			foreach ($members as &$m) {
				$m->depts2 = $this->model('user/department')->strUserDepts($m->depts);
				$authapi = $this->model('user/authapi')->byId($m->authapi_id);
				$authapi->tags = $this->model('user/tag')->byMpid($this->mpid, $m->authapi_id);
				$authapi->depts = $this->model('user/department')->byMpid($this->mpid, $m->authapi_id);
				$m->authapi = $authapi;
			}
			$fan->members = $members;
		}

		$params = array();
		$params['fan'] = $fan;
		$params['authapis'] = $this->model('user/authapi')->byMpid($this->mpid, 'Y');
		$params['groups'] = $this->model('user/fans')->getGroups($this->mpid);

		return new \ResponseData($params);
	}
	/**
	 * 获得用户选择器的页面
	 *
	 */
	public function picker_action() {
		$this->view_action('/mp/user/picker');
	}
}
