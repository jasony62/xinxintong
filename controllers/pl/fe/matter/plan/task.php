<?php
namespace pl\fe\matter\plan;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 计划任务活动主控制器
 */
class task extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/plan/frame');
		exit;
	}
	/**
	 *
	 */
	public function get_action($task) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTsk = $this->model('matter\plan\task');
		$task = $modelTsk->escape($task);

		$oTask = $modelTsk->byId($task, ['fields' => 'id,state,task_schema_id,userid,group_id,nickname,verified,born_at,patch_at,first_enroll_at,last_enroll_at,data,supplement,comment']);
		if (false === $oTask && $oTask->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelSchTsk = $this->model('matter\plan\schema\task');
		$oTask->taskSchema = $modelSchTsk->byId($oTask->task_schema_id, ['fields' => 'id,state,title,task_seq,born_mode,born_offset,jump_delayed,can_patch,as_placeholder,auto_verify']);

		return new \ResponseData($oTask);
	}
	/**
	 *
	 */
	public function list_action($app, $page = 1, $size = 20) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,check_schemas']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		// 数据过滤条件
		$oCriteria = $this->getPostJson();

		$modelTsk = $this->model('matter\plan\task');
		$aOptions = ['fields' => 'id,born_at,patch_at,userid,group_id,nickname,verified,comment,first_enroll_at,last_enroll_at,task_schema_id,task_seq,data,score'];
		if (!empty($page) && !empty($size)) {
			$aOptions['paging'] = ['page' => $page, 'size' => $size];
		}
		$oResult = $modelTsk->byApp($oApp, $aOptions, $oCriteria);

		return new \ResponseData($oResult);
	}
	/**
	 * 更新任务
	 */
	public function update_action($task) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTsk = $this->model('matter\plan\task');
		$task = $modelTsk->escape($task);

		$oTask = $modelTsk->byId($task, ['fields' => 'id,siteid,state,aid,task_schema_id,last_enroll_at,userid']);
		if (false === $oTask && $oTask->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($oTask->aid, ['fields' => 'id,state,siteid,title,summary,pic,check_schemas']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();
		$aUpdated = [];
		if (isset($oPosted)) {
			foreach ($oPosted as $prop => $val) {
				switch ($prop) {
					case 'verified':
						if (in_array($val, ['Y', 'N', 'P'])) {
							$aUpdated['verified'] = $val;
						}
						break;
					case 'comment':
						$aUpdated['comment'] = $modelApp->escape($val);
						break;
					case 'data':
						$data = $this->updateUserTask($oTask->task_schema_id, $oApp, $oTask, $val);
						$aUpdated['data'] = $modelApp->escape($modelApp->toJson($data->oCheckData));
						$aUpdated['score'] = $modelApp->escape($modelApp->toJson($data->oScoreData));
						break;
				}
			}
		}

		$rst = 0;
		if (count($aUpdated)) {
			$rst = $modelApp->update('xxt_plan_task', $aUpdated, ['id' => $oTask->id]);
			if (!empty($oPosted)) {
				$oPosted->task_schema_id = $oTask->task_schema_id;
			}
			$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'updateTask', $oPosted);
		}

		return new \ResponseData($rst);
	}
	/*
	* 修改用户任务
	*/
	private function updateUserTask($taskSchmId, $oApp, $oTask, $data) {
		$modelSchTsk = $this->model('matter\plan\schema\task');
		$oTaskSchema = $modelSchTsk->byId($taskSchmId, ['fields' => 'id,siteid,aid,title,task_seq,born_mode,born_offset,auto_verify,can_patch']);
		if (false === $oTaskSchema) {
			return new \ObjectNotFoundError();
		}
		$oActionsById = new \stdClass;
		foreach ($oTaskSchema->actions as $oAction) {
			$oActionsById->{$oAction->id} = $oAction;
		}

		$userSite = $this->model('site\fe\way')->who($oApp->siteid);
		$oCheckData = new \stdClass;
		$oScoreData = new \stdClass;
		$fScoreSum = 0; // 所有任务的累积得分
		foreach ($data as $actionId => $oActionData) {
			$oAction = $oActionsById->{$actionId};
			$oAction->siteid = $oTaskSchema->siteid;
			if (count($oApp->checkSchemas)) {
				$oAction->checkSchemas = array_merge($oAction->checkSchemas, $oApp->checkSchemas);
			}
			$oResult = $this->model('matter\plan\action')->setData($userSite, $oAction, $oTask, $oActionData);
			$oCheckData->{$actionId} = $oResult->dbData;
			$oScoreData->{$actionId} = $oResult->score;
			$fScoreSum += $oResult->score->sum;
		}

		/* 更新用户数据 */
		$aUsrData = ['last_enroll_at' => time(), 'score' => $fScoreSum];
		$modelSchTsk->update('xxt_plan_user', $aUsrData, ['aid' => $oApp->id, 'userid' => $oTask->userid]);

		$data = (object) ['oCheckData' => $oCheckData, 'oScoreData' => $oScoreData];
		return $data;
	}
	/**
	 * 选中记录通过审核
	 */
	public function batchVerify_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,siteid,title,summary,pic']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$updatedCount = 0;
		$taskIds = $this->getPostJson();
		if (!empty($taskIds)) {
			$modelTsk = $this->model('matter\plan\task');
			foreach ($taskIds as $taskId) {
				$rst = $modelTsk->update('xxt_plan_task', ['verified' => 'Y'], ['aid' => $oApp->id, 'id' => $taskId]);
				if ($rst === 1) {
					$updatedCount++;
				}
			}
		}

		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'verify.batch', $taskIds);

		return new \ResponseData($updatedCount);
	}
	/**
	 * 所有记录通过审核
	 */
	public function verifyAll_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,siteid,title,summary,pic']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$rst = $modelApp->update(
			'xxt_plan_task',
			['verified' => 'Y'],
			['aid' => $oApp->id]
		);

		// 记录操作日志
		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'verify.all');

		return new \ResponseData($rst);
	}
	/**
	 * 登记数据导出
	 */
	public function export_action($app, $filter = '') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'siteid,id,title,state,check_schemas,entry_rule']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/*包含的所有任务*/
		$oApp->taskSchemas = $this->model('matter\plan\schema\task')->byApp($oApp->id, ['fields' => 'id,title']);

		$schemas = $oApp->checkSchemas;

		// 获得有效的填写记录
		$modelTsk = $this->model('matter\plan\task');
		if (!empty($filter)) {
			$oCriteria = json_decode($filter);
		} else {
			$oCriteria = new \stdClass;
		}
		$aOptions = ['fields' => 'id,born_at,patch_at,userid,group_id,nickname,verified,comment,first_enroll_at,last_enroll_at,task_schema_id,task_seq,data,score,supplement'];
		$result = $modelTsk->byApp($oApp, $aOptions, $oCriteria);

		if ($result->total === 0) {
			die('record empty');
		}

		if (!empty($result->tasks)) {
			$remarkables = [];
			foreach ($oApp->checkSchemas as $oSchema) {
				if (isset($oSchema->remarkable) && $oSchema->remarkable === 'Y') {
					$remarkables[] = $oSchema->id;
				}
			}
			if (count($remarkables)) {
				foreach ($result->tasks as &$oRec) {
					$modelTskAct = $this->model('matter\plan\action');
					$oRecordData = $modelTskAct->byRecord($oRec->id, ['schema' => $remarkables]);
					$oRec->verbose = new \stdClass;
					$oRec->verbose->data = $oRecordData;
				}
			}
		}

		$tasks = $result->tasks;
		require_once TMS_APP_DIR . '/lib/PHPExcel.php';

		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator("信信通")
			->setLastModifiedBy("信信通")
			->setTitle($oApp->title)
			->setSubject($oApp->title)
			->setDescription($oApp->title);

		$objPHPExcel->setActiveSheetIndex(0);
		$objActiveSheet = $objPHPExcel->getActiveSheet();
		$objActiveSheet->setTitle('用户任务列表');
		$columnNum1 = 0; //列号
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '序号');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '用户');
		$oEntryRule = $oApp->entryRule;
		if (isset($oEntryRule->scope->group) && $oEntryRule->scope->group === 'Y') {
			if (isset($oEntryRule->group)) {
				$oRuleApp = $oEntryRule->group;
				if (!empty($oRuleApp->id)) {
					$oGroupApp = $this->model('matter\group')->byId($oRuleApp->id, ['fields' => 'title', 'cascaded' => 'Y']);
					$groupRounds = [];
					foreach ($oGroupApp->rounds as $oGroupRound) {
						$groupRounds[$oGroupRound->round_id] = $oGroupRound->title;
					}
					$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '分组');
				}
			}
		}
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '任务名称');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '首次登记时间');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '最后登记时间');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '审核结果');

		// 转换标题
		$aNumberSum = []; // 数值型题目的合计
		$aScoreSum = []; // 题目的分数合计
		$columnNum4 = $columnNum1; //列号
		$bRequireSum = false;
		$bRequireScore = false;
		for ($a = 0, $ii = count($schemas); $a < $ii; $a++) {
			$schema = $schemas[$a];
			/* 跳过图片,描述说明和文件 */
			if (in_array($schema->type, ['html'])) {
				continue;
			}
			/* 数值型，需要计算合计 */
			if (isset($schema->format) && $schema->format === 'number') {
				$aNumberSum[$columnNum4] = $schema->id;
				$bRequireSum = true;
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $schema->title);
			/* 需要计算得分 */
			// if ((isset($schema->requireScore) && $schema->requireScore === 'Y') || (isset($schema->format) && $schema->format === 'number')) {
			// 	$aScoreSum[$columnNum4] = $schema->id;
			// 	$bRequireScore = true;
			// 	$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '得分');
			// }
		}
		$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '备注');
		// if ($bRequireScore) {
		// 	$aScoreSum[$columnNum4] = 'sum';
		// 	$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分');
		// 	$titles[] = '总分';
		// }
		// 转换数据
		for ($j = 0, $jj = count($tasks); $j < $jj; $j++) {
			$oRecord = $tasks[$j];
			$rowIndex = $j + 2;
			$columnNum2 = 0; //列号
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $j+1);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oRecord->nickname);
			if (isset($groupRounds)) {
				if (!empty($oRecord->group_id)) {
					if (isset($groupRounds[$oRecord->group_id])) {
						$val = $groupRounds[$oRecord->group_id];
					} else {
						$val = '未分组';
					}
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $val);
				} else {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, '未分组');
				}
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oRecord->taskSchemaTitle);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, date('y-m-j H:i', $oRecord->first_enroll_at));
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, date('y-m-j H:i', $oRecord->last_enroll_at));
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oRecord->verified);
			// 处理登记项
			$data = (array) $oRecord->data;
			$data = reset($data);
			$oRecScore = empty($oRecord->score) ? null : $oRecord->score;
			$supplement = $oRecord->supplement;
			$i = 0; // 列序号
			for ($i2 = 0, $ii = count($schemas); $i2 < $ii; $i2++) {
				$columnNum3 = $columnNum2; //列号
				$schema = $schemas[$i2];
				if (isset($data->{$schema->id})) {
					$v = $data->{$schema->id};
				} else if ((strpos($schema->id, 'member.') === 0) && isset($data->member)) {
					$mbSchemaId = $schema->id;
					$mbSchemaIds = explode('.', $mbSchemaId);
					$mbSchemaId = $mbSchemaIds[1];
					if ($mbSchemaId === 'extattr' && count($mbSchemaIds) == 3) {
						$mbSchemaId = $mbSchemaIds[2];
						$v = $data->member->extattr->{$mbSchemaId};
					} else {
						$v = $data->member->{$mbSchemaId};
					}
				} else {
					$v = '';
				}

				if (in_array($schema->type, ['html'])) {
					continue;
				}
				switch ($schema->type) {
				case 'single':
					$cellValue = '';
					foreach ($schema->ops as $op) {
						if ($op->v === $v) {
							$cellValue = $op->l;
						}
					}
					if (isset($schema->supplement) && $schema->supplement === 'Y') {
						$cellValue .= ' (补充说明：' . (isset($supplement) && isset($supplement->{$schema->id}) ? $supplement->{$schema->id} : '') . ')';
					}
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $cellValue, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
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
					$cellValue = implode(',', $labels);
					if (isset($schema->supplement) && $schema->supplement === 'Y') {
						$cellValue .= ' (补充说明：' . (isset($supplement) && isset($supplement->{$schema->id}) ? $supplement->{$schema->id} : '') . ')';
					}
					$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum3++, $rowIndex, $cellValue);
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
					$v0 = '';
					if (isset($schema->supplement) && $schema->supplement === 'Y') {
						$v0 .= ' (补充说明：' . (isset($supplement) && isset($supplement->{$schema->id}) ? $supplement->{$schema->id} : '') . ')';
					}
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				case 'file':
					$v0 = '';
					if (isset($schema->supplement) && $schema->supplement === 'Y') {
						$v0 .= ' (补充说明：' . (isset($supplement) && isset($supplement->{$schema->id}) ? $supplement->{$schema->id} : '') . ')';
					}
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				case 'date':
					!empty($v) && $v = date('y-m-j H:i', $v);
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				case 'shorttext':
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				case 'multitext':
					if (is_array($v)) {
						$values = [];
						foreach ($v as $val) {
							$values[] = $val->value;
						}
						$v = implode(',', $values);
					}
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				default:
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				}
				$one = $i + $columnNum3;
				// 分数
				if (isset($oRecScore->{$schema->id})) {
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i++ + $columnNum3++, $rowIndex, $oRecScore->{$schema->id}, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
				}
				$i++;
			}
			// 备注
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $oRecord->comment);
			// 记录测验分数
			// if ($bRequireScore) {
			// 	$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, isset($oRecScore->sum) ? $oRecScore->sum : '');
			// }
		}
		// if (!empty($aNumberSum)) {
		// 	// 数值型合计
		// 	$rowIndex = count($tasks) + 2;
		// 	$oSum4Schema = $this->model('matter\plan\action')->sum4Schema($oApp, $taskId);
		// 	$objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
		// 	foreach ($aNumberSum as $key => $val) {
		// 		$objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, $oSum4Schema->$val);
		// 	}
		// }
		// if (!empty($aScoreSum)) {
		// 	// 分数合计
		// 	$rowIndex = count($tasks) + 2;
		// 	$oScore4Schema = $this->model('matter\plan\action')->score4Schema($oApp, $taskId);
		// 	$objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
		// 	foreach ($aScoreSum as $key => $val) {
		// 		$objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, $oScore4Schema->$val);
		// 	}
		// }
		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Cache-Control: max-age=0');

		$filename = $oApp->title . '.xlsx';
		$ua = $_SERVER["HTTP_USER_AGENT"];
		if (preg_match("/MSIE/", $ua) || preg_match("/Trident\/7.0/", $ua)) {
			$encoded_filename = urlencode($filename);
			$encoded_filename = str_replace("+", "%20", $encoded_filename);
			header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
		} else if (preg_match("/Firefox/", $ua)) {
			header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
		} else {
			header('Content-Disposition: attachment; filename="' . $filename . '"');
		}

		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}
	/**
	 * 导出登记数据中的图片
	 */
	public function exportImage_action($app, $taskId = '') {
		if (false === ($oUser = $this->accountUser())) {
			die('请登录');
		}
		if (defined('SAE_TMP_PATH')) {
			die('部署环境不支持该功能');
		}

		$nameSchema = null;
		$imageSchemas = [];

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'siteid,id,title,state,check_schemas,entry_rule']);
		if (false === $oApp || $oApp->state !== '1') {
			die('指定的活动不存在');
		}
		$schemas = $oApp->checkSchemas;

		foreach ($schemas as $schema) {
			if ($schema->type === 'image') {
				$imageSchemas[] = $schema;
			}
		}

		if (count($imageSchemas) === 0) {
			die('活动不包含图片数据');
		}

		// 获得有效的填写记录
		$modelTsk = $this->model('matter\plan\task');
		$oCriteria = new \stdClass;
		!empty($taskId) && $oCriteria->byTaskSchema = $taskId;
		$aOptions = ['fields' => 'id,born_at,patch_at,userid,group_id,nickname,verified,comment,first_enroll_at,last_enroll_at,task_schema_id,task_seq,data,score,supplement'];
		$result = $modelTsk->byApp($oApp, $aOptions, $oCriteria);

		if ($result->total === 0) {
			die('record empty');
		}

		$tasks = $result->tasks;

		// 转换数据
		$aImages = [];
		for ($j = 0, $jj = count($tasks); $j < $jj; $j++) {
			$record = $tasks[$j];
			// 处理登记项
			$data = (array) $record->data;
			$data = reset($data);
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
		$zipFilename = tempnam('/tmp', $oApp->id);
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
		header('Content-disposition: attachment; filename=' . $oApp->title . '.zip');
		header("Content-Type: application/zip");
		header("Content-Transfer-Encoding: binary");
		header('Content-Length: ' . filesize($zipFilename));
		@readfile($zipFilename);

		exit;
	}
}