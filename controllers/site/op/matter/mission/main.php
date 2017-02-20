<?php
namespace site\op\matter\mission;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 * 运营用户项目主控制器
 */
class main extends \site\op\base {
	/**
	 *
	 */
	public function index_action($mission) {
		$mission = $this->model('matter\mission')->byId($mission);
		if ($mission) {
			\TPL::assign('title', $mission->title);
		}
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
		if ($mission === false) {
			return new \ResponseError('指定的对象不存在');
		}
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
	 * 只有开放了运营管理者链接的活动才会列出
	 *
	 * @param int $mission 项目的ID
	 */
	public function matterList_action($mission, $matterType = null) {
		$modelMis = $this->model('matter\mission\matter');
		$matters = $modelMis->byMission($mission, $matterType, ['op_short_url_code' => true]);
		if (count($matters)) {
			foreach ($matters as &$matter) {
				if (in_array($matter->type, ['enroll', 'signin', 'group'])) {
					$modelMat = $this->model('matter\\' . $matter->type);
					$matter->opData = $modelMat->opData($matter);
				}
			}
		}

		return new \ResponseData($matters);
	}
}