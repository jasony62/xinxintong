<?php
namespace app\user;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 当前用户主页
 */
class main extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 进入主页
	 */
	public function index_action($mpid, $mocker = '', $code = null) {
		$openid = $this->doAuth($mpid, $code, $mocker);
		\TPL::output('/app/user/profile');
		exit;
	}
	/**
	 *
	 */
	public function get_action($mpid) {
		$result = new \stdClass;

		$user = $this->getUser($mpid, array('verbose' => array('fan' => 'Y', 'member' => 'Y')));

		$stat = new \stdClass;
		$article = new \stdClass;
		$article->read_num = 1;
		$article->like_num = 2;
		$article->remark_num = 3;
		$stat->article = &$article;

		$result->user = &$user;
		$result->stat = &$stat;

		return new \ResponseData($result);
	}
}