<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class matter extends \pl\fe\matter\base {
	/**
	 * 活的任务下的素材
	 *
	 * @param string $site
	 * @param int $id
	 */
	public function list_action($site, $id, $matterType = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$matters = $this->model('matter\mission')->mattersById($site, $id, $matterType);

		return new \ResponseData($matters);
	}
	/**
	 * 给项目添加素材
	 *
	 * @param string $site
	 * @param int $id
	 */
	public function add_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$matter = $this->getPostJson();

		$modelMis = $this->model('matter\mission');
		$modelMis->addMatter($user, $site, $id, $matter);

		$mission = $modelMis->byId($id, ['cascaded' => 'phase']);

		return new \ResponseData($mission);
	}
}