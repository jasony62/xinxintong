<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class matter extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/mission/frame');
		exit;
	}
	/**
	 * 获得任务下的素材
	 *
	 * @param int $id
	 */
	public function list_action($id, $matterType = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		/* 检查权限 */
		$modelAcl = $this->model('matter\mission\acl');
		if (false === ($acl = $modelAcl->byCoworker($id, $user->id))) {
			return new \ResponseError('数据不存在');
		}

		$matters = $this->model('matter\mission\matter')->byMission($id, $matterType);

		return new \ResponseData($matters);
	}
	/**
	 * 获得任务下素材的数量
	 *
	 * @param int $id mission'is
	 */
	public function count_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		/* 检查权限 */
		$modelAcl = $this->model('matter\mission\acl');
		if (false === ($acl = $modelAcl->byCoworker($id, $user->id))) {
			return new \ResponseError('项目不存在或没有访问项目的权限');
		}

		$count = $this->model('matter\mission\matter')->count($id);

		return new \ResponseData($count);
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
	/**
	 * 给项目添加素材
	 *
	 * @param int $id
	 */
	public function remove_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$matter = $this->getPostJson();

		$modelMis = $this->model('matter\mission');
		$rst = $modelMis->removeMatter($matter->id, $matter->type);

		return new \ResponseData($rst);
	}
}