<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记记录
 */
class record extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'index';
		$rule_action['actions'][] = 'get';

		return $rule_action;
	}
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 活动登记名单
	 *
	 */
	public function get_action($ek) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$mdoelRec = $this->model('matter\enroll\record');
		$record = $mdoelRec->byId($ek, ['verbose' => 'Y']);

		return new \ResponseData($record);
	}
	/**
	 * 活动登记名单
	 *
	 */
	public function list_action($site, $app, $page = 1, $size = 30, $orderby = null, $contain = null, $includeSignin = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		// 登记数据过滤条件
		$criteria = $this->getPostJson();

		// 登记记录过滤条件
		$options = array(
			'page' => $page,
			'size' => $size,
			'orderby' => $orderby,
			'contain' => $contain,
		);

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$oEnrollApp = $modelApp->byId($app);

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->byApp($oEnrollApp, $options, $criteria);
		if (!empty($result->records)) {
			$remarkables = [];
			foreach ($oEnrollApp->dataSchemas as $oSchema) {
				if (isset($oSchema->remarkable) && $oSchema->remarkable === 'Y') {
					$remarkables[] = $oSchema->id;
				}
			}
			if (count($remarkables)) {
				foreach ($result->records as &$oRec) {
					$modelRem = $this->model('matter\enroll\data');
					$oRecordData = $modelRem->byRecord($oRec->enroll_key, ['schema' => $remarkables]);
					$oRec->verbose = new \stdClass;
					$oRec->verbose->data = $oRecordData;
				}
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 计算指定登记项所有记录的合计
	 * 若不指定登记项，则返回活动中所有数值型登记项的合集
	 * 若指定的登记项不是数值型，返回0
	 */
	public function sum4Schema_action($site, $app, $rid = 'ALL') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $enrollApp) {
			return new \ObjectNotFountError();
		}

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->sum4Schema($enrollApp, $rid);

		return new \ResponseData($result);
	}
	/**
	 * 已删除的活动登记名单
	 *
	 */
	public function recycle_action($site, $app, $page = 1, $size = 30, $rid = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记记录过滤条件
		$options = array(
			'page' => $page,
			'size' => $size,
			'rid' => $rid,
		);

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app);

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->recycle($site, $enrollApp, $options);

		return new \ResponseData($result);
	}
	/**
	 * 返回指定登记项的活动登记名单
	 *
	 */
	public function list4Schema_action($site, $app, $rid = null, $schema, $page = 1, $size = 10) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记数据过滤条件
		$criteria = $this->getPostJson();
		// 登记记录过滤条件
		$options = [
			'page' => $page,
			'size' => $size,
		];
		if (!empty($rid)) {
			$options['rid'] = $rid;
		}

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app);

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->list4Schema($enrollApp, $schema, $options);

		return new \ResponseData($result);
	}
	/**
	 * 手工添加登记信息
	 *
	 * @param string $app
	 */
	public function add_action($app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$modelEnl = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');

		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);

		/* 创建登记记录 */
		$ek = $modelRec->enroll($oApp);
		$record = [];
		$record['verified'] = isset($posted->verified) ? $posted->verified : 'N';
		$record['comment'] = isset($posted->comment) ? $posted->comment : '';
		if (isset($posted->tags)) {
			$record['tags'] = $posted->tags;
			$modelEnl->updateTags($oApp->id, $posted->tags);
		}
		$modelRec->update('xxt_enroll_record', $record, "enroll_key='$ek'");

		/* 记录登记数据 */
		$result = $modelRec->setData(null, $oApp, $ek, $posted->data, '', true, isset($posted->quizScore) ? $posted->quizScore : null);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oApp->siteid, $user, $oApp, 'add', $ek);

		/* 返回完整的记录 */
		$oNewRecord = $modelRec->byId($ek, ['verbose' => 'Y']);

		return new \ResponseData($oNewRecord);
	}
	/**
	 * 更新登记记录
	 *
	 * @param string $app
	 * @param $ek record's key
	 */
	public function update_action($site, $app, $ek) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$record = $this->getPostJson();
		$modelEnl = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');

		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);

		/* 更新记录数据 */
		$updated = new \stdClass;
		$updated->enroll_at = time();
		if (isset($record->comment)) {
			$updated->comment = $modelEnl->escape($record->comment);
		}
		if (isset($record->tags)) {
			$updated->tags = $modelEnl->escape($record->tags);
			$modelEnl->updateTags($oApp->id, $updated->tags);
		}
		if (isset($record->verified)) {
			$updated->verified = $modelEnl->escape($record->verified);
		}
		if (isset($record->rid)) {
			$updated->rid = $modelEnl->escape($record->rid);
		}
		if (isset($record->supplement)) {
			$updated->supplement = $modelEnl->toJson($record->supplement);
		}
		$modelEnl->update('xxt_enroll_record', $updated, ['enroll_key' => $ek]);
		/* 记录登记数据 */
		if (isset($record->data)) {
			$score = isset($record->quizScore) ? $record->quizScore : null;
			$userSite = $this->model('site\fe\way')->who($site);
			$modelRec->setData($userSite, $oApp, $ek, $record->data, '', false, $score);
		} else if (isset($record->quizScore)) {
			/* 只修改登记项的分值 */
			$oAfterScore = new \stdClass;
			$oAfterScore->sum = 0;
			$oBeforeScore = $modelRec->query_val_ss(['score', 'xxt_enroll_record', ['aid' => $app, 'enroll_key' => $ek, 'state' => 1]]);
			$oBeforeScore = empty($oBeforeScore) ? new stdClass : json_decode($oBeforeScore);
			foreach ($oApp->dataSchemas as $schema) {
				if (empty($schema->requireScore) || $schema->requireScore !== 'Y') {
					continue;
				}
				//主观题评分
				if (in_array($schema->type, ['single', 'multiple'])) {
					$oAfterScore->{$schema->id} = isset($oBeforeScore->{$schema->id}) ? $oBeforeScore->{$schema->id} : 0;
				} else {
					if (isset($record->quizScore->{$schema->id})) {
						$modelEnl->update('xxt_enroll_record_data', ['score' => $record->quizScore->{$schema->id}], ['enroll_key' => $ek, 'schema_id' => $schema->id, 'state' => 1]);
						$oAfterScore->{$schema->id} = $record->quizScore->{$schema->id};
					} else {
						$oAfterScore->{$schema->id} = 0;
					}
				}
				$oAfterScore->sum += (int) $oAfterScore->{$schema->id};
			}
			$newScore = $modelRec->toJson($oAfterScore);
			//更新record表
			$modelRec->update('xxt_enroll_record', ['score' => $newScore], ['aid' => $app, 'enroll_key' => $ek, 'state' => 1]);
		}

		/* 更新登记项数据的轮次 */
		if (isset($record->rid)) {
			$modelEnl->update('xxt_enroll_record_data', ['rid' => $modelEnl->escape($record->rid)], ['enroll_key' => $ek, 'state' => 1]);
		}
		if ($updated->verified === 'Y') {
			$this->_whenVerifyRecord($oApp, $ek);
		}

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oApp->siteid, $user, $oApp, 'update', $record);

		/* 返回完整的记录 */
		$oNewRecord = $modelRec->byId($ek, ['verbose' => 'Y']);

		return new \ResponseData($oNewRecord);
	}
	/**
	 * 根据reocrd_data中的数据，修复record中的data字段
	 */
	public function repair_action($ek) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek);
		if (false === $oRecord) {
			return new \ParameterError();
		}

		$q = [
			'schema_id,value',
			'xxt_enroll_record_data',
			['enroll_key' => $ek, 'state' => 1],
		];
		$schemaValues = $modelRec->query_objs_ss($q);

		$oRecordData = new \stdClass;
		foreach ($schemaValues as $schemaValue) {
			if (strlen($schemaValue->value)) {
				if ($jsonVal = json_decode($schemaValue->value)) {
					$oRecordData->{$schemaValue->schema_id} = $jsonVal;
				} else {
					$oRecordData->{$schemaValue->schema_id} = $schemaValue->value;
				}
			}
		}

		$sRecordData = $modelRec->escape($modelRec->toJson($oRecordData));

		$rst = $modelRec->update('xxt_enroll_record', ['data' => $sRecordData], ['enroll_key' => $ek]);

		return new \ResponseData($rst);
	}
	/**
	 * 删除一条登记信息
	 */
	public function remove_action($site, $app, $key) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\enroll\record')->remove($app, $key);

		// 记录操作日志
		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$this->model('matter\log')->matterOp($site, $user, $app, 'remove', $key);

		return new \ResponseData($rst);
	}
	/**
	 * 恢复一条登记信息
	 */
	public function restore_action($site, $app, $key) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\enroll\record')->restore($app, $key);

		// 记录操作日志
		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'restore', $key);

		return new \ResponseData($rst);
	}
	/**
	 * 清空登记信息
	 */
	public function empty_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\enroll\record')->clean($app);

		// 记录操作日志
		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'empty');

		return new \ResponseData($rst);
	}
	/**
	 * 所有记录通过审核
	 */
	public function verifyAll_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);

		$rst = $this->model()->update(
			'xxt_enroll_record',
			['verified' => 'Y'],
			"aid='{$app->id}'"
		);

		// 记录操作日志
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'verify.all');

		return new \ResponseData($rst);
	}
	/**
	 * 指定记录通过审核
	 */
	public function batchVerify_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$eks = $posted->eks;

		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);

		$model = $this->model();
		foreach ($eks as $ek) {
			$rst = $model->update(
				'xxt_enroll_record',
				['verified' => 'Y'],
				"enroll_key='$ek'"
			);
			// 进行后续处理
			$this->_whenVerifyRecord($app, $ek);
		}

		// 记录操作日志
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'verify.batch', $eks);

		return new \ResponseData('ok');
	}
	/**
	 * 验证通过时，如果登记记录有对应的签到记录，且签到记录没有验证通过，那么验证通过
	 */
	private function _whenVerifyRecord(&$app, $enrollKey) {
		if ($app->mission_id) {
			$modelSigninRec = $this->model('matter\signin\record');
			$q = [
				'id',
				'xxt_signin',
				"enroll_app_id='{$app->id}'",
			];
			$signinApps = $modelSigninRec->query_objs_ss($q);
			if (count($signinApps)) {
				$enrollRecord = $this->model('matter\enroll\record')->byId(
					$enrollKey, ['fields' => 'userid,data', 'cascaded' => 'N']
				);
				if (!empty($enrollRecord->data)) {
					foreach ($signinApps as $signinApp) {
						// 更新对应的签到记录，如果签到记录已经审核通过就不更新
						$q = [
							'*',
							'xxt_signin_record',
							"state=1 and verified='N' and aid='$signinApp->id' and verified_enroll_key='{$enrollKey}'",
						];
						$signinRecords = $modelSigninRec->query_objs_ss($q);
						if (count($signinRecords)) {
							foreach ($signinRecords as $signinRecord) {
								if (empty($signinRecord->data)) {
									continue;
								}
								$signinData = json_decode($signinRecord->data);
								if ($signinData === null) {
									$signinData = new \stdClass;
								}
								foreach ($enrollData as $k => $v) {
									$signinData->{$k} = $v;
								}
								// 更新数据
								$modelSigninRec->delete('xxt_signin_record_data', "enroll_key='$signinRecord->enroll_key'");
								foreach ($signinData as $k => $v) {
									$ic = [
										'aid' => $app->id,
										'enroll_key' => $signinRecord->enroll_key,
										'name' => $k,
										'value' => $model->toJson($v),
									];
									$modelSigninRec->insert('xxt_signin_record_data', $ic, false);
								}
								// 验证通过
								$modelSigninRec->update(
									'xxt_signin_record',
									[
										'data' => $modelSigninRec->toJson($signinData),
									],
									"enroll_key='$signinRecord->enroll_key'"
								);
							}
						}
					}
				}
			}
		}

		return false;
	}
	/**
	 * 给记录批量添加标签
	 */
	public function batchTag_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$eks = $posted->eks;
		$tags = $posted->tags;

		/**
		 * 给记录打标签
		 */
		$modelRec = $this->model('matter\enroll\record');
		if (!empty($eks) && !empty($tags)) {
			foreach ($eks as $ek) {
				$record = $modelRec->byId($ek);
				$existent = $record->tags;
				if (empty($existent)) {
					$aNew = $tags;
				} else {
					$aExistent = explode(',', $existent);
					$aNew = array_unique(array_merge($aExistent, $tags));
				}
				$newTags = implode(',', $aNew);
				$modelRec->update('xxt_enroll_record', ['tags' => $newTags], "enroll_key='$ek'");
			}
		}
		/**
		 * 给应用打标签
		 */
		$this->model('matter\enroll')->updateTags($app, $posted->appTags);

		return new \ResponseData('ok');
	}
	/**
	 * 从关联的登记活动中查找匹配的记录
	 */
	public function matchEnroll_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$enrollRecord = $this->getPostJson();
		$result = [];

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (empty($enrollApp->enroll_app_id) || empty($enrollApp->data_schemas)) {
			return new \ParameterError();
		}

		// 匹配规则
		$isEmpty = true;
		$matchCriteria = new \stdClass;
		$schemas = json_decode($enrollApp->data_schemas);
		foreach ($schemas as $schema) {
			if (isset($schema->requireCheck) && $schema->requireCheck === 'Y') {
				if (isset($schema->fromApp) && $schema->fromApp === $enrollApp->enroll_app_id) {
					if (!empty($enrollRecord->{$schema->id})) {
						$matchCriteria->{$schema->id} = $enrollRecord->{$schema->id};
						$isEmpty = false;
					}
				}
			}
		}

		if (!$isEmpty) {
			// 查找匹配的数据
			$matchApp = $modelApp->byId($enrollApp->enroll_app_id, ['cascaded' => 'N']);
			$modelEnlRec = $this->model('matter\enroll\record');
			$matchRecords = $modelEnlRec->byData($matchApp, $matchCriteria);
			foreach ($matchRecords as $matchRec) {
				$result[] = $matchRec->data;
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 从关联的分组活动中查找匹配的记录
	 */
	public function matchGroup_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$enrollRecord = $this->getPostJson();
		$result = [];

		// 签到应用
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (empty($enrollApp->group_app_id) || empty($enrollApp->data_schemas)) {
			return new \ParameterError();
		}

		// 匹配规则
		$isEmpty = true;
		$matchCriteria = new \stdClass;
		$schemas = json_decode($enrollApp->data_schemas);
		foreach ($schemas as $schema) {
			if (isset($schema->requireCheck) && $schema->requireCheck === 'Y') {
				if (isset($schema->fromApp) && $schema->fromApp === $enrollApp->group_app_id) {
					if (!empty($enrollRecord->{$schema->id})) {
						$matchCriteria->{$schema->id} = $enrollRecord->{$schema->id};
						$isEmpty = false;
					}
				}
			}
		}

		if (!$isEmpty) {
			// 查找匹配的数据
			$groupApp = $this->model('matter\group')->byId($enrollApp->group_app_id, ['cascaded' => 'N']);
			$modelGrpRec = $this->model('matter\group\player');
			$matchedRecords = $modelGrpRec->byData($groupApp, $matchCriteria);
			foreach ($matchedRecords as $matchedRec) {
				if (isset($matchedRec->round_id)) {
					$matchedRec->data->_round_id = $matchedRec->round_id;
				}
				$result[] = $matchedRec->data;
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 登记数据导出
	 */
	public function export_action($site, $app, $rid = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$oApp = $this->model('matter\enroll')->byId($app, ['fields' => 'siteid,id,title,data_schemas,scenario,enroll_app_id,group_app_id,multi_rounds', 'cascaded' => 'N']);
		$schemas = json_decode($oApp->data_schemas);

		// 关联的登记活动
		if (!empty($oApp->enroll_app_id)) {
			$matchApp = $this->model('matter\enroll')->byId($oApp->enroll_app_id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$enrollSchemas = json_decode($matchApp->data_schemas);
			$mapOfAppSchemas = [];
			foreach ($schemas as $schema) {
				$mapOfAppSchemas[] = $schema->id;
			}
			foreach ($enrollSchemas as $schema) {
				if (!in_array($schema->id, $mapOfAppSchemas)) {
					$schemas[] = $schema;
				}
			}
		}
		// 关联的分组活动
		if (!empty($oApp->group_app_id)) {
			$matchApp = $this->model('matter\group')->byId($oApp->group_app_id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$groupSchemas = json_decode($matchApp->data_schemas);
			$mapOfAppSchemas = [];
			foreach ($schemas as $schema) {
				$mapOfAppSchemas[] = $schema->id;
			}
			foreach ($groupSchemas as $schema) {
				if (!in_array($schema->id, $mapOfAppSchemas)) {
					$schemas[] = $schema;
				}
			}
		}

		// 获得所有有效的登记记录
		$modelRec2 = $this->model('matter\enroll\record');
		$oEnrollApp = \TMS_APP::M('matter\enroll')->byId($app);
		//选择对应轮次
		$criteria = new \stdClass;
		$criteria->record = new \stdClass;
		$criteria->record->rid = new \stdClass;
		$criteria->record->rid = $rid;
		$result = $modelRec2->byApp($oApp, null, $criteria);
		if ($result->total === 0) {
			die('record empty');
		}

		if (!empty($result->records)) {
			$remarkables = [];
			foreach ($oEnrollApp->dataSchemas as $oSchema) {
				if (isset($oSchema->remarkable) && $oSchema->remarkable === 'Y') {
					$remarkables[] = $oSchema->id;
				}
			}
			if (count($remarkables)) {
				foreach ($result->records as &$oRec) {
					$modelRem = $this->model('matter\enroll\data');
					$oRecordData = $modelRem->byRecord($oRec->enroll_key, ['schema' => $remarkables]);
					$oRec->verbose = new \stdClass;
					$oRec->verbose->data = $oRecordData;
				}
			}
		}

		$records = $result->records;
		require_once TMS_APP_DIR . '/lib/PHPExcel.php';

		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator("信信通")
			->setLastModifiedBy("信信通")
			->setTitle($oApp->title)
			->setSubject($oApp->title)
			->setDescription($oApp->title);

		$objActiveSheet = $objPHPExcel->getActiveSheet();
		$columnNum1 = 0; //列号
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '登记时间');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '审核通过');
		if ($oApp->multi_rounds === 'Y') {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '登记轮次');
		}

		// 转换标题
		$isTotal = []; //是否需要合计
		$columnNum4 = $columnNum1; //列号
		$flag=false;
		for ($a = 0, $ii = count($schemas); $a < $ii; $a++) {
			$schema = $schemas[$a];
			/* 跳过图片,描述说明和文件 */
			if (in_array($schema->type, ['html'])) {
				continue;
			}

			if (isset($schema->format) && $schema->format === 'number') {
				$isTotal[$columnNum4] = $schema->id;
			}

			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $schema->title);

			if (isset($schema->format) && $schema->format === 'number') {
				$flag=true;
				$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '加权得分');
			}
			if (isset($remarkables) && in_array($schema->id, $remarkables)) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '评论数');
			}
		}

		$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '昵称');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '备注');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '标签');
		// 记录分数
		if ($oApp->scenario === 'voting') {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分数');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '平均分数');
			$titles[] = '总分数';
			$titles[] = '平均分数';
		}
		if ($oApp->scenario === 'quiz' || $flag) {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分');
			$titles[] = '总分';
		}
		// 转换数据
		for ($j = 0, $jj = count($records); $j < $jj; $j++) {
			$record = $records[$j];
			$rowIndex = $j + 2;
			$columnNum2 = 0; //列号
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, date('y-m-j H:i', $record->enroll_at));
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->verified);
			//轮次名
			if (isset($record->round)) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->round->title);
			}
			// 处理登记项
			$data = $record->data;
			!empty($record->score) && $score = $record->score;
			$supplement = $record->supplement;
			$oVerbose = isset($record->verbose) ? $record->verbose->data : false;
			$i = 0;
			for ($i2 = 0, $ii = count($schemas); $i2 < $ii; $i2++) {
				$columnNum3 = $columnNum2; //列号
				$schema = $schemas[$i2];
				$v = isset($data->{$schema->id}) ? $data->{$schema->id} : '';

				if (in_array($schema->type, ['html'])) {
					continue;
				}
				switch ($schema->type) {
				case 'single':
					$cellValue = '';
					foreach ($schema->ops as $op) {
						if ($op->v === $v) {
							$cellValue = $op->l;
						}
					}
					isset($score->{$schema->id}) && ($cellValue .= ' (' . $score->{$schema->id} . '分)');
					if (isset($schema->supplement) && $schema->supplement === 'Y') {
						$cellValue .= ' (补充说明：' . (isset($supplement) && isset($supplement->{$schema->id}) ? $supplement->{$schema->id} : '') . ')';
					}
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $cellValue, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				case 'phase':
					$disposed = null;
					foreach ($schema->ops as $op) {
						if ($op->v === $v) {
							$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum3++, $rowIndex, $op->l);
							$disposed = true;
							break;
						}
					}
					empty($disposed) && $objActiveSheet->setCellValueByColumnAndRow($i + $columnNum3++, $rowIndex, $v);
					break;
				case 'multiple':
					$labels = [];
					$v = explode(',', $v);
					foreach ($v as $oneV) {
						foreach ($schema->ops as $op) {
							if ($op->v === $oneV) {
								$labels[] = $op->l;
								break;
							}
						}
					}
					$cellValue = implode(',', $labels);
					isset($score->{$schema->id}) && $cellValue .= ' (' . $score->{$schema->id} . '分)';
					if (isset($schema->supplement) && $schema->supplement === 'Y') {
						$cellValue .= ' (补充说明：' . (isset($supplement) && isset($supplement->{$schema->id}) ? $supplement->{$schema->id} : '') . ')';
					}
					$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum3++, $rowIndex, $cellValue);
					break;
				case 'score':
					$labels = [];
					foreach ($schema->ops as $op) {
						if (isset($v->{$op->v})) {
							$labels[] = $op->l . ':' . $v->{$op->v};
						}
					}
					$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum3++, $rowIndex, implode(' / ', $labels));
					break;
				case 'image':
					$v0 = '';
					if (isset($schema->supplement) && $schema->supplement === 'Y') {
						$v0 .= ' (补充说明：' . (isset($supplement) && isset($supplement->{$schema->id}) ? $supplement->{$schema->id} : '') . ')';
					}
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				case 'file':
					$v0 = '';
					if (isset($schema->supplement) && $schema->supplement === 'Y') {
						$v0 .= ' (补充说明：' . (isset($supplement) && isset($supplement->{$schema->id}) ? $supplement->{$schema->id} : '') . ')';
					}
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				case 'date':
					!empty($v) && $v = date('y-m-j H:i', $v);
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				case 'shorttext':
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				default:
					isset($score->{$schema->id}) && $v .= ' (' . $score->{$schema->id} . '分)';
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				}
				$one=$i+$columnNum3;
				//分数
				if(isset($score->{$schema->id})){
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i++ + $columnNum3++, $rowIndex, $score->{$schema->id}, \PHPExcel_Cell_DataType::TYPE_STRING);
				}
				//评论数
				if (isset($remarkables) && in_array($schema->id, $remarkables)) {
					if (isset($oVerbose->{$schema->id})) {
						$remark_num = $oVerbose->{$schema->id}->remark_num;
					} else {
						$remark_num = 0;
					}
					$two=$i+$columnNum3;
					$col=($two-$one>=2) ? ($two-1) : $two;
					$objActiveSheet->setCellValueExplicitByColumnAndRow($col, $rowIndex, $remark_num, \PHPExcel_Cell_DataType::TYPE_STRING);
					$i++;
					$columnNum3++;
				}
				$i++;
			}
			// 昵称
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $record->nickname);
			// 备注
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $record->comment);
			// 标签
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $record->tags);
			// 记录投票分数
			if ($oApp->scenario === 'voting') {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $record->_score);
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, sprintf('%.2f', $record->_average));
			}
			// 记录测验分数
			if ($oApp->scenario === 'quiz' || $flag) {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $score->sum . '分');
			}
		}
		if (!empty($isTotal)) {
			//合计
			$total2 = $modelRec2->sum4Schema($oApp, $rid);
			$rowIndex = count($records) + 2;
			$objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
			foreach ($isTotal as $key => $val) {
				$objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, $total2->$val);
			}
		}

		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Cache-Control: max-age=0');

		$filename = $oApp->title . '.xlsx';
		$ua = $_SERVER["HTTP_USER_AGENT"];
		if (preg_match("/MSIE/", $ua) || preg_match("/Trident\/7.0/", $ua)) {
			$encoded_filename = urlencode($filename);
			$encoded_filename = str_replace("+", "%20", $encoded_filename);
			header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
		} else if (preg_match("/Firefox/", $ua)) {
			header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
		} else {
			header('Content-Disposition: attachment; filename="' . $filename . '"');
		}

		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}
	/**
	 * 导出登记数据中的图片
	 */
	public function exportImage_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			die('请先登录系统');
		}
		if (defined('SAE_TMP_PATH')) {
			die('部署环境不支持该功能');
		}

		$nameSchema = null;
		$imageSchemas = [];

		// 登记活动
		$enrollApp = $this->model('matter\enroll')->byId($app, ['fields' => 'id,title,data_schemas,scenario,enroll_app_id,group_app_id', 'cascaded' => 'N']);
		$schemas = json_decode($enrollApp->data_schemas);

		// 关联的登记活动
		if (!empty($enrollApp->enroll_app_id)) {
			$matchApp = $this->model('matter\enroll')->byId($enrollApp->enroll_app_id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$enrollSchemas = json_decode($matchApp->data_schemas);
			$mapOfAppSchemas = [];
			foreach ($schemas as $schema) {
				$mapOfAppSchemas[] = $schema->id;
			}
			foreach ($enrollSchemas as $schema) {
				if (!in_array($schema->id, $mapOfAppSchemas)) {
					$schemas[] = $schema;
				}
			}
		}
		// 关联的分组活动
		if (!empty($enrollApp->group_app_id)) {
			$matchApp = $this->model('matter\group')->byId($enrollApp->group_app_id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$groupSchemas = json_decode($matchApp->data_schemas);
			$mapOfAppSchemas = [];
			foreach ($schemas as $schema) {
				$mapOfAppSchemas[] = $schema->id;
			}
			foreach ($groupSchemas as $schema) {
				if (!in_array($schema->id, $mapOfAppSchemas)) {
					$schemas[] = $schema;
				}
			}
		}

		foreach ($schemas as $schema) {
			if ($schema->type === 'image') {
				$imageSchemas[] = $schema;
			} else if ($schema->id === 'name' || (in_array($schema->title, array('姓名', '名称')))) {
				$nameSchema = $schema;
			}
		}

		if (count($imageSchemas) === 0) {
			die('活动不包含图片数据');
		}

		// 获得所有有效的登记记录
		$records = $this->model('matter\enroll\record')->byApp($enrollApp);
		if ($records->total === 0) {
			die('record empty');
		}
		$records = $records->records;

		// 转换数据
		$aImages = [];
		for ($j = 0, $jj = count($records); $j < $jj; $j++) {
			$record = $records[$j];
			// 处理登记项
			$data = $record->data;
			for ($i = 0, $ii = count($imageSchemas); $i < $ii; $i++) {
				$schema = $imageSchemas[$i];
				if (!empty($data->{$schema->id})) {
					$aImages[] = ['url' => $data->{$schema->id}, 'schema' => $schema, 'data' => $data];
				}
			}
		}

		// 输出
		$usedRecordName = [];
		// 输出打包文件
		$zipFilename = tempnam('/tmp', $enrollApp->id);
		$zip = new \ZipArchive;
		if ($zip->open($zipFilename, \ZIPARCHIVE::CREATE) === false) {
			die('无法打开压缩文件，或者文件创建失败');
		}
		foreach ($aImages as $image) {
			$imageFilename = TMS_APP_DIR . '/' . $image['url'];
			if (file_exists($imageFilename)) {
				$imageName = basename($imageFilename);
				/**
				 * 图片文件名称替换
				 */
				if (isset($nameSchema)) {
					$data = $image['data'];
					$recordName = $data->{$nameSchema->id};
					if (!empty($recordName)) {
						if (isset($usedRecordName[$recordName])) {
							$usedRecordName[$recordName]++;
							$recordName = $recordName . '_' . $usedRecordName[$recordName];
						} else {
							$usedRecordName[$recordName] = 0;
						}
						$imageName = $recordName . '.' . explode('.', $imageName)[1];
					}
				}
				$zip->addFile($imageFilename, $image['schema']->title . '/' . $imageName);
			}
		}
		$zip->close();

		if (!file_exists($zipFilename)) {
			exit("无法找到压缩文件");
		}
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header('Content-disposition: attachment; filename=' . $enrollApp->title . '.zip');
		header("Content-Type: application/zip");
		header("Content-Transfer-Encoding: binary");
		header('Content-Length: ' . filesize($zipFilename));
		@readfile($zipFilename);

		exit;
	}
	/**
	 * 从其他的登记活动导入登记数据
	 *
	 * 导入的数据项定义必须兼容，兼容规则如下
	 * 从目标应用中导入和指定应用的数据定义中名称（title）和类型（type）一致的项
	 * 如果是单选题、多选题、打分题选项必须一致
	 * 如果是打分题，分值设置范围必须一致
	 * 项目阶段不支持导入
	 *
	 * @param string $app app'id
	 * @param string $fromApp 目标应用的id
	 * @param string $append 追加记录，否则清空现有记录
	 *
	 */
	public function importByOther_action($site, $app, $fromApp, $append = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');

		if (false === ($app = $modelApp->byId($app))) {
			return new \ResponseError('指定的活动不存在（1）');
		}
		if (false === ($fromApp = $modelApp->byId($fromApp))) {
			return new \ResponseError('指定的活动不存在（2）');
		}

		$dataSchemas = json_decode($app->data_schemas);
		$fromDataSchemas = json_decode($fromApp->data_schemas);

		/* 获得兼容的登记项 */
		$compatibleSchemas = $modelRec->compatibleSchemas($dataSchemas, $fromDataSchemas);
		if (empty($compatibleSchemas)) {
			return new \ResponseData('没有匹配的数据项');
		}
		/* 获得数据 */
		$records = $modelRec->byApp($fromApp);
		$countOfImport = 0;
		if ($records->total > 0) {
			foreach ($records->records as $record) {
				// 新登记
				$user = new \stdClass;
				$user->uid = $record->userid;
				$user->nickname = $record->nickname;
				$options = [];
				$options['enrollAt'] = $record->enroll_at;
				$options['nickname'] = $record->nickname;
				$ek = $modelRec->enroll($app, $user, $options);
				// 登记数据
				$data = new \stdClass;
				foreach ($compatibleSchemas as $cs) {
					if (empty($record->data->{$cs[0]->id})) {
						continue;
					}
					$val = $record->data->{$cs[0]->id};
					if ($cs[0]->type === 'single') {
						foreach ($cs[0]->ops as $index => $op) {
							if ($op->v === $val) {
								$val = $cs[1]->ops[$index]->v;
								break;
							}
						}
					} else if ($cs[0]->type === 'multiple') {
						$val3 = new \stdClass;
						$val2 = explode(',', $val);
						foreach ($val2 as $v) {
							foreach ($cs[0]->ops as $index => $op) {
								if ($op->v === $v) {
									$val3->{$cs[1]->ops[$index]->v} = true;
									break;
								}
							}
						}
						$val = $val3;
					} else if ($cs[0]->type === 'score') {
						$val2 = new \stdClass;
						foreach ($val as $opv => $score) {
							foreach ($cs[0]->ops as $index => $op) {
								if ($op->v === $opv) {
									$val2->{$cs[1]->ops[$index]->v} = $score;
									break;
								}
							}
						}
						$val = $val2;
					}
					$data->{$cs[1]->id} = $val;
				}
				$modelRec->setData($user, $app, $ek, $data);
				$countOfImport++;
			}
		}

		return new \ResponseData($countOfImport);
	}
	/**
	 * 返回一条登记记录的所有评论
	 */
	public function listRemark_action($ek, $page = 1, $size = 10) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$result = $this->model('matter\enroll\remark')->listByRecord($ek, $page, $size);

		return new \ResponseData($result);
	}
	/**
	 * 给指定的登记记录的添加评论
	 */
	public function addRemark_action($ek) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$data = $this->getPostJson();
		if (empty($data->content)) {
			return new \ResponseError('评论内容不允许为空');
		}

		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek);
		if (false === $oRecord) {
			return new \ObjectNotFoundError();
		}
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRecord->siteid, ['cascaded' => 'N']);
		/**
		 * 发表评论的用户
		 */
		$oRemark = new \stdClass;
		$oRemark->siteid = $oRecord->siteid;
		$oRemark->aid = $oRecord->aid;
		$oRemark->rid = $oRecord->rid;
		$oRemark->userid = $user->id;
		$oRemark->user_src = 'P';
		$oRemark->nickname = $user->name;
		$oRemark->enroll_key = $ek;
		$oRemark->enroll_userid = $oRecord->userid;
		$oRemark->create_at = time();
		$oRemark->content = $modelRec->escape($data->content);

		$oRemark->id = $modelRec->insert('xxt_enroll_record_remark', $oRemark, true);

		$modelRec->update("update xxt_enroll_record set remark_num=remark_num+1 where enroll_key='$ek'");

		//$this->_notifyHasRemark();

		return new \ResponseData($oRemark);
	}
}