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
		\TPL::output('/pl/fe/site/console');
		exit;
	}
	/**
	 * 列出站点最近操作的素材
	 */
	public function recent_action($site, $exclude = '', $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		/*分页参数*/
		$p = new \stdClass;
		$p->at = $page;
		$p->size = $size;

		$options = array(
			'page' => $p,
		);

		if (!empty($exclude)) {
			$exclude = explode(',', $exclude);
			$options['exclude'] = $exclude;
		}

		$matters = $modelLog->recentMatters($site, $options);

		return new \ResponseData($matters);
	}
	/**
	 * 最近删除的素材
	 */
	public function recycle_action($site, $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		/*分页参数*/
		$p = new \stdClass;
		$p->at = $page;
		$p->size = $size;

		$options = array(
			'page' => $p,
		);

		$matters = $modelLog->recycleMatters($site, $options);

		return new \ResponseData($matters);
	}
}