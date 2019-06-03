<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class page extends \pl\fe\matter\base {
	/**
	 * 创建项目定制页面
	 */
	public function create_action($id, $page) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$mission = $modelMis->byId($id, 'id,siteid');

		$code = $this->model('code\page')->create($mission->siteid, $user->id);

		$rst = $modelMis->update(
			'xxt_mission',
			[
				$page . '_page_name' => $code->name,
			],
			["id" => $id]
		);

		return new \ResponseData($code);
	}
	/**
	 * 修改项目定制页面
	 */
	public function update_action($id, $page) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$newPage = $this->getPostJson(false);

		$modelMis = $this->model('matter\mission');
		$mission = $modelMis->byId($id);
		$data = [
			'html' => isset($newPage->html) ? $newPage->html : '',
		];

		$modelCode = $this->model('code\page');
		$code = $modelCode->lastByName($mission->siteid, $mission->{$page . '_page_name'});
		$code = $modelCode->modify($code->id, $data);

		return new \ResponseData($code);
	}
	/**
	 * 重置定制页面
	 *
	 * @param int $codeId
	 */
	public function reset_action($site, $id, $page) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelMis = $this->model('matter\mission');
		$mission = $modelMis->byId($id);
		$data = array(
			'html' => '',
			'css' => '',
			'js' => '',
		);
		$modelCode = $this->model('code\page');
		$code = $modelCode->lastByName($mission->siteid, $mission->{$page . '_page_name'});
		$rst = $modelCode->modify($code->id, $data);

		return new \ResponseData($rst);
	}
}