<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动主控制器
 */
class page extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 添加活动页面
	 *
	 * @param string $site
	 * @param string $app
	 */
	public function add_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$options = $this->getPostJson();

		$newPage = $this->model('matter\enroll\page')->add($user, $site, $app, $options);

		return new \ResponseData($newPage);
	}
	/**
	 * 更新活动的页面的属性信息
	 *
	 * $aid 活动的id
	 * $pid 页面的id，如果id==0，是固定页面
	 * $cid 页面对应code page id
	 */
	public function update_action($site, $app, $pid, $cname) {
		$nv = $this->getPostJson();

		$rst = 0;
		if (isset($nv->html)) {
			$data = array(
				'html' => urldecode($nv->html),
			);
			$modelCode = $this->model('code\page');
			$code = $modelCode->lastByName($site, $cname);
			$rst = $modelCode->modify($code->id, $data);
			unset($nv->html);
		}
		if ($pid != 0 && count(array_keys(get_object_vars($nv)))) {
			$model = $this->model();
			if (isset($nv->data_schemas)) {
				$nv->data_schemas = $model->toJson($nv->data_schemas);
			}
			if (isset($nv->act_schemas)) {
				$nv->act_schemas = $model->toJson($nv->act_schemas);
			}
			if (isset($nv->user_schemas)) {
				$nv->user_schemas = $model->toJson($nv->user_schemas);
			}
			$rst = $model->update(
				'xxt_enroll_page',
				$nv,
				"aid='$app' and id=$pid"
			);
		}

		return new \ResponseData($rst);
	}
	/**
	 * 删除活动的页面
	 *
	 * @param string $site
	 * @param string $aid
	 * @param string $pid
	 */
	public function remove_action($site, $app, $pid, $cname) {
		$page = $this->model('matter\enroll\page')->byId($app, $pid);

		$modelCode = $this->model('code\page');
		$modelCode->removeByName($site, $cname);

		$rst = $this->model()->delete('xxt_enroll_page', "aid='$app' and id=$pid");

		return new \ResponseData($rst);
	}
}