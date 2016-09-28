<?php
/**
 * 进入快速任务
 */
class q extends TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'index';

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action($code = null) {
		if (empty($code)) {
			TPL::output('site/op/q/entry');
			exit;
		}
		/**
		 * 检查短链接是否存在
		 */
		$item = $this->model('q\url')->byCode($code);
		if (false === $item) {
			$this->outputError('没有对应的链接');
		}
		/**
		 * 检查访问密码
		 */
		if (!empty($item->password)) {
			if (empty($_POST['passwd']) || $_POST['passwd'] !== $item->password) {
				TPL::output('site/op/q/passwd');
				exit;
			}
		}
		/**
		 * 设置访问控制
		 */
		$expire = 3600;
		$accessToken = $this->_setAccessToken($code, $expire);
		//
		$url = $item->target_url;
		if (strpos($url, '?') === false) {
			$url .= '?accessToken=' . $accessToken;
		} else {
			$url .= '&accessToken=' . $accessToken;
		}
		$this->redirect($url);
	}
	/**
	 *
	 */
	protected function outputError($err, $title = '程序错误') {
		TPL::assign('title', $title);
		TPL::assign('body', $err);
		TPL::output('error');
		exit;
	}
	/**
	 * 设置访问令牌
	 */
	private function _setAccessToken($code, $expire) {
		$userAgent = $_SERVER['HTTP_USER_AGENT'];

		$token = $this->model('q\urltoken')->add($code, $userAgent, $expire);

		return $token;
	}
}