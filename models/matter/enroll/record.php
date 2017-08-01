<?php
namespace matter\enroll;
/**
 * 登记活动记录
 */
class record_model extends \TMS_MODEL {
	/**
	 * 活动登记（不包括登记数据）
	 *
	 * @param object $app
	 * @param object $oUser [uid,nickname]
	 */
	public function enroll(&$oApp, $oUser = null, $options = []) {
		$referrer = isset($options['referrer']) ? $options['referrer'] : '';
		$enrollAt = isset($options['enrollAt']) ? $options['enrollAt'] : time();

		$ek = $this->genKey($oApp->siteid, $oApp->id);

		$record = [
			'aid' => $oApp->id,
			'siteid' => $oApp->siteid,
			'enroll_at' => $enrollAt,
			'first_enroll_at' => $enrollAt,
			'enroll_key' => $ek,
			'userid' => empty($oUser->uid) ? '' : $oUser->uid,
			'group_id' => empty($oUser->group_id) ? '' : $oUser->group_id,
			'referrer' => $referrer,
		];
		/* 记录所属轮次 */
		$modelRun = $this->model('matter\enroll\round');
		if ($activeRound = $modelRun->getActive($oApp)) {
			$record['rid'] = $activeRound->rid;
		}
		/* 登记用户昵称 */
		if (isset($options['nickname'])) {
			$record['nickname'] = $this->escape($options['nickname']);
		} else {
			$nickname = $this->model('matter\enroll')->getUserNickname($oApp, $oUser, $options);
			$record['nickname'] = $this->escape($nickname);
		}
		/* 登记用户的社交账号信息 */
		if (!empty($oUser)) {
			$oUserOpenids = $this->model('site\user\account')->byId($oUser->uid, ['fields' => 'wx_openid,yx_openid,qy_openid']);
			if ($oUserOpenids) {
				$record['wx_openid'] = $oUserOpenids->wx_openid;
				$record['yx_openid'] = $oUserOpenids->yx_openid;
				$record['qy_openid'] = $oUserOpenids->qy_openid;
			}
		}

		$this->insert('xxt_enroll_record', $record, false);

		return $ek;
	}
	/**
	 * 保存登记的数据
	 *
	 * @param object $oUser [uid]
	 * @param object $oApp
	 * @param string $ek
	 * @param array $submitData 用户提交的数据
	 */
	public function setData($oUser, &$oApp, $ek, $submitData, $submitkey = '', $firstSubmit = false, $assignScore = null) {
		if (empty($submitData)) {
			return [true];
		}
		// 登记记录
		$oRecord = $this->byId($ek);
		if (false === $oRecord) {
			return [false, '指定的对象不存在'];
		}

		if (empty($submitkey)) {
			$submitkey = empty($oUser) ? '' : $oUser->uid;
		}

		$siteId = $oApp->siteid;
		$dbData = new \stdClass; // 处理后的保存到数据库中的登记记录
		$schemasById = []; // 方便获取登记项定义
		foreach ($oApp->dataSchemas as $schema) {
			$schemasById[$schema->id] = $schema;
		}

		/* 处理提交的数据，进行格式转换等操作 */
		foreach ($submitData as $schemaId => $submitVal) {
			if ($schemaId === 'member' && is_object($submitVal)) {
				/* 自定义用户信息 */
				$treatedValue = $submitVal;
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
						$fsuser = $this->model('fs/user', $siteId);
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
									$fsAli = $this->model('fs/alioss', $siteId);
									$dest = '/' . $oApp->id . '/' . $submitkey . '_' . $file->name;
									$fileUploaded2 = $fsAli->getBaseURL() . $dest;
								} else {
									$fsUser = $this->model('fs/local', $siteId, '_user');
									$fsResum = $this->model('fs/local', $siteId, '_resumable');
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
					} else {
						throw new \Exception('登记的数据类型和登记项【file】需要的类型不匹配');
					}
					break;
				case 'multiple':
					if (is_object($submitVal)) {
						// 多选题，将选项合并为逗号分隔的字符串
						$treatedValue = implode(',', array_keys(array_filter((array) $submitVal, function ($i) {return $i;})));
						$dbData->{$schemaId} = $treatedValue;
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
		/**
		 * 保存用户提交的数据
		 */
		$submitAt = time(); // 数据提交时间
		if ($oApp->scenario === 'quiz') {
			$oRecordScore = new \stdClass;
			$oRecordScore->sum = 0; //记录总分
		}
		/* 按登记项记录数据 */
		foreach ($dbData as $schemaId => $treatedValue) {
			if (is_object($treatedValue) || is_array($treatedValue)) {
				$treatedValue = $this->toJson($treatedValue);
			}

			$lastSchemaValue = $this->query_obj_ss(
				[
					'submit_at,value,modify_log,score',
					'xxt_enroll_record_data',
					['aid' => $oApp->id, 'rid' => $oRecord->rid, 'enroll_key' => $ek, 'schema_id' => $schemaId, 'state' => 1],
				]
			);
			/* 计算题目的分数。只支持对单选题和多选题自动打分 */
			if ($oApp->scenario === 'quiz' && isset($schemasById[$schemaId])) {
				$quizScore = null;
				$schema = $schemasById[$schemaId];
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
							} elseif (!empty($lastSchemaValue) && ($lastSchemaValue->value == $treatedValue) && !empty($lastSchemaValue->score)) {
								//有提交记录且没修改且已经评分
								$quizScore = $lastSchemaValue->score;
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
			//记录结果
			if (false === $lastSchemaValue) {
				$schemaValue = [
					'aid' => $oApp->id,
					'rid' => $oRecord->rid,
					'enroll_key' => $ek,
					'submit_at' => $submitAt,
					'userid' => isset($oUser->uid) ? $oUser->uid : '',
					'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
					'schema_id' => $schemaId,
					'value' => $this->escape($treatedValue),
				];
				isset($quizScore) && $schemaValue['score'] = $quizScore;
				$this->insert('xxt_enroll_record_data', $schemaValue, false);
			} else {
				if ($treatedValue !== $lastSchemaValue->value) {
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
						'submit_at' => $submitAt,
						'userid' => isset($oUser->uid) ? $oUser->uid : '',
						'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
						'value' => $this->escape($treatedValue),
						'modify_log' => $this->toJson($valueModifyLogs),
					];
					$schemaValue['score'] = isset($quizScore) ? $quizScore : 0;
				} else {
					$schemaValue = ['score' => isset($quizScore) ? $quizScore : 0];
				}
				if (!empty($schemaValue)) {
					$this->update(
						'xxt_enroll_record_data',
						$schemaValue,
						['aid' => $oApp->id, 'rid' => $oRecord->rid, 'enroll_key' => $ek, 'schema_id' => $schemaId, 'state' => 1]
					);
				}
			}
		}
		/* 更新在登记记录上记录数据 */
		$oRecordUpdated = [];
		$oRecordUpdated['data'] = $this->escape($this->toJson($dbData));
		if ($oApp->scenario === 'quiz') {
			$oRecordUpdated['score'] = $this->escape($this->toJson($oRecordScore));
		}
		/* 记录提交日志 */
		if ($firstSubmit === false) {
			if (empty($oRecord->submit_log)) {
				$recordSubmitLogs = [];
			} else {
				$recordSubmitLogs = json_decode($oRecord->submit_log);
			}
			$newSubmitLog = new \stdClass;
			$newSubmitLog->submitAt = $oRecord->enroll_at;
			$newSubmitLog->userid = $oRecord->userid;
			$recordSubmitLogs[] = $newSubmitLog;
			$oRecordUpdated['submit_log'] = json_encode($recordSubmitLogs);
		}

		$this->update('xxt_enroll_record', $oRecordUpdated, ['enroll_key' => $ek]);

		return [true, $dbData];
	}
	/**
	 * 保存登记的数据
	 *
	 * @param object $oUser [uid]
	 * @param object $oApp
	 * @param string $ek
	 * @param array $submitTag 用户提交的填写项标签
	 */
	public function setTag($oUser, &$oApp, $ek, $submitTag) {
		$wholeTags = new \stdClass;
		/*record data*/
		foreach ($submitTag as $schemaId => $tags) {
			/*题目中对应的标签*/
			$tagOlds = [];
			$q = [
				'tag',
				'xxt_enroll_record_data',
				['enroll_key' => $ek, 'schema_id' => $schemaId, 'state' => 1],
			];
			if ($tagOld = $this->query_obj_ss($q)) {
				!empty($tagOld->tag) && $tagOlds = json_decode($tagOld->tag);
			}

			/* 保证以字符串的格式存储标签id，便于以后检索 */
			$jsonTags = [];
			$tagAdd = []; //对比上一次新增的标签
			foreach ($tags as $oTag) {
				if (($key = array_search($oTag->id, $tagOlds)) === false) {
					$tagAdd[] = $oTag->id;
				} else {
					/*如果有剩余的标签说明是对比上一次本次不使用的标签，其使用数量应该减 1*/
					unset($tagOlds[$key]);
				}

				$jsonTags[] = (string) $oTag->id;
			}
			if (!empty($tagAdd)) {
				$updateAddWhere = "(" . implode(',', $tagAdd) . ")";
				$this->update("update xxt_enroll_record_tag set use_num = use_num +1 where id in $updateAddWhere");
			}
			if (!empty($tagOlds)) {
				$updateDelWhere = "(" . implode(',', $tagOlds) . ")";
				$this->update("update xxt_enroll_record_tag set use_num = use_num -1 where id in $updateDelWhere");
			}

			$wholeTags->{$schemaId} = $jsonTags;
			$jsonTags = json_encode($jsonTags);
			$rst = $this->update(
				'xxt_enroll_record_data',
				['tag' => $this->escape($jsonTags)],
				['enroll_key' => $ek, 'schema_id' => $schemaId, 'state' => 1]
			);
		}

		$rst = $this->update(
			'xxt_enroll_record',
			['data_tag' => $this->escape(json_encode($wholeTags))],
			['enroll_key' => $ek, 'state' => 1]
		);

		return $rst;
	}
	/**
	 * 保存登记的数据
	 *
	 * @param object $oUser [uid]
	 * @param object $oApp
	 * @param string $ek
	 * @param array $submitSupp 用户提交的补充说明
	 */
	public function setSupplement($oUser, &$oApp, $ek, $submitSupp) {
		/*record*/
		$rst = $this->update(
			'xxt_enroll_record',
			['supplement' => $this->escape($this->toJson($submitSupp))],
			['enroll_key' => $ek, 'state' => 1]
		);

		/*record data*/
		foreach ($submitSupp as $schemaId => $sSupplement) {
			$rst = $this->update(
				'xxt_enroll_record_data',
				['supplement' => $this->escape($sSupplement)],
				['enroll_key' => $ek, 'schema_id' => $schemaId, 'state' => 1]
			);
		}

		return $rst;
	}
	/**
	 *
	 */
	private function _processRecord(&$oRecord, $fields, $verbose = 'Y') {
		if ($fields === '*' || false !== strpos($fields, 'data')) {
			$oRecord->data = empty($oRecord->data) ? new \stdClass : json_decode($oRecord->data);
		}
		if ($fields === '*' || false !== strpos($fields, 'data_tag')) {
			$oRecord->data_tag = empty($oRecord->data_tag) ? new \stdClass : json_decode($oRecord->data_tag);
		}
		if ($fields === '*' || false !== strpos($fields, 'supplement')) {
			$oRecord->supplement = empty($oRecord->supplement) ? new \stdClass : json_decode($oRecord->supplement);
		}
		if ($fields === '*' || false !== strpos($fields, 'score')) {
			$oRecord->score = empty($oRecord->score) ? new \stdClass : json_decode($oRecord->score);
		}
		if ($verbose === 'Y' && isset($oRecord->enroll_key)) {
			$oRecord->verbose = $this->model('matter\enroll\data')->byRecord($oRecord->enroll_key);
		}
		if (!empty($oRecord->rid)) {
			$oRecord->round = new \stdClass;
			if ($round = $this->model('matter\enroll\round')->byId($oRecord->rid, ['fields' => 'title'])) {
				$oRecord->round->title = $round->title;
			} else {
				$oRecord->round->title = '';
			}
		}

		return $oRecord;
	}
	/**
	 * 根据ID返回登记记录
	 */
	public function &byId($ek, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$verbose = isset($options['verbose']) ? $options['verbose'] : 'N';

		$q = [
			$fields,
			'xxt_enroll_record',
			['enroll_key' => $ek],
		];
		if ($oRecord = $this->query_obj_ss($q)) {
			$this->_processRecord($oRecord, $fields, $verbose);
		}

		return $oRecord;
	}
	/**
	 * 获得指定用户最后一次登记记录
	 *
	 * 如果用户是注册用户，那么获得这个注册用户，在活动所属团队下，对应的所有站点用户账号填写的内容
	 *
	 * 如果设置轮次，只返回当前轮次的情况
	 */
	public function lastByUser($oApp, $oUser, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$verbose = isset($options['verbose']) ? $options['verbose'] : 'N';

		$q = [
			$fields,
			'xxt_enroll_record',
			"siteid='{$oApp->siteid}' and aid='{$oApp->id}' and state=1",
		];
		/* 指定登记用户 */
		//if (empty($oUser->unionid)) {
		$q[2] .= " and userid='{$oUser->uid}'";
		// } else {
		// 	$modelAcnt = $this->model('site\user\account');
		// 	$aSiteUsers = $modelAcnt->byUnionid($oUser->unionid, ['siteid' => $oApp->siteid, 'fields' => 'uid']);
		// 	if (count($aSiteUsers) === 1) {
		// 		$q[2] .= " and userid='{$aSiteUsers[0]->uid}'";
		// 	} else {
		// 		$q[2] .= " and userid in (";
		// 		foreach ($aSiteUsers as $index => $aSiteUser) {
		// 			if ($index > 0) {
		// 				$q[2] .= ',';
		// 			}
		// 			$q[2] .= "'{$aSiteUser->uid}'";
		// 		}
		// 		$q[2] .= ")";
		// 	}
		// }

		/* 指定登记轮次 */
		if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
			$q[2] .= " and rid='$activeRound->rid'";
		}

		/* 登记的时间 */
		$q2 = [
			'o' => 'enroll_at desc',
			'r' => ['o' => 0, 'l' => 1],
		];

		$records = $this->query_objs_ss($q, $q2);

		$oRecord = count($records) === 1 ? $records[0] : false;
		if ($oRecord) {
			$this->_processRecord($oRecord, $fields, $verbose);
		}

		return $oRecord;
	}
	/**
	 * 获得用户的登记清单
	 * @param object $oApp
	 * @param object $oUser
	 * @param array $options
	 */
	public function &byUser(&$oApp, &$oUser, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$verbose = isset($options['verbose']) ? $options['verbose'] : 'N';

		$userid = isset($oUser->uid) ? $oUser->uid : (isset($oUser->userid) ? $oUser->userid : '');
		if (empty($userid)) {
			return false;
		}

		$q = [
			$fields,
			'xxt_enroll_record',
			["state" => 1, "aid" => $oApp->id, "userid" => $userid],
		];
		if (!empty($options['rid'])) {
			if (strcasecmp('all', $options['rid']) !== 0) {
				$q[2]['rid'] = $options['rid'];
			}
		}
		$q2 = ['o' => 'enroll_at desc'];

		$records = $this->query_objs_ss($q, $q2);
		foreach ($records as $oRecord) {
			$this->_processRecord($oRecord, $fields, $verbose);
		}

		return $records;
	}
	/**
	 * 获得登记轮次的清单
	 *
	 * @param string $roundId
	 */
	public function &byRound($roundId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		if ($fields === 'count(*)') {
			$q = [
				'count(*)',
				'xxt_enroll_record',
				["state" => 1, "rid" => $roundId],
			];
			$cnt = (int) $this->query_val_ss($q);

			return $cnt;
		} else {
			$q = [
				$fields,
				'xxt_enroll_record',
				["state" => 1, "rid" => $roundId],
			];

			$q2 = ['o' => 'enroll_at desc'];

			$list = $this->query_objs_ss($q, $q2);

			return $list;
		}
	}
	/**
	 * 获得指定项目下的登记记录
	 *
	 * @param int $missionId
	 */
	public function &byMission($missionId, $options) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_enroll_record r',
		];
		$missionId = $this->escape($missionId);
		$where = "state=1 and exists(select 1 from xxt_enroll e where r.aid=e.id and e.mission_id={$missionId})";

		if (isset($options['userid'])) {
			$where .= " and userid='" . $this->escape($options['userid']) . "'";
		}
		$q[2] = $where;

		$list = $this->query_objs_ss($q);
		if (count($list)) {
			if ($fields === '*' || strpos($fields, 'data') !== false) {
				foreach ($list as &$record) {
					$record->data = json_decode($record->data);
				}
			}
		}

		return $list;
	}
	/**
	 * 根据指定的数据查找匹配的记录
	 *
	 * 不是所有的字段都检查，只检查字符串类型
	 */
	public function &byData(&$oApp, &$data, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$records = false;

		// 查找条件
		$whereByData = '';
		foreach ($data as $k => $v) {
			if (!empty($v) && is_string($v)) {
				$whereByData .= ' and (';
				$whereByData .= 'data like \'%"' . $k . '":"' . $v . '"%\'';
				$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . '"%\'';
				$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . ',%"%\'';
				$whereByData .= ' or data like \'%"' . $k . '":"' . $v . ',%"%\'';
				$whereByData .= ')';
			}
		}

		// 没有指定条件时就认为没有符合条件的记录
		if (empty($whereByData)) {
			return $records;
		}

		// 查找匹配条件的数据
		$q = [
			$fields,
			'xxt_enroll_record',
			"state=1 and aid='{$oApp->id}' $whereByData",
		];
		$records = $this->query_objs_ss($q);
		foreach ($records as &$record) {
			if (empty($record->data)) {
				$record->data = new \stdClass;
			} else {
				$data = json_decode($record->data);
				if ($data === null) {
					$record->data = 'json error(' . json_last_error() . '):' . $r->data;
				} else {
					$record->data = $data;
				}
			}
		}

		return $records;
	}
	/**
	 * 为了计算每条记录的分数，转换schema的形式
	 */
	private function _mapOfScoreSchema(&$oApp) {
		$scoreSchemas = new \stdClass;

		$schemas = is_object($oApp->data_schemas) ? $oApp->data_schemas : json_decode($oApp->data_schemas);
		foreach ($schemas as $schema) {
			if ($schema->type === 'single' && isset($schema->score) && $schema->score === 'Y') {
				$scoreSchemas->{$schema->id} = new \stdClass;
				$scoreSchemas->{$schema->id}->ops = new \stdClass;
				foreach ($schema->ops as $op) {
					$scoreSchemas->{$schema->id}->ops->{$op->v} = $op;
				}
			}
		}

		return $scoreSchemas;
	}
	/**
	 * 计算记录的分数
	 */
	private function _calcVotingScore(&$scoreSchemas, &$data) {
		$score = 0;
		foreach ($scoreSchemas as $schemaId => $schema) {
			if (!empty($data->{$schemaId})) {
				$opScore = empty($schema->ops->{$data->{$schemaId}}->score) ? 0 : $schema->ops->{$data->{$schemaId}}->score;
				$score += $opScore;
			}
		}

		return $score;
	}
	/**
	 * 登记清单
	 *
	 * @param object/string 登记活动/登记活动的id
	 * @param object/array $options
	 * --creater openid
	 * --visitor openid
	 * --page
	 * --size
	 * --kw 检索关键词
	 * --by 检索字段
	 * @param object $criteria 登记数据过滤条件
	 *
	 * @return object
	 * records 数据列表
	 * total 数据总条数
	 */
	public function byApp($oApp, $options = null, $criteria = null) {
		if (is_string($oApp)) {
			$oApp = $this->model('matter\enroll')->byId($oApp, ['cascaded' => 'N']);
		}
		if (empty($oApp)) {
			return false;
		}
		if ($options) {
			is_array($options) && $options = (object) $options;
			$creater = isset($options->creater) ? $options->creater : null;
			$inviter = isset($options->inviter) ? $options->inviter : null;
			$orderby = isset($options->orderby) ? $options->orderby : '';
			$page = isset($options->page) ? $options->page : null;
			$size = isset($options->size) ? $options->size : null;
		}
		$result = new \stdClass; // 返回的结果
		$result->total = 0;

		// 指定登记活动下的登记记录
		$w = "e.state=1 and e.aid='{$oApp->id}'";

		// 指定轮次，或者当前激活轮次
		if (!empty($criteria->record->rid)) {
			if (strcasecmp('all', $criteria->record->rid) !== 0) {
				$rid = $criteria->record->rid;
			}
		} else if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
			$rid = $activeRound->rid;
		}
		!empty($rid) && $w .= " and e.rid='$rid'";

		// 根据填写人筛选（填写端列表页需要）
		if (!empty($creater)) {
			$w .= " and e.userid='$creater'";
		} else if (!empty($inviter)) {
			$oUser = new \stdClass;
			$oUser->openid = $inviter;
			$inviterek = $this->lastKeyByUser($oApp, $oUser);
			$w .= " and e.referrer='ek:$inviterek'";
		}

		// 指定了登记记录属性过滤条件
		if (!empty($criteria->record)) {
			$whereByRecord = '';
			if (!empty($criteria->record->verified)) {
				$whereByRecord .= " and verified='{$criteria->record->verified}'";
			}
			$w .= $whereByRecord;
		}

		// 指定了记录标签
		if (!empty($criteria->tags)) {
			$whereByTag = '';
			foreach ($criteria->tags as $tag) {
				$whereByTag .= " and concat(',',e.tags,',') like '%,$tag,%'";
			}
			$w .= $whereByTag;
		}

		// 指定了登记数据过滤条件
		if (isset($criteria->data)) {
			$whereByData = '';
			foreach ($criteria->data as $k => $v) {
				if (!empty($v)) {
					$whereByData .= ' and (';
					$whereByData .= 'data like \'%"' . $k . '":"' . $v . '"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . '"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . ',%"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"' . $v . ',%"%\'';
					$whereByData .= ')';
				}
			}
			$w .= $whereByData;
		}

		// 指定了按关键字过滤
		if (!empty($criteria->keyword)) {
			$whereByData = '';
			$whereByData .= ' and (data like \'%' . $criteria->keyword . '%\')';
			$w .= $whereByData;
		}

		// 查询参数
		$q = [
			'e.enroll_key,e.rid,e.enroll_at,e.tags,e.userid,e.nickname,e.wx_openid,e.yx_openid,e.qy_openid,e.headimgurl,e.verified,e.comment,e.data,e.supplement,e.data_tag',
			"xxt_enroll_record e",
			$w,
		];
		//测验场景
		if ($oApp->scenario === 'quiz') {
			$q[0] .= ',e.score';
		}

		$q2 = [];
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		// 查询结果排序
		$q2['o'] = 'e.enroll_at desc';
		/* 处理获得的数据 */
		$roundsById = []; // 缓存轮次数据
		if ($records = $this->query_objs_ss($q, $q2)) {
			foreach ($records as &$rec) {
				$rec->data_tag = empty($rec->data_tag) ? new \stdClass : json_decode($rec->data_tag);
				$data = str_replace("\n", ' ', $rec->data);
				$data = json_decode($data);
				//测验场景
				if ($oApp->scenario === 'quiz' && !empty($rec->score)) {
					$score = str_replace("\n", ' ', $rec->score);
					$score = json_decode($score);

					if ($score === null) {
						$rec->score = 'json error(' . json_last_error_msg() . '):' . $rec->score;
					} else {
						$rec->score = $score;
					}
				}
				//附加说明
				if (!empty($rec->supplement)) {
					$supplement = str_replace("\n", ' ', $rec->supplement);
					$supplement = json_decode($supplement);

					if ($supplement === null) {
						$rec->supplement = 'json error(' . json_last_error_msg() . '):' . $rec->supplement;
					} else {
						$rec->supplement = $supplement;
					}
				}

				if ($data === null) {
					$rec->data = 'json error(' . json_last_error_msg() . '):' . $rec->data;
				} else {
					$rec->data = $data;
				}
				// 记录的登记轮次
				if (!empty($rec->rid)) {
					if (!isset($roundsById[$rec->rid])) {
						if (!isset($modelRnd)) {
							$modelRnd = $this->model('matter\enroll\round');
						}
						$round = $modelRnd->byId($rec->rid, ['fields' => 'title']);
						$roundsById[$rec->rid] = $round;
					} else {
						$round = $roundsById[$rec->rid];
					}
					if ($round) {
						$rec->round = $round;
					}
				}
				// 记录的分数
				if ($oApp->scenario === 'voting' || $oApp->scenario === 'common') {
					if (!isset($scoreSchemas)) {
						$scoreSchemas = $this->_mapOfScoreSchema($oApp);
						$countScoreSchemas = count(array_keys((array) $scoreSchemas));
					}
					$rec->_score = $this->_calcVotingScore($scoreSchemas, $data);
					$rec->_average = $countScoreSchemas === 0 ? 0 : $rec->_score / $countScoreSchemas;
				}
			}
			$result->records = $records;

			// 符合条件的数据总数
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result->total = $total;
		}

		return $result;
	}
	/**
	 * 活动登记人名单
	 *
	 * @param object $oApp
	 * @param object $options
	 * --rid 轮次id
	 *
	 * return
	 */
	public function enrolleeByApp($oApp, $options = null, $criteria = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
			$rid = null;
			if (!empty($options->rid)) {
				if (strcasecmp($options->rid, 'all') === 0) {
					$rid = null;
				} else if (!empty($options->rid)) {
					$rid = $options->rid;
				}
			} else if ($activeRound = \TMS_APP::M('matter\enroll\round')->getActive($oApp)) {
				$rid = $activeRound->rid;
			}
		}
		$fields = isset($options->fields) ? $options->fields : 'enroll_key,userid';

		$w = "state=1 and aid='{$oApp->id}' and userid<>''";

		// 按轮次过滤
		!empty($rid) && $w .= " and e.rid='$rid'";

		// 指定了登记记录过滤条件
		if (!empty($criteria->record)) {
			$whereByRecord = '';
			if (!empty($criteria->record->verified)) {
				$whereByRecord .= " and verified='{$criteria->record->verified}'";
			}
			$w .= $whereByRecord;
		}

		// 指定了记录标签
		if (!empty($criteria->tags)) {
			$whereByTag = '';
			foreach ($criteria->tags as $tag) {
				$whereByTag .= " and concat(',',e.tags,',') like '%,$tag,%'";
			}
			$w .= $whereByTag;
		}

		// 指定了登记数据过滤条件
		if (isset($criteria->data)) {
			$whereByData = '';
			foreach ($criteria->data as $k => $v) {
				if (!empty($v)) {
					$whereByData .= ' and (';
					$whereByData .= 'data like \'%"' . $k . '":"' . $v . '"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . '"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . ',%"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"' . $v . ',%"%\'';
					$whereByData .= ')';
				}
			}
			$w .= $whereByData;
		}

		// 获得填写的登记数据
		$q = [
			$fields,
			"xxt_enroll_record e",
			$w,
		];
		$enrollees = $this->query_objs_ss($q);

		return $enrollees;
	}
	/**
	 * 已删除的登记清单
	 *
	 * 1、如果活动仅限会员报名，那么要叠加会员信息
	 * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
	 *
	 * $siteId
	 * $aid
	 * $options
	 * --page
	 * --size
	 * --rid 轮次id
	 *
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 */
	public function recycle($siteId, &$app, $options = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
			$page = isset($options->page) ? $options->page : null;
			$size = isset($options->size) ? $options->size : null;
			$rid = null;
			if (!empty($options->rid)) {
				if ($options->rid === 'ALL') {
					$rid = null;
				} else if (!empty($options->rid)) {
					$rid = $options->rid;
				}
			} else if ($activeRound = $this->M('matter\enroll\round')->getActive($app)) {
				$rid = $activeRound->rid;
			}
		}
		$result = new \stdClass; // 返回的结果
		$result->total = 0;

		// 指定登记活动下的登记记录
		$w = "(e.state=100 or e.state=101 or e.state=0) and e.aid='{$app->id}'";

		// 指定了轮次
		!empty($rid) && $w .= " and e.rid='$rid'";

		// 指定了登记记录过滤条件
		if (!empty($criteria->record)) {
			$whereByRecord = '';
			if (!empty($criteria->record->verified)) {
				$whereByRecord .= " and verified='{$criteria->record->verified}'";
			}
			$w .= $whereByRecord;
		}

		$q = [
			'e.enroll_key,e.enroll_at,e.tags,e.userid,e.nickname,e.verified,e.comment,e.data,e.state',
			"xxt_enroll_record e",
			$w,
		];

		$q2 = [];
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		// 查询结果排序
		$q2['o'] = 'e.enroll_at desc';
		// 处理获得的数据
		if ($records = $this->query_objs_ss($q, $q2)) {
			foreach ($records as &$r) {
				$data = str_replace("\n", ' ', $r->data);
				$data = json_decode($data);
				if ($data === null) {
					$r->data = 'json error(' . json_last_error_msg() . '):' . $r->data;
				} else {
					$r->data = $data;
				}
				// 记录的分数
				if ($app->scenario === 'voting') {
					if (!isset($scoreSchemas)) {
						$scoreSchemas = $this->_mapOfScoreSchema($app);
						$countScoreSchemas = count(array_keys((array) $scoreSchemas));
					}
					$r->_score = $this->_calcVotingScore($scoreSchemas, $data);
					$r->_average = $countScoreSchemas === 0 ? 0 : $r->_score / $countScoreSchemas;
				}
			}
			$result->records = $records;

			// 符合条件的数据总数
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result->total = $total;
		}

		return $result;
	}
	/**
	 * 返回指定登记项的登记记录
	 *
	 */
	public function list4Schema(&$oApp, $schemaId, $options = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
			$page = isset($options->page) ? $options->page : null;
			$size = isset($options->size) ? $options->size : null;
			$rid = isset($options->rid) ? $this->escape($options->rid) : null;
		}
		$result = new \stdClass; // 返回的结果
		$result->records = [];
		$result->total = 0;

		$schemaId = $this->escape($schemaId);

		// 查询参数
		$q = [
			'enroll_key,value,like_log,like_num',
			"xxt_enroll_record_data",
			"state=1 and aid='{$oApp->id}' and schema_id='{$schemaId}' and value<>''",
		];
		/* 指定用户 */
		if (!empty($options->owner)) {
			$q[2] .= " and userid='" . $options->owner . "'";
		}
		/* 指定登记轮次 */
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

		$q2 = [];
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		$recordsBySchema = [];
		// 处理获得的数据
		if ($records = $this->query_objs_ss($q, $q2)) {
			//如果是数值型计算合计值
			foreach ($oApp->dataSchemas as $data_schema) {
				//判断是否是数值型
				if ($data_schema->id === $schemaId && isset($data_schema->number) && $data_schema->number === 'Y') {
					$p = [
						'sum(value)',
						'xxt_enroll_record_data',
						['aid' => $oApp->id, 'schema_id' => $schemaId, 'state' => 1],
					];
					if (!empty($rid)) {
						if ($rid !== 'ALL') {
							$p[2]['rid'] = $rid;
						}
					} else {
						if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
							$p[2]['rid'] = $activeRound->rid;
						}
					}

					$sum = (int) $this->query_val_ss($p);
					$result->sum = $sum;

					break;
				}
			}
			/* 补充记录标识 */
			if (!isset($oApp->rpConfig) || empty($oApp->rpConfig->marks)) {
				$defaultMark = new \stdClass;
				$defaultMark->id = 'nickname';
				$defaultMark->name = 'nickname';
				$marks = [$defaultMark];
			} else {
				$marks = $oApp->rpConfig->marks;
			}
			foreach ($records as &$record) {
				$rec = $this->byId($record->enroll_key, ['fields' => 'rid,nickname,data,enroll_at']);
				$rec->enroll_key = $record->enroll_key;
				$rec->like_log = empty($record->like_log) ? new \stdClass : json_decode($record->like_log);
				$rec->like_num = $record->like_num;
				$result->records[] = $rec;
			}
		}

		// 符合条件的数据总数
		$q[0] = 'count(*)';
		$total = (int) $this->query_val_ss($q, $q2);
		$result->total = $total;

		return $result;
	}
	/**
	 * 计算指定登记项所有记录的合计
	 */
	public function sum4Schema($oApp, $rid = 'ALL') {
		if (empty($oApp->data_schemas)) {
			return false;
		}

		$result = new \stdClass;
		$dataSchemas = json_decode($oApp->data_schemas);
		if (empty($rid)) {
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$rid = $activeRound->rid;
			}
		}
		foreach ($dataSchemas as $schema) {
			if (isset($schema->format) && $schema->format === 'number') {
				$q = [
					'sum(value)',
					'xxt_enroll_record_data',
					['aid' => $oApp->id, 'schema_id' => $schema->id, 'state' => 1],
				];
				$rid !== 'ALL' && !empty($rid) && $q[2]['rid'] = $rid;

				$sum = (int) $this->query_val_ss($q);
				$result->{$schema->id} = $sum;
			}
		}

		return $result;
	}
	/**
	 * 获得指定用户最后一次登记的key
	 * 如果设置轮次，只检查当前轮次的情况
	 *
	 * @param object $oApp
	 * @param object $oUser
	 *
	 */
	public function lastKeyByUser(&$oApp, &$oUser) {
		$last = $this->lastByUser($oApp, $oUser);

		return $last ? $last->enroll_key : false;
	}
	/**
	 *
	 */
	public function hasAcceptedInvite($aid, $openid, $ek) {
		$q = array(
			'enroll_key',
			'xxt_enroll_record',
			"aid='$aid' and openid='$openid' and referrer='ek:$ek'",
		);
		$records = $this->query_objs_ss($q);
		if (empty($records)) {
			return false;
		} else {
			return $records[0]->enroll_key;
		}
	}
	/**
	 * 生成活动登记的key
	 */
	public function genKey($siteId, $aid) {
		return md5(uniqid() . $siteId . $aid);
	}
	/**
	 *
	 */
	public function modify($ek, $data) {
		$rst = $this->update(
			'xxt_enroll_record',
			$data,
			['enroll_key' => $ek]
		);
		return $rst;
	}
	/**
	 * 登记人清除一条登记记录
	 *
	 * @param string $aid
	 * @param string $ek
	 */
	public function removeByUser($site, $appId, $ek) {
		$rst = $this->update(
			'xxt_enroll_record_data',
			['state' => 101],
			['aid' => $appId, 'enroll_key' => $ek]
		);
		$rst = $this->update(
			'xxt_enroll_record',
			['state' => 101],
			['aid' => $appId, 'enroll_key' => $ek]
		);

		return $rst;
	}
	/**
	 * 清除一条登记记录
	 *
	 * @param string $aid
	 * @param string $ek
	 */
	public function remove($appId, $ek, $byDelete = false) {
		if ($byDelete) {
			$rst = $this->delete(
				'xxt_enroll_record_data',
				['aid' => $appId, 'enroll_key' => $ek]
			);
			$rst = $this->delete(
				'xxt_enroll_record',
				['aid' => $appId, 'enroll_key' => $ek]
			);
		} else {
			$rst = $this->update(
				'xxt_enroll_record_data',
				['state' => 100],
				['aid' => $appId, 'enroll_key' => $ek]
			);
			$rst = $this->update(
				'xxt_enroll_record',
				['state' => 100],
				['aid' => $appId, 'enroll_key' => $ek]
			);
		}

		return $rst;
	}
	/**
	 *  恢复一条登记记录
	 *
	 * @param string $aid
	 * @param string $ek
	 */
	public function restore($appId, $ek) {
		$rst = $this->update(
			'xxt_enroll_record_data',
			['state' => 1],
			['aid' => $appId, 'enroll_key' => $ek]
		);
		$rst = $this->update(
			'xxt_enroll_record',
			['state' => 1],
			['aid' => $appId, 'enroll_key' => $ek]
		);

		return $rst;
	}
	/**
	 * 清除登记记录
	 *
	 * @param string $appId
	 */
	public function clean($appId, $byDelete = false) {
		if ($byDelete) {
			$rst = $this->delete(
				'xxt_enroll_record_data',
				['aid' => $appId]
			);
			$rst = $this->delete(
				'xxt_enroll_record',
				['aid' => $appId]
			);
		} else {
			$rst = $this->update(
				'xxt_enroll_record_data',
				['state' => 0],
				['aid' => $appId]
			);
			$rst = $this->update(
				'xxt_enroll_record',
				['state' => 0],
				['aid' => $appId]
			);
		}

		return $rst;
	}
	/**
	 * 统计登记信息
	 *
	 */
	public function &getStat($appId, $rid = '') {
		$app = $this->model('matter\enroll')->byId($appId, ['cascaded' => 'N']);
		if (empty($rid)) {
			if ($activeRound = $this->model('matter\enroll\round')->getActive($app)) {
				$rid2 = $activeRound->rid;
			}
		} elseif ($rid !== 'ALL') {
			$rid2 = $rid;
		}

		$result = [];

		if (empty($app->data_schemas)) {
			return $result;
		}

		$dataSchemas = json_decode($app->data_schemas);

		foreach ($dataSchemas as $schema) {
			if (!in_array($schema->type, ['single', 'multiple', 'phase', 'score'])) {
				continue;
			}
			$result[$schema->id] = ['title' => isset($schema->title) ? $schema->title : '', 'id' => $schema->id, 'ops' => []];
			if (in_array($schema->type, ['single', 'phase'])) {
				foreach ($schema->ops as $op) {
					/**
					 * 获取数据
					 */
					$q = [
						'count(*)',
						'xxt_enroll_record_data',
						['aid' => $appId, 'state' => 1, 'schema_id' => $schema->id, 'value' => $op->v],
					];
					if (isset($rid2)) {
						$q[2]['rid'] = $rid2;
					}
					$op->c = $this->query_val_ss($q);
					$result[$schema->id]['ops'][] = $op;
				}
			} else if ($schema->type === 'multiple') {
				foreach ($schema->ops as $op) {
					/**
					 * 获取数据
					 */
					$q = [
						'count(*)',
						'xxt_enroll_record_data',
						"aid='$appId' and state=1 and schema_id='{$schema->id}' and FIND_IN_SET('{$op->v}', value)",
					];
					if (isset($rid2)) {
						$rid2 = $this->escape($rid2);
						$q[2] .= " and rid = '$rid2'";
					}
					$op->c = $this->query_val_ss($q);
					$result[$schema->id]['ops'][] = $op;
				}
			} else if ($schema->type === 'score') {
				$scoreByOp = [];
				foreach ($schema->ops as &$op) {
					$op->c = 0;
					$result[$schema->id]['ops'][] = $op;
					$scoreByOp[$op->v] = $op;
				}
				// 计算总分数
				$q = [
					'value',
					'xxt_enroll_record_data',
					['aid' => $appId, 'state' => 1, 'schema_id' => $schema->id],
				];
				if (isset($rid2)) {
					$q[2]['rid'] = $rid2;
				}

				$values = $this->query_objs_ss($q);
				foreach ($values as $value) {
					$value = json_decode($value->value);
					foreach ($value as $opKey => $opValue) {
						$scoreByOp[$opKey]->c += (int) $opValue;
					}
				}
				// 计算平均分
				if ($rowNumber = count($values)) {
					foreach ($schema->ops as &$op) {
						$op->c = $op->c / $rowNumber;
					}
				} else {
					$op->c = 0;
				}
			}
		}

		return $result;
	}
	/**
	 * 获得schemasB中和schemasA兼容的登记项定义及对应关系
	 *
	 * 从目标应用中导入和指定应用的数据定义中名称（title）和类型（type）一致的项
	 * 如果是单选题、多选题、打分题选项必须一致
	 * 如果是打分题，分值设置范围必须一致
	 * name,email,mobile,shorttext,longtext认为是同一种类型
	 * 忽略：项目阶段，说明描述
	 */
	public function compatibleSchemas($schemasA, $schemasB) {
		if (empty($schemasB) || empty($schemasA)) {
			return [];
		}
		$mapOfCompatibleType = [
			'shorttext' => 'text',
			'longtext' => 'text',
			'name' => 'text',
			'email' => 'text',
			'mobile' => 'text',
			'location' => 'text',
			'date' => 'text',
			'single' => 'single',
			'multiple' => 'multiple',
			'score' => 'score',
			'file' => 'file',
			'image' => 'image',
		];
		$mapAByType = [];
		foreach ($schemasA as $schemaA) {
			if (!isset($mapOfCompatibleType[$schemaA->type])) {
				continue;
			}
			$compatibleType = $mapOfCompatibleType[$schemaA->type];
			if (!isset($mapAByType[$compatibleType])) {
				$mapAByType[$compatibleType] = [];
			}
			$mapAByType[$compatibleType][] = $schemaA;
		}

		$result = [];
		foreach ($schemasB as $schemaB) {
			if (!isset($mapOfCompatibleType[$schemaB->type])) {
				continue;
			}
			$compatibleType = $mapOfCompatibleType[$schemaB->type];
			if (!isset($mapAByType[$compatibleType])) {
				continue;
			}
			foreach ($mapAByType[$compatibleType] as $schemaA) {
				if ($schemaA->title !== $schemaB->title) {
					continue;
				}
				if ($compatibleType === 'single' || $compatibleType === 'multiple' || $compatibleType === 'score') {
					if (count($schemaA->ops) !== count($schemaB->ops)) {
						continue;
					}
					$isCompatible = true;
					for ($i = 0, $ii = count($schemaA->ops); $i < $ii; $i++) {
						if ($schemaA->ops[$i]->l !== $schemaB->ops[$i]->l) {
							$isCompatible = false;
							break;
						}
					}
					if ($isCompatible === false) {
						continue;
					}
				}
				$result[] = [$schemaB, $schemaA];
			}
		}

		return $result;
	}
}