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

		$q = array(
			'count(*)',
			'xxt_log_user_matter',
			"mpid='$mpid' and matter_type='article' and openid='$user->openid'",
		);
		$articleReadNum = $this->model()->query_val_ss($q);

		$q = array(
			'count(*)',
			'xxt_article_score',
			"mpid='$mpid' and vid='$user->vid'",
		);
		$articleLikeNum = $this->model()->query_val_ss($q);

		$q = array(
			'count(distinct article_id)',
			'xxt_article_remark',
			"mpid='$mpid' and openid='$user->openid'",
		);
		$articleRemarkNum = $this->model()->query_val_ss($q);

		$stat = new \stdClass;
		$article = new \stdClass;
		$article->read_num = $articleReadNum;
		$article->like_num = $articleLikeNum;
		$article->remark_num = $articleRemarkNum;
		$stat->article = &$article;

		$result->user = &$user;
		$result->stat = &$stat;

		return new \ResponseData($result);
	}
}