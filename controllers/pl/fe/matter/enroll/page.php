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
	 * $aid 获动的id
	 */
	public function add_action($site, $app) {
		$options = $this->getPostJson();

		$newPage = $this->model('app\enroll\page')->add($site, $app, $options);

		return new \ResponseData($newPage);
	}
	/**
	 * 更新活动的页面的属性信息
	 *
	 * $aid 活动的id
	 * $pid 页面的id，如果id==0，是固定页面
	 * $pname 页面的名称
	 * $cid 页面对应code page id
	 */
	public function update_action($site, $id, $pid, $pname, $cid) {
		$nv = $this->getPostJson();

		$rst = 0;
		if (isset($nv->html)) {
			$data = array(
				'html' => urldecode($nv->html),
			);
			$rst = $this->model('code/page')->modify($cid, $data);
		} else if (isset($nv->js)) {
			$data = array(
				'js' => urldecode($nv->js),
			);
			$rst = $this->model('code/page')->modify($cid, $data);
		} else {
			if ($pid != 0) {
				$model = $this->model();
				if (isset($nv->data_schemas)) {
					$nv->data_schemas = $model->toJson($nv->data_schemas);
				} else if (isset($nv->act_schemas)) {
					$nv->act_schemas = $model->toJson($nv->act_schemas);
				}
				$rst = $model->update(
					'xxt_enroll_page',
					$nv,
					"aid='$id' and id=$pid"
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
	public function remove_action($aid, $pid) {
		$page = $this->model('app\enroll\page')->byId($aid, $pid);

		$this->model('code/page')->remove($page->code_id);

		$rst = $this->model()->delete('xxt_enroll_page', "aid='$aid' and id=$pid");

		return new \ResponseData($rst);
	}
}