<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录数据汇总
 */
class repos extends base {
	/**
	 * 返回指定登记项的活动登记名单
	 */
	public function list4Schema_action($app, $page = 1, $size = 12) {
		$oUser = $this->who;

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,data_schemas', 'cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		// 登记数据过滤条件
		$oCriteria = $this->getPostJson();

		// 登记记录过滤条件
		$oOptions = new \stdClass;
		$oOptions->page = $page;
		$oOptions->size = $size;

		!empty($oCriteria->keyword) && $oOptions->keyword = $oCriteria->keyword;
		!empty($oCriteria->rid) && $oOptions->rid = $oCriteria->rid;
		!empty($oCriteria->agreed) && $oOptions->agreed = $oCriteria->agreed;
		!empty($oCriteria->owner) && $oOptions->owner = $oCriteria->owner;
		!empty($oCriteria->userGroup) && $oOptions->userGroup = $oCriteria->userGroup;
		!empty($oCriteria->tag) && $oOptions->tag = $oCriteria->tag;
		if (empty($oCriteria->schema)) {
			$oOptions->schemas = [];
			foreach ($oApp->dataSchemas as $dataSchema) {
				if (isset($dataSchema->shareable) && $dataSchema->shareable === 'Y') {
					$oOptions->schemas[] = $dataSchema->id;
				}
			}
			if (empty($oOptions->schemas)) {
				return new \ResponseData(['total' => 0]);
			}
		} else {
			$oOptions->schemas = [$oCriteria->schema];
		}

		// 查询结果
		$mdoelData = $this->model('matter\enroll\data');
		$result = $mdoelData->byApp($oApp, $oUser, $oOptions);
		if (count($result->records)) {
			$modelRem = $this->model('matter\enroll\remark');
			foreach ($result->records as &$oRec) {
				if ($oRec->remark_num) {
					$agreedRemarks = $modelRem->listByRecord($oUser, $oRec->enroll_key, $oRec->schema_id, $page = 1, $size = 10, ['agreed' => 'Y', 'fields' => 'id,content,create_at,nickname,like_num,like_log']);
					if ($agreedRemarks->total) {
						$oRec->agreedRemarks = $agreedRemarks;
					}
				}
				$oRec->tag = empty($oRec->tag) ? [] : json_decode($oRec->tag);
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 返回指定登记活动，指定登记项的填写内容
	 *
	 * @param string $app
	 * @param string $schema schema'id
	 * @param string $rid 轮次id，如果不指定为当前轮次，如果为ALL，所有轮次
	 * @param string $onlyMine 只返回当前用户自己的
	 *
	 */
	public function dataBySchema_action($app, $schema, $rid = '', $onlyMine = 'N', $page = 1, $size = 10) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		if (empty($oApp->dataSchemas)) {
			return new \ResponseError('活动【' . $oApp->title . '】没有定义登记项');
		}
		foreach ($oApp->dataSchemas as $dataSchema) {
			if ($dataSchema->id === $schema) {
				$oSchema = $dataSchema;
				break;
			}
		}
		if (!isset($oSchema)) {
			return new \ObjectNotFoundError();
		}

		$modelData = $this->model('matter\enroll\data');
		$options = new \stdClass;
		$options->rid = $rid;
		$options->page = $page;
		$options->size = $size;
		if ($onlyMine === 'Y') {
			//$options->userid = $this->who->uid;
		}
		$result = $modelData->bySchema($oApp, $oSchema, $options);

		return new \ResponseData($result);
	}
	/**
	 * 返回指定登记项的活动登记名单
	 */
	public function list4Record_action($app, $page = 1, $size = 12) {
		$oUser = $this->who;

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,scenario,assigned_nickname,data_schemas', 'cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		// 登记数据过滤条件
		$oCriteria = $this->getPostJson();

		// 登记记录过滤条件
		$oOptions = new \stdClass;
		$oOptions->page = $page;
		$oOptions->size = $size;

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$oResult = $mdoelRec->byApp($oApp, $oOptions);
		if (count($oResult->records)) {
			$aSchareableSchemas = [];
			foreach ($oApp->dataSchemas as $oSchema) {
				if (isset($oSchema->shareable) && $oSchema->shareable === 'Y') {
					$aSchareableSchemas[] = $oSchema->id;
				}
			}
			foreach ($oResult->records as $oRecord) {
				/* 清除非共享数据 */
				if (isset($oRecord->data)) {
					foreach ($oRecord->data as $schemaId => $value) {
						if (!in_array($schemaId, $aSchareableSchemas)) {
							unset($oRecord->data->{$schemaId});
						}
					}
				}
				/* 清除不必要的内容 */
				unset($oRecord->comment);
				unset($oRecord->verified);
				unset($oRecord->wx_openid);
				unset($oRecord->yx_openid);
				unset($oRecord->qy_openid);
				unset($oRecord->headimgurl);
			}
		}

		return new \ResponseData($oResult);
	}
}