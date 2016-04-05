<?php
namespace site\fe\matter\contribute;

include_once dirname(__FILE__) . '/base.php';
/**
 * 投稿活动
 */
class main extends \site\fe\base {
	/**
	 * 获得当前用户的信息
	 * $site
	 * $entry
	 */
	public function index_action() {
		\TPL::output('/site/fe/matter/contribute/main');
		exit;
	}
}