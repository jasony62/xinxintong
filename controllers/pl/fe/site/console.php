<?php
namespace pl\fe\site;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 工作台
 */
class console extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/main');
		exit;
	}
	/**
	 * 列出站点最近操作的素材
	 */
	public function recent_action($id, $page = 1, $size = 30) {
		$modelLog = $this->model('log');

		/*分页参数*/
		$p = new \stdClass;
		$p->at = $page;
		$p->size = $size;

		$options = array(
			'page' => $p,
		);

		$matters = $modelLog->recentMatters($id, $options);

		return new \ResponseData($matters);
	}
}