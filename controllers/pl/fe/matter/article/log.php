<?php
namespace pl\fe\matter\article;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动日志控制器
 */
class log extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action($id) {
		$access = $this->accessControlUser('article', $id);
		if ($access[0] === false) {
			die($access[1]);
		}

		\TPL::output('/pl/fe/matter/article/frame');
		exit;
	}
	/**
	 * 查询日志
	 *
	 */
	public function list_action($id, $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		$reads = $modelLog->listUserMatterOp($id, 'article', [], $page, $size);

		return new \ResponseData($reads);
	}
	/**
	 *
	 */
	public function operateStat_action($site, $appId, $operateType = 'read', $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		$options = [];
		$options['operateType'] = $operateType;
		$filter = $this->getPostJson();

		if (!empty($page) && !empty($size)) {
			$options['paging'] = ['page' => $page, 'size' => $size];
		}
		if (!empty($filter->start)) {
			$options['start'] = $modelLog->escape($filter->start);
		}
		if (!empty($filter->end)) {
			$options['end'] = $modelLog->escape($filter->end);
		}
		if (!empty($filter->nickname)) {
			$options['nickname'] = $modelLog->escape($filter->nickname);
		}
		if (!empty($filter->shareby)) {
			$options['shareby'] = $modelLog->escape($filter->shareby);
		}

		if ($operateType === 'read') {
			$logs = $modelLog->operateStatRead($site, $appId, 'article', $options);
		} else {
			$logs = $modelLog->operateStatShare($site, $appId, 'article', $options);
		}

		return new \ResponseData($logs);
	}
	/**
	 * 导出传播情况
	 */
	public function exportOperateStat_action($site, $appId, $operateType = 'read', $filter = '') {
		$modelLog = $this->model('matter\log');
		$filter = $modelLog->unescape($filter);
		$filter = empty($filter) ? new \stdClass : json_decode($filter);

		$options = [];
		$options['operateType'] = $operateType;
		if (!empty($filter->start)) {
			$options['start'] = $modelLog->escape($filter->start);
		}
		if (!empty($filter->end)) {
			$options['end'] = $modelLog->escape($filter->end);
		}
		if (!empty($filter->nickname)) {
			$options['nickname'] = $modelLog->escape($filter->nickname);
		}
		if (!empty($filter->shareby)) {
			$options['shareby'] = $modelLog->escape($filter->shareby);
		}

		if ($operateType === 'read') {
			$logs = $modelLog->operateStatRead($site, $appId, 'article', $options)->logs;
		} else {
			$logs = $modelLog->operateStatShare($site, $appId, 'article', $options)->logs;
		}


		require_once TMS_APP_DIR . '/lib/PHPExcel.php';

		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator(APP_TITLE)
			->setLastModifiedBy(APP_TITLE)
			->setTitle($oApp->title)
			->setSubject($oApp->title)
			->setDescription($oApp->title);

		$objActiveSheet = $objPHPExcel->getActiveSheet();
		$columnNum1 = 0; //列号
		if ($operateType === 'read') {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '阅读时间');
		} else {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '分享时间');
		}
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '昵称');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '阅读数');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '转发数');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '分享数');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '带来的阅读数');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '带来的阅读人数');
		


		// 转换数据
		for ($j = 0, $jj = count($logs); $j < $jj; $j++) {
			$oRecord = $records[$j];
			$rowIndex = $j + 2;
			$columnNum2 = 0; //列号
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, date('y-m-j H:i', $oRecord->enroll_at));
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oRecord->verified);
			// 轮次名
			if (isset($oRecord->round)) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oRecord->round->title);
			}
			// 处理登记项
			$data = $oRecord->data;
			$oRecScore = empty($oRecord->score) ? null : $oRecord->score;
			$supplement = $oRecord->supplement;
			$oVerbose = isset($oRecord->verbose) ? $oRecord->verbose->data : false;
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
						$v = isset($data->member->extattr->{$mbSchemaId}) ? $data->member->extattr->{$mbSchemaId} : '';
					} else {
						$v = isset($data->member->{$mbSchemaId}) ? $data->member->{$mbSchemaId} : '';
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
					if (isset($schema->format) && $schema->format === 'number') {
						$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
					} else {
						$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					}
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
				case 'url':
					$v0 = '';
					!empty($v->title) && $v0 .= '【' . $v->title . '】';
					!empty($v->description) && $v0 .= $v->description;
					!empty($v->url) && $v0 .= $v->url;
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				default:
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				}
				$one = $i + $columnNum3;
				// 分数
				if ((isset($schema->requireScore) && $schema->requireScore === 'Y') || (isset($schema->format) && $schema->format === 'number')) {
					$cellScore = empty($oRecScore->{$schema->id}) ? 0 : $oRecScore->{$schema->id};
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i++ + $columnNum3++, $rowIndex, $cellScore, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
				}
				// 留言数
				if (isset($remarkables) && in_array($schema->id, $remarkables)) {
					if (isset($oVerbose->{$schema->id})) {
						$remark_num = $oVerbose->{$schema->id}->remark_num;
					} else {
						$remark_num = 0;
					}
					$two = $i + $columnNum3;
					$col = ($two - $one >= 2) ? ($two - 1) : $two;
					$objActiveSheet->setCellValueExplicitByColumnAndRow($col, $rowIndex, $remark_num, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
					$i++;
					$columnNum3++;
				}
				$i++;
			}
			// 昵称
			if ($bRequireNickname) {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $oRecord->nickname);
			}
			// 分组
			if ($bRequireGroup) {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, isset($oRecord->group->title) ? $oRecord->group->title : '');
			}
			// 备注
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $oRecord->comment);
			// 标签
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $oRecord->tags);
			// 记录投票分数
			if ($oApp->scenario === 'voting') {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $oRecord->_score);
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, sprintf('%.2f', $oRecord->_average));
			}
			// 记录测验分数
			if ($bRequireScore) {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, isset($oRecScore->sum) ? $oRecScore->sum : '');
			}
		}
		if (!empty($aNumberSum)) {
			// 数值型合计
			$rowIndex = count($records) + 2;
			$oSum4Schema = $modelRec->sum4Schema($oApp, $rid, $gid);
			$objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
			foreach ($aNumberSum as $key => $val) {
				$objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, $oSum4Schema->$val);
			}
		}
		if (!empty($aScoreSum)) {
			// 分数合计
			$rowIndex = count($records) + 2;
			$oScore4Schema = $modelRec->score4Schema($oApp, $rid, $gid);
			$objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
			foreach ($aScoreSum as $key => $val) {
				$objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, $oScore4Schema->$val);
			}
		}
		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Cache-Control: max-age=0');

		$filename = $oApp->title . '.xlsx';
		$ua = $_SERVER["HTTP_USER_AGENT"];
		//if (preg_match("/MSIE/", $ua) || preg_match("/Trident\/7.0/", $ua)) {
		if (preg_match("/MSIE/", $ua)) {
			$encoded_filename = urlencode($filename);
			$encoded_filename = str_replace("+", "%20", $encoded_filename);
			$encoded_filename = iconv('UTF-8', 'GBK//IGNORE', $encoded_filename);
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
	 * 附件下载日志
	 */
	public function attachmentLog($id, $page = 1, $size = 30) {
		$model = $this->model();
		$filter = $this->getPostJson();

		$q = [
			'id,userid,openid,nickname,download_at,attachment_id',
			'xxt_article_download_log',
			['article_id' = $id]
		];
		if (!empty($filter->start)) {
			$start = new \stdClass;
			$start->op = '>';
			$start->pat = $model->escape($filter->start);
			$q[2]['download_at'] = $start;
		}
		if (!empty($filter->end)) {
			$end = new \stdClass;
			$end->op = '<';
			$end->pat = $model->escape($filter->end);
			$q[2]['download_at'] = $end;
		}
		if (!empty($filter->nickname)) {
			$q['2']['nickname'] = $model->escape($filter->nickname);
		}

		$p = ['o' => 'download_at desc'];
		if (!empty($page) && !empty($size)) {
			$p['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		$logs = $model->query_objs_ss($q, $p);

		$data = new \stdClass;
		$data->logs = $logs;
		$q[0] = 'count(id)';
		$data->total = $model->query_val_ss($q);

		return new \ResponseData($data);
	}
}