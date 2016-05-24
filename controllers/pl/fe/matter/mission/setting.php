<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class setting extends \pl\fe\matter\base {
	/**
	 * 更新任务设置
	 */
	public function update_action($site, $id) {
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		/*data*/
		$nv = (array) $this->getPostJson();
		foreach ($nv as $n => $v) {
			if (in_array($n, array('title', 'summary'))) {
				$nv[$n] = $model->escape(urldecode($v));
			} else if (in_array($n, array('extattrs'))) {
				$nv[$n] = $model->toJson($v);
			}
		}
		/*modifier*/
		$nv['modifier'] = $user->id;
		$nv['modifier_src'] = $user->src;
		$nv['modifier_name'] = $user->name;
		$nv['modify_at'] = time();
		/*update*/
		$rst = $this->model()->update('xxt_mission', $nv, "id='$id'");
		/*记录操作日志*/
		if ($rst) {
			$mission = $this->model('mission')->byId($id, 'id,title,summary,pic');
			$mission->type = 'mission';
			$this->model('log')->matterOp($site, $user, $mission, 'U');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 创建频道定制页面
	 */
	public function pageCreate_action($site, $id, $page) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$code = $this->model('code\page')->create($site, $user->id);

		$rst = $this->model()->update(
			'xxt_mission',
			array(
				$page . '_page_name' => $code->name,
			),
			"siteid='{$site}' and id='$id'"
		);

		return new \ResponseData($code);
	}
	/**
	 * 重置定制页面
	 *
	 * @param int $codeId
	 */
	public function pageReset_action($site, $id, $page) {
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
		$code = $modelCode->lastByName($site, $mission->{$page . '_page_name'});
		$rst = $modelCode->modify($code->id, $data);

		return new \ResponseData($rst);
	}
}