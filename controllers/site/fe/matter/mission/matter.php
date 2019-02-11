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
	 *
	 */
	private function _getEnlApp(&$oMisAgreed) {
		if (!isset($this->_modelEnlApp)) {
			$this->_modelEnlApp = $this->model('matter\enroll');
		}
		if (!isset($this->_enlAppsById)) {
			$this->_enlAppsById = new \stdClass;
		}
		if (isset($this->_enlAppsById->{$oMisAgreed->matter_id})) {
			$oMisAgreed->matter = $this->_enlAppsById->{$oMisAgreed->matter_id};
		} else {
			$oMatter = $this->_modelEnlApp->byId($oMisAgreed->matter_id, ['cascaded' => 'N', 'fields' => 'id,title,data_schemas']);
			unset($oMatter->data_schemas);
			unset($oMatter->pages);
			if (count($oMatter->dataSchemas)) {
				$oShareableSchemas = new \stdClass;
				foreach ($oMatter->dataSchemas as $oSchema) {
					if (isset($oSchema->shareable) && $oSchema->shareable === 'Y') {
						$oShareableSchemas->{$oSchema->id} = $oSchema;
					}
				}
				$oMatter->dataSchemas = $oShareableSchemas;
			}

			$oMisAgreed->matter = $this->_enlAppsById->{$oMatter->id} = $oMatter;
		}

		unset($oMisAgreed->matter_id);
		unset($oMisAgreed->matter_type);

		return $oMisAgreed;
	}
	/**
	 * 获得记录活动的记录
	 */
	private function _getEnlRecord(&$oMisAgreed) {
		if (!isset($this->_modelEnlRec)) {
			$this->_modelEnlRec = $this->model('matter\enroll\record');
		}
		$oMisAgreed->obj = $this->_modelEnlRec->byId($oMisAgreed->obj_key, ['fields' => 'nickname,data,enroll_at,like_num,like_log,remark_num,score']);
		foreach ($oMisAgreed->obj->data as $schemaId => $value) {
			if (!isset($oMisAgreed->matter->dataSchemas->{$schemaId})) {
				unset($oMisAgreed->obj->data->{$schemaId});
			} else {
				$oSchema = $oMisAgreed->matter->dataSchemas->{$schemaId};
				switch ($oSchema->type) {
				case 'image':
					$oMisAgreed->obj->data->{$schemaId} = explode(',', $oMisAgreed->obj->data->{$schemaId});
					break;
				}
			}
		}

		return $oMisAgreed;
	}
	/**
	 * 获得记录活动的记录的题目
	 */
	private function _getEnlRecData(&$oMisAgreed) {
		if (!isset($this->_modelEnlDat)) {
			$this->_modelEnlDat = $this->model('matter\enroll\data');
		}

		$oRecData = $this->_modelEnlDat->byId($oMisAgreed->obj_data_id);
		if (false === $oRecData) {
			return false;
		}
		$oData = new \stdClass;
		$oMisAgreed->obj = new \stdClass;
		$oMisAgreed->obj->enroll_at = $oRecData->submit_at;
		$oMisAgreed->obj->like_num = $oRecData->like_num;
		$oMisAgreed->obj->like_log = $oRecData->like_log;
		$oMisAgreed->obj->remark_num = $oRecData->remark_num;
		$oMisAgreed->obj->score = $oRecData->score;
		$oMisAgreed->obj->schema_id = $oRecData->schema_id;
		$oData->{$oRecData->schema_id} = $oRecData->value;
		$oMisAgreed->obj->data = $oData;

		$oMatter = clone $oMisAgreed->matter;
		$dataSchemas = clone $oMatter->dataSchemas;
		foreach ($dataSchemas as $schemaId => $oSchema) {
			if ($schemaId !== $oRecData->schema_id) {
				unset($dataSchemas->{$schemaId});
			}
		}
		$oMatter->dataSchemas = $dataSchemas;
		$oMisAgreed->matter = $oMatter;

		if (isset($dataSchemas->{$oRecData->schema_id})) {
			$oSchema = $dataSchemas->{$oRecData->schema_id};
			switch ($oSchema->type) {
			case 'file':
				$oData->{$oSchema->id} = json_decode($oData->{$oSchema->id});
				break;
			case 'image':
				$oData->{$oSchema->id} = explode(',', $oData->{$oSchema->id});
				break;
			}
		}

		if (!isset($this->_modelEnlUsr)) {
			$this->_modelEnlUsr = $this->model('matter\enroll\user');
		}
		$oEnlUser = $this->_modelEnlUsr->byId($oMatter, $oRecData->userid, ['fields' => 'nickname,group_id']);
		if ($oEnlUser) {
			$oMisAgreed->obj->nickname = $oEnlUser->nickname;
		}

		return $oMisAgreed;
	}
	/**
	 * 获得指定项目下记录活动中的推荐内容
	 *
	 * @param int $mission
	 */
	public function agreedList_action($mission, $page = 1, $size = 12) {
		$oMission = $this->model('matter\mission')->byId($mission, ['fields' => 'id,entry_rule']);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}

		$modelMisMat = $this->model('matter\mission\matter');
		$q = ['matter_id,matter_type,obj_unit,obj_key,obj_data_id,op_at', 'xxt_mission_agreed', ['mission_id' => $oMission->id]];
		$q2 = ['o' => 'op_at desc'];
		$misAgreeds = $modelMisMat->query_objs_ss($q, $q2);
		if (count($misAgreeds)) {
			foreach ($misAgreeds as $oMisAgreed) {
				switch ($oMisAgreed->matter_type) {
				case 'enroll':
					$this->_getEnlApp($oMisAgreed);
					if ($oMisAgreed->obj_unit === 'R') {
						$this->_getEnlRecord($oMisAgreed);
					} else if ($oMisAgreed->obj_unit === 'D') {
						$this->_getEnlRecData($oMisAgreed);
					}
					break;
				}
			}
		}

		$oResult = new \stdClass;
		$oResult->agreed = $misAgreeds;

		return new \ResponseData($oResult);
	}
	/**
	 * 获得指定项目下记录活动中的推荐内容
	 *
	 * @param int $mission
	 */
	public function agreedList2_action($mission, $page = 1, $size = 12) {
		$oMission = $this->model('matter\mission')->byId($mission, ['fields' => 'id,entry_rule,user_app_id,user_app_type']);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}

		/* 如果项目用户名单是分组活动，获得分组信息 */
		if ($oMission->user_app_type === 'group' && !empty($oMission->user_app_id)) {
			$oMisUsrGrpApp = (object) ['id' => $oMission->user_app_id];
			$modelGrpUsr = $this->model('matter\group\record');
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
			$modelGrpTeam = $this->model('matter\group\team');
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
					$oGrpRnd = $modelGrpTeam->byId($oRecData->group_id, ['fields' => 'title']);
					if ($oGrpRnd) {
						$oRecData->group = (object) ['id' => $oRecData->group_id, 'title' => $oGrpRnd->title];
					}
				} else if (!empty($oEnlUser->group_id)) {
					$oGrpRnd = $modelGrpTeam->byId($oEnlUser->group_id, ['fields' => 'title']);
					if ($oGrpRnd) {
						$oRecData->group = (object) ['id' => $oEnlUser->group_id, 'title' => $oGrpRnd->title];
					}
				} else if (isset($oMisUsrGrpApp)) {
					$oGrpUsr = $modelGrpUsr->byUser($oMisUsrGrpApp, $oRecData->userid, ['fields' => 'team_id,team_title', 'onlyOne' => true]);
					if ($oGrpUsr) {
						$oRecData->group = (object) ['id' => $oGrpUsr->team_id, 'title' => $oGrpUsr->team_title];
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
	 * 转换记录活动的值
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