<?php
namespace discuss;
/**
 * 评论访问控制器
 */
class base extends \TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$ruleAction = [
			'rule_type' => 'black',
		];

		return $ruleAction;
	}
	/**
	 * 获得用户信息
	 * 根据指定的domain，返回平台用户信息或者站点用户信息
	 *
	 * @param string $domain 'platform'或者siteid
	 */
	protected function getUser($domain) {
		if ($domain === 'platform') {
			if ($account = \TMS_CLIENT::account()) {
				$user = new \stdClass;
				$user->key = $account->uid;
				$user->name = $account->nickname;
				$user->domain = $domain;
			} else {
				$user = false;
			}
		} else {
			$modelWay = $this->model('site\fe\way');
			if ($who = $modelWay->who($domain)) {
				$user = new \stdClass;
				$user->key = $who->uid;
				$user->name = $who->nickname;
				$user->domain = $domain;
			} else {
				$user = false;
			}
		}

		return $user;
	}
}