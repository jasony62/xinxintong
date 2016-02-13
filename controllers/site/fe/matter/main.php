<?php
namespace site\fe\matter;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 返回访问的素材页面
 */
class main extends \site\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * @param string $id
	 * @param string $type
	 * @param string $shareby
	 */
	public function index_action($id, $type, $shareby = '') {
		/*返回页面*/
		switch ($type) {
		case 'article':
			$modelArticle = $this->model('matter\article');
			$article = $modelArticle->byId($id, 'title');
			if (isset($_GET['tpl']) && $_GET['tpl'] === 'cus') {
				\TPL::assign('title', $article->title);
				\TPL::output('site/fe/matter/custom');
				exit;
			} else {
				\TPL::assign('title', $article->title);
				\TPL::output('site/fe/matter/article');
				exit;
			}
			break;
		}
	}
}