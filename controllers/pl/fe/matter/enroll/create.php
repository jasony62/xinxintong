<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/**
 * 创建记录活动
 */
class create extends main_base {
	/**
	 * 创建指定活动指定题目的打分活动
	 * 例如：给答案打分
	 */
	public function schemaScore_action($app, $schema) {
		if (false === ($oCreator = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$oSourceApp = $modelApp->byId($app, 'id,siteid,title,summary,pic,scenario,start_at,end_at,mission_id,data_schemas');
		if (false === $oSourceApp || $oSourceApp->state !== 1) {
			return new \ObjectNotFoundError();
		}
		$modelSch = $this->model('matter\enroll\schema');
		$oSourceSchema = $modelSch->asAssoc($oSourceApp->dataSchemas, ['filter' => function ($oSchema) {return $oSchema->id === $schema;}], true);
		if (count($oSourceSchema) !== 1) {
			return new \ResponseError('指定的题目不存在');
		}
		$oSourceSchema = array_values($oSourceSchema)[0];

		$oSite = $this->model('site')->byId($oSourceApp->siteid, ['fields' => 'id,heading_pic']);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}

		if (empty($oSourceApp->mission_id)) {
			$oMission = null;
		} else {
			$modelMis = $this->model('matter\mission');
			$oMission = $modelMis->byId($oSourceApp->mission_id);
			if (false === $oMission) {
				return new \ObjectNotFoundError();
			}
		}

		//$oCustomConfig = $this->getPostJson();
		$oCustomConfig = new \stdClass;
		// 不按照模板生成题目
		$this->setDeepValue($oCustomConfig, 'proto.schema.default.empty', true);

		$oNewApp = $modelApp->createByTemplate($oCreator, $oSite, $oCustomConfig, $oMission);

		$aDataSchemas = [];
		$modelApp->modify($oCreator, $oNewApp, ['data_schemas' => $this->escape($modelApp->toJson($aDataSchemas))]);

		return new \ResponseData($oNewApp);
	}
}