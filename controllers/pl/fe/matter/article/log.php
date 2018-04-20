<?php
namespace pl\fe\matter\article;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动日志控制器
 */
class log extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action($id) {
		$access = $this->accessControlUser('article', $id);
		if ($access[0] === false) {
			die($access[1]);
		}

		\TPL::output('/pl/fe/matter/article/frame');
		exit;
	}
	/**
	 * 查询日志
	 *
	 */
	public function list_action($id, $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		$reads = $modelLog->listUserMatterOp($id, 'article', [], $page, $size);

		return new \ResponseData($reads);
	}
	/**
	 *
	 */
	public function operateStat_action($site, $appId, $operateType = 'read', $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		$options = [];
		$options['operateType'] = $operateType;
		$filter = $this->getPostJson();

		if (!empty($page) && !empty($size)) {
			$options['paging'] = ['page' => $page, 'size' => $size];
		}
		if (!empty($filter->start)) {
			$options['start'] = $modelLog->escape($filter->start);
		}
		if (!empty($filter->end)) {
			$options['end'] = $modelLog->escape($filter->end);
		}
		if (!empty($filter->nickname)) {
			$options['nickname'] = $modelLog->escape($filter->nickname);
		}
		if (!empty($filter->shareby)) {
			$options['shareby'] = $modelLog->escape($filter->shareby);
		}

		if ($operateType === 'read') {
			$logs = $modelLog->operateStatRead($site, $appId, 'article', $options);
		} else {
			$logs = $modelLog->operateStatShare($site, $appId, 'article', $options);
		}

		return new \ResponseData($logs);
	}
}