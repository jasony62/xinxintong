<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 *
 */
class record extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 活动报名名单
	 *
	 * 1、如果活动仅限会员报名，那么要叠加会员信息
	 * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 */
	public function list_action($site, $app, $page = 1, $size = 30, $signinStartAt = null, $signinEndAt = null, $tags = null, $rid = null, $kw = null, $by = null, $orderby = null, $contain = null) {
		/*应用*/
		$modelApp = $this->model('matter\enroll');
		$app = $modelApp->byId($app);
		/*参数*/
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
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->find($site, $app, $options);
		/* 获得数据项定义 */
		$result->schema = json_decode($app->data_schemas);

		return new \ResponseData($result);
	}
}