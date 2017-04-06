<?php
namespace pl\be;
/**
 *
 */
class base extends \TMS_CONTROLLER {
	/**
	 * 检查用户权限
	 */
	public function __construct(){
		if($account = \TMS_CLIENT::account()){
			$model=\TMS_APP::M('account');
			$rst=$model->checkAcl($account->uid);
			
			if(!$rst){
				die("<script>alert('管理员没有访问权限！');window.history.back();</script>");
			}
		}else{
			return new \ResponseTimeout();
		}
	}
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 * 获得当前登录账号的用户信息
	 */
	protected function &accountUser() {
		$account = \TMS_CLIENT::account();
		if ($account) {
			$user = new \stdClass;
			$user->id = $account->uid;
			$user->name = $account->nickname;
			$user->src = 'A';

		} else {
			$user = false;
		}
		return $user;
	}
}