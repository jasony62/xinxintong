<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记数据
 */
class data extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function agree_action($ek, $schema, $value = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelData = $this->model('matter\enroll\data');
		if (!in_array($value, ['Y', 'N', 'A'])) {
			$value = '';
		}

		$rst = $modelData->update(
			'xxt_enroll_record_data',
			['agreed' => $value],
			['enroll_key' => $ek, 'schema_id' => $schema, 'state' => 1]
		);

		return new \ResponseData($rst);
	}
	/**
	 * 返回指定登记项的活动登记名单
	 */
	public function list4Schema_action($app, $page = 1, $size = 12) {
		if (false === ($oLoginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['fields' => 'id,data_schemas', 'cascaded' => 'N']);
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

		$oUser = new \stdClass;

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
}