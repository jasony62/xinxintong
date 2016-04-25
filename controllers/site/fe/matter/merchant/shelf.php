<?php
namespace site\fe\matter\merchant;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 货架
 */
class shelf extends \site\fe\matter\base {
	/**
	 * 进入产品展示页
	 *
	 * $site site'id
	 * $page page'id
	 */
	public function index_action($site, $page) {
		$page = $this->model('matter\merchant\page')->byId($page);
		\TPL::assign('title', $page->title);
		\TPL::output('/site/fe/matter/merchant/shelf');
		exit;
	}
	/**
	 *
	 */
	public function get_action($site, $page) {
		// current visitor
		$user = $this->who;
		// page
		$page = $this->model('matter\merchant\page')->byId($page);

		$params = array(
			'user' => $user,
			'page' => $page,
		);

		return new \ResponseData($params);
	}
}