<?php
namespace matter\enroll;

require_once dirname(__FILE__) . '/entity.php';

/**
 * 数据项
 */
class data_model extends entity_model {
	/**
	 * 缺省返回的列
	 */
	const DEFAULT_FIELDS = 'id,state,value,tag,supplement,rid,enroll_key,schema_id,userid,group_id,nickname,submit_at,score,remark_num,last_remark_at,like_num,like_log,modify_log,agreed,agreed_log,multitext_seq,vote_num';
	/**
	 * 按题目记录数据
	 * 不产生日志、积分等记录
	 *
	 * @param object ['dbData', 'score']
	 */
	public function setData($oUser, $oApp, $oRecord, $submitData, $submitkey = '', $oAssignScore = null) {
		if (empty($submitkey)) {
			$submitkey = empty($oUser->uid) ? '' : $oUser->uid;
		}

		$schemasById = []; // 方便获取题目的定义
		/* 以"member."开头的数据会记录在data.member中 */
		foreach ($oApp->dynaDataSchemas as $oSchema) {
			if (strpos($oSchema->id, 'member.') === 0) {
				$oSchema->id = 'member';
			}
			$schemasById[$oSchema->id] = $oSchema;
		};

		$dbData = $this->disposRecrdData($oApp, $schemasById, $submitData, $submitkey, $oRecord);
		if ($dbData[0] === false) {
			return $dbData;
		}
		$dbData = $dbData[1];

		/* 获得题目的得分 */
		$oRecordScore = $this->socreRecordData($oApp, $oRecord, $schemasById, $dbData, $oAssignScore);
		/* 将每条协作填写项保存为1条数据，并返回题目中记录的汇总数据 */
		$fnNewItems = function ($schemaId, $aNewItems) use ($oApp, $oRecord, $oUser, $dbData) {
			$aSchemaVal = []; // 记录的题目数据中记录的整体内容
			foreach ($aNewItems as $index => $oNewItem) {
				$aNewItemData = [
					'aid' => $oApp->id,
					'rid' => $oRecord->rid,
					'purpose' => $oRecord->purpose,
					'record_id' => $oRecord->id,
					'enroll_key' => $oRecord->enroll_key,
					'state' => $oRecord->state,
					'submit_at' => $oRecord->enroll_at,
					'userid' => isset($oUser->uid) ? $oUser->uid : '',
					'nickname' => $this->escape($oRecord->nickname),
					'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
					'schema_id' => $schemaId,
					'is_multitext_root' => 'N',
					'multitext_seq' => (int) $index + 1,
					'value' => $this->escape($oNewItem->value),
				];
				$dataId = $this->insert('xxt_enroll_record_data', $aNewItemData, true);
				$aNewItems[$index]->id = $dataId;
				$aSchemaVal[] = (object) ['id' => $dataId, 'value' => $oNewItem->value];
			}
			$dbData->{$schemaId} = $aSchemaVal;
			$treatedValue = $this->toJson($aNewItems);

			return $treatedValue;
		};
		/* 更新多项填写题中的协作填写项 */
		$fnUpdItems = function ($schemaId, $newSchemaValues, $oLastSchemaValues) use ($oApp, $oRecord, $oUser, $dbData) {
			$aSchemaVal = []; // 记录的题目数据中记录的整体内容
			$oBeforeSchemaVal = null; //旧的总数据
			$aBeforeSchemaItems = []; //旧的项
			foreach ($oLastSchemaValues as $v) {
				if ((int) $v->multitext_seq === 0) {
					/* 题目的根数据 */
					$oBeforeSchemaVal = $v;
				} else {
					$aBeforeSchemaItems[$v->id] = $v;
				}
			}
			foreach ($newSchemaValues as $index => $oUpdatedItem) {
				if ($oUpdatedItem->id == 0) {
					/* 新增加的填写项 */
					$aSchemaData = [
						'aid' => $oApp->id,
						'rid' => $oRecord->rid,
						'purpose' => $oRecord->purpose,
						'record_id' => $oRecord->id,
						'enroll_key' => $oRecord->enroll_key,
						'state' => $oRecord->state,
						'submit_at' => $oRecord->enroll_at,
						'userid' => isset($oUser->uid) ? $oUser->uid : '',
						'nickname' => $this->escape($oRecord->nickname),
						'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
						'schema_id' => $schemaId,
						'is_multitext_root' => 'N',
						'multitext_seq' => (int) $index + 1,
						'value' => $this->escape($oUpdatedItem->value),
					];
					$dataId = $this->insert('xxt_enroll_record_data', $aSchemaData, true);
					$newSchemaValues[$index]->id = $dataId;
					$aSchemaVal[] = (object) ['id' => $dataId, 'value' => $oUpdatedItem->value];
				} else if (isset($aBeforeSchemaItems[$oUpdatedItem->id])) {
					if ($aBeforeSchemaItems[$oUpdatedItem->id]->value !== $oUpdatedItem->value || (int) $aBeforeSchemaItems[$oUpdatedItem->id]->multitext_seq !== ($index + 1)) {
						/* 修改了内容或位置的填写项 */
						if (strlen($aBeforeSchemaItems[$oUpdatedItem->id]->modify_log)) {
							$valueModifyLogs = json_decode($aBeforeSchemaItems[$oUpdatedItem->id]->modify_log);
						} else {
							$valueModifyLogs = [];
						}
						$oNewModifyLog = new \stdClass;
						$oNewModifyLog->submitAt = $aBeforeSchemaItems[$oUpdatedItem->id]->submit_at;
						$oNewModifyLog->value = $aBeforeSchemaItems[$oUpdatedItem->id]->value;
						$valueModifyLogs[] = $oNewModifyLog;
						$aSchemaData = [
							'state' => $oRecord->state,
							'submit_at' => $oRecord->enroll_at,
							'value' => $this->escape($oUpdatedItem->value),
							'modify_log' => $this->escape($this->toJson($valueModifyLogs)),
							'multitext_seq' => (int) $index + 1,
						];

						$this->update(
							'xxt_enroll_record_data',
							$aSchemaData,
							['id' => $oUpdatedItem->id]
						);
					}
					$aSchemaVal[] = (object) ['id' => $oUpdatedItem->id, 'value' => $oUpdatedItem->value];
					// 处理完后就去除这一条如果还有剩余的说明是本次用户修改已经删除的
					unset($aBeforeSchemaItems[$oUpdatedItem->id]);
				}
			}
			/* 处理被删除的数据 */
			if (count($aBeforeSchemaItems) > 0) {
				foreach ($aBeforeSchemaItems as $oBeforeSchemaItem) {
					// 如果删除某项，需要删除其对应的点赞数和留言数
					$this->update("update xxt_enroll_record_data set remark_num = remark_num - " . $oBeforeSchemaItem->remark_num . " , like_num = like_num - " . $oBeforeSchemaItem->like_num . " where aid = '{$oApp->id}' and rid = '{$oRecord->rid}' and enroll_key = '{$oRecord->enroll_key}' and schema_id = '{$schemaId}' and multitext_seq = 0");
					$this->update(
						'xxt_enroll_record_data',
						['state' => 101],
						['id' => $oBeforeSchemaItem->id]
					);
				}
			}
			/* 修改总数据 */
			$dbData->{$schemaId} = $aSchemaVal;
			$treatedValue = $this->toJson($aSchemaVal);

			if ($oBeforeSchemaVal->value !== $treatedValue) {
				if (strlen($oBeforeSchemaVal->modify_log)) {
					$valueModifyLogs = json_decode($oBeforeSchemaVal->modify_log);
				} else {
					$valueModifyLogs = [];
				}
				$oNewModifyLog = new \stdClass;
				$oNewModifyLog->submitAt = $oBeforeSchemaVal->submit_at;
				$oNewModifyLog->value = $oBeforeSchemaVal->value;
				$valueModifyLogs[] = $oNewModifyLog;
				$aSchemaData = [
					'state' => $oRecord->state,
					'submit_at' => $oRecord->enroll_at,
					'value' => $this->escape($treatedValue),
					'modify_log' => $this->escape($this->toJson($valueModifyLogs)),
					'multitext_seq' => 0,
				];
				if ($oBeforeSchemaVal->userid !== $oUser->uid) {
					$aSchemaData['nickname'] = $this->escape($oRecord->nickname);
					$aSchemaData['group_id'] = isset($oUser->group_id) ? $oUser->group_id : '';
				}
				$this->update(
					'xxt_enroll_record_data',
					$aSchemaData,
					['id' => $oBeforeSchemaVal->id]
				);
			}
		};

		foreach ($dbData as $schemaId => $treatedValue) {
			if (!isset($schemasById[$schemaId])) {
				continue;
			}
			$oSchema = $schemasById[$schemaId];

			/* 记录的题目之前保存过的数据 */
			$oLastSchemaValues = $this->query_objs_ss(
				[
					'id,userid,submit_at,value,modify_log,score,multitext_seq,remark_num,like_num',
					'xxt_enroll_record_data',
					['aid' => $oApp->id, 'rid' => $oRecord->rid, 'enroll_key' => $oRecord->enroll_key, 'schema_id' => $schemaId, 'state' => 1],
				]
			);
			/**
			 * 记录数据
			 */
			if (empty($oLastSchemaValues)) {
				if ($oSchema->type == 'multitext') {
					if (!empty($treatedValue)) {
						/* 记录协作填写内容 */
						$treatedValue = $fnNewItems($schemaId, $treatedValue);
					} else {
						unset($dbData->{$schemaId});
						continue;
					}
				} else {
					if (is_object($treatedValue) || is_array($treatedValue)) {
						$treatedValue = $this->toJson($treatedValue);
					}
				}
				$aSchemaData = [
					'aid' => $oApp->id,
					'rid' => $oRecord->rid,
					'purpose' => $oRecord->purpose,
					'record_id' => $oRecord->id,
					'enroll_key' => $oRecord->enroll_key,
					'state' => $oRecord->state,
					'submit_at' => $oRecord->enroll_at,
					'userid' => isset($oUser->uid) ? $oUser->uid : '',
					'nickname' => $this->escape($oRecord->nickname),
					'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
					'schema_id' => $schemaId,
					'value' => $this->escape($treatedValue),
				];
				if ($oSchema->type == 'multitext') {
					/* 多项填写题根数据 */
					$aSchemaData['is_multitext_root'] = 'Y';
					$aSchemaData['multitext_seq'] = 0;
				}
				isset($oRecordScore->{$schemaId}) && $aSchemaData['score'] = $oRecordScore->{$schemaId};
				$this->insert('xxt_enroll_record_data', $aSchemaData, false);
			} else if (count($oLastSchemaValues) == 1) {
				$aSchemaData = [];
				if ($oSchema->type == 'multitext') {
					/* 处理多项填写题型（已经存在1条空的总数据，需要更新这条数据）*/
					$treatedValue = $fnNewItems($schemaId, $treatedValue);
				} else {
					if (is_object($treatedValue) || is_array($treatedValue)) {
						$treatedValue = $this->toJson($treatedValue);
					}
				}
				if ($treatedValue !== $oLastSchemaValues[0]->value) {
					if (strlen($oLastSchemaValues[0]->modify_log)) {
						$valueModifyLogs = json_decode($oLastSchemaValues[0]->modify_log);
					} else {
						$valueModifyLogs = [];
					}
					$oNewModifyLog = new \stdClass;
					$oNewModifyLog->submitAt = $oLastSchemaValues[0]->submit_at;
					$oNewModifyLog->value = $oLastSchemaValues[0]->value;
					$valueModifyLogs[] = $oNewModifyLog;
					$aSchemaData = [
						'submit_at' => $oRecord->enroll_at,
						'value' => $this->escape($treatedValue),
						'modify_log' => $this->escape($this->toJson($valueModifyLogs)),
						'score' => isset($oRecordScore->{$schemaId}) ? $oRecordScore->{$schemaId} : 0,
					];
					if ($oLastSchemaValues[0]->userid === $oUser->uid) {
						$aSchemaData['nickname'] = $this->escape($oRecord->nickname);
						$aSchemaData['group_id'] = isset($oUser->group_id) ? $oUser->group_id : '';
					}
				} else {
					$aSchemaData = [
						'score' => isset($oRecordScore->{$schemaId}) ? $oRecordScore->{$schemaId} : 0,
					];
				}

				if (!empty($aSchemaData)) {
					$this->update(
						'xxt_enroll_record_data',
						$aSchemaData,
						['id' => $oLastSchemaValues[0]->id]
					);
				}
			} else {
				/* 获得一道题目的多条数据，多项填写题型 */
				if ($oSchema->type === 'multitext') {
					$fnUpdItems($schemaId, $treatedValue, $oLastSchemaValues);
				}
			}
		}

		return (object) ['dbData' => $dbData, 'score' => $oRecordScore];
	}
	/**
	 * 处理提交的数据
	 * 包括图片和文件的上传
	 */
	public function disposRecrdData($oApp, $schemasById, $submitData, $submitkey) {
		$oDbData = new \stdClass; // 处理后的保存到数据库中的登记记录
		/* 处理提交的数据，进行格式转换等操作 */
		foreach ($submitData as $schemaId => $submitVal) {
			if ($schemaId === 'member' && is_object($submitVal)) {
				/* 通信录用户信息 */
				$oDbData->{$schemaId} = $submitVal;
			} else if (isset($schemasById[$schemaId])) {
				/* 活动中定义的登记项 */
				$oSchema = $schemasById[$schemaId];
				if (empty($oSchema->type)) {
					return [false, '填写项【' . $oSchema->id . '】定义不完整'];
				}
				switch ($oSchema->type) {
				case 'image':
					if (is_array($submitVal) && (isset($submitVal[0]->serverId) || isset($submitVal[0]->imgSrc))) {
						/* 上传图片 */
						$treatedValue = [];
						$fsuser = $this->model('fs/user', $oApp->siteid);
						foreach ($submitVal as $img) {
							$rst = $fsuser->storeImg($img);
							if (false === $rst[0]) {
								return $rst;
							}
							$treatedValue[] = $rst[1];
						}
						$treatedValue = implode(',', $treatedValue);
						$oDbData->{$schemaId} = $treatedValue;
					} else if (empty($submitVal)) {
						$oDbData->{$schemaId} = $treatedValue = '';
					} else if (is_string($submitVal)) {
						$oDbData->{$schemaId} = $submitVal;
					} else {
						throw new \Exception('填写数据的类型和填写项【image】需要的类型不匹配');
					}
					break;
				case 'file':
					if (is_array($submitVal)) {
						$treatedValue = [];
						foreach ($submitVal as $oFile) {
							if (isset($oFile->uniqueIdentifier)) {
								/* 新上传的文件 */
								if (defined('APP_FS_USER') && APP_FS_USER === 'ali-oss') {
									$fsAli = $this->model('fs/alioss', $oApp->siteid);
									$dest = '/enroll/' . $oApp->id . '/' . $submitkey . '_' . $oFile->name;
									$fileUploaded2 = $fsAli->getBaseURL() . $dest;
								} else {
									$fsResum = $this->model('fs/local', $oApp->siteid, '_resumable');
									$fileUploaded = $fsResum->rootDir . '/enroll/' . $oApp->id . '/' . $submitkey . '_' . $oFile->name;
									$fsUser = $this->model('fs/local', $oApp->siteid, '_user');
									$dirUploaded = $fsUser->rootDir . '/' . $submitkey;
									if (!file_exists($dirUploaded)) {
										if (false === mkdir($dirUploaded, 0777, true)) {
											return [false, '创建文件上传目录失败'];
										}
									}
									if (!file_exists($fileUploaded)) {
										return [false, '上传文件没有被正确保存'];
									}
									/* 如果同一次提交中包含相同的文件，文件只会上传一次，并且被改名 */
									$fileUploaded2 = $dirUploaded . '/' . $oFile->name;
									if (false === @rename($fileUploaded, $fileUploaded2)) {
										return [false, '移动上传文件失败'];
									}
								}
								unset($oFile->uniqueIdentifier);
								$oFile->url = $fileUploaded2;
								$treatedValue[] = $oFile;
							} else {
								/* 已经上传过的文件 */
								$treatedValue[] = $oFile;
							}
						}
						$oDbData->{$schemaId} = $treatedValue;
					} else if (is_string($submitVal)) {
						$oDbData->{$schemaId} = $submitVal;
					} else {
						throw new \Exception('填写数据的类型和填写项【file】需要的类型不匹配');
					}
					break;
				case 'voice':
					if (is_array($submitVal)) {
						$treatedValue = [];
						$userFs = $this->model('fs/user', $oApp->siteid);
						foreach ($submitVal as $oVoice) {
							if (isset($oVoice->serverId)) {
								$rst = $userFs->storeWxVoice($oVoice);
								if (false === $rst[0]) {
									return $rst;
								}
								$treatedValue[] = $oVoice;
							} else {
								$treatedValue[] = $oVoice;
							}
						}
						$oDbData->{$schemaId} = $treatedValue;
					}
					break;
				case 'multiple':
					if (is_object($submitVal)) {
						// 多选题，将选项合并为逗号分隔的字符串
						$treatedValue = implode(',', array_keys(array_filter((array) $submitVal, function ($i) {return $i;})));
						$oDbData->{$schemaId} = $treatedValue;
					} else if (is_string($submitVal)) {
						$oDbData->{$schemaId} = $submitVal;
					} else {
						throw new \Exception('填写数据的类型和填写项【multiple】需要的类型不匹配');
					}
					break;
				case 'url':
					unset($submitVal->_text);
					$oDbData->{$schemaId} = $submitVal;
					break;
				default:
					// string & score
					if (is_string($submitVal)) {
						$submitVal = $this->cleanEmoji($submitVal);
					}
					$oDbData->{$schemaId} = $submitVal;
				}
			} else {
				/* 如果登记活动指定匹配清单，那么提交数据会包含匹配登记记录的数据，但是这些数据不在登记项定义中 */
				$oDbData->{$schemaId} = $submitVal;
			}
		}

		return [true, $oDbData];
	}
	/**
	 * 计算题目的分数
	 *
	 * @param array $aOptimizedFormulas 记录题目权重公式，避免全局重复计算问题
	 */
	public function socreRecordData($oApp, $oRecord, $aSchemasById, $dbData, $oAssignScore, &$aOptimizedFormulas = null) {
		$oRecordScore = new \stdClass; // 记录的得分数据
		$oRecordScore->sum = 0; // 记录总分
		$oQuizNum = new \stdClass;
		$oQuizNum->schema = 0; // 测验题目的数量
		$oQuizNum->correctSchema = 0; // 答对测验题目的数量

		/* 评估 */
		$oScoreContext = new \stdClass; // 实现在活动范围内计算得分
		$oScoreContext->app = $oApp;
		$oScoreContext->record = $oRecord;
		$oScoreContext->optimizedFormulas = &$aOptimizedFormulas;

		$fnEvaluation = function (&$oSchema, $treatedValue, &$oRecordScore) use ($oScoreContext) {
			$schemaScore = null; // 题目的得分
			switch ($oSchema->type) {
			case 'shorttext';
				if (isset($oSchema->format) && $oSchema->format === 'number') {
					if (isset($oSchema->weight)) {
						$aScoreResult = $this->model('matter\enroll\schema')->scoreByWeight($oSchema, $treatedValue, $oScoreContext);
						if (true === $aScoreResult[0] && false !== $aScoreResult[1] && is_numeric($aScoreResult[1])) {
							$schemaScore = $aScoreResult[1];
						} else {
							$schemaScore = $treatedValue;
						}
					} else {
						$schemaScore = 0;
					}
				}
				break;
			case 'single':
				if (!empty($oSchema->ops)) {
					foreach ($oSchema->ops as $oOp) {
						if (isset($oOp->v) && $treatedValue === $oOp->v) {
							if (!empty($oOp->score) && is_numeric($oOp->score)) {
								$schemaScore = $oOp->score;
							}
							break;
						}
					}
				}
				break;
			case 'multiple':
				if (!empty($oSchema->ops)) {
					$aTreatedValue = explode(',', $treatedValue);
					foreach ($oSchema->ops as $oOp) {
						if (isset($oOp->v) && in_array($oOp->v, $aTreatedValue)) {
							if (!empty($oOp->score) && is_numeric($oOp->score)) {
								if (isset($schemaScore)) {
									$schemaScore += $oOp->score;
								} else {
									$schemaScore = $oOp->score;
								}
							}
							/* 去掉已经比较过的选中项，提高效率 */
							if (count($aTreatedValue) === 1) {
								break;
							}
							array_splice($aTreatedValue, array_search($oOp->v, $aTreatedValue), 1);
						}
					}
				}
				break;
			case 'score': // 打分题
				if (!empty($oSchema->ops)) {
					$oTreatedValue = json_decode($treatedValue);
					foreach ($oSchema->ops as $oOp) {
						if (isset($oOp->v) && !empty($oTreatedValue->{$oOp->v}) && is_numeric($oTreatedValue->{$oOp->v})) {
							if (isset($schemaScore)) {
								$schemaScore += $oTreatedValue->{$oOp->v};
							} else {
								$schemaScore = $oTreatedValue->{$oOp->v};
							}
						}
					}
				}
				break;
			}
			if (isset($schemaScore)) {
				$oRecordScore->{$oSchema->id} = $schemaScore;
				$oRecordScore->sum += round((float) $schemaScore, 2);
				return true;
			}
			return false;
		};

		/* 测验 */
		$fnQuestion = function (&$oSchema, $treatedValue, &$oRecordScore) use ($oRecord, $oAssignScore, $oQuizNum) {
			if (empty($oSchema->answer)) {
				return false;
			}
			$quizScore = null;
			$oQuizNum->schema++;
			switch ($oSchema->type) {
			case 'single':
				if ($treatedValue === $oSchema->answer) {
					$quizScore = empty($oSchema->score) ? 0 : $oSchema->score;
					$oQuizNum->correctSchema++;
				} else {
					$quizScore = 0;
				}
				break;
			case 'multiple':
				$correct = 0;
				$pendingValues = explode(',', $treatedValue);
				is_string($oSchema->answer) && $oSchema->answer = explode(',', $oSchema->answer);
				foreach ($pendingValues as $pending) {
					if (in_array($pending, $oSchema->answer)) {
						$correct++;
					} else {
						$correct = 0;
						break;
					}
				}
				$quizScore = (empty($oSchema->score) ? 0 : $oSchema->score) / count($oSchema->answer) * $correct;
				if (count($oSchema->answer) === $correct) {
					$oQuizNum->correctSchema++;
				}
				break;
			default: // 主观题
				if (!empty($oAssignScore) && isset($oAssignScore->{$oSchema->id})) {
					//有指定的优先使用指定的评分
					$quizScore = $oAssignScore->{$oSchema->id};
				} else {
					$oLastSchemaValues = $this->query_objs_ss(
						[
							'id,value,score',
							'xxt_enroll_record_data',
							['enroll_key' => $oRecord->enroll_key, 'schema_id' => $oSchema->id, 'state' => 1],
						]
					);
					if (!empty($oLastSchemaValues) && (count($oLastSchemaValues) == 1) && ($oLastSchemaValues[0]->value == $treatedValue) && !empty($oLastSchemaValues[0]->score)) {
						//有提交记录且没修改且已经评分
						$quizScore = $oLastSchemaValues[0]->score;
					} elseif ($treatedValue === $oSchema->answer) {
						$quizScore = $oSchema->score;
					} else {
						$quizScore = 0;
					}
				}
				if ($quizScore == $oSchema->score) {
					$oQuizNum->correctSchema++;
				}
				break;
			}
			// 记录分数
			if (isset($quizScore)) {
				$oRecordScore->{$oSchema->id} = round((float) $quizScore, 2);
				$oRecordScore->sum += round((float) $quizScore, 2);
			}
			return true;
		};

		foreach ($dbData as $schemaId => $treatedValue) {
			if (!isset($aSchemasById[$schemaId])) {
				continue;
			}
			$oSchema = $aSchemasById[$schemaId];
			if (!isset($oSchema->requireScore) || $oSchema->requireScore !== 'Y' || !isset($oSchema->scoreMode)) {
				continue;
			}
			// @todo 为什么要有这么一段代码？
			if (is_object($treatedValue) || is_array($treatedValue)) {
				$treatedValue = $this->toJson($treatedValue);
			}
			/**
			 * 计算单个题目的得分
			 */
			switch ($oSchema->scoreMode) {
			case 'evaluation':
				$fnEvaluation($oSchema, $treatedValue, $oRecordScore);
				break;
			case 'question':
				$fnQuestion($oSchema, $treatedValue, $oRecordScore);
				break;
			}
		}

		/* 如果测验题目全对，且指定了总分，那么总得分为指定的总分 */
		if ($oApp->scenario === 'quiz' && !empty($oApp->scenarioConfig->quizSum)) {
			if ($oQuizNum->schema === $oQuizNum->correctSchema) {
				$oRecordScore->sum = $oApp->scenarioConfig->quizSum;
			}
		}

		return $oRecordScore;
	}
	/**
	 * 更新得分数据排名
	 * 如果有对应的汇总轮次，更新汇总轮次的排名
	 */
	public function setScoreRank($oApp, $oSchema, $rid) {
		/* 更新指定轮次的数据 */
		$fnRankByRound = function ($oRnd) use ($oApp, $oSchema) {
			$q = [
				'id,score',
				'xxt_enroll_record_data',
				['aid' => $oApp->id, 'rid' => $oRnd->rid, 'schema_id' => $oSchema->id, 'state' => 1],
			];
			$q2['o'] = 'score desc,submit_at asc';
			$items = $this->query_objs_ss($q, $q2);
			if (count($items)) {
				$oItem = $items[0];
				if (isset($oSchema->rankScoreAbove) && is_numeric($oSchema->rankScoreAbove) && $oItem->score <= $oSchema->rankScoreAbove) {
					$this->update('xxt_enroll_record_data', ['score_rank' => 0], ['id' => $oItem->id]);
				} else {
					$rank = 1;
					$this->update('xxt_enroll_record_data', ['score_rank' => $rank], ['id' => $oItem->id]);
					$lastScore = $oItem->score;
				}
				for ($i = 1, $l = count($items); $i < $l; $i++) {
					$oItem = $items[$i];
					if (isset($oSchema->rankScoreAbove) && is_numeric($oSchema->rankScoreAbove) && $oItem->score <= $oSchema->rankScoreAbove) {
						$this->update('xxt_enroll_record_data', ['score_rank' => 0], ['id' => $oItem->id]);
					} else {
						if ($oItem->score < $lastScore) {
							$rank = $i + 1;
						}
						$this->update('xxt_enroll_record_data', ['score_rank' => $rank], ['id' => $oItem->id]);
						$lastScore = $oItem->score;
					}
				}
			}
			return count($items);
		};

		/* 指定了需要汇总的轮次 */
		$modelRnd = $this->model('matter\enroll\round');
		$oAssignedRnd = $modelRnd->byId($rid, ['fields' => 'rid,start_at']);
		if (false === $oAssignedRnd) {
			return 0;
		}
		/* 更新指定轮次 */
		$cnt = $fnRankByRound($oAssignedRnd);
		if ($cnt > 0) {
			/* 更新汇总轮次 */
			$oSumRnds = $modelRnd->getSummary($oApp, $oAssignedRnd->start_at, ['fields' => 'id,rid,title,start_at,state', 'includeRounds' => 'N']);
			if (!empty($oSumRnds)) {
				array_walk($oSumRnds, $fnRankByRound);
			}
		}

		return $cnt;
	}
	/**
	 * 获得指定登记记录登记数据的详细信息
	 */
	public function byRecord($ek, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : self::DEFAULT_FIELDS;
		$bExcludeRoot = isset($aOptions['excludeRoot']) ? isset($aOptions['excludeRoot']) : false;

		$q = [
			$fields,
			'xxt_enroll_record_data',
			['enroll_key' => $ek, 'state' => 1],
		];

		if ($bExcludeRoot) {
			$q[2]['is_multitext_root'] = 'N';
		} else {
			$q[2]['multitext_seq'] = 0;
		}

		$fnHandleData = function (&$oData) {
			if (property_exists($oData, 'tag')) {
				$oData->tag = empty($oData->tag) ? [] : json_decode($oData->tag);
			}
			if (property_exists($oData, 'like_log')) {
				$oData->like_log = empty($oData->like_log) ? new \stdClass : json_decode($oData->like_log);
			}
			if (property_exists($oData, 'agreed_log')) {
				$oData->agreed_log = empty($oData->agreed_log) ? new \stdClass : json_decode($oData->agreed_log);
			}
		};
		$fnHandleResult = function ($data) use ($bExcludeRoot, $fnHandleData) {
			$oResult = new \stdClass;
			if (count($data)) {
				foreach ($data as $oSchemaData) {
					$fnHandleData($oSchemaData);
					$schemaId = $oSchemaData->schema_id;
					unset($oSchemaData->schema_id);
					if ($bExcludeRoot) {
						if ($oSchemaData->multitext_seq > 0) {
							$oResult->{$schemaId}[] = $oSchemaData;
						} else {
							unset($oSchemaData->multitext_seq);
							$oResult->{$schemaId} = $oSchemaData;
						}
					} else {
						$oResult->{$schemaId} = $oSchemaData;
					}
				}
			}

			return $oResult;
		};
		if (!isset($aOptions['schema']) || is_array($aOptions['schema'])) {
			false === strpos($fields, 'schema_id') && $q[0] .= ',schema_id';
			false === strpos($fields, 'multitext_seq') && $q[0] .= ',multitext_seq';
			if (isset($aOptions['schema']) && is_array($aOptions['schema'])) {
				$q[2]['schema_id'] = $aOptions['schema'];
			}
			$data = $this->query_objs_ss($q);

			$mixResult = $fnHandleResult($data);
		} else {
			$q[2]['schema_id'] = $aOptions['schema'];
			if ($bExcludeRoot) {
				false === strpos($fields, 'multitext_seq') && $q[0] .= ',multitext_seq';
				$data = $this->query_objs_ss($q);
				array_walk($data, $fnHandleData);
				if (count($data) === 1 && $data[0]->multitext_seq === 0) {
					$fnHandleData($data);
					$mixResult = $data[0];
				} else {
					$mixResult = $data;
				}
			} else {
				if ($data = $this->query_obj_ss($q)) {
					$fnHandleData($data);
				}
				$mixResult = $data;
			}
		}

		return $mixResult;
	}
	/**
	 * 返回指定活动，指定登记项的填写数据
	 */
	public function bySchema($oApp, $oSchema, $oOptions = null) {
		if ($oOptions) {
			is_array($oOptions) && $oOptions = (object) $oOptions;
			$page = isset($oOptions->page) ? $oOptions->page : null;
			$size = isset($oOptions->size) ? $oOptions->size : null;
			$rid = isset($oOptions->rid) ? $this->escape($oOptions->rid) : null;
		}
		$oResult = new \stdClass; // 返回的结果

		// 查询参数
		$q = [
			'distinct value',
			"xxt_enroll_record_data d",
			"state=1 and aid='{$oApp->id}' and schema_id='{$oSchema->id}' and value<>''",
		];
		/* 限制关联数据 */
		if (isset($oOptions->assocData) && is_object($oOptions->assocData)) {
			$oAssocData = $oOptions->assocData;
			foreach ($oAssocData as $schemaId => $value) {
				if (!empty($value) && is_string($value)) {
					$alias = 'd' . $schemaId;
					$q[2] .= " and exists(select 1 from xxt_enroll_record_data {$alias} where d.enroll_key={$alias}.enroll_key and state=1 and aid='{$oApp->id}' and schema_id='{$schemaId}' and value='{$value}')";
				}
			}
		}
		/* 是否排除协作填写数据 */
		if (isset($oOptions->multitext_seq)) {
			$q[2] .= ' and multitext_seq=' . $oOptions->multitext_seq;
		}
		/* 限制填写轮次 */
		if (!empty($rid)) {
			if ($rid !== 'ALL') {
				$q[2] .= " and rid='{$rid}'";
			}
		} else {
			/* 没有指定轮次，就使用当前轮次 */
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$q[2] .= " and rid='{$activeRound->rid}'";
			}
		}
		/* 限制填写用户 */
		if (!empty($oOptions->userid)) {
			$q[2] .= " and userid='{$oOptions->userid}'";
		}

		$q2 = [];
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		// 处理获得的数据
		$oResult->records = $this->query_objs_ss($q, $q2);

		// 符合条件的数据总数
		$q[0] = 'count(distinct value)';
		$total = (int) $this->query_val_ss($q, $q2);
		$oResult->total = $total;

		return $oResult;
	}
	/**
	 * 返回最小值
	 */
	public function minBySchema($oApp, $oSchema, $oOptions = null) {
		if ($oOptions) {
			is_array($oOptions) && $oOptions = (object) $oOptions;
			$rid = isset($oOptions->rid) ? $this->escape($oOptions->rid) : null;
		}
		$q = [
			'min(value)',
			"xxt_enroll_record_data d",
			['state' => 1, 'aid' => $oApp->id, 'schema_id' => $oSchema->id],
		];
		if (!empty($rid) && $rid !== 'ALL') {
			$q[2]['rid'] = $rid;
		} else if (isset($oApp->appRound->rid)) {
			$q[2]['rid'] = $oApp->appRound->rid;
		}

		$value = $this->query_val_ss($q);

		return $value;
	}
	/**
	 * 返回最小值
	 */
	public function maxBySchema($oApp, $oSchema, $oOptions = null) {
		if ($oOptions) {
			is_array($oOptions) && $oOptions = (object) $oOptions;
			$rid = isset($oOptions->rid) ? $this->escape($oOptions->rid) : null;
		}
		$q = [
			'max(value)',
			"xxt_enroll_record_data d",
			['state' => 1, 'aid' => $oApp->id, 'schema_id' => $oSchema->id],
		];
		if (!empty($rid) && $rid !== 'ALL') {
			$q[2]['rid'] = $rid;
		} else if (isset($oApp->appRound->rid)) {
			$q[2]['rid'] = $oApp->appRound->rid;
		}

		$value = $this->query_val_ss($q);

		return $value;
	}
	/**
	 * 返回指定活动，填写的数据
	 */
	public function byApp($oApp, $oUser, $oOptions = null) {
		if ($oOptions && is_array($oOptions)) {
			$oOptions = (object) $oOptions;
		}
		$fields = isset($oOptions->fields) ? $oOptions->fields : self::DEFAULT_FIELDS;
		$page = isset($oOptions->page) ? $oOptions->page : null;
		$size = isset($oOptions->size) ? $oOptions->size : null;
		$rid = isset($oOptions->rid) ? $this->escape($oOptions->rid) : null;
		$tag = isset($oOptions->tag) ? $this->escape($oOptions->tag) : null;

		$oResult = new \stdClass; // 返回的结果

		// 查询参数
		$q = [
			$fields,
			"xxt_enroll_record_data rd",
			"state=1 and aid='{$oApp->id}' and multitext_seq = 0",
		];
		if (empty($oOptions->keyword)) {
			$q[2] .= " and value<>''";
		} else {
			$q[2] .= " and (value like '%" . $oOptions->keyword . "%' or supplement like '%" . $oOptions->keyword . "%')";
		}
		if (isset($oOptions->schemas) && count($oOptions->schemas)) {
			$q[2] .= " and schema_id in(";
			foreach ($oOptions->schemas as $index => $schemaId) {
				if ($index > 0) {
					$q[2] .= ',';
				}
				$q[2] .= "'" . $this->escape($schemaId) . "'";
			}
			$q[2] .= ")";
		}
		/* 限制填写轮次 */
		if (!empty($rid)) {
			if (strcasecmp($rid, 'all') !== 0) {
				$q[2] .= " and (rid='{$rid}' or exists(select 1 from xxt_enroll_record_remark rr where rr.aid=rd.aid and rr.enroll_key=rd.enroll_key and rr.rid='$rid'))";
			}
		} else {
			/* 没有指定轮次，就使用当前轮次 */
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$q[2] .= " and (rid='{$activeRound->rid}' or exists(select 1 from xxt_enroll_record_remark rr where rr.aid=rd.aid and rr.enroll_key=rd.enroll_key and rr.rid='{$activeRound->rid}'))";
			}
		}
		/* 限制管理员态度 */
		if (!empty($oOptions->agreed) && $oOptions->agreed === 'Y') {
			$q[2] .= " and agreed='Y'";
		}
		/* 根据用户分组进行筛选 */
		if (!empty($oOptions->userGroup)) {
			$q[2] .= " and group_id='{$oOptions->userGroup}'";
		}
		/* 限制填写用户 */
		if (!empty($oOptions->owner) && strcasecmp($oOptions->owner, 'all') !== 0) {
			$q[2] .= " and userid='{$oOptions->owner}'";
		} else if (!empty($oUser->uid)) {
			$q[2] .= " and (agreed<>'N' or userid='{$oUser->uid}')";
		} else {
			$q[2] .= " and agreed<>'N'";
		}
		/*限制标签*/
		if (!empty($tag)) {
			$q[2] .= " and tag like '%" . '"' . $tag . '"' . "%'";
		}

		$q2 = [];
		// 排序规则
		$q2['o'] = "agreed desc,submit_at desc";
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		// 处理获得的数据
		$mapOfNicknames = [];
		$aRecDatas = $this->query_objs_ss($q, $q2);
		if (count($aRecDatas)) {
			$visibilitySchemasCount = 0;
			// 题目类型
			$oVisibilitySchemas = new \stdClass; // 设置了可见性的题目
			$oSchemaByIds = new \stdClass;
			if (isset($oApp->dataSchemas)) {
				foreach ($oApp->dataSchemas as $oSchema) {
					$oSchemaByIds->{$oSchema->id} = $oSchema;
					if (!empty($oSchema->visibility->rules)) {
						$oVisibilitySchemas->{$oSchema->id} = $oSchema;
						$visibilitySchemasCount++;
					}
				}
			}
			if ($visibilitySchemasCount) {
				$fnCheckSchemaVisibility = function ($oSchema, &$oRecordData) {
					foreach ($oSchema->visibility->rules as $oRule) {
						if (strpos($oSchema->id, 'member.extattr') === 0) {
							$memberSchemaId = str_replace('member.extattr.', '', $oSchema->id);
							if (!isset($oRecordData->member->extattr->{$memberSchemaId}) || ($oRecordData->member->extattr->{$memberSchemaId} !== $oRule->op && empty($oRecordData->member->extattr->{$memberSchemaId}))) {
								return false;
							}
						} else if (!isset($oRecordData->{$oRule->schema}) || ($oRecordData->{$oRule->schema} !== $oRule->op && empty($oRecordData->{$oRule->schema}->{$oRule->op}))) {
							return false;
						}
					}
					return true;
				};
			}
			$oRecDatas2 = [];
			$oRecordByIds = new \stdClass;
			$modelRec = $this->model('matter\enroll\record');
			foreach ($aRecDatas as $oRecData) {
				/* 根据题目的可见性设置，过滤数据 */
				if ($visibilitySchemasCount) {
					if (isset($oVisibilitySchemas->{$oRecData->schema_id})) {
						if (isset($oRecordByIds->{$oRecData->enroll_key})) {
							$oRecord = $oRecordByIds->{$oRecData->enroll_key};
						} else {
							$oRecord = $oRecordByIds->{$oRecData->enroll_key} = $modelRec->byId($oRecData->enroll_key, ['fields' => 'nickname,data']);
						}
						if (false === $fnCheckSchemaVisibility($oVisibilitySchemas->{$oRecData->schema_id}, $oRecord->data)) {
							continue;
						}
					}
				}
				/* 获得nickname */
				if (!isset($mapOfNicknames[$oRecData->userid])) {
					if (isset($oRecordByIds->{$oRecData->enroll_key})) {
						$oRecord = $oRecordByIds->{$oRecData->enroll_key};
					} else {
						$oRecord = $oRecordByIds->{$oRecData->enroll_key} = $modelRec->byId($oRecData->enroll_key, ['fields' => 'nickname']);
					}
					$mapOfNicknames[$oRecData->userid] = $oRecord->nickname;
				}
				$oRecData->nickname = $mapOfNicknames[$oRecData->userid];
				/* like log */
				if ($oRecData->like_log) {
					$oRecData->like_log = json_decode($oRecData->like_log);
				}
				/* 处理多项填写题 */
				if (isset($oRecData->schema_id) && isset($oSchemaByIds->{$oRecData->schema_id}) && $oSchemaByIds->{$oRecData->schema_id}->type === 'multitext') {
					$oRecData->value = empty($oRecData->value) ? [] : json_decode($oRecData->value);
					$items = [];
					foreach ($oRecData->value as $val) {
						$items[] = $this->byId($val->id, ['fields' => $fields]);
					}
					$oRecData->items = $items;
				}
				$oRecDatas2[] = $oRecData;
			}
			$aRecDatas = $oRecDatas2;
		}

		$oResult->records = $aRecDatas;

		// 符合条件的数据总数
		$q[0] = 'count(*)';
		$total = (int) $this->query_val_ss($q, $q2);
		$oResult->total = $total;

		return $oResult;
	}
	/**
	 * 答案清单
	 */
	public function coworkDataByApp($oApp, $oOptions = null, $oCriteria = null, $oUser = null) {
		if (is_string($oApp)) {
			$oApp = $this->model('matter\enroll')->byId($oApp, ['cascaded' => 'N']);
		}
		if (false === $oApp && empty($oApp->dynaDataSchemas)) {
			return false;
		}
		if ($oOptions && is_array($oOptions)) {
			$oOptions = (object) $oOptions;
		}
		// 活动中是否存在协作填写题
		$oSchemasById = new \stdClass; // 方便查找题目
		$coworkSchemaIds = [];
		foreach ($oApp->dynaDataSchemas as $oSchema) {
			$oSchemasById->{$oSchema->id} = $oSchema;
			if (isset($oSchema->cowork) && $oSchema->cowork === 'Y') {
				$coworkSchemaIds[] = $oSchema->id;
			}
		}
		if (empty($coworkSchemaIds)) {
			return false;
		}

		// 指定记录活动下的记录记录
		$w = "rd.state=1 and rd.aid='{$oApp->id}' and rd.multitext_seq > 0 and rd.value<>''";
		$coworkSchemaId = implode("','", $coworkSchemaIds);
		$w .= " and rd.schema_id in ('" . $coworkSchemaId . "')";

		/* 指定轮次，或者当前激活轮次 */
		if (empty($oCriteria->recordData->rid)) {
			if (!empty($oApp->appRound->rid)) {
				$rid = $oApp->appRound->rid;
				$w .= " and (rd.rid='$rid')";
			}
		} else {
			if (is_string($oCriteria->recordData->rid)) {
				if (strcasecmp('all', $oCriteria->recordData->rid) !== 0) {
					$rid = $oCriteria->recordData->rid;
					$w .= " and (rd.rid='$rid')";
				}
			} else if (is_array($oCriteria->recordData->rid)) {
				if (empty(array_intersect(['all', 'ALL'], $oCriteria->recordData->rid))) {
					$rid = $oCriteria->recordData->rid;
					$w .= " and rd.rid in('" . implode("','", $rid) . "')";
				}
			}
		}

		// 根据用户分组过滤
		if (isset($oCriteria->recordData->group_id)) {
			$w .= " and rd.group_id='{$oCriteria->recordData->group_id}'";
		}

		// 根据填写人筛选（填写端列表页需要）
		if (!empty($oCriteria->recordData->userid)) {
			$w .= " and rd.userid='{$oCriteria->recordData->userid}'";
		}

		/**
		 * 推荐状态
		 */
		if (isset($oCriteria->recordData->agreed)) {
			$w .= " and rd.agreed='{$oCriteria->recordData->agreed}'";
		} else {
			// 屏蔽状态的记录默认不可见
			$w .= " and rd.agreed<>'N'";
		}
		// 讨论状态的记录仅提交人，同组用户或超级用户可见
		if (isset($oUser)) {
			// 当前用户角色
			if (empty($oUser->is_leader) || $oUser->is_leader !== 'S') {
				if (!empty($oUser->uid)) {
					$w .= " and (";
					$w .= " (rd.agreed<>'D'";
					if (isset($oUser->is_editor) && $oUser->is_editor !== 'Y') {
						$w .= " and rd.agreed<>''"; // 如果活动指定了编辑，未表态的数据默认不公开
					}
					$w .= ")";
					$w .= " or rd.userid='{$oUser->uid}'";
					if (!empty($oUser->group_id)) {
						$w .= " or rd.group_id='{$oUser->group_id}'";
					}
					if (isset($oUser->is_editor) && $oUser->is_editor === 'Y') {
						$w .= " or rd.group_id=''";
					}
					$w .= ")";
				}
			}
		}

		// 预制条件：指定分组或赞同数大于
		if (isset($oCriteria->recordData->GroupOrLikeNum) && is_object($oCriteria->recordData->GroupOrLikeNum)) {
			if (!empty($oCriteria->recordData->GroupOrLikeNum->group_id) && isset($oCriteria->recordData->GroupOrLikeNum->like_num)) {
				$w .= " and (rd.group_id='{$oCriteria->recordData->GroupOrLikeNum->group_id}' or rd.like_num>={$oCriteria->recordData->GroupOrLikeNum->like_num})";
			}
		}

		// 指定了按关键字过滤
		if (!empty($oCriteria->keyword)) {
			$w .= " and (r.data like '%{$oCriteria->keyword}%')";
		}
		// 指定了记录数据过滤条件
		if (isset($oCriteria->data)) {
			$whereByData = '';
			foreach ($oCriteria->data as $k => $v) {
				if (!empty($v) && isset($oSchemasById->{$k})) {
					$oSchema = $oSchemasById->{$k};
					$whereByData .= ' and (';
					if ($oSchema->type === 'multiple') {
						// 选项ID是否互斥，不存在，例如：v1和v11
						$bOpExclusive = true;
						$strOpVals = '';
						foreach ($oSchema->ops as $op) {
							$strOpVals .= ',' . $op->v;
						}
						foreach ($oSchema->ops as $op) {
							if (false !== strpos($strOpVals, $op->v)) {
								$bOpExclusive = false;
								break;
							}
						}
						// 拼写sql
						$v2 = explode(',', $v);
						foreach ($v2 as $index => $v2v) {
							if ($index > 0) {
								$whereByData .= ' and ';
							}
							// 获得和题目匹配的子字符串
							$dataBySchema = 'substr(substr(r.data,locate(\'"' . $k . '":"\',r.data)),1,locate(\'"\',substr(r.data,locate(\'"' . $k . '":"\',r.data)),' . (strlen($k) + 5) . '))';
							$whereByData .= '(';
							if ($bOpExclusive) {
								$whereByData .= $dataBySchema . ' like \'%' . $v2v . '%\'';
							} else {
								$whereByData .= $dataBySchema . ' like \'%"' . $v2v . '"%\'';
								$whereByData .= ' or ' . $dataBySchema . ' like \'%"' . $v2v . ',%\'';
								$whereByData .= ' or ' . $dataBySchema . ' like \'%,' . $v2v . ',%\'';
								$whereByData .= ' or ' . $dataBySchema . ' like \'%,' . $v2v . '"%\'';
							}
							$whereByData .= ')';
						}
					} else {
						$whereByData .= 'r.data like \'%"' . $k . '":"' . $v . '"%\'';
					}
					$whereByData .= ')';
				}
			}
			$w .= $whereByData;
		}

		// 查询参数
		$fields = 'r.id,rd.id dataId,rd.enroll_key,rd.rid,rd.purpose,rd.submit_at enroll_at,rd.userid,rd.group_id,rd.nickname,rd.schema_id,rd.value,rd.score,rd.agreed,rd.like_num,rd.like_log,rd.remark_num,rd.dislike_num,rd.dislike_log,r.data';
		$table = "xxt_enroll_record_data rd,xxt_enroll_record r";
		$w .= " and rd.enroll_key = r.enroll_key and r.state = 1";

		$q = [$fields, $table, $w];
		$q2 = [];
		// 查询结果分页
		if (!empty($oOptions->page) && !empty($oOptions->size)) {
			$q2['r'] = ['o' => ($oOptions->page - 1) * $oOptions->size, 'l' => $oOptions->size];
		}

		// 查询结果排序
		if (!empty($oOptions->orderby)) {
			$fnOrderBy = function ($orderbys) {
				is_string($orderbys) && $orderbys = [$orderbys];
				$sqls = [];
				foreach ($orderbys as $orderby) {
					switch ($orderby) {
					case 'sum':
						$sqls[] = 'rd.score desc';
						break;
					case 'agreed':
						$sqls[] = 'rd.agreed desc';
						break;
					case 'vote_num':
						$sqls[] = 'rd.vote_num desc';
						break;
					case 'like_num':
						$sqls[] = 'rd.like_num desc';
						break;
					case 'submit_at':
						$sqls[] = 'rd.submit_at desc';
						break;
					case 'submit_at asc':
						$sqls[] = 'rd.submit_at';
						break;
					}
				}
				return implode(',', $sqls);
			};
			$q2['o'] = $fnOrderBy($oOptions->orderby);
		} else {
			$q2['o'] = 'rd.submit_at desc';
		}
		/**
		 * 处理获得的数据
		 */
		$oResult = new \stdClass; // 返回的结果
		if ($aRecDatas = $this->query_objs_ss($q, $q2)) {
			//
			$oResult->recordDatas = $this->_parse($oApp, $aRecDatas);
		} else {
			$oResult->recordDatas = [];
		}
		// 符合条件的数据总数
		$q[0] = 'count(*)';
		$total = (int) $this->query_val_ss($q);
		$oResult->total = $total;

		return $oResult;
	}
	/**
	 * 解析记录的内容，将数据库中的格式转换为应用格式
	 */
	private function _parse($oApp, &$aRecDatas) {
		$visibilitySchemas = []; // 设置了可见性规则的题目
		if (!empty($oApp->dynaDataSchemas)) {
			foreach ($oApp->dynaDataSchemas as $oSchema) {
				if (!empty($oSchema->visibility->rules)) {
					$visibilitySchemas[] = $oSchema;
				}
			}
		}

		$aGroupsById = []; // 缓存分组数据
		$aRoundsById = []; // 缓存轮次数据
		$oGroupsByUser = []; // 缓存分组用户

		$fnCheckSchemaVisibility = function ($oSchemas, &$oRecordData) {
			foreach ($oSchemas as $oSchema) {
				foreach ($oSchema->visibility->rules as $oRule) {
					if (strpos($oSchema->id, 'member.extattr') === 0) {
						$memberSchemaId = str_replace('member.extattr.', '', $oSchema->id);
						if (!isset($oRecordData->member->extattr->{$memberSchemaId}) || ($oRecordData->member->extattr->{$memberSchemaId} !== $oRule->op && empty($oRecordData->member->extattr->{$memberSchemaId}))) {
							unset($oRecordData->{$oSchema->id});
							break;
						}
					} else if (!isset($oRecordData->{$oRule->schema}) || ($oRecordData->{$oRule->schema} !== $oRule->op && empty($oRecordData->{$oRule->schema}->{$oRule->op}))) {
						unset($oRecordData->{$oSchema->id});
						break;
					}
				}
			}
		};

		$aFnHandlers = []; // 记录处理函数
		/* 用户所属分组 */
		if (!empty($oApp->entryRule->group->id)) {
			$groupAppId = $oApp->entryRule->group->id;
			$modelGrpUser = $this->model('matter\group\user');
			$aFnHandlers[] = function ($aRecData) use ($groupAppId, $modelGrpUser) {
				if (!empty($aRecData->userid)) {
					if (!isset($oGroupsByUser[$aRecData->userid])) {
						$oGrpUser = $modelGrpUser->byUser((object) ['id' => $groupAppId], $aRecData->userid, ['fields' => 'team_id,team_title', 'onlyOne' => true]);
						$oGroupsByUser[$aRecData->userid] = $oGrpUser;
					} else {
						$oGrpUser = $oGroupsByUser[$aRecData->userid];
					}
					if ($oGrpUser) {
						if (!isset($aRecData->user)) {
							$aRecData->user = new \stdClass;
						}
						$aRecData->user->group = (object) ['id' => $oGrpUser->team_id, 'title' => $oGrpUser->team_title];
					}
				}
			};
		}

		foreach ($aRecDatas as $aRecData) {
			if (property_exists($aRecData, 'like_log')) {
				$aRecData->like_log = empty($aRecData->like_log) ? new \stdClass : json_decode($aRecData->like_log);
			}
			if (property_exists($aRecData, 'dislike_log')) {
				$aRecData->dislike_log = empty($aRecData->dislike_log) ? new \stdClass : json_decode($aRecData->dislike_log);
			}

			if (!empty($aRecData->data)) {
				$data = str_replace("\n", ' ', $aRecData->data);
				$data = json_decode($data);
				if ($data === null) {
					$aRecData->data = 'json error(' . json_last_error_msg() . '):' . $aRecData->data;
				} else {
					$aRecData->data = $data;
					/* 处理提交数据后分组的问题 */
					if (!empty($aRecData->group_id) && !isset($aRecData->data->_round_id)) {
						$aRecData->data->_round_id = $aRecData->group_id;
					}
					/* 处理提交数据后指定昵称题的问题 */
					if ($aRecData->nickname && isset($oApp->assignedNickname->valid) && $oApp->assignedNickname->valid === 'Y') {
						if (isset($oApp->assignedNickname->schema->id)) {
							$nicknameSchemaId = $oApp->assignedNickname->schema->id;
							if (0 === strpos($nicknameSchemaId, 'member.')) {
								$nicknameSchemaId = explode('.', $nicknameSchemaId);
								if (!isset($aRecData->data->member)) {
									$aRecData->data->member = new \stdClass;
								}
								if (!isset($aRecData->data->member->{$nicknameSchemaId[1]})) {
									$aRecData->data->member->{$nicknameSchemaId[1]} = $aRecData->nickname;
								}
							} else {
								if (!isset($aRecData->data->{$nicknameSchemaId})) {
									$aRecData->data->{$nicknameSchemaId} = $oRec->nickname;
								}
							}
						}
					}
					/* 根据题目的可见性处理数据 */
					if (count($visibilitySchemas)) {
						$fnCheckSchemaVisibility($visibilitySchemas, $aRecData->data);
					}
				}
			}

			// 记录的分组
			if (!empty($aRecData->group_id)) {
				if (!isset($aGroupsById[$aRecData->group_id])) {
					if (!isset($modelGrpTeam)) {
						$modelGrpTeam = $this->model('matter\group\team');
					}
					$oGroup = $modelGrpTeam->byId($aRecData->group_id, ['fields' => 'title']);
					$aGroupsById[$aRecData->group_id] = $oGroup;
				} else {
					$oGroup = $aGroupsById[$aRecData->group_id];
				}
				if ($oGroup) {
					$aRecData->group = $oGroup;
				}
			}

			// 记录的记录轮次
			if (!empty($aRecData->rid)) {
				if (!isset($aRoundsById[$aRecData->rid])) {
					if (!isset($modelRnd)) {
						$modelRnd = $this->model('matter\enroll\round');
					}
					$round = $modelRnd->byId($aRecData->rid, ['fields' => 'rid,title,purpose,start_at,end_at,state']);
					$aRoundsById[$aRecData->rid] = $round;
				} else {
					$round = $aRoundsById[$aRecData->rid];
				}
				if ($round) {
					$aRecData->round = $round;
				}
			}

			foreach ($aFnHandlers as $fnHandler) {
				$fnHandler($aRecData);
			}
		}

		return $aRecDatas;
	}
	/**
	 * 返回指定活动，指定数据项的填写数据
	 */
	public function byId($id, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : self::DEFAULT_FIELDS;

		$q = [
			$fields,
			"xxt_enroll_record_data",
			['id' => $id, 'state' => 1],
		];
		$oRecData = $this->query_obj_ss($q);
		if ($oRecData) {
			if (property_exists($oRecData, 'tag')) {
				$oRecData->tag = empty($oRecData->tag) ? [] : json_decode($oRecData->tag);
			}
			if (property_exists($oRecData, 'like_log')) {
				$oRecData->like_log = empty($oRecData->like_log) ? new \stdClass : json_decode($oRecData->like_log);
			}
			if (property_exists($oRecData, 'dislike_log')) {
				$oRecData->dislike_log = empty($oRecData->dislike_log) ? new \stdClass : json_decode($oRecData->dislike_log);
			}
			if (property_exists($oRecData, 'agreed_log')) {
				$oRecData->agreed_log = empty($oRecData->agreed_log) ? new \stdClass : json_decode($oRecData->agreed_log);
			}
		}

		return $oRecData;
	}
	/**
	 * 添加协作填写项
	 */
	public function addCowork($oUser, $oApp, $oRecData, $value, $agreed) {
		$oRecord = $this->model('matter\enroll\record')->byId($oRecData->enroll_key, ['fields' => 'id,data']);
		if (false === $oRecord) {
			return fasle;
		}

		$current = time();
		$oNewItem = new \stdClass;
		$oNewItem->aid = $oApp->id;
		$oNewItem->rid = $oRecData->rid;
		$oNewItem->record_id = $oRecData->record_id;
		$oNewItem->enroll_key = $oRecData->enroll_key;
		$oNewItem->submit_at = $current;
		$oNewItem->userid = isset($oUser->uid) ? $oUser->uid : '';
		$oNewItem->nickname = isset($oUser->nickname) ? $this->escape($oUser->nickname) : '';
		$oNewItem->group_id = isset($oUser->group_id) ? $oUser->group_id : '';
		$oNewItem->schema_id = $oRecData->schema_id;
		$oNewItem->value = $this->escape($value);
		$oNewItem->agreed = $agreed;
		$oNewItem->multitext_seq = count($oRecData->value) + 1;

		$oNewItem->id = $this->insert('xxt_enroll_record_data', $oNewItem, true);

		/* 更新题目数据 */
		$oRecData->value[] = (object) ['id' => $oNewItem->id, 'value' => $oNewItem->value];
		$this->update(
			'xxt_enroll_record_data',
			['value' => $this->escape($this->toJson($oRecData->value))],
			['id' => $oRecData->id]
		);
		/* 更新记录数据 */
		$oRecord->data->{$oRecData->schema_id} = $oRecData->value;
		$this->update(
			'xxt_enroll_record',
			['data' => $this->escape($this->toJson($oRecord->data))],
			['id' => $oRecord->id]
		);

		$oNewItem = $this->byId($oNewItem->id, ['fields' => '*']);

		return $oNewItem;
	}
	/**
	 * 获得多项填写题数据
	 */
	public function getCowork($ek, $schemaId, $oOptions = []) {
		$fields = isset($oOptions['fields']) ? $oOptions['fields'] : self::DEFAULT_FIELDS . ',multitext_seq';

		$q = [
			$fields,
			'xxt_enroll_record_data',
			['enroll_key' => $ek, 'state' => 1, 'schema_id' => $schemaId],
		];
		if (isset($oOptions['excludeRoot']) && $oOptions['excludeRoot']) {
			$q[2]['multitext_seq'] = (object) ['op' => '>', 'pat' => 0];
		}
		if (isset($oOptions['agreed'])) {
			$q[2]['agreed'] = $oOptions['agreed'];
		}
		if (isset($oOptions['user'])) {
			$oUser = $oOptions['user'];
		}

		$fnHandler = function (&$oData) {
			if (property_exists($oData, 'tag')) {
				$oData->tag = empty($oData->tag) ? [] : json_decode($oData->tag);
			}
			if (property_exists($oData, 'like_log')) {
				$oData->like_log = empty($oData->like_log) ? new \stdClass : json_decode($oData->like_log);
			}
			if (property_exists($oData, 'agreed_log')) {
				$oData->agreed_log = empty($oData->agreed_log) ? new \stdClass : json_decode($oData->agreed_log);
			}
		};

		$q2 = [];
		// 排序规则
		$q2['o'] = "multitext_seq";
		$data = $this->query_objs_ss($q);
		if (count($data)) {
			foreach ($data as $schemaData) {
				if (isset($fnHandler)) {
					$fnHandler($schemaData);
				}
			}
		}

		return $data;
	}
	/**
	 * 对填写数据投票
	 */
	public function vote($oApp, $oTask, $recDataId, $oUser) {
		$oRecData = $this->byId($recDataId, ['fields' => 'id,aid,rid,group_id,enroll_key,schema_id,multitext_seq']);
		if (false === $oRecData) {
			return [false, '（1）指定的对象不存在或不可用'];
		}
		if ($oRecData->multitext_seq > 0) {
			$oParentRecData = $this->byRecord($oRecData->enroll_key, ['schema' => $oRecData->schema_id]);
			if (false === $oParentRecData) {
				return [false, '（2）指定的对象不存在或不可用'];
			}
		}
		$oRecord = $this->model('matter\enroll\record')->byId($oRecData->enroll_key, ['cascaded' => 'N', 'fields' => 'id,siteid,state,rid,group_id']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return [false, '指定的记录不存在'];
		}
		$modelTsk = $this->model('matter\enroll\task', $oApp);
		$oActiveRnd = $oApp->appRound;

		$oVoteRule = $modelTsk->ruleByTask($oTask, $oActiveRnd);
		if (false === $oVoteRule[0]) {
			return new \ParameterError($oVoteRule[1]);
		}
		$oVoteRule = $oVoteRule[1];

		if ($oVoteRule->state === 'BS') {
			return [false, '投票还没有开始'];
		}
		if ($oVoteRule->state === 'AE') {
			return [false, '投票已经结束'];
		}
		if (!in_array($oRecData->schema_id, $oVoteRule->schemas)) {
			return [false, '指定的题目不支持投票'];
		}
		if (!empty($oVoteRule->groups)) {
			if (!in_array($this->getDeepValue($oUser, 'group_id'), $oVoteRule->groups)) {
				return [false, '不符合投票的用户分组规则，不能投票'];
			}
		}

		$q = [
			'id,vote_at',
			'xxt_enroll_vote',
			['data_id' => $oRecData->id, 'rid' => $oActiveRnd->rid, 'userid' => $oUser->uid, 'state' => 1],
		];
		if ($oBefore = $this->query_obj_ss($q)) {
			return [false, '已经投过票，不允许重复投票', $oBefore];
		}

		$limitMax = $this->getDeepValue($oVoteRule, 'limit.max', 0);
		$q = [
			'count(*)',
			'xxt_enroll_vote',
			['aid' => $oRecData->aid, 'rid' => $oActiveRnd->rid, 'schema_id' => $oRecData->schema_id, 'userid' => $oUser->uid, 'state' => 1],
		];
		$beforeCount = (int) $this->query_val_ss($q);
		if ($limitMax > 0) {
			if ($beforeCount >= $limitMax) {
				return [false, '最多可以投【' . $limitMax . '】票，不能继续投票'];
			}
		}

		/* 新建投票记录 */
		$oNew = new \stdClass;
		$oNew->aid = $oRecData->aid;
		$oNew->rid = $oActiveRnd->rid;
		$oNew->siteid = $oRecord->siteid;
		$oNew->record_id = $oRecord->id;
		$oNew->data_id = $oRecData->id;
		$oNew->schema_id = $oRecData->schema_id;
		$oNew->vote_at = time();
		$oNew->userid = $oUser->uid;
		$oNew->nickname = $this->escape($oUser->nickname);
		$oNew->id = $this->insert('xxt_enroll_vote', $oNew, true);

		/* 更新汇总数据 */
		$this->update('xxt_enroll_record_data', ['vote_num' => (object) ['op' => '+=', 'pat' => 1]], ['id' => $oRecData->id]);
		if (isset($oParentRecData)) {
			/* 协作填写汇总数据 */
			$this->update('xxt_enroll_record_data', ['vote_num' => (object) ['op' => '+=', 'pat' => 1]], ['id' => $oParentRecData->id]);
			$this->update('xxt_enroll_record', ['vote_cowork_num' => (object) ['op' => '+=', 'pat' => 1]], ['id' => $oRecord->id]);
		} else {
			/* 题目汇总数据 */
			$this->update('xxt_enroll_record', ['vote_schema_num' => (object) ['op' => '+=', 'pat' => 1]], ['id' => $oRecord->id]);
		}

		$beforeCount++;

		return [true, $oNew, [$limitMax, $beforeCount]];
	}
	/**
	 * 撤销对填写数据投票
	 */
	public function unvote($oApp, $oTask, $recDataId, $oUser) {
		$oActiveRnd = $oApp->appRound;

		$oRecData = $this->byId($recDataId, ['fields' => 'id,aid,rid,enroll_key,schema_id,multitext_seq']);
		if (false === $oRecData) {
			return [false, '（1）指定的对象不存在或不可用'];
		}
		$q = [
			'id,vote_at',
			'xxt_enroll_vote',
			['data_id' => $oRecData->id, 'rid' => $oActiveRnd->rid, 'userid' => $oUser->uid, 'state' => 1],
		];
		$oBefore = $this->query_obj_ss($q);
		if (false === $oBefore) {
			return [false, '没有投过票，无法撤销投票'];
		}
		if ($oRecData->multitext_seq > 0) {
			$oParentRecData = $this->byRecord($oRecData->enroll_key, ['schema' => $oRecData->schema_id]);
			if (false === $oParentRecData) {
				return [false, '（2）指定的对象不存在或不可用'];
			}
		}
		$oRecord = $this->model('matter\enroll\record')->byId($oRecData->enroll_key, ['cascaded' => 'N', 'fields' => 'id,state,rid']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return [false, '指定的记录不存在'];
		}

		$modelTsk = $this->model('matter\enroll\task', $oApp);

		$oVoteRule = $modelTsk->ruleByTask($oTask, $oActiveRnd);
		if (false === $oVoteRule[0]) {
			return new \ParameterError($oVoteRule[1]);
		}
		$oVoteRule = $oVoteRule[1];

		if ($oVoteRule->state === 'BS') {
			return [false, '投票还没有开始'];
		}
		if ($oVoteRule->state === 'AE') {
			return [false, '投票已经结束'];
		}
		if (!in_array($oRecData->schema_id, $oVoteRule->schemas)) {
			return [false, '指定的题目不支持投票'];
		}
		if (!empty($oVoteRule->groups)) {
			if (!in_array($this->getDeepValue($oUser, 'group_id'), $oVoteRule->groups)) {
				return [false, '不符合投票的用户分组规则，不能投票'];
			}
		}

		/* 更新投票记录 */
		$this->update('xxt_enroll_vote', ['state' => 0], ['id' => $oBefore->id]);

		/* 更新汇总数据 */
		$this->update('xxt_enroll_record_data', ['vote_num' => (object) ['op' => '-=', 'pat' => 1]], ['id' => $oRecData->id]);
		if (isset($oParentRecData)) {
			/* 协作填写汇总数据 */
			$this->update('xxt_enroll_record_data', ['vote_num' => (object) ['op' => '+=', 'pat' => 1]], ['id' => $oParentRecData->id]);
			$this->update('xxt_enroll_record', ['vote_cowork_num' => (object) ['op' => '+=', 'pat' => 1]], ['id' => $oRecord->id]);
		} else {
			/* 题目汇总数据 */
			$this->update('xxt_enroll_record', ['vote_schema_num' => (object) ['op' => '+=', 'pat' => 1]], ['id' => $oRecord->id]);
		}

		$q = [
			'count(*)',
			'xxt_enroll_vote',
			['aid' => $oRecData->aid, 'rid' => $oActiveRnd->rid, 'userid' => $oUser->uid, 'state' => 1],
		];
		$beforeCount = (int) $this->query_val_ss($q);

		return [true, [$this->getDeepValue($oVoteRule, 'limit.max'), $beforeCount]];
	}
}