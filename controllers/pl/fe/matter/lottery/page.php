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
	public function update_action($site, $app, $pattern, $name = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelCode = $this->model('code\page');
		if (!empty($name)) {
			$page = $modelCode->lastByName($site, $name);
		} else {
			$page = $modelCode->create($site, $user->id);
			$this->model()->update(
				'xxt_lottery',
				array(
					'page_id' => $page->id,
					'page_code_name' => $page->name,
				),
				"id='$app'"
			);
		}
		$template = TMS_APP_TEMPLATE . '/pl/fe/matter/lottery/' . $pattern;
		$data = array(
			'html' => file_get_contents($template . '.html'),
			'css' => file_get_contents($template . '.css'),
			'js' => file_get_contents($template . '.js'),
		);
		$rst = $modelCode->modify($page->id, $data);

		return new \ResponseData($rst);
	}
}