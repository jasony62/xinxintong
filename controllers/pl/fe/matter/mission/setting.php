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
	public function update_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		/* data */
		$nv = $this->getPostJson();

		if (isset($nv->extattrs)) {
			$nv->extattrs = $modelMis->escape($modelMis->toJson($nv->extattrs));
		}
		/* modifier */
		$nv->modifier = $user->id;
		$nv->modifier_src = $user->src;
		$nv->modifier_name = $modelMis->escape($user->name);
		$nv->modify_at = time();

		/* update */
		$rst = $modelMis->update('xxt_mission', $nv, ["id" => $id]);
		if ($rst) {
			$mission = $modelMis->byId($id, 'id,siteid,title,summary,pic');
			/*记录操作日志*/
			$this->model('matter\log')->matterOp($mission->siteid, $user, $mission, 'U');
			/*更新acl*/
			$mission = $this->model('matter\mission\acl')->updateMission($mission);
		}

		return new \ResponseData($rst);
	}
}