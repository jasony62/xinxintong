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
		if (!$this->checkAccessToken()) {
			header('HTTP/1.0 500 parameter error:accessToken is invalid.');
			die('没有获得有效访问令牌！');
		}

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
	public function user_action($mission) {
		if (!$this->checkAccessToken()) {
			header('HTTP/1.0 500 parameter error:accessToken is invalid.');
			die('没有获得有效访问令牌！');
		}

		$mission = $this->model('matter\mission')->byId($mission);
		if ($mission) {
			\TPL::assign('title', $mission->title);
		}
		\TPL::output('site/op/matter/mission/user');
		exit;
	}
	/**
	 *
	 */
	public function get_action($mission) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$params = [];
		/* 项目定义 */
		$modelMis = $this->model('matter\mission');
		$mission = $modelMis->byId($mission);
		if ($mission === false) {
			return new \ResponseError('指定的对象不存在');
		}
		/* 关联的用户名单活动 */
		if ($mission->user_app_id) {
			if ($mission->user_app_type === 'enroll') {
				$mission->userApp = $this->model('matter\enroll')->byId($mission->user_app_id, ['cascaded' => 'N']);
			} else if ($mission->user_app_type === 'signin') {
				$mission->userApp = $this->model('matter\signin')->byId($mission->user_app_id, ['cascaded' => 'N']);
			}
		}

		$params['mission'] = &$mission;

		return new \ResponseData($params);
	}
	/**
	 * 获得任务下的素材
	 *
	 * @param int $mission 项目的ID
	 */
	public function matterList_action($mission, $matterType = null) {
		$modelMis = $this->model('matter\mission\matter');
		$matters = $modelMis->byMission($mission, $matterType, ['is_public' => 'Y']);
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