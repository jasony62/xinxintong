<?php
namespace pl\fe\matter\plan;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 记录活动日志控制器
 */
class log extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action($id) {
		\TPL::output('/pl/fe/matter/plan/frame');
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
			$options['byUser'] = $modelLog->escape($criteria->byUser);
		}
		if (!empty($criteria->byOp) && (strcasecmp('all', $criteria->byOp) != 0)) {
			$options['byOp'] = $modelLog->escape($criteria->byOp);
		}
		if (!empty($criteria->byRid) && (strcasecmp('all', $criteria->byRid) != 0)) {
			$options['byTask'] = $modelLog->escape($criteria->byRid);
		}

		if ($logType === 'pl') {
			$reads = $modelLog->listMatterOp($app, 'plan', $options, $page, $size);
		} else {
			$reads = $modelLog->listUserMatterOp($app, 'plan', $options, $page, $size);
		}

		return new \ResponseData($reads);
	}
}