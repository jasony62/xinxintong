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
		$rule_action['actions'][] = 'get';

		return $rule_action;
	}
	/**
	 * 返回视图
	 */
	public function index_action($id) {
		$access = $this->accessControlUser('enroll', $id);
		if ($access[0] === false) {
			die($access[1]);
		}

		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 活动登记名单
	 *
	 */
	public function get_action($ek) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$mdoelRec = $this->model('matter\enroll\record');
		$record = $mdoelRec->byId($ek, ['verbose' => 'Y']);
		if ($record) {
			$modelApp = $this->model('matter\enroll');
			$oApp = $modelApp->byId($record->aid);
			$dataSchemas = new \stdClass;
			foreach ($oApp->dataSchemas as $schema) {
				$dataSchemas->{$schema->id} = $schema;
			}
			foreach ($record->data as $k => $data) {
				if (isset($dataSchemas->{$k}) && $dataSchemas->{$k}->type === 'multitext') {
					$verboseVals = json_decode($record->verbose->{$k}->value);
					$items = [];
					foreach ($verboseVals as $verboseVal) {
						$res = $this->model('matter\enroll\data')->byId($verboseVal->id);
						$items[] = $res;
					}
					$record->verbose->{$k}->items = $items;
				}
			}
		}

		return new \ResponseData($record);
	}
	/**
	 * 活动登记名单
	 *
	 */
	public function list_action($site, $app, $page = 1, $size = 30, $orderby = null, $contain = null, $includeSignin = null) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}
		// 登记数据过滤条件
		$oCriteria = $this->getPostJson();

		// 登记记录过滤条件
		$aOptions = [
			'page' => $page,
			'size' => $size,
			'orderby' => $orderby,
			'contain' => $contain,
		];

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$oEnrollApp = $modelApp->byId($app);

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$oResult = $mdoelRec->byApp($oEnrollApp, $aOptions, $oCriteria);
		if (!empty($oResult->records)) {
			$remarkables = [];
			$bRequireScore = false;
			foreach ($oEnrollApp->dataSchemas as $oSchema) {
				if (isset($oSchema->remarkable) && $oSchema->remarkable === 'Y') {
					$remarkables[] = $oSchema->id;
				}
				if ($oSchema->type == 'shorttext' && isset($oSchema->format) && $oSchema->format == 'number') {
					$bRequireScore = true;
				}
			}
			if (count($remarkables)) {
				foreach ($oResult->records as &$oRec) {
					$modelRem = $this->model('matter\enroll\data');
					$oRecordData = $modelRem->byRecord($oRec->enroll_key, ['schema' => $remarkables]);
					$oRec->verbose = new \stdClass;
					$oRec->verbose->data = $oRecordData;
				}
			}
			if ($bRequireScore) {
				foreach ($oResult->records as &$oRec) {
					$one = $mdoelRec->query_obj_ss([
						'id,score',
						'xxt_enroll_record',
						['siteid' => $site, 'enroll_key' => $oRec->enroll_key],
					]);
					if (count($one)) {
						$oRec->score = json_decode($one->score);
					} else {
						$oRec->score = new \stdClass;
					}
				}
			}
		}

		return new \ResponseData($oResult);
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
			return new \ObjectNotFoundError();
		}

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->sum4Schema($enrollApp, $rid);

		return new \ResponseData($result);
	}
	/**
	 * 计算指定登记项的得分
	 */
	public function score4Schema_action($site, $app, $rid = 'ALL') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $enrollApp) {
			return new \ObjectNotFoundError();
		}

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->score4Schema($enrollApp, $rid);

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
		$aOptions = array(
			'page' => $page,
			'size' => $size,
			'rid' => $rid,
		);

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app);

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->recycle($site, $enrollApp, $aOptions);

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

		// 登记记录过滤条件
		$aOptions = [
			'page' => $page,
			'size' => $size,
		];
		if (!empty($rid)) {
			$aOptions['rid'] = $rid;
		}

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app);

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->list4Schema($enrollApp, $schema, $aOptions);

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
		$modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);

		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);

		/* 创建登记记录 */
		$aOptions = [];
		!empty($posted->rid) && $aOptions['assignRid'] = $posted->rid;
		$ek = $modelRec->enroll($oApp, '', $aOptions);
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
		$oRecord = $modelRec->byId($ek, ['fields' => 'enroll_key,data,rid']);
		$this->model('matter\log')->matterOp($oApp->siteid, $user, $oApp, 'add', $oRecord);

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
		$modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);

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
			$userRecord = $modelRec->byId($ek, ['fields' => 'id,rid,userid']);
			if ($userRecord === false) {
				return new \ResponseError('用户指定错误');
			}

			$userOldRid = $userRecord->rid;
			$userNewRid = $record->rid;
			/* 同步enroll_user中的轮次 */
			if ($userOldRid !== $userNewRid) {
				$modelUser = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

				/* 获取enroll_user中用户现在的轮次,如果有积分则不能移动 */
				$resOld = $modelUser->byId($oApp, $userRecord->userid, ['rid' => $userOldRid]);
				if ($resOld->user_total_coin > 0) {
					return new \ResponseError('用户在当前轮次上以获得积分，不能更换轮次！！');
				}
				/* 查询此用户的记录是否被点赞或者被评论，如果有就不能更改 */
				$qd = [
					'count(*)',
					'xxt_enroll_record_data',
					"enroll_key = '$ek' and state = 1 and (like_num > 0 or remark_num > 0)",
				];
				$UsrDataSum = $modelRec->query_val_ss($qd);
				if ($UsrDataSum > 0) {
					return new \ResponseError('此数据在当前轮次上被点赞或被评论，不能更换轮次！！');
				}

				/* 在新的轮次中用户是否以有记录 */
				$resNew = $modelUser->byId($oApp, $userRecord->userid, ['rid' => $userNewRid]);
				if ($resNew === false) {
					if ($resOld->enroll_num > 1) {
						$modelRec->update("update xxt_enroll_user set enroll_num = enroll_num - 1 where id = $resOld->id");
						//插入新的数据
						$inData = ['last_enroll_at' => time(), 'enroll_num' => 1];
						$inData['rid'] = $userNewRid;
						$oUser = new \stdClass;
						$oUser->uid = $resOld->userid;
						$oUser->nickname = $resOld->nickname;
						$oUser->group_id = empty($resOld->group_id) ? '' : $resOld->group_id;
						$modelUser->add($oApp, $oUser, $inData);
					} else {
						$modelRec->update('xxt_enroll_user',
							['rid' => $userNewRid],
							['id' => $resOld->id]
						);
					}
				} else {
					if ($resOld->enroll_num > 1) {
						$modelRec->update("update xxt_enroll_user set enroll_num = enroll_num - 1 where id = $resOld->id");
					} else {
						$modelRec->delete('xxt_enroll_user', ['id' => $resOld->id]);
					}

					$modelRec->update("update xxt_enroll_user set enroll_num = enroll_num + 1 where id = $resNew->id");
				}

				$updated->rid = $modelEnl->escape($record->rid);
			}
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
		//数值型填空题
		if (isset($record->score)) {
			$dataSchemas = $oApp->dataSchemas;
			$modelUsr = $this->model('matter\enroll\user');
			$modelUsr->setOnlyWriteDbConn(true);
			$d['sum'] = 0;
			foreach ($dataSchemas as &$schema) {
				if (isset($record->score->{$schema->id})) {
					$d[$schema->id] = $record->score->{$schema->id};
					$modelUsr->update('xxt_enroll_record_data', ['score' => $record->score->{$schema->id}], ['enroll_key' => $ek, 'schema_id' => $schema->id, 'state' => 1]);
					$d['sum'] += $d[$schema->id];
				}
			}
			$newScore = $modelRec->toJson($d);
			//更新record表
			$modelRec->update('xxt_enroll_record', ['score' => $newScore], ['aid' => $app, 'enroll_key' => $ek, 'state' => 1]);
			//更新enroll_user表
			$result = $modelRec->byId($ek);
			if (isset($result->score->sum)) {
				$upData['score'] = $result->score->sum;
			}
			$modelUsr->update(
				'xxt_enroll_user',
				$upData,
				['siteid' => $site, 'aid' => $result->aid, 'rid' => $result->rid, 'userid' => $result->userid]
			);
			/* 更新用户获得的分数 */
			$users = $modelUsr->query_objs_ss([
				'id,score',
				'xxt_enroll_user',
				"siteid='$site' and aid='$result->aid' and userid='$result->userid' and rid !='ALL'",
			]);
			$total = 0;
			foreach ($users as $v) {
				if (!empty($v->score)) {
					$total += (float) $v->score;
				}
			}
			$upDataALL['score'] = $total;
			$modelUsr->update(
				'xxt_enroll_user',
				$upDataALL,
				['siteid' => $site, 'aid' => $result->aid, 'rid' => 'ALL', 'userid' => $result->userid]
			);
		}

		/* 更新登记项数据的轮次 */
		if (isset($record->rid)) {
			$modelEnl->update('xxt_enroll_record_data', ['rid' => $modelEnl->escape($record->rid)], ['enroll_key' => $ek, 'state' => 1]);
		}
		if ($updated->verified === 'Y') {
			$this->_whenVerifyRecord($oApp, $ek);
		}

		/* 返回完整的记录 */
		$oNewRecord = $modelRec->byId($ek, ['verbose' => 'Y']);

		/* 记录操作日志 */
		$operation = new \stdClass;
		$operation->enroll_key = $ek;
		isset($record->data) && $operation->data = $record->data;
		isset($record->quizScore) && $operation->quizScore = $record->quizScore;
		isset($record->score) && $operation->score = $record->score;
		isset($record->tags) && $operation->tags = $record->tags;
		isset($record->comment) && $operation->comment = $record->comment;
		$operation->rid = $oNewRecord->rid;
		isset($oNewRecord->round) && $operation->round = $oNewRecord->round;
		$this->model('matter\log')->matterOp($oApp->siteid, $user, $oApp, 'updateData', $operation);

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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$modelEnlRec = $this->model('matter\enroll\record');
		$oRecord = $modelEnlRec->byId($key, ['fields' => 'userid,state,enroll_key,data,rid']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}
		// 如果已经获得积分不允许删除
		if (!empty($oRecord->userid)) {
			$modelEnlUsr = $this->model('matter\enroll\user');
			$oEnlUsrRnd = $modelEnlUsr->byId($oApp, $oRecord->userid, ['fields' => 'user_total_coin', 'rid' => $oRecord->rid]);
			if ($oEnlUsrRnd && $oEnlUsrRnd->user_total_coin > 0) {
				return new \ResponseError('提交的记录已经获得活动积分，不能删除');
			}
		}
		// 删除数据
		$rst = $modelEnlRec->remove($oApp, $oRecord);

		// 记录操作日志
		unset($oRecord->userid);
		unset($oRecord->state);
		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'removeData', $oRecord);

		return new \ResponseData($rst);
	}
	/**
	 * 恢复一条登记信息
	 */
	public function restore_action($app, $key) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$modelEnlRec = $this->model('matter\enroll\record');
		$oRecord = $modelEnlRec->byId($key, ['fields' => 'userid,enroll_key,data,rid']);
		if (false === $oRecord) {
			return new ObjectNotFoundError();
		}

		$rst = $modelEnlRec->restore($oApp, $oRecord);

		// 记录操作日志
		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'restoreData', $oRecord);

		return new \ResponseData($rst);
	}
	/**
	 * 清空登记信息
	 */
	public function empty_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$app = $this->escape($app);
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelRec = $this->model('matter\enroll\record');
		/* 清除填写记录 */
		$rst = $modelRec->clean($oApp);

		// 记录操作日志
		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'empty');

		return new \ResponseData($rst);
	}
	/**
	 * 所有记录通过审核
	 */
	public function verifyAll_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
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
		$this->model('matter\log')->matterOp($site, $oUser, $app, 'verify.all');

		return new \ResponseData($rst);
	}
	/**
	 * 指定记录通过审核
	 */
	public function batchVerify_action($site, $app, $all = 'N') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		$modelRun = $this->model('matter\enroll\round');
		if ($activeRound = $modelRun->getActive($oApp)) {
			$rid = $activeRound->rid;
		}

		if ($all === 'Y') {
			$modelApp->update(
				'xxt_enroll_record',
				['verified' => 'Y'],
				['aid' => $oApp->id]
			);
			// 记录操作日志
			$operationData = new \stdClass;
			if (isset($rid)) {
				$operationData->rid = $rid;
			}
			$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'verify.all', $operationData);
		} else {

			$posted = $this->getPostJson();
			$eks = $posted->eks;

			$model = $this->model();
			foreach ($eks as $ek) {
				$modelApp->update(
					'xxt_enroll_record',
					['verified' => 'Y'],
					['enroll_key' => $ek]
				);
				// 进行后续处理
				$this->_whenVerifyRecord($oApp, $ek);
			}
			// 记录操作日志
			$operationData = new \stdClass;
			$operationData->data = $eks;
			if (isset($rid)) {
				$operationData->rid = $rid;
			}
			$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'verify.batch', $operationData);
		}

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
		if (false === ($oUser = $this->accountUser())) {
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
		if (false === ($oUser = $this->accountUser())) {
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
		if (false === ($oUser = $this->accountUser())) {
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
	public function export_action($site, $app, $rid = '', $filter = '') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$oApp = $this->model('matter\enroll')->byId($app, ['fields' => 'siteid,id,title,data_schemas,assigned_nickname,scenario,enroll_app_id,group_app_id,multi_rounds', 'cascaded' => 'N']);
		$schemas = $oApp->dataSchemas;

		// 关联的登记活动
		if (!empty($oApp->enroll_app_id)) {
			$matchApp = $this->model('matter\enroll')->byId($oApp->enroll_app_id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$enrollSchemas = $matchApp->dataSchemas;
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
		$modelRec = $this->model('matter\enroll\record');
		//选择对应轮次
		if (!empty($filter)) {
			$oCriteria = json_decode($filter);
		} else {
			$oCriteria = new \stdClass;
			$oCriteria->record = new \stdClass;
			$oCriteria->record->rid = new \stdClass;
			$oCriteria->record->rid = $rid;
		}
		$result = $modelRec->byApp($oApp, null, $oCriteria);
		if ($result->total === 0) {
			die('record empty');
		}

		if (!empty($result->records)) {
			$remarkables = [];
			foreach ($oApp->dataSchemas as $oSchema) {
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
		$aNumberSum = []; // 数值型题目的合计
		$aScoreSum = []; // 题目的分数合计
		$columnNum4 = $columnNum1; //列号
		$bRequireNickname = true;
		if ((isset($oApp->assignedNickname->valid) && $oApp->assignedNickname->valid !== 'Y') || isset($oApp->assignedNickname->schema->id)) {
			$bRequireNickname = false;
		}
		$bRequireSum = false;
		$bRequireScore = false;
		for ($a = 0, $ii = count($schemas); $a < $ii; $a++) {
			$schema = $schemas[$a];
			/* 跳过图片,描述说明和文件 */
			if (in_array($schema->type, ['html'])) {
				continue;
			}
			/* 数值型，需要计算合计 */
			if (isset($schema->format) && $schema->format === 'number') {
				$aNumberSum[$columnNum4] = $schema->id;
				$bRequireSum = true;
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $schema->title);
			/* 需要计算得分 */
			if ((isset($schema->requireScore) && $schema->requireScore === 'Y') || (isset($schema->format) && $schema->format === 'number')) {
				$aScoreSum[$columnNum4] = $schema->id;
				$bRequireScore = true;
				$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '得分');
			}
			if (isset($remarkables) && in_array($schema->id, $remarkables)) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '评论数');
			}
		}
		if ($bRequireNickname) {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '昵称');
		}
		$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '备注');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '标签');
		// 记录分数
		if ($oApp->scenario === 'voting') {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分数');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '平均分数');
			$titles[] = '总分数';
			$titles[] = '平均分数';
		}
		if ($bRequireScore) {
			$aScoreSum[$columnNum4] = 'sum';
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分');
			$titles[] = '总分';
		}
		// 转换数据
		for ($j = 0, $jj = count($records); $j < $jj; $j++) {
			$oRecord = $records[$j];
			$rowIndex = $j + 2;
			$columnNum2 = 0; //列号
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, date('y-m-j H:i', $oRecord->enroll_at));
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oRecord->verified);
			// 轮次名
			if (isset($oRecord->round)) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oRecord->round->title);
			}
			// 处理登记项
			$data = $oRecord->data;
			$oRecScore = empty($oRecord->score) ? null : $oRecord->score;
			$supplement = $oRecord->supplement;
			$oVerbose = isset($oRecord->verbose) ? $oRecord->verbose->data : false;
			$i = 0; // 列序号
			for ($i2 = 0, $ii = count($schemas); $i2 < $ii; $i2++) {
				$columnNum3 = $columnNum2; //列号
				$schema = $schemas[$i2];
				if (isset($data->{$schema->id})) {
					$v = $data->{$schema->id};
				} else if ((strpos($schema->id, 'member.') === 0) && isset($data->member)) {
					$mbSchemaId = $schema->id;
					$mbSchemaIds = explode('.', $mbSchemaId);
					$mbSchemaId = $mbSchemaIds[1];
					if ($mbSchemaId === 'extattr' && count($mbSchemaIds) == 3) {
						$mbSchemaId = $mbSchemaIds[2];
						$v = isset($data->member->extattr->{$mbSchemaId}) ? $data->member->extattr->{$mbSchemaId} : '';
					} else {
						$v = isset($data->member->{$mbSchemaId}) ? $data->member->{$mbSchemaId} : '';
					}
				} else {
					$v = '';
				}

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
				case 'multitext':
					if (is_array($v)) {
						$values = [];
						foreach ($v as $val) {
							$values[] = $val->value;
						}
						$v = implode(',', $values);
					}
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				default:
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				}
				$one = $i + $columnNum3;
				// 分数
				if (isset($oRecScore->{$schema->id})) {
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i++ + $columnNum3++, $rowIndex, $oRecScore->{$schema->id}, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
				}
				// 评论数
				if (isset($remarkables) && in_array($schema->id, $remarkables)) {
					if (isset($oVerbose->{$schema->id})) {
						$remark_num = $oVerbose->{$schema->id}->remark_num;
					} else {
						$remark_num = 0;
					}
					$two = $i + $columnNum3;
					$col = ($two - $one >= 2) ? ($two - 1) : $two;
					$objActiveSheet->setCellValueExplicitByColumnAndRow($col, $rowIndex, $remark_num, \PHPExcel_Cell_DataType::TYPE_STRING);
					$i++;
					$columnNum3++;
				}
				$i++;
			}
			// 昵称
			if ($bRequireNickname) {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $oRecord->nickname);
			}
			// 备注
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $oRecord->comment);
			// 标签
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $oRecord->tags);
			// 记录投票分数
			if ($oApp->scenario === 'voting') {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $oRecord->_score);
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, sprintf('%.2f', $oRecord->_average));
			}
			// 记录测验分数
			if ($bRequireScore) {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, isset($oRecScore->sum) ? $oRecScore->sum : '');
			}
		}
		if (!empty($aNumberSum)) {
			// 数值型合计
			$rowIndex = count($records) + 2;
			$oSum4Schema = $modelRec->sum4Schema($oApp, $rid);
			$objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
			foreach ($aNumberSum as $key => $val) {
				$objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, $oSum4Schema->$val);
			}
		}
		if (!empty($aScoreSum)) {
			// 分数合计
			$rowIndex = count($records) + 2;
			$oScore4Schema = $modelRec->score4Schema($oApp, $rid);
			$objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
			foreach ($aScoreSum as $key => $val) {
				$objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, $oScore4Schema->$val);
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
		if (false === ($oUser = $this->accountUser())) {
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
		if (false === ($oUser = $this->accountUser())) {
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
				$aOptions = [];
				$aOptions['enrollAt'] = $record->enroll_at;
				$aOptions['nickname'] = $record->nickname;
				$ek = $modelRec->enroll($app, $user, $aOptions);
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