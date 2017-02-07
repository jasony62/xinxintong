<?php
namespace site\op\matter\signin;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 *
 */
class record extends \site\op\base {
	/**
	 *
	 */
	public function list_action($site, $app, $page = 1, $size = 30, $signinStartAt = null, $signinEndAt = null, $tags = null, $rid = null, $kw = null, $by = null, $orderby = null, $contain = null) {
		// 登记数据过滤条件
		$criteria = $this->getPostJson();

		$options = array(
			'page' => $page,
			'size' => $size,
			'tags' => $tags,
			'signinStartAt' => $signinStartAt,
			'signinEndAt' => $signinEndAt,
			'rid' => $rid,
			'kw' => $kw,
			'by' => $by,
			'orderby' => $orderby,
			'contain' => $contain,
		);
		$mdoelRec = $this->model('matter\signin\record');
		$app = $this->model('matter\signin')->byId($app);
		$result = $mdoelRec->find($site, $app, $options, $criteria);

		return new \ResponseData($result);
	}
	/**
	 * 指定记录通过审核
	 */
	public function batchVerify_action($site, $app) {
		$posted = $this->getPostJson();
		$eks = $posted->eks;

		$modelApp = $this->model('matter\signin');
		$app = $modelApp->byId($app, ['cascaded' => 'N']);

		foreach ($eks as $ek) {
			$rst = $modelApp->update(
				'xxt_signin_record',
				['verified' => 'Y'],
				"enroll_key='$ek'"
			);
		}

		// 记录操作日志
		//$this->model('matter\log')->matterOp($site, $user, $app, 'verify.batch', $eks);

		return new \ResponseData('ok');
	}
}