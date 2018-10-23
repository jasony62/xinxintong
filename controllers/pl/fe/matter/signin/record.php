<?php
namespace pl\fe\matter\signin;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 *
 */
class record extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/signin/frame');
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

		$mdoelRec = $this->model('matter\signin\record');
		$oRecord = $mdoelRec->byId($ek, ['verbose' => 'Y']);

		return new \ResponseData($oRecord);
	}
	/**
	 * 签到名单
	 */
	public function list_action($app, $page = 1, $size = 30, $rid = null, $orderby = null, $contain = null) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		// 登记数据过滤条件
		$criteria = $this->getPostJson();

		/*应用*/
		$modelApp = $this->model('matter\signin');
		$oApp = $modelApp->byId($app);
		/*参数*/
		$aOptions = [
			'page' => $page,
			'size' => $size,
			'orderby' => $orderby,
			'contain' => $contain,
		];
		!empty($rid) && (strcasecmp($rid, 'all') !== 0) && $aOptions['rid'] = $rid;

		$modelRec = $this->model('matter\signin\record');
		$oResult = $modelRec->byApp($oApp, $aOptions, $criteria);
		if ($oResult->total > 0 && !empty($oApp->entryRule->enroll->id)) {
			foreach ($oResult->records as $oRecord) {
				$q = [
					'enroll_at,tags,comment',
					'xxt_enroll_record',
					['state' => 1, 'aid' => $oApp->entryRule->enroll->id, 'enroll_key' => $oRecord->verified_enroll_key],
				];
				if ($oEnrollRecord = $modelApp->query_obj_ss($q)) {
					$oRecord->_enrollRecord = $oEnrollRecord;
				}
			}
		}

		return new \ResponseData($oResult);
	}
	/**
	 * 导入签到数据
	 */
	public function importByEnrollApp_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 签到应用
		$modelApp = $this->model('matter\signin');
		$oSigninApp = $modelApp->byId($app);

		if (empty($oSigninApp->entryRule->enroll->id)) {
			return new \ResponseError('参数错误，没有指定关联的报名活动');
		}

		// 和签到在同一个项目阶段的报名
		$oCriteria = new \stdClass;
		// 登记记录
		$aOptions = [];
		$oEnrollApp = $this->model('matter\enroll')->byId($oSigninApp->entryRule->enroll->id);
		$modelRec = $this->model('matter\enroll\record');

		$oResult = $modelRec->byApp($oEnrollApp, $aOptions, $oCriteria);
		$countOfNew = 0;
		if ($oResult->total > 0) {
			$current = time();
			$modelSigninRec = $this->model('matter\signin\record');
			foreach ($oResult->records as $oRecord) {
				$q = [
					'verified,enroll_key,enroll_at,data',
					'xxt_signin_record',
					['aid' => $oSigninApp->id, 'state' => 1, 'verified_enroll_key' => $oRecord->enroll_key],
				];
				$signinRecords = $modelSigninRec->query_objs_ss($q);
				if (count($signinRecords) === 1) {
					$signinRecord = $signinRecords[0];
					/* 已经有对应的记录，根据登记时间更新数据 */
					if ($signinRecord->verified === 'N' && $oRecord->enroll_at > $signinRecord->enroll_at) {
						$data = json_decode($signinRecord->data);
						foreach ($oRecord->data as $n => $v) {
							$data->{$n} = $v;
						}
						$modelSigninRec->setData($site, $oSigninApp, $signinRecord->enroll_key, $data, $user->id);
						$countOfNew++;
					}
				} else if (count($signinRecords) === 0) {
					/* 没有对应的记录，创建新的 */
					$ek = $modelSigninRec->enroll($oSigninApp, null, ['verified_enroll_key' => $oRecord->enroll_key]);
					$modelSigninRec->setData($site, $oSigninApp, $ek, $oRecord->data, $user->id);
					$countOfNew++;
				} else {
					//@todo 会出现这种情况吗？出现了合理吗?
				}
			}
		}

		return new \ResponseData($countOfNew);
	}
	/**
	 * 从报名表中查找匹配的记录
	 */
	public function matchEnroll_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$signinRecord = $this->getPostJson();

		// 签到应用
		$modelApp = $this->model('matter\signin');
		$oSigninApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (empty($oSigninApp->entryRule->enroll->id) || empty($oSigninApp->dataSchemas)) {
			return new \ParameterError();
		}

		// 匹配规则
		$bEmpty = true;
		$matchCriteria = new \stdClass;
		foreach ($oSigninApp->dataSchemas as $schema) {
			if (isset($schema->requireCheck) && $schema->requireCheck === 'Y' && !empty($signinRecord->{$schema->id})) {
				$matchCriteria->{$schema->id} = $signinRecord->{$schema->id};
				$bEmpty = false;
			}
		}

		$aResult = [];
		if (!$bEmpty) {
			// 查找匹配的数据
			$oEnrollApp = $this->model('matter\enroll')->byId($oSigninApp->entryRule->enroll->id, ['cascaded' => 'N']);
			$modelEnlRec = $this->model('matter\enroll\record');
			$enlRecords = $modelEnlRec->byData($oEnrollApp, $matchCriteria);
			foreach ($enlRecords as $enlRec) {
				$aResult[] = $enlRec->data;
			}
		}

		return new \ResponseData($aResult);
	}
	/**
	 * 手工添加登记信息
	 *
	 * 1、是否带报名信息
	 * 2、指定签到的轮次和对应的签到时间
	 *
	 * @param string $aid
	 */
	public function add_action($site, $app, $round = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$modelApp = $this->model('matter\signin');
		$modelRec = $this->model('matter\signin\record');

		$oSigninApp = $modelApp->byId($app, ['cascaded' => 'N']);
		$ek = $modelRec->enroll($oSigninApp);
		/**
		 * 签到登记记录
		 */
		$oAddedRecord = new \stdClass;
		if (isset($posted->verified)) {
			$oAddedRecord->verified = $posted->verified;
		}
		if (isset($posted->comment)) {
			$oAddedRecord->comment = $posted->comment;
		}
		if (isset($posted->tags)) {
			$oAddedRecord->tags = $posted->tags;
			$this->model('matter\signin')->updateTags($oSigninApp->id, $posted->tags);
		}

		// 签到日志
		if (isset($posted->signin_log)) {
			$signinNum = 0;
			$signinAtLast = 0;
			$modelSinLog = $this->model('matter\signin\log');
			foreach ($posted->signin_log as $roundId => $signinAt) {
				if ($signinAt) {
					$signinAt > $signinAtLast && $signinAtLast = $signinAt;
					$signinNum++;
					// 保存签到日志
					$modelRec->insert(
						'xxt_signin_log',
						[
							'siteid' => $site,
							'aid' => $app,
							'rid' => $roundId,
							'enroll_key' => $ek,
							'userid' => '',
							'nickname' => '',
							'signin_at' => $signinAt,
						],
						false
					);
				}
			}
			$oAddedRecord->signin_num = $signinNum;
			$oAddedRecord->signin_at = $signinAtLast;
			$oAddedRecord->signin_log = \TMS_MODEL::toJson($posted->signin_log);
		}
		// 更新登记记录数据
		$modelRec->update(
			'xxt_signin_record',
			$oAddedRecord,
			['enroll_key' => $ek]
		);

		// 保存登记数据
		$modelRec->setData($site, $oSigninApp, $ek, $posted->data, $user->id);

		// 记录操作日志
		$this->model('matter\log')->matterOp($site, $user, $oSigninApp, 'add', $ek);

		$oRecord = $modelRec->byId($ek);

		return new \ResponseData($oRecord);
	}
	/**
	 * 更新登记记录
	 *
	 * 1、是否带报名信息
	 * 2、指定签到的轮次和对应的签到时间
	 *
	 * @param string $app
	 * @param $ek record's key
	 */
	public function update_action($site, $app, $ek) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oRecord = $this->getPostJson();
		$modelApp = $this->model('matter\signin');
		$modelRec = $this->model('matter\signin\record');

		$oSigninApp = $modelApp->byId($app, ['cascaded' => 'N']);

		$current = time();
		$oUpdatedRecord = new \stdClass;
		$oUpdatedRecord->enroll_at = $current;
		isset($oRecord->comment) && $oUpdatedRecord->comment = $oRecord->comment;

		// 是否通过验证
		if (isset($oRecord->verified)) {
			$oUpdatedRecord->verified = $oRecord->verified;
			if ($oRecord->verified === 'N') {
				// 如果不通过验证，解除关联的报名应用信息
				$oUpdatedRecord->verified_enroll_key = '';
			}
		}

		// 标签
		if (isset($oRecord->tags)) {
			// 更新记录的标签时，要同步更新活动的标签，实现标签在整个活动中有效
			$oUpdatedRecord->tags = $oRecord->tags;
			$modelApp->updateTags($oSigninApp->id, $oRecord->tags);
		}

		// 签到日志
		if (isset($oRecord->signin_log)) {
			$signinNum = 0;
			$signinAtLast = 0;
			$modelSinLog = $this->model('matter\signin\log');
			foreach ($oRecord->signin_log as $roundId => $signinAt) {
				if ($signinAt) {
					$signinAt > $signinAtLast && $signinAtLast = $signinAt;
					$signinNum++;
					// 保存签到日志
					if ($sinLog = $modelSinLog->byRecord($ek, $roundId)) {
						$modelSinLog->update(
							'xxt_signin_log',
							['signin_at' => $signinAt],
							['enroll_key' => $ek, 'rid' => $roundId]
						);
					} else {
						$modelRec->insert(
							'xxt_signin_log',
							[
								'siteid' => $site,
								'aid' => $oSigninApp->id,
								'rid' => $roundId,
								'enroll_key' => $ek,
								'userid' => '',
								'nickname' => '',
								'signin_at' => $signinAt,
							],
							false
						);
					}
				} else {
					// 清除掉无效的数据
					unset($oRecord->signin_log->{$roundId});
					$modelSinLog->delete(
						'xxt_signin_log',
						['enroll_key' => $ek, 'rid' => $roundId]
					);
				}
			}
			$oUpdatedRecord->signin_num = $oRecord->signin_num = $signinNum;
			$oUpdatedRecord->signin_at = $oRecord->signin_at = $signinAtLast;
			$oUpdatedRecord->signin_log = \TMS_MODEL::toJson($oRecord->signin_log);
		}
		// 更新登记记录数据
		$modelRec->update(
			'xxt_signin_record',
			$oUpdatedRecord,
			['enroll_key' => $ek]
		);

		// 更新登记数据
		$modelRec->setData($site, $oSigninApp, $ek, $oRecord->data, $user->id);

		// 记录操作日志
		$this->model('matter\log')->matterOp($site, $user, $oSigninApp, 'update', $oRecord);

		// 返回完整的记录
		$oRecord = $modelRec->byId($ek);

		return new \ResponseData($oRecord);
	}
	/**
	 * 给记录批量添加标签
	 */
	public function batchTag_action($app) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		$eks = $oPosted->eks;
		$tags = $oPosted->tags;

		/**
		 * 给记录打标签
		 */
		$modelRec = $this->model('matter\signin\record');
		if (!empty($eks) && !empty($tags)) {
			foreach ($eks as $ek) {
				$oRecord = $modelRec->byId($ek);
				$existent = $oRecord->tags;
				if (empty($existent)) {
					$aNew = $tags;
				} else {
					$aExistent = explode(',', $existent);
					$aNew = array_unique(array_merge($aExistent, $tags));
				}
				$newTags = implode(',', $aNew);
				$modelRec->update('xxt_signin_record', ['tags' => $newTags], ['enroll_key' => $ek]);
			}
		}
		/**
		 * 给应用打标签
		 */
		$this->model('matter\signin')->updateTags($app, $oPosted->appTags);

		return new \ResponseData('ok');
	}
	/**
	 * 清空一条登记信息
	 */
	public function remove_action($app, $key) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oApp = $this->model('matter\signin')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelRec = $this->model('matter\signin\record');
		$oRecord = $modelRec->byId($key);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$rst = $modelRec->remove($oApp, $oRecord);

		// 记录操作日志
		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'remove', $key);

		return new \ResponseData($rst);
	}
	/**
	 * 清空登记信息
	 */
	public function empty_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oApp = $this->model('matter\signin')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$rst = $this->model('matter\signin\record')->clean($oApp);

		// 记录操作日志
		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'empty');

		return new \ResponseData($rst);
	}
	/**
	 * 登记数据导出
	 *
	 * 如果活动关联了报名活动，需要将关联的数据导出
	 */
	public function export_action($site, $app, $round = null) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$modelApp = $this->model('matter\signin');
		$oSigninApp = $modelApp->byId(
			$app,
			['fields' => 'id,title,data_schemas,assigned_nickname,tags,siteid,mission_id,entry_rule,absent_cause', 'cascaded' => 'Y']
		);
		$schemas = $oSigninApp->dataSchemas;
		if (!empty($round)) {
			foreach ($oSigninApp->rounds as $rnd) {
				if ($rnd->rid === $round) {
					$round = $rnd;
					break;
				}
			}
		}

		// 关联的报名活动
		if (!empty($oSigninApp->entryRule->enroll->id)) {
			$oEnrollApp = $this->model('matter\enroll')->byId($oSigninApp->entryRule->enroll->id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$enrollSchemas = $oEnrollApp->dataSchemas;
			$mapOfSigninSchemas = [];
			foreach ($schemas as $schema) {
				$mapOfSigninSchemas[] = $schema->id;
			}
			foreach ($enrollSchemas as $schema) {
				if (!in_array($schema->id, $mapOfSigninSchemas)) {
					$schemas[] = $schema;
				}
			}
		}

		// 关联的报名活动
		if (!empty($oSigninApp->entryRule->group->id)) {
			$groupApp = $this->model('matter\group')->byId($oSigninApp->entryRule->group->id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$groupSchemas = $groupApp->dataSchemas;
			$mapOfSigninSchemas = [];
			foreach ($schemas as $schema) {
				$mapOfSigninSchemas[] = $schema->id;
			}
			foreach ($groupSchemas as $schema) {
				if (!in_array($schema->id, $mapOfSigninSchemas)) {
					$schemas[] = $schema;
				}
			}
		}

		// 获得所有有效的登记记录
		$q = [
			'enroll_at,signin_at,signin_num,verified,data,signin_log,tags,comment,verified_enroll_key',
			'xxt_signin_record',
			["aid" => $oSigninApp->id, 'state' => 1],
		];
		$records = $this->model()->query_objs_ss($q);
		if (count($records) === 0) {
			die('record empty');
		}

		require_once TMS_APP_DIR . '/lib/PHPExcel.php';

		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator(APP_TITLE)
			->setLastModifiedBy(APP_TITLE)
			->setTitle($oSigninApp->title)
			->setSubject($oSigninApp->title)
			->setDescription($oSigninApp->title);

		$objPHPExcel->setActiveSheetIndex(0);
		$objActiveSheet = $objPHPExcel->getActiveSheet();
		$objActiveSheet->setTitle('签到人员数据');

		$colNumber = 0;
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '登记时间');

		if (!empty($round)) {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到时间');
		} else {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到次数');
			foreach ($oSigninApp->rounds as $rnd) {
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, $rnd->title);
			}
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '迟到次数');
		}
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '审核通过');
		foreach ($schemas as $schema) {
			if (in_array($schema->type, ['html', 'image', 'file'])) {
				continue;
			}
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, $schema->title);
		}
		if (!empty($oSigninApp->tags)) {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到标签');
		}
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到备注');
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '报名标签');
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '报名备注');

		// 转换数据
		$rowNumber = 2;
		foreach ($records as $record) {
			$colNumber = 0;
			// 基本信息
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, date('y-m-j H:i', $record->enroll_at));
			// 处理签到日志
			$signinLog = empty($record->signin_log) ? new \stdClass : json_decode($record->signin_log);
			if (!empty($round)) {
				if (isset($signinLog->{$round->rid})) {
					$signinAt = $signinLog->{$round->rid};
					if (!empty($round->late_at) && $signinAt > $round->late_at + 59) {
						$objActiveSheet->setCellValueByColumnAndRow($colNumber, $rowNumber, date('y-m-j H:i', $signinAt));
						$objActiveSheet->getStyleByColumnAndRow($colNumber++, $rowNumber)->getFont()->getColor()->setRGB('FF0000');
					} else {
						$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, date('y-m-j H:i', $signinAt));
					}
				} else {
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
				}
			} else {
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $record->signin_num);
				$lateCount = 0;
				foreach ($oSigninApp->rounds as $rnd) {
					if (isset($signinLog->{$rnd->rid})) {
						$signinAt = $signinLog->{$rnd->rid};
						if (!empty($rnd->late_at) && $signinAt > $rnd->late_at + 59) {
							$objActiveSheet->setCellValueByColumnAndRow($colNumber, $rowNumber, date('y-m-j H:i', $signinAt));
							$objActiveSheet->getStyleByColumnAndRow($colNumber++, $rowNumber)->getFont()->getColor()->setRGB('FF0000');
							$lateCount++;
						} else {
							$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, date('y-m-j H:i', $signinAt));
						}
					} else {
						$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
					}
				}
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $lateCount);
			}
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $record->verified);
			// 处理登记项
			$data = json_decode($record->data);
			foreach ($schemas as $schema) {
				if (in_array($schema->type, ['html', 'image', 'file'])) {
					continue;
				}
				$v = isset($data->{$schema->id}) ? $data->{$schema->id} : '';
				switch ($schema->type) {
				case 'single':
					foreach ($schema->ops as $op) {
						if ($op->v === $v) {
							$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $op->l);
							$disposed = true;
							break;
						}
					}
					empty($disposed) && $objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $v);
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
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, implode(',', $labels));
					break;
				case 'score':
					$labels = [];
					foreach ($schema->ops as $op) {
						if (isset($v->{$op->v})) {
							$labels[] = $op->l . ':' . $v->{$op->v};
						}
					}
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, implode(' / ', $labels));
					break;
				case 'shorttext':
					if (isset($oSchema->format) && $oSchema->format === 'number') {
						$objActiveSheet->setCellValueExplicitByColumnAndRow($colNumber++, $rowNumber, $v, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
					} else {
						$objActiveSheet->setCellValueExplicitByColumnAndRow($colNumber++, $rowNumber, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					}
					break;
				default:
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $v);
					break;
				}
			}
			// 基本信息
			if (!empty($oSigninApp->tags)) {
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($record->tags) ? $record->tags : '');
			}
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($record->comment) ? $record->comment : '');
			// 关联的报名记录
			if (!empty($record->verified_enroll_key)) {
				$q = [
					'enroll_at,tags,comment',
					'xxt_enroll_record',
					['state' => 1, 'aid' => $oSigninApp->entryRule->enroll->id, 'enroll_key' => $record->verified_enroll_key],
				];
				if ($oEnrollRecord = $modelApp->query_obj_ss($q)) {
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($oEnrollRecord->tags) ? $oEnrollRecord->tags : '');
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($oEnrollRecord->comment) ? $oEnrollRecord->comment : '');
				} else {
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
				}
			} else {
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
			}
			// next row
			$rowNumber++;
		}

		/* 未签到用户名单 */
		$modelUsr = $this->model('matter\signin\record');
		/* 获取未签到人员 */
		$result = $modelUsr->absentByApp($oSigninApp, $round);
		$absentUsers = $result->users;
		if (count($absentUsers)) {
			$objPHPExcel->createSheet();
			$objPHPExcel->setActiveSheetIndex(1);
			$objActiveSheet2 = $objPHPExcel->getActiveSheet();
			$objActiveSheet2->setTitle('未签到人员数据');

			$colNumber = 0;
			$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '序号');
			$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '分组');
			$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '姓名');
			$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '备注');

			$rowNumber = 2;
			foreach ($absentUsers as $k => $absentUser) {
				$colNumber = 0;
				$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, $k + 1);
				if (isset($absentUser->round_title)) {
					$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, $absentUser->round_title);
				} else {
					$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
				}
				$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, $absentUser->nickname);
				$objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, $absentUser->absent_cause);
				$rowNumber++;
			}
		}

		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $oSigninApp->title . '.xlsx"');
		header('Cache-Control: max-age=0');
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

		$oNameSchema = null;
		$imageSchemas = [];

		// 登记活动
		$modelApp = $this->model('matter\signin');
		$oSigninApp = $modelApp->byId(
			$app,
			['fields' => 'id,title,entry_rule,data_schemas', 'cascaded' => 'Y']
		);
		$schemas = $oSigninApp->dataSchemas;

		// 关联的登记活动
		if (!empty($oSigninApp->entryRule->enroll->id)) {
			$oEnrollApp = $this->model('matter\enroll')->byId($oSigninApp->entryRule->enroll->id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$enrollSchemas = $oEnrollApp->dataSchemas;
			$mapOfSigninSchemas = [];
			foreach ($schemas as $schema) {
				$mapOfSigninSchemas[] = $schema->id;
			}
			foreach ($enrollSchemas as $schema) {
				if (!in_array($schema->id, $mapOfSigninSchemas)) {
					$schemas[] = $schema;
				}
			}
		}
		foreach ($schemas as $schema) {
			if ($schema->type === 'image') {
				$imageSchemas[] = $schema;
			} else if ($schema->id === 'name' || (in_array($schema->title, array('姓名', '名称')))) {
				$oNameSchema = $schema;
			}
		}
		if (count($imageSchemas) === 0) {
			die('活动不包含图片数据');
		}

		$q = [
			'data',
			'xxt_signin_record',
			["aid" => $oSigninApp->id, 'state' => 1],
		];
		$records = $this->model()->query_objs_ss($q);
		if (count($records) === 0) {
			die('record empty');
		}

		// 转换数据
		$aImages = [];
		for ($j = 0, $jj = count($records); $j < $jj; $j++) {
			$record = $records[$j];
			// 处理登记项
			$data = json_decode($record->data);
			for ($i = 0, $ii = count($imageSchemas); $i < $ii; $i++) {
				$schema = $imageSchemas[$i];
				if (!empty($data->{$schema->id})) {
					$aImages[] = ['url' => $data->{$schema->id}, 'schema' => $schema, 'data' => $data];
				}
			}
		}
		$usedRecordName = [];
		// 输出打包文件
		$zipFilename = tempnam('/tmp', $oSigninApp->id);
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
				if (isset($oNameSchema)) {
					$data = $image['data'];
					$recordName = $data->{$oNameSchema->id};
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
		header('Content-disposition: attachment; filename=' . $oSigninApp->title . '.zip');
		header("Content-Type: application/zip");
		header("Content-Transfer-Encoding: binary");
		header('Content-Length: ' . filesize($zipFilename));
		@readfile($zipFilename);

		exit;
	}
	/**
	 * 指定记录通过审核
	 */
	public function batchVerify_action($app) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		$eks = $oPosted->eks;

		$modelApp = $this->model('matter\signin');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);

		foreach ($eks as $ek) {
			$rst = $modelApp->update(
				'xxt_signin_record',
				['verified' => 'Y'],
				['enroll_key' => $ek]
			);
		}

		// 记录操作日志
		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'verify.batch', $eks);

		return new \ResponseData('ok');
	}
	/**
	 * 缺席用户列表
	 * 1、如果活动指定了通讯录用户参与；如果活动指定了分组活动的分组用户
	 * 2、如果活动关联了分组活动
	 * 3、如果活动所属项目指定了用户名单
	 */
	public function absent_action($app, $rid = '') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelSin = $this->model('matter\signin');
		$oApp = $modelSin->byId($app, ['cascaded' => 'N', 'fields' => 'siteid,id,state,mission_id,entry_rule,absent_cause']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\signin\record');
		$oResult = $modelUsr->absentByApp($oApp, $rid);

		return new \ResponseData($oResult);
	}
}