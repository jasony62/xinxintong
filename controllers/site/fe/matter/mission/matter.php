<?php
namespace site\fe\matter\mission;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 项目素材
 */
class matter extends \site\fe\matter\base {
	/**
	 * 获得指定项目的项目资料，包括：单图文，多图文和频道
	 *
	 * @param int $mission
	 */
	public function docList_action($mission) {
		$oMission = $this->model('matter\mission')->byId($mission, ['fields' => 'id,title,summary,pic']);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}

		$aMatterTypes = ['article', 'link', 'channel'];
		$docs = $this->model('matter\mission\matter')->byMission($oMission->id, $aMatterTypes, ['is_public' => 'Y']);

		return new \ResponseData($docs);
	}
	/**
	 * 获得指定项目下登记活动中的推荐内容
	 *
	 * @param int $mission
	 */
	public function agreedList_action($mission, $page = 1, $size = 12) {
		$oMission = $this->model('matter\mission')->byId($mission, ['fields' => 'id,entry_rule']);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}

		/* 如果项目用户名单是分组活动，获得分组信息 */
		if (!empty($oMission->entry_rule->group->id)) {
			$oMisUsrGrpApp = (object) ['id' => $oMission->entry_rule->group->id];
			$modelGrpUsr = $this->model('matter\group\player');
		}

		$modelMisMat = $this->model('matter\mission\matter');
		$matterType = 'enroll';
		$options = ['fields' => 'matter_id'];
		$aEnlApps = $modelMisMat->byMission($oMission->id, $matterType, $options, 'N');
		$aids = [];
		foreach ($aEnlApps as $oEnlApp) {
			$aids[] = $oEnlApp->matter_id;
		}
		$fields = 'aid,schema_id,enroll_key,userid,group_id,submit_at,value,supplement,tag,score,remark_num,like_num';
		$q = [$fields, 'xxt_enroll_record_data', ['aid' => $aids, 'agreed' => 'Y']];
		$q2 = [
			'o' => 'submit_at desc',
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];

		$result = new \stdClass;
		$aRecDatas = $modelMisMat->query_objs_ss($q, $q2);
		if (count($aRecDatas)) {
			$oEnlAppsById = new \stdClass;
			$modelEnl = $this->model('matter\enroll');
			$modelEnlUsr = $this->model('matter\enroll\user');
			$modelGrpRnd = $this->model('matter\group\round');
			$modelSiteAct = $this->model('site\user\account');
			$oOptions = ['cascaded' => 'N', 'fields' => 'id,title,data_schemas'];
			foreach ($aRecDatas as $oRecData) {
				/* app */
				if (isset($oEnlAppsById->{$oRecData->aid})) {
					$oEnlApp = $oEnlAppsById->{$oRecData->aid};
				} else {
					$oEnlApp = $oEnlAppsById->{$oRecData->aid} = $modelEnl->byId($oRecData->aid, $oOptions);
				}
				$oRecData->app = (object) ['id' => $oEnlApp->id, 'title' => $oEnlApp->title];
				unset($oRecData->aid);
				/* value */
				foreach ($oEnlApp->dataSchemas as $oSchema) {
					if ($oRecData->schema_id === $oSchema->id) {
						$oRecData->schema = (object) ['id' => $oSchema->id, 'type' => $oSchema->type, 'title' => $oSchema->title];
						unset($oRecData->schema_id);
						$oRecData->value = $this->_convertRecData($oRecData->value, $oSchema);
						break;
					}
				}
				/* user */
				$oEnlUser = $modelEnlUsr->byId($oEnlApp, $oRecData->userid, ['fields' => 'nickname,group_id']);
				if ($oEnlUser) {
					$oRecData->nickname = $oEnlUser->nickname;
					$oSiteUsr = $modelSiteAct->byId($oRecData->userid, ['fields' => 'headimgurl']);
					if ($oSiteUsr) {
						$oRecData->headimgurl = $oSiteUsr->headimgurl;
					}
				}
				/* group */
				if (!empty($oRecData->group_id)) {
					$oGrpRnd = $modelGrpRnd->byId($oRecData->group_id, ['fields' => 'title']);
					if ($oGrpRnd) {
						$oRecData->group = (object) ['id' => $oRecData->group_id, 'title' => $oGrpRnd->title];
					}
				} else if (!empty($oEnlUser->group_id)) {
					$oGrpRnd = $modelGrpRnd->byId($oEnlUser->group_id, ['fields' => 'title']);
					if ($oGrpRnd) {
						$oRecData->group = (object) ['id' => $oEnlUser->group_id, 'title' => $oGrpRnd->title];
					}
				} else if (isset($oMisUsrGrpApp)) {
					$oGrpUsr = $modelGrpUsr->byUser($oMisUsrGrpApp, $oRecData->userid, ['fields' => 'round_id,round_title', 'onlyOne' => true]);
					if ($oGrpUsr) {
						$oRecData->group = (object) ['id' => $oGrpUsr->round_id, 'title' => $oGrpUsr->round_title];
					}
				}
				unset($oRecData->group_id);
			}
		}
		$result->agreed = $aRecDatas;
		$q[0] = 'count(*)';
		$result->total = $modelMisMat->query_val_ss($q);

		return new \ResponseData($result);
	}
	/**
	 * 转换登记活动的值
	 *
	 * @param string $value
	 * @param object $oSchema
	 */
	private function _convertRecData($value, $oSchema) {
		switch ($oSchema->type) {
		case 'single':
			$bMatched = false;
			if (!empty($oSchema->ops)) {
				foreach ($oSchema->ops as $op) {
					if ($op->v === $value) {
						$bMatched = true;
						$converted = $op->l;
						break;
					}
				}
			}
			if (false === $bMatched) {
				$converted = '';
			}
			break;
		case 'multiple':
			break;
		case 'file':
			$converted = json_decode($value);
			break;
		default:
			$converted = $value;
		}

		return $converted;
	}
}