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
		$modelShop = $this->model('matter\template');

		$rst = $modelShop->pushHome($template);

		return new \ResponseData($rst);
	}
	/**
	 * 从主页撤销
	 */
	public function pullTemplate_action($template) {
		$modelShop = $this->model('matter\template');

		$rst = $modelShop->pullHome($template);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function listMatter_action($category, $page = 1, $size = 8) {
		$modelHome = $this->model('matter\home');

		$options = [];
		$options['page']['at']=$page;
		$options['page']['size']=$size;
		if ($category === 'app') {
			$matters = $modelHome->findApp($options);
		} else if ($category === 'article') {
			$matters = $modelHome->findArticle($options);
		} else if ($category === 'channel') {
			$matters = $modelHome->findChannel($options);
		} else {
			$matters = false;
		}

		return new \ResponseData($matters);
	}
	/**
	 * 素材加入到主页
	 */
	public function pushMatter_action($application, $homeGroup = '') {
		$modelHome = $this->model('matter\home');

		$rst = $modelHome->pushHome($application, $homeGroup);

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
	public function listSite_action($page = 1, $size = 8) {
		$modelHome = $this->model('site\home');
		$options = [];
		$options['page']['at']=$page;
		$options['page']['size']=$size;
		$matters = $modelHome->find($options);

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
	/**
	 * [素材置顶]
	 * @param  [type] $application [description]
	 * @return [type]              [description]
	 */
	public function pushMatterTop_action($application) {
		$modelHome = $this->model('matter\home');

		$rst = $modelHome->pushHomeTop($application);
		if(isset($rst[0]) && $rst[0] === false){
			return new \ResponseError($rst[1]);
		}

		return new \ResponseData($rst);
	}
	/**
	 * [撤销素材置顶]
	 * @param  [type] $application [description]
	 * @return [type]              [description]
	 */
	public function pullMatterTop_action($application) {
		$modelHome = $this->model('matter\home');

		$rst = $modelHome->pullHomeTop($application);

		return new \ResponseData($rst);
	}
}