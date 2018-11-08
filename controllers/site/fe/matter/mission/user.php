<?php
namespace site\fe\matter\mission;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 项目用户
 */
class user extends \site\fe\matter\base {
	/**
	 *
	 */
	public function get_action($mission) {
		$oMission = $this->model('matter\mission')->byId($mission, ['fields' => 'id,entry_rule,user_app_type,user_app_id']);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}
		$modelMisUsr = $this->model('matter\mission\user');

		$oMisUser = $modelMisUsr->byId($oMission, $this->who->uid);

		return new \ResponseData($oMisUser);
	}
	/**
	 * 更新用户设置
	 */
	public function updateCustom_action($mission) {
		$oMission = $this->model('matter\mission')->byId($mission, ['fields' => 'id,entry_rule,user_app_type,user_app_id']);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}
		$modelMisUsr = $this->model('matter\mission\user');
		$oMisUser = $modelMisUsr->byId($oMission, $this->who->uid, ['fields' => 'id,custom']);
		if (false === $oMisUser) {
			$oMisUser = $modelMisUsr->add($oMission, $this->who);
			$oMisUser->custom = new \stdClass;
		}
		$oPosted = $this->getPostJson();
		foreach ($oPosted as $prop => $val) {
			switch ($prop) {
			case 'main':
			case 'board':
				$oPurifiedVal = new \stdClass;
				if (is_object($val)) {
					foreach ($val as $prop2 => $val2) {
						switch ($prop2) {
						case 'nav':
							if (is_object($val2)) {
								$oPurifiedVal->nav = new \stdClass;
								foreach ($val2 as $prop3 => $val3) {
									switch ($prop3) {
									case 'stopTip':
										$oPurifiedVal->nav->stopTip = is_bool($val3) ? $val3 : false;
										break;
									}
								}
							}
							break;
						}
					}
				}
				break;
			}
			$oMisUser->custom->{$prop} = $oPurifiedVal;
		}

		$modelMisUsr->update(
			'xxt_mission_user',
			['custom' => $modelMisUsr->escape($modelMisUsr->toJson($oMisUser->custom))],
			['id' => $oMisUser->id]
		);

		return new \ResponseData('ok');
	}
	/**
	 * 获得指定项目的用户排行
	 *
	 * @param int $mission
	 */
	public function rank_action($mission, $page = 1, $size = 36) {
		$oMission = $this->model('matter\mission')->byId($mission, ['fields' => 'id,entry_rule,user_app_type,user_app_id']);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}

		/* 如果项目用户名单是分组活动，获得分组信息 */
		if ($oMission->user_app_type === 'group' && !empty($oMission->user_app_id)) {
			$oMisUsrGrpApp = (object) ['id' => $oMission->user_app_id];
			$modelGrpUsr = $this->model('matter\group\player');
		}

		$modelMisUsr = $this->model('matter\mission\user');

		$fields = 'userid,group_id,nickname,score';
		$q = [
			$fields,
			'xxt_mission_user',
			['mission_id' => $oMission->id],
		];
		$q2 = [
			'o' => 'score desc',
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];

		$oResult = new \stdClass;
		$oResult->users = $modelMisUsr->query_objs_ss($q, $q2);
		if (count($oResult->users)) {
			$modelGrpRnd = $this->model('matter\group\round');
			$modelSiteAct = $this->model('site\user\account');
			foreach ($oResult->users as $oUser) {
				/* user */
				$oSiteUsr = $modelSiteAct->byId($oUser->userid, ['fields' => 'headimgurl']);
				if ($oSiteUsr) {
					$oUser->headimgurl = $oSiteUsr->headimgurl;
				}
				/* group */
				if (!empty($oUser->group_id)) {
					$oGrpRnd = $modelGrpRnd->byId($oUser->group_id, ['fields' => 'title']);
					if ($oGrpRnd) {
						$oUser->group = (object) ['id' => $oUser->group_id, 'title' => $oGrpRnd->title];
					}
				} else if (isset($oMisUsrGrpApp)) {
					$oGrpUsr = $modelGrpUsr->byUser($oMisUsrGrpApp, $oUser->userid, ['fields' => 'round_id,round_title', 'onlyOne' => true]);
					if ($oGrpUsr) {
						$oUser->group = (object) ['id' => $oGrpUsr->round_id, 'title' => $oGrpUsr->round_title];
					}
				}
				unset($oUser->group_id);
			}
		}
		$q[0] = 'count(*)';
		$oResult->total = $modelMisUsr->query_val_ss($q);

		return new \ResponseData($oResult);
	}
}