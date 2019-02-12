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
			return new \ResponseError('没有项目访问权限');
		}
		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission, ['fields' => 'id,siteid,user_app_id,user_app_type']);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}

		$oCriteria = $this->getPostJson();

		$aOptions = [];
		$aOptions = ['fields' => 'userid,nickname,group_id,score,user_total_coin,entry_num,last_entry_at,total_elapse,enroll_num,last_enroll_at,do_remark_num,last_do_remark_at,do_like_num,last_do_like_at,agree_num,last_agree_at,signin_num,last_signin_at'];

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
			/* 微信公众号信息 */
			$modelAnt = $this->model('site\user\account');
			$modelWxfan = $this->model('sns\wx\fan');
			$fnHander = function ($oMission, $oEnrollee) use ($modelAnt, $modelWxfan) {
				$oSiteUser = $modelAnt->byId($oEnrollee->userid, ['wx_openid']);
				if ($oSiteUser) {
					$oWxfan = $modelWxfan->byOpenid($oMission->siteid, $oSiteUser->wx_openid, 'nickname,headimgurl', 'Y');
					if ($oWxfan) {
						$oEnrollee->wxfan = $oWxfan;
					}
				}
			};
			$aHandlers[] = $fnHander;
			/* 项目通讯录用户 */
			$modelMs = $this->model('site\user\memberschema');
			$modelMem = $this->model('site\user\member');
			$mschemas = $modelMs->bySite($oMission->siteid, 'Y', ['onlyMatter' => 'Y', 'matter' => $oMission]);
			if (count($mschemas)) {
				$mschemaIds = [];
				foreach ($mschemas as $oMschema) {
					$mschemaIds[] = $oMschema->id;
				}
				$fnHander = function ($oMission, $oEnrollee) use ($modelMem, $mschemaIds) {
					$members = $modelMem->byUser($oEnrollee->userid, ['fields' => 'id,name,email,mobile', 'schemas' => $mschemaIds]);
					if (count($members)) {
						$oEnrollee->members = $members;
					}
				};
			}
			$aHandlers[] = $fnHander;
			/* 填充分组信息 */
			if (!empty($oMission->user_app_type) && $oMission->user_app_type === 'group') {
				if (!empty($oMission->user_app_id)) {
					$modelGrpTeam = $this->model('matter\group\team');
					$fnHander = function ($oMission, $oEnrollee) use ($modelGrpTeam) {
						if (empty($oEnrollee->group_id)) {
							return;
						}
						$oGrpRnd = $modelGrpTeam->byId($oEnrollee->group_id, ['fields' => 'title']);
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

		$oResult = new \stdClass;
		$oResult->enrollees = $enrollees;

		return new \ResponseData($oResult);
	}
	/**
	 * 更新用户在项目中的累计得分数据
	 */
	public function renewScore_action($mission) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/* 检查权限 */
		$modelAcl = $this->model('matter\mission\acl');
		if (false === ($acl = $modelAcl->byCoworker($mission, $oUser->id))) {
			return new \ResponseError('没有项目访问权限');
		}
		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission, ['fields' => 'id,siteid,user_app_id,user_app_type']);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}
		$modelMisUsr = $this->model('matter\mission\user');
		$misUsers = $modelMisUsr->enrolleeByMission($oMission, ['fields' => 'id,userid']);
		if (empty($misUsers)) {
			return new \ObjectNotFoundError('项目中没有用户，不需要进行更新');
		}

		$matters = $this->model('matter\mission\matter')->byMission($oMission->id, ['enroll'], [], 'N');
		if (empty($matters)) {
			return new \ObjectNotFoundError('项目中没有记录活动，不需要进行更新');
		}

		/*清空现有的得分*/
		foreach ($misUsers as $oMisUser) {
			$modelMisUsr->update('xxt_mission_user', ['score' => 0], ['id' => $oMisUser->id]);
		}

		$modelEnlUsr = $this->model('matter\enroll\user');
		foreach ($matters as $oMatter) {
			$oEnlApp = (object) ['id' => $oMatter->matter_id];
			foreach ($misUsers as $oMisUser) {
				$oEnlUser = $modelEnlUsr->byId($oEnlApp, $oMisUser->userid, ['fields' => 'score']);
				if ($oEnlUser) {
					$modelMisUsr->update('xxt_mission_user', ['score' => (object) ['op' => '+=', 'pat' => $oEnlUser->score]], ['id' => $oMisUser->id]);
				}
			}
		}

		return new \ResponseData('ok');
	}
}