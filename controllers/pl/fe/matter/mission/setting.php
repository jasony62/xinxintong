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
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$mission = $modelMis->byId($id, 'id,siteid,title,summary,pic');
		/*data*/
		$nv = $this->getPostJson();
		foreach ($nv as $n => $v) {
			if (in_array($n, ['extattrs'])) {
				$nv[$n] = $modelMis->escape($modelMis->toJson($v));
			}
		}
		/*modifier*/
		$nv->modifier = $user->id;
		$nv->modifier_src = $user->src;
		$nv->modifier_name = $modelMis->escape($user->name);
		$nv->modify_at = time();
		/*update*/
		$rst = $modelMis->update('xxt_mission', $nv, ["id" => $id]);
		if ($rst) {
			/*记录操作日志*/
			$mission->type = 'mission';
			$this->model('log')->matterOp($mission->siteid, $user, $mission, 'U');
			/*更新acl*/
			$mission = $modelMis->escape($mission);
			$mission = $this->model('matter\mission\acl')->updateMission($mission);
		}

		return new \ResponseData($rst);
	}
}