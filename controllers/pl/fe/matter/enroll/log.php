<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动日志控制器
 */
class log extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 查询日志
	 *
	 */
	public function list_action($app, $logType = 'site', $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		$criteria = $this->getPostJson();
		$options = [];
		if (!empty($criteria->byUser)) {
			$options['byUser'] = $criteria->byUser;
		}
		if (!empty($criteria->byOp)) {
			$options['byOp'] = $criteria->byOp;
		}
		if (!empty($criteria->byRid) && (strcasecmp('all', $criteria->byRid) != 0)) {
			$options['byRid'] = $criteria->byRid;
		}

		if ($logType === 'pl') {
			$reads = $modelLog->listMatterOp($app, 'enroll', $options, $page, $size);
		} else {
			$reads = $modelLog->listUserMatterOp($app, 'enroll', $options, $page, $size);
		}

		return new \ResponseData($reads);
	}
	/*
	 *提交记录用户列表
	*/
	public function listUser_action($app, $page = '', $size = '') {
		if (($oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N'])) === false) {
			return new \ObjectNotFoundError();
		}

		$modelUser = $this->model('matter\enroll\user');
		$users = $modelUser->enrolleeByApp($oApp, $page, $size, ['cascaded' => 'N', 'onlyEnrolled' => 'Y', 'fields' => 'userid,nickname']);

		return new \ResponseData($users);
	}
}