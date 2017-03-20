<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动主控制器
 */
class round extends \pl\fe\matter\base {
	/**
	 * 返回指定登记活动下的轮次
	 *
	 * @param string $app app's id
	 *
	 */
	public function list_action($app, $page = 1, $size = 10) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelRnd = $this->model('matter\enroll\round');

		/* 先检查是否要根据定时规则生成轮次 */
		$modelRnd->getActive($oApp);

		$oPage = new \stdClass;
		$oPage->num = $page;
		$oPage->size = $size;

		$result = $modelRnd->byApp($oApp, ['page' => $oPage]);

		return new \ResponseData($result);
	}
	/**
	 * 添加轮次
	 *
	 * @param string $app
	 *
	 */
	public function add_action($app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelRnd = $this->model('matter\enroll\round');
		$posted = $this->getPostJson();

		$rst = $modelRnd->create($oApp, $posted, $user);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		return new \ResponseData($rst[1]);
	}
	/**
	 * 更新轮次
	 *
	 * @param string $app
	 * @param string $rid
	 */
	public function update_action($site, $app, $rid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelRnd = $this->model('matter\enroll\round');
		$posted = $this->getPostJson();

		if (isset($posted->state) && (int) $posted->state === 1) {
			/**
			 * 启用一个轮次，要停用上一个轮次
			 */
			if ($lastRound = $modelRnd->getLast($oApp)) {
				if ((int) $lastRound->state !== 2) {
					$modelRnd->update(
						'xxt_enroll_round',
						['state' => 2],
						['aid' => $oApp->id, 'rid' => $lastRound->rid]
					);
				}
			}
		}

		$rst = $modelRnd->update(
			'xxt_enroll_round',
			$posted,
			['aid' => $app, 'rid' => $rid]
		);

		return new \ResponseData($rst);
	}
	/**
	 * 删除轮次
	 *
	 * @param string $app
	 * @param string $rid
	 */
	public function remove_action($app, $rid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelRnd = $this->model('matter\enroll\round');
		/**
		 * 删除轮次
		 * ??? 如果轮次已经启用？如果已经有数据呢？
		 */
		$rst = $modelRnd->delete(
			'xxt_enroll_round',
			['aid' => $oApp->id, 'rid' => $rid]
		);

		if (false === $modelRnd->getLast($oApp)) {
			/**
			 * 如果不存在轮次了修改登记活动的状态标记
			 */
			$modelRnd->update(
				'xxt_enroll',
				['multi_rounds' => 'N'],
				['id' => $oApp->id]
			);
		}

		return new \ResponseData($rst);
	}
}