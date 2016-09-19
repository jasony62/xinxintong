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

		return new \ResponseData($result);
	}
	/**
	 * 关联的报名名单
	 */
	public function listByEnroll_action($site, $app, $page = 1, $size = 30, $rid = null, $orderby = null, $contain = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		// 登记数据过滤条件
		$criteria = $this->getPostJson();

		// 签到应用
		$modelApp = $this->model('matter\signin');
		$signinApp = $modelApp->byId($app);

		if (empty($signinApp->enroll_app_id)) {
			return new \ResponseError('参数错误，没有指定关联的报名活动');
		}
		// 和签到在同一个项目阶段的报名
		if (!empty($signinApp->mission_phase_id)) {
			if (!isset($criteria->data)) {
				$criteria->data = new \stdClass;
			}
			$criteria->data->phase = $signinApp->mission_phase_id;
		}

		// 查询结果
		$enrollApp = $this->model('matter\enroll')->byId($signinApp->enroll_app_id);
		$mdoelRec = $this->model('matter\enroll\record');
		// 登记记录过滤条件
		$options = array(
			'page' => $page,
			'size' => $size,
			'orderby' => $orderby,
			'contain' => $contain,
		);
		$result = $mdoelRec->find($site, $enrollApp, $options, $criteria);

		if ($result->total > 0) {
			foreach ($result->records as &$record) {
				$q = [
					'enroll_at,signin_at,signin_num,data,signin_log,tags,comment',
					'xxt_signin_record',
					"state=1 and aid='{$signinApp->id}' and verified_enroll_key='$record->enroll_key'",
				];
				if ($signinRecord = $modelApp->query_obj_ss($q)) {
					$signinRecord->data = json_decode($signinRecord->data);
					$signinRecord->signin_log = empty($signinRecord->signin_log) ? new \stdClass : json_decode($signinRecord->signin_log);
					// 计算迟到次数
					$lateCount = 0;
					foreach ($signinApp->rounds as $round) {
						if (isset($signinRecord->signin_log->{$round->rid}) && !empty($round->late_at)) {
							if ($signinRecord->signin_log->{$round->rid} > $round->late_at + 60) {
								$lateCount++;
							}
						}
					}
					$signinRecord->lateCount = $lateCount;
					$record->_signinRecord = $signinRecord;
				}
			}
		}

		return new \ResponseData($result);
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
		$current = time();
		$modelRec = $this->model('matter\signin\record');
		$ek = $modelRec->genKey($site, $app);

		/**
		 * 签到登记记录
		 */
		$record = new \stdClass;
		$record->siteid = $site;
		$record->aid = $app;
		$record->enroll_key = $ek;
		$record->enroll_at = $current;
		if (isset($posted->verified)) {
			$record->verified = $posted->verified;
		}
		if (isset($posted->comment)) {
			$record->comment = $posted->comment;
		}
		if (isset($posted->tags)) {
			$record->tags = $posted->tags;
			$this->model('matter\signin')->updateTags($app, $posted->tags);
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
			$record->signin_num = $signinNum;
			$record->signin_at = $signinAtLast;
			$record->signin_log = \TMS_MODEL::toJson($posted->signin_log);
		}
		/**
		 * 登记数据
		 */
		if (isset($posted->data)) {
			$dbData = new \stdClass;
			foreach ($posted->data as $n => $v) {
				if (is_array($v) && isset($v[0]->imgSrc)) {
					/* 上传图片 */
					$vv = [];
					$fsuser = $this->model('fs/user', $site);
					foreach ($v as $img) {
						if (preg_match('/^data:.+base64/', $img->imgSrc)) {
							$rst = $fsuser->storeImg($img);
							if (false === $rst[0]) {
								return new \ResponseError($rst[1]);
							}
							$vv[] = $rst[1];
						} else {
							$vv[] = $img->imgSrc;
						}
					}
					$v = implode(',', $vv);
					//
					$dbData->{$n} = $v;
				} elseif (is_string($v)) {
					$v = $modelRec->escape($v);
					//
					$dbData->{$n} = $v;
				} elseif (is_object($v) || is_array($c = v)) {
					/*多选题*/
					$v = implode(',', array_keys(array_filter((array) $v, function ($i) {return $i;})));
					//
					$dbData->{$n} = $v;
				}
				// 记录数据
				$cd = [
					'aid' => $app,
					'enroll_key' => $ek,
					'name' => $n,
					'value' => $v,
				];
				$modelRec->insert('xxt_signin_record_data', $cd, false);
			}
			// 记录数据
			$dbData = $modelRec->toJson($dbData);
			$record->data = $dbData;
		}

		// 保存登记记录
		$modelRec->insert('xxt_signin_record', $record, false);
		$record = $modelRec->byId($ek);

		// 记录操作日志
		$app = $this->model('matter\signin')->byId($app, ['cascaded' => 'N']);
		$app->type = 'signin';
		$this->model('matter\log')->matterOp($site, $user, $app, 'add', $ek);

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
		$modelRec = $this->model('matter\signin\record');
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
			$this->model('matter\signin')->updateTags($app, $record->tags);
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
			}
			$updatedRecord->signin_num = $record->signin_num = $signinNum;
			$updatedRecord->signin_at = $record->signin_at = $signinAtLast;
			$updatedRecord->signin_log = \TMS_MODEL::toJson($record->signin_log);
		}

		// 更新登记数据
		if (isset($record->data) && is_object($record->data)) {
			$dbData = new \stdClass;
			foreach ($record->data as $cn => $cv) {
				if (is_array($cv) && isset($cv[0]->imgSrc)) {
					//上传图片
					$vv = [];
					$fsuser = $this->model('fs/user', $site);
					foreach ($cv as $img) {
						if (preg_match('/^data:.+base64/', $img->imgSrc)) {
							$rst = $fsuser->storeImg($img);
							if (false === $rst[0]) {
								return new \ResponseError($rst[1]);
							}
							$vv[] = $rst[1];
						} else {
							$vv[] = $img->imgSrc;
						}
					}
					$cv = implode(',', $vv);
					$dbData->{$cn} = $cv;
				} elseif (is_object($cv) || is_array($cv)) {
					// 多选题
					$cv = implode(',', array_keys(array_filter((array) $cv, function ($i) {return $i;})));
					$dbData->{$cn} = $cv;
				} elseif (is_string($cv)) {
					$cv = $modelRec->escape($cv);
					$dbData->{$cn} = $cv;
				}
				// 检查数据项是否存在，如果不存在就先创建一条
				$q = [
					'count(*)',
					'xxt_signin_record_data',
					"aid='$app' and enroll_key='$ek' and name='$cn'",
				];
				if (1 === (int) $modelRec->query_val_ss($q)) {
					$modelRec->update(
						'xxt_signin_record_data',
						['value' => $cv],
						"aid='$app' and enroll_key='$ek' and name='$cn'"
					);
				} else {
					$cd = [
						'aid' => $app,
						'enroll_key' => $ek,
						'name' => $cn,
						'value' => $cv,
					];
					$modelRec->insert('xxt_signin_record_data', $cd, false);
				}
				$record->data->{$cn} = $cv;
			}
			// 记录数据
			$dbData = $modelRec->toJson($dbData);
			$updatedRecord->data = $dbData;
		}

		// 更新数据
		$modelRec->update(
			'xxt_signin_record',
			$updatedRecord,
			"enroll_key='$ek'"
		);

		// 记录操作日志
		$app = $this->model('matter\signin')->byId($app, ['cascaded' => 'N']);
		$app->type = 'signin';
		$this->model('matter\log')->matterOp($site, $user, $app, 'update', $record);

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
		$signinApp = $this->model('matter\signin')->byId(
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
			'enroll_at,signin_at,signin_num,verified,data,signin_log,tags,comment',
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
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '标签');
		}
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '备注');

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
					if (!empty($round->late_at) && $signinAt > $round->late_at + 60) {
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
						if (!empty($rnd->late_at) && $signinAt > $rnd->late_at + 60) {
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
	 * 登记数据导出
	 */
	public function exportByEnroll_action($site, $app, $round) {
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

		// 登记应用
		$enrollApp = $this->model('matter\enroll')->byId(
			$signinApp->enroll_app_id,
			['fields' => 'id,title,data_schemas,scenario', 'cascaded' => 'N']
		);
		$schemas = json_decode($enrollApp->data_schemas);

		// 获得所有有效的登记记录
		$result = $this->model('matter\enroll\record')->find($site, $enrollApp, null, $criteria);
		if ($result->total == 0) {
			die('record empty');
		}
		$records = $result->records;

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
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '审核通过');

		foreach ($schemas as $schema) {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, $schema->title);
		}
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '报名备注');
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '报名标签');
		if (empty($round)) {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到次数');
			foreach ($signinApp->rounds as $rnd) {
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, $rnd->title);
			}
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '迟到次数');
		} else {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到时间');
		}
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到备注');
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '签到标签');

		// 转换数据
		$rowNumber = 2;
		foreach ($records as $record) {
			$colNumber = 0;
			// 基本信息
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, date('y-m-j H:i', $record->enroll_at));
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $record->verified);
			// 处理登记项
			$data = $record->data;
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
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $record->comment);
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $record->tags);

			// 获得对应的签到数据
			$q = [
				'enroll_at,signin_num,data,signin_log,tags,comment',
				'xxt_signin_record',
				"state=1 and aid='{$signinApp->id}' and verified_enroll_key='$record->enroll_key'",
			];
			if ($signinRecord = $modelApp->query_obj_ss($q)) {
				$signinLog = empty($signinRecord->signin_log) ? new \stdClass : json_decode($signinRecord->signin_log);
				if (empty($round)) {
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $signinRecord->signin_num);
					$lateCount = 0;
					foreach ($signinApp->rounds as $rnd) {
						if (isset($signinLog->{$rnd->rid})) {
							$signinAt = $signinLog->{$rnd->rid};
							if (!empty($rnd->late_at) && $signinAt > $rnd->late_at + 60) {
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
				} else {
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, date('y-m-j H:i', $signinLog->{$round}));
				}
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $signinRecord->comment);
				$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $signinRecord->tags);
			} else {
				if (empty($round)) {
					foreach ($signinApp->rounds as $rnd) {
						$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
					}
				} else {
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
				}
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