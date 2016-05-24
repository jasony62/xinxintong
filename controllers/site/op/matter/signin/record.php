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
		$result = $mdoelRec->find($site, $app, $options);

		return new \ResponseData($result);
	}
}