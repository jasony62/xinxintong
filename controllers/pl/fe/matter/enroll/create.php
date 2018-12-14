<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/**
 * 创建记录活动
 */
class create extends main_base {
	/**
	 * 根据系统模板创建记录活动
	 *
	 * @param string $site site's id
	 * @param string $mission mission's id
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 *
	 */
	public function bySysTemplate_action($site, $mission = null, $scenario = 'common', $template = 'simple') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}
		if (empty($mission)) {
			$oMission = null;
		} else {
			$modelMis = $this->model('matter\mission');
			$oMission = $modelMis->byId($mission);
			if (false === $oMission) {
				return new \ObjectNotFoundError();
			}
		}
		$modelApp = $this->model('matter\enroll')->setOnlyWriteDbConn(true);

		$oCustomConfig = $this->getPostJson();

		$oNewApp = $modelApp->createByTemplate($oUser, $oSite, $oCustomConfig, $oMission, $scenario, $template);

		return new \ResponseData($oNewApp);
	}
	/**
	 * 创建指定活动指定题目的打分活动
	 * 例如：给答案打分
	 */
	public function scoreSchema_action($app, $schema) {
		if (false === ($oCreator = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$oSourceApp = $modelApp->byId($app, 'id,siteid,title,summary,pic,scenario,start_at,end_at,mission_id,sync_mission_round,data_schemas');
		if (false === $oSourceApp || $oSourceApp->state !== '1') {
			return new \ObjectNotFoundError('指定的活动不存在');
		}
		$modelSch = $this->model('matter\enroll\schema');
		$oSourceSchema = $modelSch->asAssoc($oSourceApp->dataSchemas, ['filter' => function ($oSchema) use ($schema) {return $oSchema->id === $schema;}], true);
		if (count($oSourceSchema) !== 1) {
			return new \ResponseError('指定的题目不存在');
		}
		$oSourceSchema = array_values($oSourceSchema)[0];

		$oSite = $this->model('site')->byId($oSourceApp->siteid, ['fields' => 'id,heading_pic']);
		if (false === $oSite) {
			return new \ObjectNotFoundError('活动所属团队不存在');
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

		$oProto = $this->getPostJson();

		$oCustomConfig = new \stdClass;
		$this->setDeepValue($oCustomConfig, 'proto.title', $this->getDeepValue($oProto, 'title', $oSourceApp->title . ' - ' . $oSourceSchema->title . '（打分）'));
		$this->setDeepValue($oCustomConfig, 'proto.sync_mission_round', $oSourceApp->sync_mission_round);
		// 不按照模板生成题目
		$this->setDeepValue($oCustomConfig, 'proto.schema.default.empty', true);

		$oNewApp = $modelApp->createByTemplate($oCreator, $oSite, $oCustomConfig, $oMission);

		$oNewSchema = new \stdClass;

		$oNewSchema->dsSchema = (object) [
			'app' => (object) ['id' => $oSourceApp->id, 'title' => $oSourceApp->title],
			'schema' => (object) ['id' => $oSourceSchema->id, 'title' => $oSourceSchema->title, 'type' => $oSourceSchema->type],
		];
		$oNewSchema->id = 's' . uniqid();
		$oNewSchema->required = "Y";
		$oNewSchema->type = "score";
		$oNewSchema->unique = "N";

		$oNewSchema->title = "打分题";
		$oNewSchema->range = [1, 5];
		if (empty($oProto->schema->ops)) {
			$oNewSchema->ops = [(object) ['l' => '打分项1', 'v' => 'v1'], (object) ['l' => '打分项2', 'v' => 'v2']];
		} else {
			foreach ($oProto->schema->ops as $index => $oOp) {
				$seq = ++$index;
				$oNewSchema->ops[] = (object) ['l' => $this->getDeepValue($oOp, 'l', '打分项' . $seq), 'v' => 'v' . $seq];
			}
		}

		$modelApp->modify($oCreator, $oNewApp, (object) ['data_schemas' => $this->escape($modelApp->toJson([$oNewSchema]))]);

		$oSourceSchema->scoreApp = (object) ['id' => $oNewApp->id, 'schema' => (object) ['id' => $oNewSchema->id]];
		$modelApp->modify($oCreator, $oSourceApp, (object) ['data_schemas' => $this->escape($modelApp->toJson($oSourceApp->dataSchemas))]);

		return new \ResponseData($oSourceSchema);
	}
}