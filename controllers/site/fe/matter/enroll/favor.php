<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录收藏
 */
class favor extends base {
	/**
	 * 收藏填写记录
	 */
	public function add_action($ek) {
		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek, ['fields' => 'id,state,aid']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户进行收藏，请登录后再进行此操作');
		}

		$q = [
			'id',
			'xxt_enroll_record_favor',
			['record_id' => $oRecord->id, 'favor_unionid' => $oUser->unionid, 'state' => 1],
		];
		$oFavorLog = $modelRec->query_obj_ss($q);
		if ($oFavorLog) {
			return new \ResponseError('记录已经收藏');
		}

		$oFavorLog = new \stdClass;
		$oFavorLog->aid = $oRecord->aid;
		$oFavorLog->siteid = $oApp->siteid;
		$oFavorLog->record_id = $oRecord->id;
		$oFavorLog->favor_unionid = $oUser->unionid;
		$oFavorLog->favor_at = time();
		$oFavorLog->id = $modelRec->insert('xxt_enroll_record_favor', $oFavorLog, true);

		$modelRec->update(
			'xxt_enroll_record',
			['favor_num' => (object) ['op' => '+=', 'pat' => 1]],
			['id' => $oRecord->id]
		);

		return new \ResponseData($oFavorLog);
	}
	/**
	 * 取消收藏填写记录
	 */
	public function remove_action($ek) {
		$oUser = $this->who;
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户进行收藏，请登录后再进行此操作');
		}

		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek, ['fields' => 'id,state']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$q = [
			'id,favor_unionid,record_id,state',
			'xxt_enroll_record_favor',
			['record_id' => $oRecord->id, 'favor_unionid' => $oUser->unionid, 'state' => 1],
		];
		$oFavorLog = $modelRec->query_obj_ss($q);
		if (false === $oFavorLog || $oFavorLog->state !== '1') {
			return new \ResponseError('收藏记录不存在');
		}

		$rst = $modelRec->update('xxt_enroll_record_favor', ['state' => 0], ['id' => $oFavorLog->id]);

		$modelRec->update(
			'xxt_enroll_record',
			['favor_num' => (object) ['op' => '-=', 'pat' => 1]],
			['id' => $oRecord->id]
		);

		return new \ResponseData($oFavorLog);
	}
	/**
	 * 收藏列表
	 */
	public function list_action($app, $page = 1, $size = 30) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->getUser($oApp);
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户进行收藏，请登录后再进行此操作');
		}

		$oCriteria = $this->getPostJson();
		// 指定记录活动下的记录
		$w = "f.aid = '{$oApp->id}' and f.state = 1 and f.favor_unionid = '{$oUser->unionid}' and f.record_id = r.id and r.state=1";
		/* 指定轮次，或者当前激活轮次 */
		if (!empty($oCriteria->record->rid) && stripos($oCriteria->record->rid, 'all') === false) {
			$w .= " and (r.rid='" . $modelApp->escape($oCriteria->record->rid) . "')";
		}
		// 记录推荐状态
		if (!empty($oCriteria->record->agreed) && stripos($oCriteria->record->agreed, 'all') === false) {
			$w .= " and r.agreed='{$oCriteria->record->agreed}'";
		} else {
			// 屏蔽状态的记录默认不可见
			$w .= " and r.agreed<>'N'";
		}
		// 讨论状态的记录仅提交人，同组用户或超级用户可见
		if (empty($oUser->is_leader) || $oUser->is_leader !== 'S') {
			$w .= " and (";
			$w .= " (r.agreed<>'D'";
			if (isset($oUser->is_editor) && $oUser->is_editor !== 'Y') {
				$w .= " and r.agreed<>''"; // 如果活动指定了编辑，未表态的数据默认不公开
			}
			$w .= ")";
			$w .= " or r.userid='{$oUser->uid}'";
			if (!empty($oUser->group_id)) {
				$w .= " or r.group_id='{$oUser->group_id}'";
			}
			if (isset($oUser->is_editor) && $oUser->is_editor === 'Y') {
				$w .= " or r.group_id=''";
			}
			$w .= ")";
		}
		// 指定了按关键字过滤
		if (!empty($oCriteria->keyword)) {
			$w .= " and r.data like '%" . $modelApp->escape($oCriteria->keyword) . "%'";
		}
		// 查询条件
		$q = [
			'f.favor_at,r.id,r.state,r.enroll_key,r.rid,r.purpose,r.enroll_at,r.userid,r.group_id,r.nickname,r.verified,r.comment,r.data,r.score,r.supplement,r.agreed,r.like_num,r.like_log,r.remark_num,r.favor_num,r.dislike_num,r.dislike_log',
			"xxt_enroll_record_favor f,xxt_enroll_record r",
			$w,
		];
		$q2 = [];
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		// 查询结果排序
		if (!empty($oCriteria->orderby)) {
				$fnOrderBy = function ($orderbys) {
					is_string($orderbys) && $orderbys = [$orderbys];
					$sqls = [];
					foreach ($orderbys as $orderby) {
						switch ($orderby) {
						case 'vote_schema_num':
							$sqls[] = 'r.vote_schema_num desc';
							break;
						case 'favor':
							$sqls[] = 'f.favor_at desc';
							break;
						case 'favor asc':
							$sqls[] = 'f.favor_at asc';
							break;
						}
					}
					return implode(',', $sqls);
				};
				$q2['o'] = $fnOrderBy($oCriteria->orderby);
		} else {
			$q2['o'] = 'f.favor_at desc';
		}
		/**
		 * 处理获得的数据
		 */
		$oResult = new \stdClass; // 返回的结果
		$records = $modelApp->query_objs_ss($q, $q2);
		/* 检查题目是否可见 */
		$oResult->records = $this->model('matter\enroll\record')->parse($oApp, $records);
		if (!empty($oResult->records)) {
			$this->_processDatas($oApp, $oUser, $oResult->records, 'recordList');
		}
		// 符合条件的数据总数
		$q[0] = 'count(*)';
		$total = (int) $modelApp->query_val_ss($q);
		$oResult->total = $total;

		return new \ResponseData($oResult);
	}
	/**
	 * 处理数据
	 */
	private function _processDatas($oApp, $oUser, &$rawDatas) {
		$modelData = $this->model('matter\enroll\data');
		if (!empty($oApp->voteConfig)) {
			$modelTask = $this->model('matter\enroll\task', $oApp);
		}
		/* 是否设置了编辑组 */
		$oEditorGrp = $this->getEditorGroup($oApp);
		foreach ($rawDatas as &$rawData) {
			/* 获取记录的投票信息 */
			$aCoworkState = [];
			$recordDirs = [];
			if (isset($rawData->data)) {
				$processedData = new \stdClass;
				foreach ($oApp->dynaDataSchemas as $oSchema) {
					$schemaId = $oSchema->id;
					// 分类目录
					if ($this->getDeepValue($oSchema, 'asdir') === 'Y' && !empty($oSchema->ops) && !empty($rawData->data->{$schemaId})) {
						foreach ($oSchema->ops as $op) {
							if ($op->v === $rawData->data->{$schemaId}) {
								$recordDirs[] = $op->l;
							}
						}
					}
					/* 清除非共享数据 */
					if (!isset($oSchema->shareable) || $oSchema->shareable !== 'Y') {
						continue;
					}
					// 过滤空数据
					$rawDataVal = $this->getDeepValue($rawData->data, $schemaId, null);
					if (null === $rawDataVal) {
						continue;
					}
					// 选择题题目可见性规则
					if (!empty($oSchema->visibility->rules)) {
						$checkSchemaVisibility = true;
						foreach ($oSchema->visibility->rules as $oRule) {
							if (strpos($schemaId, 'member.extattr') === 0) {
								$memberSchemaId = str_replace('member.extattr.', '', $schemaId);
								if (!isset($rawData->data->member->extattr->{$memberSchemaId}) || ($rawData->data->member->extattr->{$memberSchemaId} !== $oRule->op && empty($rawData->data->member->extattr->{$memberSchemaId}))) {
									$checkSchemaVisibility =  false;
								}
							} else if (!isset($rawData->data->{$oRule->schema}) || ($rawData->data->{$oRule->schema} !== $oRule->op && empty($rawData->data->{$oRule->schema}->{$oRule->op}))) {
								$checkSchemaVisibility = false;
							}
						}
						if ($checkSchemaVisibility === false) {
							continue;
						}
					}
					/* 协作填写题 */
					if ($this->getDeepValue($oSchema, 'cowork') === 'Y') {
						$aOptions = ['fields' => 'id', 'agreed' => ['Y', 'A']];
						$countItems = $modelData->getCowork($rawData->enroll_key, $schemaId, $aOptions);
						$aCoworkState[$schemaId] = (object) ['length' => count($countItems)];
					} else if ($this->getDeepValue($oSchema, 'type') === 'multitext') {
						$newData = [];
						foreach ($rawDataVal as &$val) {
							$val2 = new \stdClass;
							$val2->id = $val->id;
							$val2->value = $this->replaceHTMLTags($val->value);
							$newData[] = $val2;
						}
						$this->setDeepValue($processedData, $schemaId, $newData);
					} else if ($this->getDeepValue($oSchema, 'type') === 'single') {
						foreach ($oSchema->ops as $val) {
							if ($val->v === $rawDataVal) {
								$this->setDeepValue($processedData, $schemaId, $val->l);
							}
						}
					} else if ($this->getDeepValue($oSchema, 'type') === 'score') {
						$ops = new \stdClass;
						foreach ($oSchema->ops as $val) {
							$ops->{$val->v} = $val->l;
						}
						$newData = [];
						foreach ($rawDataVal as $key => $val) {
							$data2 = new \stdClass;
							$data2->title = $ops->{$key};
							$data2->score = $val;
							$newData[] = $data2;
						}
						$this->setDeepValue($processedData, $schemaId, $newData);
					} else if ($this->getDeepValue($oSchema, 'type') === 'multiple') {
						$rawDataVal2 = explode(',', $rawDataVal);
						$ops = new \stdClass;
						foreach ($oSchema->ops as $val) {
							$ops->{$val->v} = $val->l;
						}
						$newData = [];
						foreach ($rawDataVal2 as $val) {
							$newData[] = $ops->{$val};
						}
						$this->setDeepValue($processedData, $schemaId, $newData);
					} else {
						$this->setDeepValue($processedData, $schemaId, $rawDataVal);
					}
				}
				$rawData->data = $processedData;
				if (!empty($aCoworkState)) {
					$rawData->coworkState = (object) $aCoworkState;
					// 协作填写题数据总数量
					$sum = 0;
					foreach ($aCoworkState as $k => $v) {
						$sum += (int) $v->length;
					}
					$rawData->coworkDataTotal = $sum;
				}
				if (!empty($recordDirs)) {
					$rawData->recordDir = $recordDirs;
				}
			}
			/* 设置昵称 */
			$this->setNickname($rawData, $oUser, isset($oEditorGrp) ? $oEditorGrp : null);
			/* 清除不必要的内容 */
			unset($rawData->comment);
			unset($rawData->verified);
			/* 记录的标签 */
			if (!isset($modelTag)) {
				$modelTag = $this->model('matter\enroll\tag2');
			}
			$oRecordTags = $modelTag->byRecord($rawData, $oUser, ['UserAndPublic' => empty($oPosted->favored)]);
			if (!empty($oRecordTags->user)) {
				$rawData->userTags = $oRecordTags->user;
			}
			if (!empty($oRecordTags->public)) {
				$rawData->tags = $oRecordTags->public;
			}
		}
		return $rawDatas;
	}
}