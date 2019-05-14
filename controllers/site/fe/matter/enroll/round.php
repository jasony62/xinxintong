<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 记录活动轮次
 */
class round extends base {
	/**
	 *
	 * @param string $app
	 */
	public function list_action($app, $page = 1, $size = 10) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$modelRun = $this->model('matter\enroll\round');
		$aOptions = [
			'fields' => 'rid,title',
			'state' => ['1', '2'],
		];

		$oPage = new \stdClass;
		$oPage->num = $page;
		$oPage->size = $size;
		$aOptions['page'] = $oPage;

		$oResult = $modelRun->byApp($oApp, $aOptions);

		return new \ResponseData($oResult);
	}
	/**
	 *
	 * @param string $app
	 * @param string $rid
	 */
	public function get_action($app, $rid) {
		if (empty($rid)) {
			return new \ParameterError();
		}
		$rid = explode(',', $rid);

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N', 'id,state']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$modelRun = $this->model('matter\enroll\round');
		$q = [
			'rid,title,start_at,end_at',
			'xxt_enroll_round',
			['aid' => $oApp->id, 'rid' => $rid],
		];
		$q2 = ['o' => 'start_at desc,id desc'];
		$rounds = $modelRun->query_objs_ss($q, $q2);

		return new \ResponseData($rounds);
	}
	/**
	 *
	 * @param string $app
	 */
	public function getActive_action($app) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N', 'id,state,sync_mission_round,mission_id,round_cron']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelRnd = $this->model('matter\enroll\round');
		$oActiveRnd = $modelRnd->getActive($oApp, ['fields' => 'id,rid,title,purpose,start_at,end_at,mission_rid']);

		return new \ResponseData($oActiveRnd);
	}
}