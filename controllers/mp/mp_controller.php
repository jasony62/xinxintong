<?php
namespace mp;

require_once dirname(dirname(__FILE__)) . '/xxt_base.php';

class mp_controller extends \xxt_base {
	/**
	 *
	 */
	protected $mpid;
	/**
	 *
	 */
	private $yx_token;
	/**
	 *
	 */
	private $wx_token;
	/**
	 *
	 */
	public function __construct() {
		$account = \TMS_CLIENT::account();
		if ($account === false) {
			return new \ResponseTimeout();
		}
		if (isset($_GET['mpid']) && ($mpid = $_GET['mpid'])) {
			$_SESSION['mpid'] = $mpid;
		} else if (!isset($_SESSION['mpid']) || !($mpid = $_SESSION['mpid'])) {
			header('HTTP/1.0 500 parameter error:mpid is empty.');
			die('参数不完整');
		}
		$this->mpid = $mpid;
		/**
		 * entries
		 */
		$prights = $this->model('mp\permission')->hasMpRight(
			$this->mpid,
			array('mpsetting', 'matter', 'app', 'reply', 'user', 'analyze'),
			'read'
		);
		$entries = array();
		(true === $prights || $prights['mpsetting']['read_p'] === 'Y') && $entries['/rest/mp/mpaccount'] = array('title' => '账号管理', 'entry' => '');
		(true === $prights || $prights['matter']['read_p'] === 'Y') && $entries['/rest/mp/matter'] = array('title' => '素材管理', 'entry' => '');
		(true === $prights || $prights['app']['read_p'] === 'Y') && $entries['/rest/mp/app'] = array('title' => '应用管理', 'entry' => '');
		(true === $prights || $prights['reply']['read_p'] === 'Y') && $entries['/rest/mp/call'] = array('title' => '回复管理', 'entry' => '');
		(true === $prights || $prights['user']['read_p'] === 'Y') && $entries['/page/mp/user/received'] = array('title' => '用户管理', 'entry' => '');
		(true === $prights || $prights['analyze']['read_p'] === 'Y') && $entries['/page/mp/analyze'] = array('title' => '统计分析', 'entry' => '');

		\TPL::assign('mp_view_entries', $entries);
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
	 *
	 */
	protected function getMpaccount() {
		return \TMS_APP::M('mp\mpaccount')->byId($this->mpid, 'name,mpid,mpsrc,asparent,parent_mpid,yx_joined,wx_joined,qy_joined');
	}
	/**
	 * 获得父公众号的ID
	 */
	protected function getParentMpid() {
		$mpa = $this->getMpaccount();
		return empty($mpa->parent_mpid) ? false : $mpa->parent_mpid;
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