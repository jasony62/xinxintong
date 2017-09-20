<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目用户控制器
 */
class user extends \pl\fe\matter\base {
	/**
	 * 项目的参与人列表
	 */
	public function enrolleeList_action($mission) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/* 检查权限 */
		$modelAcl = $this->model('matter\mission\acl');
		if (false === ($acl = $modelAcl->byCoworker($mission, $oUser->id))) {
			return new \ResponseError('项目不存在');
		}
		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission, ['fields' => 'id,user_app_id,user_app_type']);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}

		$oCriteria = $this->getPostJson();

		$aOptions = [];
		$aOptions = ['fields' => 'userid,nickname,group_id,user_total_coin,enroll_num,last_enroll_at,remark_other_num,last_remark_other_at,like_other_num,last_like_other_at,signin_num,last_signin_at'];

		/* filter */
		if (!empty($oCriteria->filter->by) && !empty($oCriteria->filter->keyword)) {
			$aOptions['filter'] = $oCriteria->filter;
		}

		/* order by */
		if (!empty($oCriteria->orderBy)) {
			$aOptions['orderBy'] = $oCriteria->orderBy;
		}

		$modelMisUsr = $this->model('matter\mission\user');
		$enrollees = $modelMisUsr->enrolleeByMission($oMission, $aOptions);
		if (count($enrollees)) {
			$aHandlers = [];
			if (!empty($oMission->user_app_type) && $oMission->user_app_type === 'group') {
				/* 填充分组信息 */
				if (!empty($oMission->user_app_id)) {
					$modelGrpRnd = $this->model('matter\group\round');
					$fnHander = function ($oMission, $oEnrollee) use ($modelGrpRnd) {
						if (empty($oEnrollee->group_id)) {
							return;
						}
						$oGrpRnd = $modelGrpRnd->byId($oEnrollee->group_id, ['fields' => 'title']);
						if ($oGrpRnd) {
							$oEnrollee->group = $oGrpRnd;
						}
					};
					$aHandlers[] = $fnHander;
				}
			}
			foreach ($enrollees as $oEnrollee) {
				foreach ($aHandlers as $fnHander) {
					$fnHander($oMission, $oEnrollee);
				}
			}
		}

		$result = new \stdClass;
		$result->enrollees = $enrollees;

		return new \ResponseData($result);
	}
}