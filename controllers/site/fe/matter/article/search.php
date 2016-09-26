<?php 
namespace site\fe\matter\article;

include_once dirname(dirname(__FILE__)) . '/base.php';

/**
 *[查看更多]搜索更多
 */
class search extends \site\fe\matter\base{
	/**
	 * 搜索页面
	 */
	public function index_action() {
		\TPL::output('/matter/article-list');
		exit;
	}
	/*
	 * 返回所有的搜索结果
	 */
	public function list_action($site,$keyword='') {
		$matters = \TMS_APP::model('matter\article')->search_all($site, $keyword);	
		return new \ResponseData($matters);
	}
	/*
	 * 返回所有的内容标签
	 */
	public function tags_action($site) {
		$r=  $this->model("tag")->get_tags($site);
		return new \ResponseData($r);
	}
	/*
	 * 返回搜索结果的总数
	 */
	public function sum_action($site,$keyword) {
		$sum = \TMS_APP::model('matter\article')->fullsearch_num($site, $keyword);  
		return new \ResponseData($sum);
	}
}