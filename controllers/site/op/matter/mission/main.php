<?php
namespace site\op\matter\mission;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 * 项目主控制器
 */
class main extends \site\op\base {
	/**
	 *
	 */
	public function index_action($mission) {
		$mission = $this->model('matter\mission')->byId($mission);
		\TPL::assign('title', $mission->title);
		\TPL::output('site/op/matter/mission/main');
		exit;
	}
	/**
	 *
	 */
	public function get_action($mission) {
		$params = [];
		/* 项目定义 */
		$modelMis = $this->model('matter\mission');
		$mission = $modelMis->byId($mission);
		$params['mission'] = &$mission;
		/* 页面定义 */
		$templateDir = TMS_APP_TEMPLATE . '/site/op/matter/mission';
		$templateName = $templateDir . '/basic';

		$page = [
			'html' => file_get_contents($templateName . '.html'),
			'css' => file_get_contents($templateName . '.css'),
			'js' => file_get_contents($templateName . '.js'),
		];
		$params['page'] = &$page;

		return new \ResponseData($params);
	}
	/**
	 * 获得任务下的素材
	 *
	 * @param int $id
	 */
	public function matterList_action($mission, $matterType = null) {
		$modelMis = $this->model('matter\mission');
		$matters = $modelMis->mattersById($mission, $matterType);

		return new \ResponseData($matters);
	}
}