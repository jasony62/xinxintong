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
	public function list_action($app, $target_type = 'site', $target_id = '', $page = 1, $size = 30) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N', 'notDecode' => true]);
		if (false === $oApp || $oApp->state != 1) {
			return new \ObjectNotFountError();
		}

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
			$options['byRid'] = $modelLog->escape($criteria->byRid);
		}
		if (!empty($criteria->startAt)) {
			$options['startAt'] = $modelLog->escape($criteria->startAt);
		}
		if (!empty($criteria->endAt)) {
			$options['endAt'] = $modelLog->escape($criteria->endAt);
		}
		
		if ($target_type === 'pl') {
			$reads = $modelLog->listMatterOp($oApp->id, 'enroll', $options, $page, $size);
		} else if (in_array($target_type, ['topic', 'repos', 'cowork'])) {
			if (empty($target_id)) {
				return new \ResponseError('参数不完整，缺失目标日志ID');
			}
			if (isset($options['byOp'])) {
				$options['byEvent'] = $options['byOp'];
			}
			if (!empty($page) && !empty($size)) {
				$options['paging'] = ['page' => $page, 'size' => $size];
			}

			$reads = $modelLog->listMatterAction($oApp->siteid, 'enroll.' . $target_type, $target_id, $options);
		} else {
			$reads = $this->model('matter\enroll\log')->list($oApp->id, $options, $page, $size);
		}

		return new \ResponseData($reads);
	}
}