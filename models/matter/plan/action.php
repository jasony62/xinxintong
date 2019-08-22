<?php
namespace matter\plan;
/**
 *
 */
class action_model extends \TMS_MODEL {
	/**
	 * 按题目记录数据
	 */
	public function setData($oUser, $oAction, $oUsrTask, $oSubmitData, $submitkey = '') {
		if (empty($submitkey)) {
			$submitkey = empty($oUser) ? '' : $oUser->uid;
		}
		$schemasById = []; // 方便获取登记项定义
		foreach ($oAction->checkSchemas as $schema) {
			$schemasById[$schema->id] = $schema;
		}
		$dbData = $this->disposRecrdData($oAction, $schemasById, $oSubmitData, $submitkey);
		if ($dbData[0] === false) {
			return $dbData;
		}
		$dbData = $dbData[1];

		$oActionScore = new \stdClass;
		$oActionScore->sum = 0; //记录总分
		foreach ($dbData as $schemaId => $treatedValue) {
			if (is_object($treatedValue) || is_array($treatedValue)) {
				$treatedValue = $this->toJson($treatedValue);
			}
			$oLastSchemaValue = $this->query_obj_ss(
				[
					'id,enroll_at,value,modify_log',
					'xxt_plan_task_action',
					['userid' => $oUsrTask->userid, 'task_id' => $oUsrTask->id, 'action_schema_id' => $oAction->id, 'check_schema_id' => $schemaId, 'state' => 1],
				]
			);
			if (isset($schemasById[$schemaId])) {
				$oSchema = $schemasById[$schemaId];
				/* 根据填写内容计算分数 */
				if ($oSchema->type == 'shorttext' && isset($oSchema->format) && $oSchema->format == 'number') {
					$weight = isset($oSchema->weight) ? $oSchema->weight : 1;
					$oActionScore->{$schemaId} = $treatedValue * $weight;
					$oActionScore->sum += $oActionScore->{$schemaId};
				}
				/* 计算题目的分数。只支持对单选题和多选题自动打分 */
				$quizScore = null;
				if (isset($oSchema->requireScore) && $oSchema->requireScore === 'Y') {
					if (!empty($oSchema->answer)) {
						switch ($oSchema->type) {
						case 'single':
							$quizScore = $treatedValue === $oSchema->answer ? ($oSchema->score ? $oSchema->score : 0) : 0;
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
							$quizScore = ($oSchema->score ? $oSchema->score : 0) / count($oSchema->answer) * $correct;
							break;
						default: //主观题
							if (!empty($assignScore) && isset($assignScore->{$schemaId})) {
								//有指定的优先使用指定的评分
								$quizScore = $assignScore->{$schemaId};
							} elseif (!empty($oLastSchemaValue) && ($oLastSchemaValue->value == $treatedValue) && !empty($oLastSchemaValue->score)) {
								//有提交记录且没修改且已经评分
								$quizScore = $oLastSchemaValue->score;
							} elseif ($treatedValue === $oSchema->answer) {
								$quizScore = $oSchema->score;
							} else {
								$quizScore = 0;
							}
							break;
						}
					}
					//记录分数
					if (isset($quizScore)) {
						$oActionScore->{$schemaId} = $quizScore;
						$oActionScore->sum += (int) $quizScore;
					}
				}
				/* 记录结果 */
				if (false === $oLastSchemaValue) {
					$aSchemaValue = [
						'siteid' => $oUsrTask->siteid,
						'aid' => $oUsrTask->aid,
						'task_id' => $oUsrTask->id,
						'task_schema_id' => $oUsrTask->task_schema_id,
						'action_schema_id' => $oAction->id,
						'check_schema_id' => $schemaId,
						'userid' => isset($oUser->uid) ? $oUser->uid : '',
						'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
						'enroll_at' => $oUsrTask->last_enroll_at,
						'value' => $this->escape($treatedValue),
					];
					isset($oActionScore->{$schemaId}) && $aSchemaValue['score'] = $oActionScore->{$schemaId};
					$this->insert('xxt_plan_task_action', $aSchemaValue, false);
				} else {
					$aSchemaValue = '';
					if ($treatedValue !== $oLastSchemaValue->value) {
						if (strlen($oLastSchemaValue->modify_log)) {
							$aValueModifyLogs = json_decode($oLastSchemaValue->modify_log);
						} else {
							$aValueModifyLogs = [];
						}
						$oNewModifyLog = new \stdClass;
						$oNewModifyLog->enrollAt = $oLastSchemaValue->enroll_at;
						$oNewModifyLog->value = $this->escape($oLastSchemaValue->value);
						$aValueModifyLogs[] = $oNewModifyLog;
						$aSchemaValue = [
							'enroll_at' => $oUsrTask->last_enroll_at,
							'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
							'value' => $this->escape($treatedValue),
							'modify_log' => $this->toJson($aValueModifyLogs),
						];
						$aSchemaValue['score'] = isset($oActionScore->{$schemaId}) ? $oActionScore->{$schemaId} : 0;
					}
					if (!empty($aSchemaValue)) {
						$this->update(
							'xxt_plan_task_action',
							$aSchemaValue,
							['id' => $oLastSchemaValue->id]
						);
					}
				}
			}
		}

		return (object) ['dbData' => $dbData, 'score' => $oActionScore];
	}
	/**
	 * 处理提交的数据
	 */
	public function disposRecrdData($oAction, $schemasById, $oSubmitData, $submitkey) {
		$dbData = new \stdClass; // 处理后的保存到数据库中的登记记录
		/* 处理提交的数据，进行格式转换等操作 */
		foreach ($oSubmitData as $schemaId => $submitVal) {
			if (isset($schemasById[$schemaId])) {
				/* 活动中定义的登记项 */
				$schema = $schemasById[$schemaId];
				if (empty($schema->type)) {
					return [false, '登记项【' . $schema->id . '】定义不完整'];
				}
				switch ($schema->type) {
				case 'image':
					if (is_array($submitVal) && (isset($submitVal[0]->serverId) || isset($submitVal[0]->imgSrc))) {
						/* 上传图片 */
						$treatedValue = [];
						$fsuser = $this->model('fs/user', $oAction->siteid);
						foreach ($submitVal as $img) {
							$rst = $fsuser->storeImg($img);
							if (false === $rst[0]) {
								return $rst;
							}
							$treatedValue[] = $rst[1];
						}
						$treatedValue = implode(',', $treatedValue);
						$dbData->{$schemaId} = $treatedValue;
					} else if (empty($submitVal)) {
						$dbData->{$schemaId} = $treatedValue = '';
					} else if (is_string($submitVal)) {
						$dbData->{$schemaId} = $submitVal;
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
									$fsAli = $this->model('fs/alioss', $oAction->siteid);
									$dest = '/plan/' . $oAction->id . '/' . $submitkey . '_' . $oFile->name;
									$fileUploaded2 = $fsAli->getBaseURL() . $dest;
								} else {
									$fsResum = $this->model('fs/local', $oAction->siteid, '_resumable');
									$fileUploaded = $fsResum->rootDir . '/plan/' . $oAction->id . '/' . $submitkey . '_' . $oFile->name;
									$fsUser = $this->model('fs/local', $oAction->siteid, '_user');
									$dirUploaded = $fsUser->rootDir . '/' . $submitkey;
									if (!file_exists($dirUploaded)) {
										if (false === mkdir($dirUploaded, 0777, true)) {
											return [false, '创建文件上传目录失败'];
										}
									}
									if (file_exists($fileUploaded)) {
										/* 如果同一次提交中包含相同的文件，文件只会上传一次，并且被改名 */
										$fileUploaded2 = $dirUploaded . '/' . $file->name;
										if (false === @rename($fileUploaded, $fileUploaded2)) {
											return [false, '移动上传文件失败'];
										}
									}
								}
								if (empty($fileUploaded2)) {
									return [false, '没有获得上传的文件'];
								}
								unset($oFile->uniqueIdentifier);
								$oFile->url = $fileUploaded2;
								$treatedValue[] = $oFile;
							} else {
								/* 已经上传过的文件 */
								$treatedValue[] = $oFile;
							}
						}
						$dbData->{$schemaId} = $treatedValue;
					} else if (is_string($submitVal)) {
						$dbData->{$schemaId} = $submitVal;
					} else {
						throw new \Exception('登记的数据类型和登记项【file】需要的类型不匹配');
					}
					break;
				case 'multiple':
					if (is_object($submitVal)) {
						// 多选题，将选项合并为逗号分隔的字符串
						$treatedValue = implode(',', array_keys(array_filter((array) $submitVal, function ($i) {return $i;})));
						$dbData->{$schemaId} = $treatedValue;
					} else if (is_string($submitVal)) {
						$dbData->{$schemaId} = $submitVal;
					} else {
						throw new \Exception('登记的数据类型和登记项【multiple】需要的类型不匹配');
					}
					break;
				default:
					// string & score
					$dbData->{$schemaId} = $treatedValue = $submitVal;
				}
			} else {
				/* 如果记录活动指定匹配清单，那么提交数据会包含匹配登记记录的数据，但是这些数据不在登记项定义中 */
				$dbData->{$schemaId} = $treatedValue = $submitVal;
			}
		}

		return [true, $dbData];
	}
	/**
	 * 保存补充说明
	 *
	 * @param object $oUser [uid]
	 * @param object $oAction
	 * @param string $oUserTask
	 * @param array $submitSupp 用户提交的补充说明
	 *
	 */
	public function setSupplement($oUser, $oAction, $oUsrTask, $submitSupl) {
		$count = 0;
		foreach ($submitSupl as $schemaId => $sSupplement) {
			$rst = $this->update(
				'xxt_plan_task_action',
				['supplement' => $this->escape($sSupplement)],
				['userid' => $oUser->uid, 'task_id' => $oUsrTask->id, 'action_schema_id' => $oAction->id, 'check_schema_id' => $schemaId]
			);
			if ($rst) {
				$count++;
			}
		}

		return $count;
	}
	/**
	 * 获得指定记录数据的详细信息
	 */
	public function byRecord($taskId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_plan_task_action',
			['task_id' => $taskId, 'state' => 1],
		];

		$fnHandler = function (&$oData) {
			$oData->tag = empty($oData->tag) ? [] : json_decode($oData->tag);
		};

		if (isset($options['schema'])) {
			if (is_array($options['schema'])) {
				$result = new \stdClass;
				$q[2]['check_schema_id'] = $options['schema'];
				$data = $this->query_objs_ss($q);
				if (count($data)) {
					foreach ($data as $schemaData) {
						if (isset($fnHandler)) {
							$fnHandler($schemaData);
						}
						$schemaId = $schemaData->check_schema_id;
						unset($schemaData->check_schema_id);
						$result->{$schemaId} = $schemaData;
					}
				}
				return $result;
			} else {
				$q[2]['check_schema_id'] = $options['schema'];
				if ($data = $this->query_obj_ss($q)) {
					if (isset($fnHandler)) {
						$fnHandler($data);
					}
				}
				return $data;
			}
		} else {
			$result = new \stdClass;
			$data = $this->query_objs_ss($q);
			if (count($data)) {
				foreach ($data as $schemaData) {
					if (isset($fnHandler)) {
						$fnHandler($schemaData);
					}
					$schemaId = $schemaData->check_schema_id;
					unset($schemaData->check_schema_id);
					$result->{$schemaId} = $schemaData;
				}
			}

			return $result;
		}
	}
	/**
	 * 计算指定登记项所有记录的合计
	 */
	public function sum4Schema($oApp, $taskSchmId = '', $actSchmId = '') {
		$result = new \stdClass;
		$checkSchemas = $oApp->checkSchemas;
		/* 每道题目的合计 */
		foreach ($checkSchemas as $schema) {
			if (isset($schema->format) && $schema->format === 'number') {
				$q = [
					'sum(value)',
					'xxt_plan_task_action',
					['aid' => $oApp->id, 'check_schema_id' => $schema->id, 'state' => 1],
				];
				!empty($taskSchmId) && $q[2]['task_schema_id'] = $this->escape($taskSchmId);
				!empty($actSchmId) && $q[2]['action_schema_id'] = $this->escape($actSchmId);

				$sum = (float) $this->query_val_ss($q);
				$sum = number_format($sum, 2, '.', '');
				$result->{$schema->id} = (float) $sum;
			}
		}

		return $result;
	}
	/**
	 * 计算指定登记项所有记录的合计
	 */
	public function score4Schema($oApp, $taskSchmId = '') {
		$result = new \stdClass;
		$checkSchemas = $oApp->checkSchemas;

		/* 每道题目的数据分 */
		foreach ($checkSchemas as $oSchema) {
			if ((isset($oSchema->requireScore) && $oSchema->requireScore === 'Y') || (isset($oSchema->format) && $oSchema->format === 'number')) {
				$q = [
					'sum(score)',
					'xxt_plan_task_action',
					['aid' => $oApp->id, 'check_schema_id' => $oSchema->id, 'state' => 1],
				];
				!empty($taskSchmId) && $q[2]['task_schema_id'] = $this->escape($taskSchmId);

				$sum = (float) $this->query_val_ss($q);
				$sum = number_format($sum, 2, '.', '');
				$result->{$oSchema->id} = (float) $sum;
			}
		}

		/*所有题的数据分合计*/
		$q = [
			'sum(score)',
			'xxt_plan_task_action',
			['aid' => $oApp->id, 'state' => 1],
		];
		!empty($taskSchmId) && $q[2]['task_schema_id'] = $this->escape($taskSchmId);

		$sum = (float) $this->query_val_ss($q);
		$sum = number_format($sum, 2, '.', '');
		$result->sum = (float) $sum;

		return $result;
	}
}