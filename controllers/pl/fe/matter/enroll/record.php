<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记记录
 */
class record extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 活动登记名单
	 *
	 */
	public function list_action($site, $app, $page = 1, $size = 30, $orderby = null, $contain = null, $includeSignin = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		// 登记数据过滤条件
		$criteria = $this->getPostJson();

		// 登记记录过滤条件
		$options = array(
			'page' => $page,
			'size' => $size,
			'orderby' => $orderby,
			'contain' => $contain,
		);

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app);

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->find($enrollApp, $options, $criteria);

		return new \ResponseData($result);
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
			return new \ObjectNotFountError();
		}

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->sum4Schema($enrollApp, $rid);

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
		$options = array(
			'page' => $page,
			'size' => $size,
			'rid' => $rid,
		);

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app);

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->recycle($site, $enrollApp, $options);

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

		// 登记数据过滤条件
		$criteria = $this->getPostJson();
		// 登记记录过滤条件
		$options = [
			'page' => $page,
			'size' => $size,
		];
		if (!empty($rid)) {
			$options['rid'] = $rid;
		}

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app);

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->list4Schema($site, $enrollApp, $schema, $options);

		return new \ResponseData($result);
	}
	/**
	 * 手工添加登记信息
	 *
	 * @param string $app
	 */
	public function add_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$modelEnl = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');

		$app = $modelEnl->byId($app, ['cascaded' => 'N']);

		/* 创建登记记录 */
		$ek = $modelRec->enroll($site, $app);
		$record = [];
		$record['verified'] = isset($posted->verified) ? $posted->verified : 'N';
		$record['comment'] = isset($posted->comment) ? $posted->comment : '';
		if (isset($posted->tags)) {
			$record['tags'] = $posted->tags;
			$modelEnl->updateTags($app->id, $posted->tags);
		}
		$modelRec->update('xxt_enroll_record', $record, "enroll_key='$ek'");

		/* 记录登记数据 */
		$result = $modelRec->setData(null, $app, $ek, $posted->data);

		/* 记录操作日志 */
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'add', $ek);

		/* 返回完整的记录 */
		$record = $modelRec->byId($ek);

		return new \ResponseData($record);
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
		$modelRec = $this->model('matter\enroll\record');

		$app = $modelEnl->byId($app, ['cascaded' => 'N']);

		/* 更新记录数据 */
		$updated = new \stdClass;
		$updated->enroll_at = time();
		if (isset($record->comment)) {
			$updated->comment = $modelEnl->escape($record->comment);
		}
		if (isset($record->tags)) {
			$updated->tags = $modelEnl->escape($record->tags);
			$modelEnl->updateTags($app->id, $updated->tags);
		}
		if (isset($record->verified)) {
			$updated->verified = $modelEnl->escape($record->verified);
		}
		if (isset($record->rid)) {
			$updated->rid = $modelEnl->escape($record->rid);
		}
		$modelEnl->update('xxt_enroll_record', $updated, "enroll_key='$ek'");

		/* 记录登记数据 */
		$result = $modelRec->setData(null, $app, $ek, isset($record->data) ? $record->data : new \stdClass);
		$updated2 = new \stdClass;
		if (isset($record->rid)) {
			$updated2->rid = $modelEnl->escape($record->rid);
		}
		$modelEnl->update('xxt_enroll_record_data', $updated2, "enroll_key='$ek'");

		if ($updated->verified === 'Y') {
			$this->_whenVerifyRecord($app, $ek);
		}

		/* 记录操作日志 */
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'update', $record);

		/* 返回完整的记录 */
		$record = $modelRec->byId($ek);
		if (isset($record->rid)) {
			$record->round = new \stdClass;
			if ($round = $this->model('matter\enroll\round')->byId($record->rid, ['fields' => 'title'])) {
				$record->round->title = $round->title;
			} else {
				$record->round->title = '';
			}
		}

		return new \ResponseData($record);
	}
	/**
	 * 删除一条登记信息
	 */
	public function remove_action($site, $app, $key) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\enroll\record')->remove($app, $key);

		// 记录操作日志
		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'remove', $key);

		return new \ResponseData($rst);
	}
	/**
	 * 恢复一条登记信息
	 */
	public function restore_action($site, $app, $key) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\enroll\record')->restore($app, $key);

		// 记录操作日志
		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'restore', $key);

		return new \ResponseData($rst);
	}
	/**
	 * 清空登记信息
	 */
	public function empty_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\enroll\record')->clean($app);

		// 记录操作日志
		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'empty');

		return new \ResponseData($rst);
	}
	/**
	 * 所有记录通过审核
	 */
	public function verifyAll_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
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
		$this->model('matter\log')->matterOp($site, $user, $app, 'verify.all');

		return new \ResponseData($rst);
	}
	/**
	 * 指定记录通过审核
	 */
	public function batchVerify_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$eks = $posted->eks;

		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);

		$model = $this->model();
		foreach ($eks as $ek) {
			$rst = $model->update(
				'xxt_enroll_record',
				['verified' => 'Y'],
				"enroll_key='$ek'"
			);
			// 进行后续处理
			$this->_whenVerifyRecord($app, $ek);
		}

		// 记录操作日志
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'verify.batch', $eks);

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
										'value' => $v,
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
		if (false === ($user = $this->accountUser())) {
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
		if (false === ($user = $this->accountUser())) {
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
			$matchRecords = $modelEnlRec->byData($site, $matchApp, $matchCriteria);
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
		if (false === ($user = $this->accountUser())) {
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
			$matchedRecords = $modelGrpRec->byData($site, $groupApp, $matchCriteria);
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
	public function export_action($site, $app, $rid = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$oApp = $this->model('matter\enroll')->byId($app, ['fields' => 'siteid,id,title,data_schemas,scenario,enroll_app_id,group_app_id,multi_rounds', 'cascaded' => 'N']);
		$schemas = json_decode($oApp->data_schemas);

		// 关联的登记活动
		if (!empty($oApp->enroll_app_id)) {
			$matchApp = $this->model('matter\enroll')->byId($oApp->enroll_app_id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
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
		$modelRec2 = $this->model('matter\enroll\record');
		//选择对应轮次
		$criteria = new \stdClass;
		$criteria->record = new \stdClass;
		$criteria->record->rid = new \stdClass;
		$criteria->record->rid = $rid;
		$records = $modelRec2->find($oApp, null, $criteria);
		if ($records->total === 0) {
			die('record empty');
		}
		$records = $records->records;

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
		$isTotal = []; //是否需要合计
		for ($i = 0, $ii = count($schemas); $i < $ii; $i++) {
			$columnNum4 = $columnNum1; //列号
			$schema = $schemas[$i];
			/* 跳过图片和文件 */
			if (in_array($schema->type, ['image', 'file'])) {
				continue;
			}
			if (isset($schema->number) && $schema->number === 'Y') {
				$isTotal[($i + $columnNum4)] = $schema->id;
			}

			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum4++, 1, $schema->title);
		}
		$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum1++, 1, '昵称');
		$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum1++, 1, '备注');
		$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum1++, 1, '标签');
		// 记录分数
		if ($oApp->scenario === 'voting') {
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum1++, 1, '总分数');
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum1++, 1, '平均分数');
			$titles[] = '总分数';
			$titles[] = '平均分数';
		}
		// 转换数据
		for ($j = 0, $jj = count($records); $j < $jj; $j++) {
			$record = $records[$j];
			$rowIndex = $j + 2;
			$columnNum2 = 0; //列号
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, date('y-m-j H:i', $record->enroll_at));
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->verified);
			//轮次名
			if (isset($record->round)) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->round->title);
			}
			// 处理登记项
			$data = $record->data;
			for ($i = 0, $ii = count($schemas); $i < $ii; $i++) {
				$columnNum3 = $columnNum2; //列号
				$schema = $schemas[$i];
				$v = isset($data->{$schema->id}) ? $data->{$schema->id} : '';
				if (empty($v)) {
					continue;
				}
				switch ($schema->type) {
				case 'single':
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
					$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum3++, $rowIndex, implode(',', $labels));
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
				case 'file':
					break;
				default:
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				}
			}
			// 昵称
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $record->nickname);
			// 备注
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $record->comment);
			// 标签
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $record->tags);
			// 记录分数
			if ($oApp->scenario === 'voting') {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $record->_score);
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, sprintf('%.2f', $record->_average));
			}
		}
		if (!empty($isTotal)) {
			//合计
			$total2 = $modelRec2->sum4Schema($oApp, $rid);
			$rowIndex = count($records) + 2;
			$objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
			foreach ($isTotal as $key => $val) {
				$objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, $total2->$val);
			}
		}

		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $oApp->title . '.xlsx"');
		header('Cache-Control: max-age=0');
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}
	/**
	 * 导出登记数据中的图片
	 */
	public function exportImage_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
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
		$records = $this->model('matter\enroll\record')->find($enrollApp);
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
		if (false === ($user = $this->accountUser())) {
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
		$records = $modelRec->find($fromApp);
		$countOfImport = 0;
		if ($records->total > 0) {
			foreach ($records->records as $record) {
				// 新登记
				$user = new \stdClass;
				$user->uid = $record->userid;
				$user->nickname = $record->nickname;
				$options = [];
				$options['enrollAt'] = $record->enroll_at;
				$options['nickname'] = $record->nickname;
				$ek = $modelRec->enroll($site, $app, $user, $options);
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
}