<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动用户
 */
class user extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 提交过登记记录的用户
	 */
	public function enrollee_action($app, $page = 1, $size = 30) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		$result = $modelUsr->enrolleeByApp($oApp, $page, $size);

		return new \ResponseData($result);
	}
	/**
	 * 根据通讯录返回用户完成情况
	 */
	public function byMschema_action($app, $mschema) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelEnl = $this->model('site\user\memberschema');
		$oMschema = $modelEnl->byId($mschema, ['cascaded' => 'N']);
		if (false === $oMschema) {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		$result = $modelUsr->enrolleeByMschema($oApp, $oMschema);

		return new \ResponseData($result);
	}
	/**
	 * 发表过评论的用户
	 */
	public function remarker_action($app, $page = 1, $size = 30) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		$result = $modelUsr->remarkerByApp($oApp, $page, $size);

		return new \ResponseData($result);
	}
	/**
	 * 数据导出
	 */
	public function export_action($site, $app, $mschema = 'none', $rid = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		if (false === ($oApp = $this->model('matter\enroll')->byId($app, ['fields' => 'siteid,id,title,entry_rule', 'cascaded' => 'N']))) {
			return new \ParameterError();
		}

		if($oApp->entry_rule->scope === 'member'){
			if(empty((array)$oApp->entry_rule->member)){
				return new \ParameterError('请选择通讯录');
			}
			var_dump($oApp->entry_rule->member, empty((array)$oApp->entry_rule->member));die;
			$modelEnl = $this->model('site\user\memberschema');
			$oMschema = $modelEnl->byId($mschema, ['cascaded' => 'N']);
			if (false === $oMschema) {
				return new \ObjectNotFoundError();
			}

			$modelUsr = $this->model('matter\enroll\user');
			$result = $modelUsr->enrolleeByMschema($oApp, $oMschema);
		}
		// 获得所有有效的登记记录
		$modelRec2 = $this->model('matter\enroll\record');
		$oEnrollApp = \TMS_APP::M('matter\enroll')->byId($app);
		//选择对应轮次
		$criteria = new \stdClass;
		$criteria->record = new \stdClass;
		$criteria->record->rid = new \stdClass;
		$criteria->record->rid = $rid;
		$result = $modelRec2->byApp($oApp, null, $criteria);
		if ($result->total === 0) {
			die('record empty');
		}

		if (!empty($result->records)) {
			$remarkables = [];
			foreach ($oEnrollApp->dataSchemas as $oSchema) {
				if (isset($oSchema->remarkable) && $oSchema->remarkable === 'Y') {
					$remarkables[] = $oSchema->id;
				}
			}
			if (count($remarkables)) {
				foreach ($result->records as &$oRec) {
					$modelRem = $this->model('matter\enroll\data');
					$oRecordData = $modelRem->byRecord($oRec->enroll_key, ['schema' => $remarkables]);
					$oRec->verbose = new \stdClass;
					$oRec->verbose->data = $oRecordData;
				}
			}
		}

		$records = $result->records;
		//print_r($records);die();
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
		$columnNum4 = $columnNum1; //列号
		for ($a = 0, $ii = count($schemas); $a < $ii; $a++) {
			$schema = $schemas[$a];
			/* 跳过图片,描述说明和文件 */
			if (in_array($schema->type, ['html'])) {
				continue;
			}
			if (isset($schema->format) && $schema->format === 'number') {
				$isTotal[$columnNum4] = $schema->id;
			}
			//var_dump($i,$columnNum4,$i+$columnNum4,$i + $columnNum4++,$columnNum4++);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $schema->title);

			if (isset($remarkables) && in_array($schema->id, $remarkables)) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '评论数');
			}
		}

		$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '昵称');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '备注');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '标签');
		// 记录分数
		if ($oApp->scenario === 'voting') {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分数');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '平均分数');
			$titles[] = '总分数';
			$titles[] = '平均分数';
		}
		if ($oApp->scenario === 'quiz') {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分');
			$titles[] = '总分';
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
			!empty($record->score) && $score = $record->score;
			$supplement = $record->supplement;
			$oVerbose = isset($record->verbose) ? $record->verbose->data : false;
			$i = 0;
			for ($i2 = 0, $ii = count($schemas); $i2 < $ii; $i2++) {
				$columnNum3 = $columnNum2; //列号
				$schema = $schemas[$i2];
				$v = isset($data->{$schema->id}) ? $data->{$schema->id} : '';

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
					isset($score->{$schema->id}) && ($cellValue .= ' (' . $score->{$schema->id} . '分)');
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
					isset($score->{$schema->id}) && $cellValue .= ' (' . $score->{$schema->id} . '分)';
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
				default:
					isset($score->{$schema->id}) && $v .= ' (' . $score->{$schema->id} . '分)';
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				}
				if (isset($remarkables) && in_array($schema->id, $remarkables)) {
					if (isset($oVerbose->{$schema->id})) {
						$remark_num = $oVerbose->{$schema->id}->remark_num;
					} else {
						$remark_num = 0;
					}
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i++ + $columnNum3++, $rowIndex, $remark_num, \PHPExcel_Cell_DataType::TYPE_STRING);
				}
				$i++;
			}
			// 昵称
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $record->nickname);
			// 备注
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $record->comment);
			// 标签
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $record->tags);
			// 记录投票分数
			if ($oApp->scenario === 'voting') {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $record->_score);
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, sprintf('%.2f', $record->_average));
			}
			// 记录测验分数
			if ($oApp->scenario === 'quiz') {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $score->sum . '分');
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
}