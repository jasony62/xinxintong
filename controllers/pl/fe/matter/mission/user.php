<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class user extends \pl\fe\matter\base {
	private $_modelUsr;
	/**
	 *
	 */
	public function __construct() {
		$this->_modelMis = $this->model('matter\mission');
	}
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/mission/frame');
		exit;
	}
	/**
	 * 获得项目的用户列表
	 *
	 * @param int $mission mission's id
	 */
	public function list_action($mission, $page = 1, $size = 30) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$mission = $this->model('matter\mission')->byId($mission, ['fields' => 'user_app_id,user_app_type']);
		if ($mission === false) {
			return new \ObjectNotFoundError();
		}

		$criteria = $this->getPostJson();
		$options = [
			'page' => $page,
			'size' => $size,
		];

		$modelUsr = $this->model('matter\mission\user');
		$result = $modelUsr->byMission($mission, $criteria, $options);
		if ($result[0] === false) {
			return new \ResponseError($result[1]);
		}

		return new \ResponseData($result[1]);
	}
	/**
	 * 获得指定用户在项目中的行为记录
	 */
	public function recordByUser_action($mission, $user) {
		$result = new \stdClass;

		$modelEnlRec = $this->model('matter\enroll\record');
		$records = $modelEnlRec->byMission($mission, ['userid' => $user]);
		$result->enroll = $records;

		$modelSigRec = $this->model('matter\signin\record');
		$records = $modelSigRec->byMission($mission, ['userid' => $user]);
		$result->signin = $records;

		$modelGrpRec = $this->model('matter\group\player');
		$records = $modelGrpRec->byMission($mission, ['userid' => $user]);
		$result->group = $records;

		return new \ResponseData($result);
	}
}