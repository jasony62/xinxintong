<?php
namespace pl\fe\matter\lottery;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 抽奖活动控制器
 */
class page extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/lottery/frame');
		exit;
	}
	/**
	 *
	 */
	public function update_action($app, $pageid, $pattern) {
		$codeModel = $this->model('code/page');
		if ($pageid) {
			$page = $codeModel->byId($pageid);
		} else {
			$uid = \TMS_CLIENT::get_client_uid();
			$page = $codeModel->create($uid);
			$this->model()->update('xxt_lottery', array('page_id' => $page->id), "id='$app'");
		}
		$template = TMS_APP_TEMPLATE . '/pl/fe/matter/lottery/' . $pattern;
		$data = array(
			'html' => file_get_contents($template . '.html'),
			'css' => file_get_contents($template . '.css'),
			'js' => file_get_contents($template . '.js'),
		);
		$rst = $codeModel->modify($page->id, $data);

		return new \ResponseData($rst);
	}
}