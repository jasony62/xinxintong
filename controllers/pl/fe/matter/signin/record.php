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
	 * 签到名单
	 *
	 */
	public function list_action($site, $app, $page = 1, $size = 30, $rid = null, $orderby = null, $contain = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记数据过滤条件
		$criteria = $this->getPostJson();

		/*应用*/
		$modelApp = $this->model('matter\signin');
		$app = $modelApp->byId($app);
		/*参数*/
		$options = [
			'page' => $page,
			'size' => $size,
			'orderby' => $orderby,
			'contain' => $contain,
		];
		!empty($rid) && $rid !== 'ALL' && $options['rid'] = $rid;

		$mdoelRec = $this->model('matter\signin\record');
		$result = $mdoelRec->find($site, $app, $options, $criteria);
		if ($result->total > 0 && !empty($app->enroll_app_id)) {
			foreach ($result->records as &$record) {
				$q = [
					'enroll_at,tags,comment',
					'xxt_enroll_record',
					"state=1 and aid='{$app->enroll_app_id}' and enroll_key='{$record->verified_enroll_key}'",
				];
				if ($enrollRecord = $modelApp->query_obj_ss($q)) {
					$record->_enrollRecord = $enrollRecord;
				}
			}
		}

		return new \ResponseData($result);
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
		$signinApp = $modelApp->byId($app);

		if (empty($signinApp->enroll_app_id)) {
			return new \ResponseError('参数错误，没有指定关联的报名活动');
		}

		// 和签到在同一个项目阶段的报名
		$criteria = new \stdClass;
		if (!empty($signinApp->mission_phase_id)) {
			if (!isset($criteria->data)) {
				$criteria->data = new \stdClass;
			}
			$criteria->data->phase = $signinApp->mission_phase_id;
		}

		// 登记记录
		$options = [];
		$enrollApp = $this->model('matter\enroll')->byId($signinApp->enroll_app_id);
		$mdoelRec = $this->model('matter\enroll\record');

		$result = $mdoelRec->find($site, $enrollApp, $options, $criteria);
		$countOfNew = 0;
		if ($result->total > 0) {
			$current = time();
			$mdoelSigninRec = $this->model('matter\signin\record');
			foreach ($result->records as $record) {
				$q = [
					'verified,enroll_key,enroll_at,data',
					'xxt_signin_record',
					['aid' => $signinApp->id, 'state' => 1, 'verified_enroll_key' => $record->enroll_key],
				];
				$signinRecords = $mdoelSigninRec->query_objs_ss($q);
				if (count($signinRecords) === 1) {
					$signinRecord = $signinRecords[0];
					/* 已经有对应的记录，根据登记时间更新数据 */
					if ($signinRecord->verified === 'N' && $record->enroll_at > $signinRecord->enroll_at) {
						$data = json_decode($signinRecord->data);
						foreach ($record->data as $n => $v) {
							$data->{$n} = $v;
						}
						$mdoelSigninRec->setData($site, $signinApp, $signinRecord->enroll_key, $data, $user->id);
						$countOfNew++;
					}
				} else if (count($signinRecords) === 0) {
					/* 没有对应的记录，创建新的 */
					$ek = $mdoelSigninRec->enroll($site, $signinApp, null, ['verified_enroll_key' => $record->enroll_key]);
					$mdoelSigninRec->setData($site, $signinApp, $ek, $record->data, $user->id);
					$countOfNew++;
				} else {
					//@todo 会出现这种情况吗？出现了合理吗?
				}
			}
		}

		return new \ResponseData($countOfNew);
	}
	/**
	 * 登记情况汇总信息
	 */
	public function summary_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$mdoelRec = $this->model('matter\signin\record');
		$summary = $mdoelRec->summary($site, $app);

		return new \ResponseData($summary);
	}
	/**
	 * 从报名表中查找匹配的记录
	 */
	public function matchEnroll_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$signinRecord = $this->getPostJson();
		$result = [];

		// 签到应用
		$modelApp = $this->model('matter\signin');
		$signinApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (empty($signinApp->enroll_app_id) || empty($signinApp->data_schemas)) {
			return new \ParameterError();
		}

		// 匹配规则
		$isEmpty = true;
		$matchCriteria = new \stdClass;
		$schemas = json_decode($signinApp->data_schemas);
		foreach ($schemas as $schema) {
			if (isset($schema->requireCheck) && $schema->requireCheck === 'Y' && !empty($signinRecord->{$schema->id})) {
				$matchCriteria->{$schema->id} = $signinRecord->{$schema->id};
				$isEmpty = false;
			}
		}

		if (!$isEmpty) {
			// 查找匹配的数据
			$enrollApp = $this->model('matter\enroll')->byId($signinApp->enroll_app_id, ['cascaded' => 'N']);
			$modelEnlRec = $this->model('matter\enroll\record');
			$enlRecords = $modelEnlRec->byData($site, $enrollApp, $matchCriteria);
			foreach ($enlRecords as $enlRec) {
				$result[] = $enlRec->data;
			}
		}

		return new \ResponseData($result);
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

		$signinApp = $modelApp->byId($app, ['cascaded' => 'N']);
		$ek = $modelRec->enroll($site, $signinApp);
		/**
		 * 签到登记记录
		 */
		$addedRecord = new \stdClass;
		if (isset($posted->verified)) {
			$addedRecord->verified = $posted->verified;
		}
		if (isset($posted->comment)) {
			$addedRecord->comment = $posted->comment;
		}
		if (isset($posted->tags)) {
			$addedRecord->tags = $posted->tags;
			$this->model('matter\signin')->updateTags($signinApp->id, $posted->tags);
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
			$addedRecord->signin_num = $signinNum;
			$addedRecord->signin_at = $signinAtLast;
			$addedRecord->signin_log = \TMS_MODEL::toJson($posted->signin_log);
		}
		// 更新登记记录数据
		$modelRec->update(
			'xxt_signin_record',
			$addedRecord,
			"enroll_key='$ek'"
		);

		// 保存登记数据
		$modelRec->setData($site, $signinApp, $ek, $posted->data, $user->id);

		// 记录操作日志
		$signinApp->type = 'signin';
		$this->model('matter\log')->matterOp($site, $user, $signinApp, 'add', $ek);

		$record = $modelRec->byId($ek);

		return new \ResponseData($record);
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

		$record = $this->getPostJson();
		$modelApp = $this->model('matter\signin');
		$modelRec = $this->model('matter\signin\record');

		$signinApp = $modelApp->byId($app, ['cascaded' => 'N']);

		$current = time();
		$updatedRecord = new \stdClass;
		$updatedRecord->enroll_at = $current;
		isset($record->comment) && $updatedRecord->comment = $record->comment;

		// 是否通过验证
		if (isset($record->verified)) {
			$updatedRecord->verified = $record->verified;
			if ($record->verified === 'N') {
				// 如果不通过验证，解除关联的报名应用信息
				$updatedRecord->verified_enroll_key = '';
			}
		}

		// 标签
		if (isset($record->tags)) {
			// 更新记录的标签时，要同步更新活动的标签，实现标签在整个活动中有效
			$updatedRecord->tags = $record->tags;
			$modelApp->updateTags($signinApp->id, $record->tags);
		}

		// 签到日志
		if (isset($record->signin_log)) {
			$signinNum = 0;
			$signinAtLast = 0;
			$modelSinLog = $this->model('matter\signin\log');
			foreach ($record->signin_log as $roundId => $signinAt) {
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
								'aid' => $signinApp,
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
			}
			$updatedRecord->signin_num = $record->signin_num = $signinNum;
			$updatedRecord->signin_at = $record->signin_at = $signinAtLast;
			$updatedRecord->signin_log = \TMS_MODEL::toJson($record->signin_log);
		}
		// 更新登记记录数据
		$modelRec->update(
			'xxt_signin_record',
			$updatedRecord,
			"enroll_key='$ek'"
		);

		// 更新登记数据
		$modelRec->setData($site, $signinApp, $ek, $record->data, $user->id);

		// 记录操作日志
		$signinApp->type = 'signin';
		$this->model('matter\log')->matterOp($site, $user, $signinApp, 'update', $record);

		// 返回完整的记录
		$record = $modelRec->byId($ek);

		return new \ResponseData($record);
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
		$modelRec = $this->model('matter\signin\record');
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
				$modelRec->update('xxt_signin_record', ['tags' => $newTags], "enroll_key='$ek'");
			}
		}
		/**
		 * 给应用打标签
		 */
		$this->model('matter\signin')->updateTags($app, $posted->appTags);

		return new \ResponseData('ok');
	}
	/**
	 * 清空一条登记信息
	 */
	public function remove_action($site, $app, $key, $keepData = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\signin\record')->remove($app, $key, $keepData !== 'Y');

		// 记录操作日志
		$app = $this->model('matter\signin')->byId($app, ['cascaded' => 'N']);
		$app->type = 'signin';
		$this->model('matter\log')->matterOp($site, $user, $app, 'remvoe', $key);

		return new \ResponseData($rst);
	}
	/**
	 * 清空登记信息
	 */
	public function empty_action($site, $app, $keepData = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\signin\record')->clean($app, $keepData !== 'Y');

		// 记录操作日志
		$app = $this->model('matter\signin')->byId($app, ['cascaded' => 'N']);
		$app->type = 'signin';
		$this->model('matter\log')->matterOp($site, $user, $app, 'empty');

		return new \ResponseData($rst);
	}
	/**
	 * 登记数据导出
	 *
	 * 如果活动关联了报名活动，需要将关联的数据导出
	 */
	public function export_action($site, $app, $round = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$modelApp = $this->model('matter\signin');
		$signinApp = $modelApp->byId(
			$app,
			['fields' => 'id,title,data_schemas,enroll_app_id,tags', 'cascaded' => 'Y']
		);
		$schemas = json_decode($signinApp->data_schemas);
		if (!empty($round)) {
			foreach ($signinApp->rounds as $rnd) {
				if ($rnd->rid === $round) {
					$round = $rnd;
					break;
				}
			}
		}

		// 关联的报名活动
		if (!empty($signinApp->enroll_app_id)) {
			$enrollApp = $this->model('matter\enroll')->byId($signinApp->enroll_app_id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$enrollSchemas = json_decode($enrollApp->data_schemas);
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

		// 获得所有有效的登记记录
		$q = [
			'enroll_at,signin_at,signin_num,verified,data,signin_log,tags,comment,verified_enroll_key',
			'xxt_signin_record',
			["aid" => $signinApp->id, 'state' => 1],
		];
		$records = $this->model()->query_objs_ss($q);
		if (count($records) === 0) {
			die('record empty');
		}

		require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/PHPExcel.php';

		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator("信信通")
			->setLastModifiedBy("信信通")
			->setTitle($signinApp->title)
			->setSubject($signinApp->title)
			->setDescription($signinApp->title);

		$objActiveSheet = $objPHPExcel->getActiveSheet();

		$colNumber = 0;
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '登记时间');

		if (!empty($round)) {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到时间');
		} else {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到次数');
			foreach ($signinApp->rounds as $rnd) {
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, $rnd->title);
			}
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '迟到次数');
		}
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '审核通过');
		foreach ($schemas as $schema) {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, $schema->title);
		}
		if (!empty($signinApp->tags)) {
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
				foreach ($signinApp->rounds as $rnd) {
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
			//$data = str_replace("\n", ' ', $record->data);
			$data = json_decode($record->data);
			foreach ($schemas as $schema) {
				$v = isset($data->{$schema->id}) ? $data->{$schema->id} : '';
				switch ($schema->type) {
				case 'single':
				case 'phase':
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
				default:
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $v);
					break;
				}
			}
			// 基本信息
			if (!empty($signinApp->tags)) {
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($record->tags) ? $record->tags : '');
			}
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($record->comment) ? $record->comment : '');
			// 关联的报名记录
			if (!empty($record->verified_enroll_key)) {
				$q = [
					'enroll_at,tags,comment',
					'xxt_enroll_record',
					"state=1 and aid='{$signinApp->enroll_app_id}' and enroll_key='{$record->verified_enroll_key}'",
				];
				if ($enrollRecord = $modelApp->query_obj_ss($q)) {
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($enrollRecord->tags) ? $enrollRecord->tags : '');
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($enrollRecord->comment) ? $enrollRecord->comment : '');
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

		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $signinApp->title . '.xlsx"');
		header('Cache-Control: max-age=0');
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');

		exit;
	}
	/**
	 * 将数据导出到另一个活动
	 */
	public function exportByData_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$posted = $this->getPostJson();
		$filter = $posted->filter;
		$target = $posted->target;
		$includeData = isset($posted->includeData) ? $posted->includeData : 'N';

		if (!empty($target)) {
			/*更新应用标签*/
			$modelApp = $this->model('matter\signin');
			/*给符合条件的记录打标签*/
			$modelRec = $this->model('matter\signin\record');
			$q = [
				'distinct enroll_key',
				'xxt_signin_record_data',
				"aid='$app' and state=1",
			];
			$eks = null;
			foreach ($filter as $k => $v) {
				$w = "(name='$k' and ";
				$w .= "concat(',',value,',') like '%,$v,%'";
				$w .= ')';
				$q2 = $q;
				$q2[2] .= ' and ' . $w;
				$eks2 = $modelRec->query_vals_ss($q2);
				$eks = ($eks === null) ? $eks2 : array_intersect($eks, $eks2);
			}
			if (!empty($eks)) {
				$objApp = $modelApp->byId($target, ['cascade' => 'N']);
				$options = ['cascaded' => $includeData];
				foreach ($eks as $ek) {
					$record = $modelRec->byId($ek, $options);
					$user = new \stdClass;
					$user->nickname = $record->nickname;
					$newek = $modelRec->add($site, $objApp, $user);
					if ($includeData === 'Y') {
						$modelRec->setData($user, $site, $objApp, $newek, $record->data);
					}
				}
			}
		}

		return new \ResponseData('ok');
	}
}