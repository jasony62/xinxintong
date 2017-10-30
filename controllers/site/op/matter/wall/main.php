<?php
namespace site\op\matter\wall;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 *
 */
class main extends \site\op\base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 信息墙大屏幕页面
	 *
	 * @param string $wall
	 *
	 */
	public function index_action($wall) {
		$model = $this->model('matter\wall');
		$w = $model->byId($wall, 'title');
		\TPL::assign('title', $w->title);

		$params = array();
		\TPL::assign('params', $params);

		\TPL::output('/site/op/matter/wall/page');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($wall) {
		// page
		$page = $this->model('matter\wall\page')->byType('op', $wall);
		if (empty($page)) {
			return new \ResponseError('没有获得页面定义');
		}
		$page = $page[0];
		$model = $this->model('matter\wall');
		$wall = $model->byId($wall);
		if (!empty($wall->interact_matter)) {
			foreach ($wall->interact_matter as $key => $matter) {
				$options = array('cascaded' => 'N', 'fields' => 'siteid,id,title');
				$wall->interact_matter[$key] = $this->model('matter\\' . $matter->type)->byId($matter->id, $options);
			}
		}
		$params = array(
			'wall' => $wall,
			'page' => $page,
		);

		return new \ResponseData($params);
	}
	/**
	 *
	 */
	public function messageList_action($site, $wall) {
		$model = $this->model('matter\wall');
		$last = $this->getGet('last', 0);
		$m = $model->approvedMessages($site, $wall, $last);

		return new \ResponseData($m);
	}
	/*
	* 获取素材分享者列表
	* $startTime 分享开始时间
	*/
	public function listPlayer_action($site, $app, $startTime, $startId = null) {
		$modelWall = $this->model('matter\wall')->setOnlyWriteDbConn(true);
		if (($oApp = $modelWall->byId($app, ['fields' => 'scenario_config,interact_matter'])) === false) {
			return new \ObjectNotFoundError();
		}
		if(empty($oApp->interact_matter)){
			return new \ResponseError('未指定互动素材');
		}
		
		$users = $this->model('matter\wall')->listPlayer($startTime, $startId, $oApp);

		return new \ResponseData($users);
	}
}