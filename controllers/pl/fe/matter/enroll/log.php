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
	public function index_action($id) {
		$access = $this->accessControlUser('enroll', $id);
		if ($access[0] === false) {
			die($access[1]);
		}

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
		if (!empty($criteria->byOp) && (strcasecmp('all', $criteria->byOp) != 0)) {
			$options['byOp'] = $criteria->byOp;
		}
		if (!empty($criteria->byRid) && (strcasecmp('all', $criteria->byRid) != 0)) {
			$options['byRid'] = $criteria->byRid;
		}
		
		if ($logType === 'pl') {
			$reads = $modelLog->listMatterOp($app, 'enroll', $options, $page, $size);
		} else {
			$reads = $this->model('matter\enroll\log')->list($app, $options, $page, $size);
		}

		return new \ResponseData($reads);
	}
}