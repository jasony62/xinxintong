<?php
namespace matter\enroll;
/**
 * 登记的数据项
 */
class data_model extends \TMS_MODEL {
	/**
	 * 缺省返回的列
	 */
	const DEFAULT_FIELDS = 'id,value,tag,supplement,enroll_key,schema_id,userid,submit_at,score,remark_num,last_remark_at,like_num,like_log,modify_log,agreed,agreed_log';
	/**
	 * 按题目记录数据
	 */
	public function setData($oUser, $oApp, $oRecord, $submitData, $submitkey = '') {
		if (empty($submitkey)) {
			$submitkey = empty($oUser) ? '' : $oUser->uid;
		}
		
		$schemasById = []; // 方便获取登记项定义
		foreach ($oApp->dataSchemas as $schema) {
			$schemasById[$schema->id] = $schema;
		}
		$dbData = $this->disposRecrdData($oApp, $schemasById, $submitData, $submitkey);
		if ($dbData[0] === false) {
			return $dbData;
		}
		$dbData = $dbData[1];

		$oRecordScore = new \stdClass;
		$oRecordScore->sum = 0; //记录总分
		foreach ($dbData as $schemaId => $treatedValue) {
			if (is_object($treatedValue) || is_array($treatedValue)) {
				$treatedValue = $this->toJson($treatedValue);
			}

			$lastSchemaValues = $this->query_objs_ss(
				[
					'id,submit_at,value,modify_log,score,multitext_seq',
					'xxt_enroll_record_data',
					['aid' => $oApp->id, 'rid' => $oRecord->rid, 'enroll_key' => $oRecord->enroll_key, 'schema_id' => $schemaId, 'state' => 1],
				]
			);
			// 计算题目的得分
			if (isset($schemasById[$schemaId])) {
				$schema = $schemasById[$schemaId];
				if ($schema->type == 'shorttext' && isset($schema->format) && $schema->format == 'number') {
					$weight = isset($schema->weight) ? $schema->weight : 1;
					$oRecordScore->{$schemaId} = $treatedValue * $weight;
					$oRecordScore->sum += $oRecordScore->{$schemaId};
				}
				/* 计算题目的分数。只支持对单选题和多选题自动打分 */
				if ($oApp->scenario === 'quiz') {
					$quizScore = null;
					if (isset($schema->requireScore) && $schema->requireScore === 'Y') {
						if (!empty($schema->answer)) {
							switch ($schema->type) {
							case 'single':
								$quizScore = $treatedValue === $schema->answer ? ($schema->score ? $schema->score : 0) : 0;
								break;
							case 'multiple':
								$correct = 0;
								$pendingValues = explode(',', $treatedValue);
								is_string($schema->answer) && $schema->answer = explode(',', $schema->answer);
								foreach ($pendingValues as $pending) {
									if (in_array($pending, $schema->answer)) {
										$correct++;
									} else {
										$correct = 0;
										break;
									}
								}
								$quizScore = ($schema->score ? $schema->score : 0) / count($schema->answer) * $correct;
								break;
							//主观题
							default:
								if (!empty($assignScore) && isset($assignScore->{$schemaId})) {
									//有指定的优先使用指定的评分
									$quizScore = $assignScore->{$schemaId};
								} elseif (!empty($lastSchemaValues) && (count($lastSchemaValues) == 1) && ($lastSchemaValues[0]->value == $treatedValue) && !empty($lastSchemaValues[0]->score)) {
									//有提交记录且没修改且已经评分
									$quizScore = $lastSchemaValues[0]->score;
								} elseif ($treatedValue === $schema->answer) {
									$quizScore = $schema->score;
								} else {
									$quizScore = 0;
								}
								break;
							}
						}
						//记录分数
						if (isset($quizScore)) {
							$oRecordScore->{$schemaId} = $quizScore;
							$oRecordScore->sum += (int) $quizScore;
						}
					}
				}
			}
			//记录结果
			if (empty($lastSchemaValues)) {
				$schemaValue = [
					'aid' => $oApp->id,
					'rid' => $oRecord->rid,
					'enroll_key' => $oRecord->enroll_key,
					'submit_at' => $oRecord->enroll_at,
					'userid' => isset($oUser->uid) ? $oUser->uid : '',
					'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
					'schema_id' => $schemaId,
					'value' => $this->escape($treatedValue),
				];
				isset($oRecordScore->{$schemaId}) && $schemaValue['score'] = $oRecordScore->{$schemaId};
				$this->insert('xxt_enroll_record_data', $schemaValue, false);
				/* 处理多项填写题型 */
				if (isset($schemasById[$schemaId])) {
					$schema = $schemasById[$schemaId];
					if ($schema->type == 'multitext') {
						$treatedValues = json_decode($treatedValue);
						foreach ($treatedValues as $k => $v) {
							$schemaValue = [
								'aid' => $oApp->id,
								'rid' => $oRecord->rid,
								'enroll_key' => $oRecord->enroll_key,
								'submit_at' => $oRecord->enroll_at,
								'userid' => isset($oUser->uid) ? $oUser->uid : '',
								'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
								'schema_id' => $schemaId,
								'multitext_seq' => (int)$k + 1,
								'value' => $this->escape($v),
							];
							$this->insert('xxt_enroll_record_data', $schemaValue, false);
						}
					}
				}
			} else if (count($lastSchemaValues) == 1) {
				if ($treatedValue !== $lastSchemaValues[0]->value) {
					if (strlen($lastSchemaValues[0]->modify_log)) {
						$valueModifyLogs = json_decode($lastSchemaValues[0]->modify_log);
					} else {
						$valueModifyLogs = [];
					}
					$newModifyLog = new \stdClass;
					$newModifyLog->submitAt = $lastSchemaValues[0]->submit_at;
					$newModifyLog->value = $this->escape($lastSchemaValues[0]->value);
					$valueModifyLogs[] = $newModifyLog;
					$schemaValue = [
						'submit_at' => $oRecord->enroll_at,
						'userid' => isset($oUser->uid) ? $oUser->uid : '',
						'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
						'value' => $this->escape($treatedValue),
						'modify_log' => $this->toJson($valueModifyLogs),
					];
				}
				$schemaValue['score'] = isset($oRecordScore->{$schemaId}) ? $oRecordScore->{$schemaId} : 0;

				if (!empty($schemaValue)) {
					$this->update(
						'xxt_enroll_record_data',
						$schemaValue,
						['id' => $lastSchemaValues[0]->id]
					);
				}

				/* 处理多项填写题型 */
				if (isset($schemasById[$schemaId])) {
					$schema = $schemasById[$schemaId];
					if ($schema->type == 'multitext') {
						$treatedValues = json_decode($treatedValue);
						foreach ($treatedValues as $k => $v) {
							$schemaValue2 = [
								'aid' => $oApp->id,
								'rid' => $oRecord->rid,
								'enroll_key' => $oRecord->enroll_key,
								'submit_at' => $oRecord->enroll_at,
								'userid' => isset($oUser->uid) ? $oUser->uid : '',
								'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
								'schema_id' => $schemaId,
								'multitext_seq' => (int)$k + 1,
								'value' => $this->escape($v),
							];
							$this->insert('xxt_enroll_record_data', $schemaValue2, false);
						}
					}
				}
			} else { // 处理可多项填写题型
				if (isset($schemasById[$schemaId])) {
					$schema = $schemasById[$schemaId];
					if ($schema->type === 'multitext') {
						$ctTreatedVals = []; // 多项填写题的所有自定义的条
						$ctTreatedVals[0] = $treatedValue; // (第一条为总数据)
						$treatedValues = json_decode($treatedValue);
						foreach ($treatedValues as $k => $v) {
							$seq = (int)$k + 1; // 保持与插入时的seq一致
							$ctTreatedVals[$seq] = $v;
						}
						// 修改多项填写题的总记录
						foreach ($lastSchemaValues as $lastSchemaValue) {
							$multitext_seq = $lastSchemaValue->multitext_seq;
							// 是否在新提交的数据总找到了这一条，找到了就处理，没有找到就说明已经删除了
							if (isset($ctTreatedVals[$multitext_seq])) {
								if ($ctTreatedVals[$multitext_seq] !== $lastSchemaValue->value) {
									if (strlen($lastSchemaValue->modify_log)) {
										$valueModifyLogs = json_decode($lastSchemaValue->modify_log);
									} else {
										$valueModifyLogs = [];
									}
									$newModifyLog = new \stdClass;
									$newModifyLog->submitAt = $lastSchemaValue->submit_at;
									$newModifyLog->value = $this->escape($lastSchemaValue->value);
									$valueModifyLogs[] = $newModifyLog;
									$schemaValue = [
										'submit_at' => $oRecord->enroll_at,
										'userid' => isset($oUser->uid) ? $oUser->uid : '',
										'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
										'value' => $this->escape($ctTreatedVals[$multitext_seq]),
										'modify_log' => $this->toJson($valueModifyLogs),
									];

									$this->update(
										'xxt_enroll_record_data',
										$schemaValue,
										['id' => $lastSchemaValue->id]
									);
								}
								// 处理完后就去除这一条如果还有剩余的就是新添加的
								unset($ctTreatedVals[$multitext_seq]);
							} else { // 删除已经删除了的条
								$this->delete('xxt_enroll_record_data', ['id' => $lastSchemaValue->id]);
							}
						}
						// 处理新添加的条
						if (count($ctTreatedVals) > 0) {
							foreach ($ctTreatedVals as $k => $v) {
								$schemaValue = [
									'aid' => $oApp->id,
									'rid' => $oRecord->rid,
									'enroll_key' => $oRecord->enroll_key,
									'submit_at' => $oRecord->enroll_at,
									'userid' => isset($oUser->uid) ? $oUser->uid : '',
									'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
									'schema_id' => $schemaId,
									'multitext_seq' => (int)$k,
									'value' => $this->escape($v),
								];
								$this->insert('xxt_enroll_record_data', $schemaValue, false);
							}
						}
					}
				}
			}
		}

		return (object) ['dbData' => $dbData, 'score' => $oRecordScore];
	}
	/*
	 * 处理提交的数据
	 */
	public function disposRecrdData($oApp, $schemasById, $submitData, $submitkey) {
		$dbData = new \stdClass; // 处理后的保存到数据库中的登记记录
		/* 处理提交的数据，进行格式转换等操作 */
		foreach ($submitData as $schemaId => $submitVal) {
			if ($schemaId === 'member' && is_object($submitVal)) {
				/* 自定义用户信息 */
				$dbData->{$schemaId} = $submitVal;
			} else if (isset($schemasById[$schemaId])) {
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
						$fsuser = $this->model('fs/user', $oApp->siteid);
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
						foreach ($submitVal as $file) {
							if (isset($file->uniqueIdentifier)) {
								/* 新上传的文件 */
								if (defined('SAE_TMP_PATH')) {
									$fsAli = $this->model('fs/alioss', $oApp->siteid);
									$dest = '/' . $oApp->id . '/' . $submitkey . '_' . $file->name;
									$fileUploaded2 = $fsAli->getBaseURL() . $dest;
								} else {
									$fsUser = $this->model('fs/local', $oApp->siteid, '_user');
									$fsResum = $this->model('fs/local', $oApp->siteid, '_resumable');
									$fileUploaded = $fsResum->rootDir . '/' . $submitkey . '_' . $file->uniqueIdentifier;
									$dirUploaded = $fsUser->rootDir . '/' . $submitkey;
									if (!file_exists($dirUploaded)) {
										if (false === mkdir($dirUploaded, 0777, true)) {
											return array(false, '创建文件上传目录失败');
										}
									}
									if (file_exists($fileUploaded)) {
										/* 如果同一次提交中包含相同的文件，文件只会上传一次，并且被改名 */
										$fileUploaded2 = $dirUploaded . '/' . $file->name;
										if (false === @rename($fileUploaded, $fileUploaded2)) {
											return array(false, '移动上传文件失败');
										}
									}
								}
								unset($file->uniqueIdentifier);
								$file->url = $fileUploaded2;
								$treatedValue[] = $file;
							} else {
								/* 已经上传过的文件 */
								$treatedValue[] = $file;
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
				/* 如果登记活动指定匹配清单，那么提交数据会包含匹配登记记录的数据，但是这些数据不在登记项定义中 */
				$dbData->{$schemaId} = $treatedValue = $submitVal;
			}
		}

		return [true, $dbData];
	}
	/**
	 * 获得指定登记记录登记数据的详细信息
	 */
	public function byRecord($ek, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : self::DEFAULT_FIELDS;

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

		if (isset($options['schema'])) {
			if (is_array($options['schema'])) {
				$result = new \stdClass;
				$q[2]['schema_id'] = $options['schema'];
				$data = $this->query_objs_ss($q);
				if (count($data)) {
					foreach ($data as $schemaData) {
						if (isset($fnHandler)) {
							$fnHandler($schemaData);
						}
						$schemaId = $schemaData->schema_id;
						unset($schemaData->schema_id);
						$result->{$schemaId} = $schemaData;
					}
				}
				return $result;
			} else {
				$q[2]['schema_id'] = $options['schema'];
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
					$schemaId = $schemaData->schema_id;
					unset($schemaData->schema_id);
					$result->{$schemaId} = $schemaData;
				}
			}

			return $result;
		}
	}
	/**
	 * 返回指定活动，指定登记项的填写数据
	 */
	public function bySchema(&$oApp, $oSchema, $options = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
			$page = isset($options->page) ? $options->page : null;
			$size = isset($options->size) ? $options->size : null;
			$rid = isset($options->rid) ? $this->escape($options->rid) : null;
		}
		$result = new \stdClass; // 返回的结果

		// 查询参数
		$schemaId = $this->escape($oSchema->id);
		$q = [
			'distinct value',
			"xxt_enroll_record_data",
			"state=1 and aid='{$oApp->id}' and schema_id='{$schemaId}' and value<>''",
		];
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
		if (!empty($options->userid)) {
			$q[2] .= " and userid='{$options->userid}'";
		}

		$q2 = [];
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		// 处理获得的数据
		$result->records = $this->query_objs_ss($q, $q2);

		// 符合条件的数据总数
		$q[0] = 'count(distinct value)';
		$total = (int) $this->query_val_ss($q, $q2);
		$result->total = $total;

		return $result;
	}
	/**
	 * 返回指定活动，填写的数据
	 */
	public function byApp(&$oApp, $oUser, $options = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
		}
		$fields = isset($options->fields) ? $options->fields : self::DEFAULT_FIELDS;
		$page = isset($options->page) ? $options->page : null;
		$size = isset($options->size) ? $options->size : null;
		$rid = isset($options->rid) ? $this->escape($options->rid) : null;
		$tag = isset($options->tag) ? $this->escape($options->tag) : null;

		$result = new \stdClass; // 返回的结果

		// 查询参数
		$q = [
			$fields,
			"xxt_enroll_record_data",
			"state=1 and aid='{$oApp->id}' and multitext_seq = 0",
		];
		if (empty($options->keyword)) {
			$q[2] .= " and value<>''";
		} else {
			$q[2] .= " and (value like '%" . $options->keyword . "%' or supplement like '%" . $options->keyword . "%')";
		}
		if (isset($options->schemas) && count($options->schemas)) {
			$q[2] .= " and schema_id in(";
			foreach ($options->schemas as $index => $schemaId) {
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
		if (!empty($options->agreed) && $options->agreed === 'Y') {
			$q[2] .= " and agreed='Y'";
		}
		/* 根据用户分组进行筛选 */
		if (!empty($options->userGroup)) {
			$q[2] .= " and group_id='{$options->userGroup}'";
		}
		/* 限制填写用户 */
		if (!empty($options->owner) && strcasecmp($options->owner, 'all') !== 0) {
			$q[2] .= " and userid='{$options->owner}'";
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
		$aRecords = $this->query_objs_ss($q, $q2);
		if (count($aRecords)) {
			$modelRec = $this->model('matter\enroll\record');
			foreach ($aRecords as &$oRecord) {
				/* 获得nickname */
				if (!isset($mapOfNicknames[$oRecord->userid])) {
					$rec = $modelRec->byId($oRecord->enroll_key, ['fields' => 'nickname']);
					$mapOfNicknames[$oRecord->userid] = $rec->nickname;
				}
				$oRecord->nickname = $mapOfNicknames[$oRecord->userid];
				/* like log */
				if ($oRecord->like_log) {
					$oRecord->like_log = json_decode($oRecord->like_log);
				}
			}
		}
		$result->records = $aRecords;

		// 符合条件的数据总数
		$q[0] = 'count(*)';
		$total = (int) $this->query_val_ss($q, $q2);
		$result->total = $total;

		return $result;
	}
	/**
	 * 返回指定活动，指定数据项的填写数据
	 */
	public function byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : self::DEFAULT_FIELDS;

		// 查询参数
		$q = [
			$fields,
			"xxt_enroll_record_data",
			['id' => $id, 'state' => 1],
		];
		$result = $this->query_obj_ss($q);

		return $result;
	}
	/*
	*
	*/
	public function getMultitext($ek, $schema, $options = []) {
		if (empty($schema)) {
			return [false, '没有指定题目id'];
		}

		$fields = isset($options['fields']) ? $options['fields'] : self::DEFAULT_FIELDS . ',multitext_seq';

		$q = [
			$fields,
			'xxt_enroll_record_data',
			"enroll_key = '" . $this->escape($ek) . "' and state = 1 and schema_id = '" . $this->escape($schema) . "'",
		];

		$fnHandler = function (&$oData) {
			$oData->tag = empty($oData->tag) ? [] : json_decode($oData->tag);
			$oData->like_log = empty($oData->like_log) ? new \stdClass : json_decode($oData->like_log);
			$oData->agreed_log = empty($oData->agreed_log) ? new \stdClass : json_decode($oData->agreed_log);
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

		return [true, $data];
	}
}