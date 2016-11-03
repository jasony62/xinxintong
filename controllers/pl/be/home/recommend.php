<?php
namespace pl\be\home;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 首页推荐
 */
class recommend extends \pl\be\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/be/home/frame');
		exit;
	}
	/**
	 * 加入到主页
	 */
	public function pushTemplate_action($template) {
		$modelShop = $this->model('template\shop');

		$rst = $modelShop->pushHome($template);

		return new \ResponseData($rst);
	}
	/**
	 * 从主页撤销
	 */
	public function pullTemplate_action($template) {
		$modelShop = $this->model('template\shop');

		$rst = $modelShop->pullHome($template);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function listMatter_action($category) {
		$modelHome = $this->model('matter\home');

		if ($category === 'app') {
			$matters = $modelHome->findApp();
		} else if ($category === 'article') {
			$matters = $modelHome->findArticle();
		} else {
			$matters = false;
		}

		return new \ResponseData($matters);
	}
	/**
	 * 素材加入到主页
	 */
	public function pushMatter_action($application) {
		$modelHome = $this->model('matter\home');

		$rst = $modelHome->pushHome($application);

		return new \ResponseData($rst);
	}
	/**
	 * 从主页撤销素材
	 */
	public function pullMatter_action($application) {
		$modelHome = $this->model('matter\home');

		$rst = $modelHome->pullHome($application);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function listSite_action() {
		$modelHome = $this->model('site\home');

		$matters = $modelHome->find();

		return new \ResponseData($matters);
	}
	/**
	 * 加入到主页
	 */
	public function pushSite_action($application) {
		$modelHome = $this->model('site\home');

		$rst = $modelHome->pushHome($application);

		return new \ResponseData($rst);
	}
	/**
	 * 从主页撤销
	 */
	public function pullSite_action($application) {
		$modelHome = $this->model('site\home');

		$rst = $modelHome->pullHome($application);

		return new \ResponseData($rst);
	}
}