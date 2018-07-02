<?php
namespace matter\enroll;

require_once dirname(__FILE__) . '/entity.php';

/**
 * 登记的数据项
 */
class data_model extends entity_model {
	/**
	 * 缺省返回的列
	 */
	const DEFAULT_FIELDS = 'id,state,value,tag,supplement,rid,enroll_key,schema_id,userid,nickname,submit_at,score,remark_num,last_remark_at,like_num,like_log,modify_log,agreed,agreed_log,multitext_seq';
	/**
	 * 按题目记录数据
	 */
	public function setData($oUser, $oApp, $oRecord, $submitData, $submitkey = '', $oAssignScore = null) {
		if (empty($submitkey)) {
			$submitkey = empty($oUser->uid) ? '' : $oUser->uid;
		}

		$schemasById = []; // 方便获取登记项定义
		foreach ($oApp->dynaDataSchemas as $schema) {
			if (strpos($schema->id, 'member.') === 0) {
				$schema->id = 'member';
			}
			$schemasById[$schema->id] = $schema;
		}
		$dbData = $this->disposRecrdData($oApp, $schemasById, $submitData, $submitkey, $oRecord);
		if ($dbData[0] === false) {
			return $dbData;
		}
		$dbData = $dbData[1];

		/* 获得题目的得分 */
		$oRecordScore = $this->socreRecordData($oApp, $oRecord, $schemasById, $dbData, $oAssignScore);

		foreach ($dbData as $schemaId => $treatedValue) {
			if (!isset($schemasById[$schemaId])) {
				continue;
			}
			$oSchema = $schemasById[$schemaId];

			if (is_object($treatedValue) || is_array($treatedValue)) {
				$treatedValue = $this->toJson($treatedValue);
			}

			$oLastSchemaValues = $this->query_objs_ss(
				[
					'id,submit_at,value,modify_log,score,multitext_seq,remark_num,like_num',
					'xxt_enroll_record_data',
					['aid' => $oApp->id, 'rid' => $oRecord->rid, 'enroll_key' => $oRecord->enroll_key, 'schema_id' => $schemaId, 'state' => 1],
				]
			);
			/**
			 * 记录数据
			 */
			if (empty($oLastSchemaValues)) {
				/* 处理多项填写题型，作为协作填写题时不允许提交数据 */
				if ($oSchema->type == 'multitext') {
					if (isset($oSchema->cowork) && $oSchema->cowork === 'Y') {
						unset($dbData->{$schemaId});
						$treatedValue = '[]';
					} else {
						$treatedValues = json_decode($treatedValue);
						foreach ($treatedValues as $k => $v) {
							$aSchemaValue = [
								'aid' => $oApp->id,
								'rid' => $oRecord->rid,
								'enroll_key' => $oRecord->enroll_key,
								'submit_at' => $oRecord->enroll_at,
								'userid' => isset($oUser->uid) ? $oUser->uid : '',
								'nickname' => $oRecord->nickname,
								'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
								'schema_id' => $schemaId,
								'multitext_seq' => (int) $k + 1,
								'value' => $this->escape($v->value),
							];
							$dataId = $this->insert('xxt_enroll_record_data', $aSchemaValue, true);
							$treatedValues[$k]->id = $dataId;
						}
						$dbData->{$schemaId} = $treatedValues;
						$treatedValue = $this->toJson($treatedValues);
					}
				}
				if (!empty($treatedValue)) {
					$aSchemaValue = [
						'aid' => $oApp->id,
						'rid' => $oRecord->rid,
						'enroll_key' => $oRecord->enroll_key,
						'submit_at' => $oRecord->enroll_at,
						'userid' => isset($oUser->uid) ? $oUser->uid : '',
						'nickname' => $oRecord->nickname,
						'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
						'schema_id' => $schemaId,
						'value' => $this->escape($treatedValue),
					];
					isset($oRecordScore->{$schemaId}) && $aSchemaValue['score'] = $oRecordScore->{$schemaId};
					$this->insert('xxt_enroll_record_data', $aSchemaValue, false);
				}
			} else if (count($oLastSchemaValues) == 1) {
				$aSchemaValue = [];
				/* 处理多项填写题型，作为协作填写题时不允许提交数据 */
				if ($oSchema->type == 'multitext') {
					if (isset($oSchema->cowork) && $oSchema->cowork === 'Y') {
						$treatedValue = $oLastSchemaValues[0]->value;
						$dbData->{$schemaId} = json_decode($oLastSchemaValues[0]->value);
					} else {
						$treatedValues = json_decode($treatedValue);
						foreach ($treatedValues as $k => $v) {
							$aSchemaValue2 = [
								'aid' => $oApp->id,
								'rid' => $oRecord->rid,
								'enroll_key' => $oRecord->enroll_key,
								'submit_at' => $oRecord->enroll_at,
								'userid' => isset($oUser->uid) ? $oUser->uid : '',
								'nickname' => $oRecord->nickname,
								'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
								'schema_id' => $schemaId,
								'multitext_seq' => (int) $k + 1,
								'value' => $this->escape($v->value),
							];
							$dataId = $this->insert('xxt_enroll_record_data', $aSchemaValue2, true);
							$treatedValues[$k]->id = $dataId;
						}
						$dbData->{$schemaId} = $treatedValues;
						$treatedValue = $this->toJson($treatedValues);
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
					$aSchemaValue = [
						'submit_at' => $oRecord->enroll_at,
						'userid' => isset($oUser->uid) ? $oUser->uid : '',
						'nickname' => $oRecord->nickname,
						'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
						'value' => $this->escape($treatedValue),
						'modify_log' => $this->escape($this->toJson($valueModifyLogs)),
						'score' => isset($oRecordScore->{$schemaId}) ? $oRecordScore->{$schemaId} : 0,
					];
				} else {
					$aSchemaValue = [
						'score' => isset($oRecordScore->{$schemaId}) ? $oRecordScore->{$schemaId} : 0,
					];
				}

				if (!empty($aSchemaValue)) {
					$this->update(
						'xxt_enroll_record_data',
						$aSchemaValue,
						['id' => $oLastSchemaValues[0]->id]
					);
				}
			} else {
				/* 获得一道题目的多条数据，多项填写题型 */
				$aSchemaValue = [];
				if ($oSchema->type === 'multitext') {
					$newSchemaValues = json_decode($treatedValue);
					$oBeforeSchemaVal = null; //旧的总数据
					$beforeSchemaItems = []; //旧的项
					foreach ($oLastSchemaValues as $v) {
						if ((int) $v->multitext_seq > 0) {
							$beforeSchemaItems[$v->id] = $v;
						} else if ((int) $v->multitext_seq === 0) {
							/* 题目的根数据 */
							$oBeforeSchemaVal = $v;
						}
					}
					if (isset($oSchema->cowork) && $oSchema->cowork === 'Y') {
						if (isset($oBeforeSchemaVal->value)) {
							$treatedValue = $oBeforeSchemaVal->value;
							$dbData->{$schemaId} = json_encode($treatedValue);
						}
					} else {
						foreach ($newSchemaValues as $k => $newSchemaValue) {
							if ($newSchemaValue->id == 0) {
								$aSchemaValue = [
									'aid' => $oApp->id,
									'rid' => $oRecord->rid,
									'enroll_key' => $oRecord->enroll_key,
									'submit_at' => $oRecord->enroll_at,
									'userid' => isset($oUser->uid) ? $oUser->uid : '',
									'nickname' => $oRecord->nickname,
									'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
									'schema_id' => $schemaId,
									'multitext_seq' => (int) $k + 1,
									'value' => $this->escape($newSchemaValue->value),
								];
								$dataId = $this->insert('xxt_enroll_record_data', $aSchemaValue, true);
								$newSchemaValues[$k]->id = $dataId;
							} else {
								if (isset($beforeSchemaItems[$newSchemaValue->id])) {
									if ($beforeSchemaItems[$newSchemaValue->id]->value !== $newSchemaValue->value || $beforeSchemaItems[$newSchemaValue->id]->multitext_seq != ($k + 1)) {
										if (strlen($beforeSchemaItems[$newSchemaValue->id]->modify_log)) {
											$valueModifyLogs = json_decode($beforeSchemaItems[$newSchemaValue->id]->modify_log);
										} else {
											$valueModifyLogs = [];
										}
										$oNewModifyLog = new \stdClass;
										$oNewModifyLog->submitAt = $beforeSchemaItems[$newSchemaValue->id]->submit_at;
										$oNewModifyLog->value = $beforeSchemaItems[$newSchemaValue->id]->value;
										$valueModifyLogs[] = $oNewModifyLog;
										$aSchemaValue = [
											'submit_at' => $oRecord->enroll_at,
											'userid' => isset($oUser->uid) ? $oUser->uid : '',
											'nickname' => $oRecord->nickname,
											'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
											'value' => $this->escape($newSchemaValue->value),
											'modify_log' => $this->escape($this->toJson($valueModifyLogs)),
											'multitext_seq' => (int) $k + 1,
										];

										$this->update(
											'xxt_enroll_record_data',
											$aSchemaValue,
											['id' => $newSchemaValue->id]
										);
									}
									// 处理完后就去除这一条如果还有剩余的说明是本次用户修改已经删除的
									unset($beforeSchemaItems[$newSchemaValue->id]);
								}
							}
						}
						/* 处理被删除的数据 */
						if (count($beforeSchemaItems) > 0) {
							foreach ($beforeSchemaItems as $oBeforeSchemaItem) {
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
						$dbData->{$schemaId} = $newSchemaValues;
						$treatedValue = $this->toJson($newSchemaValues);

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
							$aSchemaValue = [
								'submit_at' => $oRecord->enroll_at,
								'userid' => isset($oUser->uid) ? $oUser->uid : '',
								'nickname' => $oRecord->nickname,
								'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
								'value' => $this->escape($treatedValue),
								'modify_log' => $this->escape($this->toJson($valueModifyLogs)),
								'multitext_seq' => 0,
							];

							$this->update(
								'xxt_enroll_record_data',
								$aSchemaValue,
								['id' => $oBeforeSchemaVal->id]
							);
						}
					}
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
				/* 自定义用户信息 */
				$oDbData->{$schemaId} = $submitVal;
			} else if (isset($schemasById[$schemaId])) {
				/* 活动中定义的登记项 */
				$oSchema = $schemasById[$schemaId];
				if (empty($oSchema->type)) {
					return [false, '登记项【' . $oSchema->id . '】定义不完整'];
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
						throw new \Exception('登记的数据类型和登记项【image】需要的类型不匹配');
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
						throw new \Exception('登记的数据类型和登记项【file】需要的类型不匹配');
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
						throw new \Exception('登记的数据类型和登记项【multiple】需要的类型不匹配');
					}
					break;
				case 'url':
					unset($submitVal->_text);
					$oDbData->{$schemaId} = $submitVal;
					break;
				default:
					// string & score
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
	 */
	public function socreRecordData($oApp, $oRecord, $aSchemasById, $dbData, $oAssignScore = null) {
		$oRecordScore = new \stdClass; // 记录的得分数据
		$oRecordScore->sum = 0; // 记录总分
		$quizSchemaNum = 0; // 测验题目的数量
		$quizCorrectSchemaNum = 0; // 答对测验题目的数量

		/* 评估 */
		$fnEvaluation = function (&$oSchema, $treatedValue, &$oRecordScore) {
			$schemaScore = null; // 题目的得分
			switch ($oSchema->type) {
			case 'shorttext';
				if (isset($oSchema->format) && $oSchema->format === 'number') {
					$weight = (isset($oSchema->weight) && is_numeric($oSchema->weight)) ? $oSchema->weight : 1;
					$schemaScore = $treatedValue * $weight;
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
		$fnQuestion = function (&$oSchema, $treatedValue, &$oRecordScore) use ($oRecord, $oAssignScore, $quizSchemaNum, $quizCorrectSchemaNum) {
			if (empty($oSchema->answer)) {
				return false;
			}
			$quizScore = null;
			$quizSchemaNum++;
			switch ($oSchema->type) {
			case 'single':
				if ($treatedValue === $oSchema->answer) {
					$quizScore = empty($oSchema->score) ? 0 : $oSchema->score;
					$quizCorrectSchemaNum++;
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
					$quizCorrectSchemaNum++;
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
					$quizCorrectSchemaNum++;
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
			if ($quizSchemaNum === $quizCorrectSchemaNum) {
				$oRecordScore->sum = $oApp->scenarioConfig->quizSum;
			}
		}

		return $oRecordScore;
	}
	/**
	 * 获得指定登记记录登记数据的详细信息
	 */
	public function byRecord($ek, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : self::DEFAULT_FIELDS;

		$q = [
			$fields,
			'xxt_enroll_record_data',
			['enroll_key' => $ek, 'state' => 1, 'multitext_seq' => 0],
		];

		$fnHandler = function (&$oData) {
			$oData->tag = empty($oData->tag) ? [] : json_decode($oData->tag);
			$oData->like_log = empty($oData->like_log) ? new \stdClass : json_decode($oData->like_log);
			$oData->agreed_log = empty($oData->agreed_log) ? new \stdClass : json_decode($oData->agreed_log);
		};

		if (isset($aOptions['schema'])) {
			if (is_array($aOptions['schema'])) {
				$oResult = new \stdClass;
				$q[2]['schema_id'] = $aOptions['schema'];
				$data = $this->query_objs_ss($q);
				if (count($data)) {
					foreach ($data as $schemaData) {
						if (isset($fnHandler)) {
							$fnHandler($schemaData);
						}
						$schemaId = $schemaData->schema_id;
						unset($schemaData->schema_id);
						$oResult->{$schemaId} = $schemaData;
					}
				}
				return $oResult;
			} else {
				$q[2]['schema_id'] = $aOptions['schema'];
				if ($data = $this->query_obj_ss($q)) {
					if (isset($fnHandler)) {
						$fnHandler($data);
					}
				}
				return $data;
			}
		} else {
			$oResult = new \stdClass;
			$data = $this->query_objs_ss($q);
			if (count($data)) {
				foreach ($data as $schemaData) {
					if (isset($fnHandler)) {
						$fnHandler($schemaData);
					}
					$schemaId = $schemaData->schema_id;
					unset($schemaData->schema_id);
					$oResult->{$schemaId} = $schemaData;
				}
			}

			return $oResult;
		}
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
			"xxt_enroll_record_data",
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
				$q[2] .= " and rid='{$rid}'";
			}
		} else {
			/* 没有指定轮次，就使用当前轮次 */
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$q[2] .= " and rid='{$activeRound->rid}'";
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
	 * 返回指定活动，指定数据项的填写数据
	 */
	public function byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : self::DEFAULT_FIELDS;

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
			if (property_exists($oRecData, 'agreed_log')) {
				$oRecData->agreed_log = empty($oRecData->agreed_log) ? new \stdClass : json_decode($oRecData->agreed_log);
			}
		}

		return $oRecData;
	}
	/**
	 * 获得多项填写题数据
	 */
	public function getMultitext($ek, $schema, $oOptions = []) {
		$fields = isset($oOptions['fields']) ? $oOptions['fields'] : self::DEFAULT_FIELDS . ',multitext_seq';

		$q = [
			$fields,
			'xxt_enroll_record_data',
			['enroll_key' => $ek, 'state' => 1, 'schema_id' => $schema],
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
}