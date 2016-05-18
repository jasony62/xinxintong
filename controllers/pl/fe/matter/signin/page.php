<?php
namespace pl\fe\matter\signin;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动主控制器
 */
class page extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/signin/frame');
		exit;
	}
	/**
	 * 添加活动页面
	 *
	 * $aid 获动的id
	 */
	public function add_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$options = $this->getPostJson();

		$newPage = $this->model('matter\signin\page')->add($user, $site, $app, $options);

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
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$nv = $this->getPostJson();

		$rst = 0;
		if (isset($nv->html)) {
			$data = array(
				'html' => urldecode($nv->html),
			);
			$modelCode = $this->model('code\page');
			$code = $modelCode->lastByName($site, $cname);
			$rst = $modelCode->modify($code->id, $data);
		} else if (isset($nv->js)) {
			$data = array(
				'js' => urldecode($nv->js),
			);
			$rst = $this->model('code\page')->modify($cid, $data);
		} else {
			if ($pid != 0) {
				$model = $this->model();
				if (isset($nv->data_schemas)) {
					$nv->data_schemas = $model->toJson($nv->data_schemas);
				} else if (isset($nv->act_schemas)) {
					$nv->act_schemas = $model->toJson($nv->act_schemas);
				} else if (isset($nv->user_schemas)) {
					$nv->user_schemas = $model->toJson($nv->user_schemas);
				}
				$rst = $model->update(
					'xxt_signin_page',
					$nv,
					"aid='$app' and id=$pid"
				);
			}
		}

		return new \ResponseData($rst);
	}
	/**
	 * 删除活动的页面
	 *
	 * $aid
	 * $pid
	 */
	public function remove_action($app, $pid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$page = $this->model('matter\signin\page')->byId($app, $pid);

		$modelCode = $this->model('code\page');
		$code = $modelCode->lastByName($site, $cname);
		$modelCode->remove($code->id);

		$rst = $this->model()->delete('xxt_signin_page', "aid='$app' and id=$pid");

		return new \ResponseData($rst);
	}
}