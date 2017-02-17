<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点用户注册
 */
class register extends \site\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 打开注册页
	 */
	public function index_action() {
		/* 整理cookie中的数据，便于后续处理 */
		$modelWay = $this->model('site\fe\way');
		$modelWay->resetAllCookieUser();

		\TPL::output('/site/fe/user/register');
		exit;
	}
	/**
	 * 执行注册
	 */
	public function do_action() {
		$user = $this->who;
		$data = $this->getPostJson();
		if (empty($data->uname) || empty($data->password)) {
			return new \ResponseError("注册信息不完整");
		}

		$modelWay = $this->model('site\fe\way');
		$cookieRegUser = $modelWay->getCookieRegUser();
		if ($cookieRegUser) {
			return new \ResponseError("请退出当前账号再注册");
		}

		$modelReg = $this->model('site\user\registration');
		/* uname */
		$uname = $data->uname;
		/* password */
		$password = $data->password;

		$options = [];
		/* nickname */
		if (isset($data->nickname)) {
			$options['nickname'] = $data->nickname;
		} else if (isset($user->nickname)) {
			$options['nickname'] = $user->nickname;
		}
		/* other options */
		$options['from_ip'] = $this->client_ip();

		/* create registration */
		$registration = $modelReg->create($this->siteId, $uname, $password, $options);
		if ($registration[0] === false) {
			return new \ResponseError($registration[1]);
		}
		$registration = $registration[1];

		/* cookie中保留注册信息 */
		$cookieRegUser = $modelWay->shiftRegUser($registration, false);

		return new \ResponseData($cookieRegUser);
	}
}