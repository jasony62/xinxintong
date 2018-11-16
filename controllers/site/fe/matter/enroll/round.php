<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动轮次
 */
class round extends base {
	/**
	 *
	 * @param string $app
	 */
	public function list_action($app, $page = 1, $size = 10) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$modelRun = $this->model('matter\enroll\round');
		$options = [
			'fields' => 'rid,title',
			'state' => ['1', '2'],
		];

		$oPage = new \stdClass;
		$oPage->num = $page;
		$oPage->size = $size;
		$options['page'] = $oPage;

		$result = $modelRun->byApp($oApp, $options);

		return new \ResponseData($result);
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
		if (false === $oApp && $oApp->state !== '1') {
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
}