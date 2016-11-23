<?php
namespace site\fe\discuss;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点用户评论
 */
class main extends \site\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';

		return $rule_action;
	}
	/**
	 * 打开评论页面
	 */
	public function index_action() {
		\TPL::output('/site/fe/discuss/main');
		exit;
	}
}