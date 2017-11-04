<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 *
 */
class stat extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 统计登记信息
	 *
	 * 只统计single/multiple类型的数据项
	 *
	 * @return array name => array(l=>label,c=>count)
	 *
	 */
	public function get_action($site, $app, $rid = '', $renewCache = 'Y') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$result = $this->model('matter\enroll\record')->getStat($oApp, $rid, $renewCache);

		return new \ResponseData($result);
	}
	/**
	 * 导出报告
	 */
	public function export_action($site, $app, $rid = '') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oSite = $this->model('site')->byId($site, ['fields' => 'name']);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		if (defined('SAE_TMP_PATH')) {
			$this->_saeExport($oApp, $rid);
		}

		require_once TMS_APP_DIR . '/lib/jpgraph/jpgraph.php';
		require_once TMS_APP_DIR . '/lib/jpgraph/jpgraph_bar.php';
		require_once TMS_APP_DIR . '/lib/jpgraph/jpgraph_pie.php';
		require_once TMS_APP_DIR . '/lib/jpgraph/jpgraph_line.php';
		require_once TMS_APP_DIR . '/lib/PHPWord/bootstrap.php';

		if (isset($oApp->rpConfig->pl)) {
			$oPlConfig = $oApp->rpConfig->pl;
			$aExcludeSchemaIds = empty($oPlConfig->exclude) ? [] : $oPlConfig->exclude;
		} else {
			$aExcludeSchemaIds = [];
		}
		$schemas = [];
		$schemasById = [];
		foreach ($oApp->dataSchemas as $oSchema) {
			if (!in_array($oSchema->id, $aExcludeSchemaIds) && $oSchema->type !== 'html') {
				$schemas[] = $oSchema;
				$schemasById[$oSchema->id] = $oSchema;
			}
		}

		$aStatResult = $this->model('matter\enroll\record')->getStat($oApp, $rid);

		$phpWord = new \PhpOffice\PhpWord\PhpWord();
		$phpWord->setDefaultFontName('Times New Roman');
		$section = $phpWord->addSection(array('pageNumberingStart' => 1));
		$header = $section->addHeader();
		$header->addText($oSite->name, ['bold' => true, 'size' => 14, 'name' => 'Arial'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
		$footer = $section->addFooter();
		$footer->addPreserveText('Page {PAGE} of {NUMPAGES}.', null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

		$mappingOfImages = [];
		$modelRec = $this->model('matter\enroll\record');

		$scoreSummary = []; //所有打分题汇总数据
		$totalScoreSummary = 0; //所有打分题的平局分合计
		$fancyTableStyle = array(
			'borderSize' => 6,
			//'borderColor' => '006699',
			//'cellMargin' => 44,
			'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
		);
		$firstStyle = [
			//'borderBottomSize' => 12,
			//'borderBottomColor' => '0000FF',
			'bold' => true,
			'size' => 14,
		];
		$fancyTableCellStyle = array('valign' => 'center');
		$paragraphStyle = array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER);
		$cellTextStyle = ['size' => 12];
		$imgStyle = array(
			'marginTop' => 1,
			'marginLeft' => 1,
			'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
			'wrappingStyle' => 'behind',
		);
		// a4纸宽210mm 取15㎝，1CM=567 twips
		$a4_width = 15 * 567;
		$graphWidth = 450;
		$graphHeight = 300;
		$section->addText($oApp->title, ['bold' => true, 'size' => 24], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
		$section->addTextBreak(2, null, null);

		foreach ($schemas as $index => $schema) {
			$section->addText($schema->title, ['bold' => true, 'size' => 16]);
			$section->addTextBreak(1, null, null);

			if (in_array($schema->type, ['name', 'email', 'mobile', 'date', 'location', 'shorttext', 'longtext'])) {
				$textResult = $modelRec->list4Schema($oApp, $schema->id, ['rid' => $rid]);
				if (!empty($textResult->records)) {
					//数值型的饼图
					if (isset($schema->format) && $schema->format === 'number') {
						$data = [];
						foreach ($textResult->records as $record) {
							$schemaId = $schema->id;
							if (isset($record->data->$schemaId)) {
								$data[] = $record->data->$schemaId;
							}
						}
						if (empty($data)) {
							continue;
						}

						$graph = new \PieGraph($graphWidth, $graphHeight);
						$graph->SetShadow();
						$pie = new \PiePlot($data);
						$labels = [];
						for ($i = 0, $l = count($data); $i < $l; $i++) {
							$labels[] = $op = $data[$i] . '：%.1f%%';
						}
						$pie->value->SetFont(FF_SIMSUN, FS_NORMAL);
						$graph->Add($pie);
						$pie->ShowBorder();
						$pie->setSliceColors(['#F7A35C', '#8085E9', '#90ED7D', '#7CB5EC', '#434348']);
						$pie->SetColor(array(255, 255, 255));
						foreach ($labels as &$rec) {
							$rec = iconv("UTF-8", "GB2312//IGNORE", $rec);
						}
						$pie->SetLabels($labels, 1);

						//$graph->title->Set($schema->title);
						//$graph->title->SetFont(FF_SIMSUN, FS_NORMAL);

						$graph->Stroke(_IMG_HANDLER);
						ob_start(); // start buffering
						$graph->img->Stream(); // print data to buffer
						$image_data = ob_get_contents(); // retrieve buffer contents
						ob_end_clean(); // stop buffer
						//
						$section->addImage($image_data, $imgStyle);
					}
					//拼装表格
					$records = $textResult->records;
					$phpWord->addTableStyle("one", $fancyTableStyle, $firstStyle);
					$table1 = $section->addTable("one", $fancyTableStyle);
					$sumNumber = 0; //数值型最后合计的列号

					if (isset($oApp->rpConfig)) {
						$rpConfig = $oApp->rpConfig;
						if (!empty($rpConfig->marks)) {
							foreach ($rpConfig->marks as $key => $mark) {
								if ($schema->title !== $mark->title) {
									$sumNumber++;
								}
							}
						}
					} else {
						//$sumNumber++;
					}

					$cell_w1 = floor(1.6 * 567);
					$cell_w2 = floor(($a4_width - $cell_w1) / ($sumNumber + 1));

					$table1->addRow(500);
					$table1->addCell($cell_w1, $fancyTableCellStyle)->addText('序号', $firstStyle, $paragraphStyle);

					//标识
					if (!empty($oApp->rp_config)) {
						$rpConfig = json_decode($oApp->rp_config);
						if (!empty($rpConfig->marks)) {
							foreach ($rpConfig->marks as $key => $mark) {
								if ($schema->title !== $mark->title) {
									$table1->addCell($cell_w2, $fancyTableCellStyle)->addText($mark->title, $firstStyle, $paragraphStyle);
									//$sumNumber++;
								}
							}
						}
					} else {
						//$table1->addCell($cell_w2, $fancyTableCellStyle)->addText('昵称', $firstStyle, $paragraphStyle);
						//$sumNumber++;
					}
					$table1->addCell($cell_w2, $fancyTableCellStyle)->addText('登记内容', $firstStyle, $paragraphStyle);

					for ($i = 0, $l = count($records); $i < $l; $i++) {
						$table1->addRow(500);
						$record = $records[$i];
						$table1->addCell($cell_w1, $fancyTableCellStyle)->addText(($i + 1), $cellTextStyle);
						//标识
						if (isset($rpConfig) && !empty($rpConfig->marks)) {
							foreach ($rpConfig->marks as $mark) {
								if ($schema->id !== $mark->id) {
									if ($mark->id === 'nickname') {
										$table1->addCell($cell_w2, $fancyTableCellStyle)->addText($record->nickname, $cellTextStyle);
									} else {
										$markId = $mark->id;
										if (isset($record->data->$markId)) {
											$markSchema = $schemasById[$mark->id];
											if (in_array($markSchema->type, ['single', 'phase'])) {
												$label = '';
												foreach ($markSchema->ops as $op) {
													if ($op->v === $record->data->$markId) {
														$label = $op->l;
														break;
													}
												}
											} else {
												$label = $record->data->$markId;
											}
											$table1->addCell($cell_w2, $fancyTableCellStyle)->addText($label, $cellTextStyle);
										} else {
											$table1->addCell($cell_w2, $fancyTableCellStyle)->addText('');
										}
									}
								}
							}
						} else {
							//$table1->addCell($cell_w2, $fancyTableCellStyle)->addText($record->nickname);
						}
						$schemaId = $schema->id;
						if (isset($record->data->$schemaId)) {
							$table1->addCell($cell_w2, $fancyTableCellStyle)->addText($record->data->$schemaId, $cellTextStyle);
						} else {
							$table1->addCell($cell_w2, $fancyTableCellStyle)->addText('');
						}
					}
					//数值型显示合计
					if (isset($textResult->sum)) {
						$table1->addRow(500);
						$table1->addCell($cell_w1, $fancyTableCellStyle)->addText('合计', $cellTextStyle);
						if ($sumNumber > 0) {
							for ($i = 0, $j = $sumNumber; $i < $j; $i++) {
								$table1->addCell($cell_w2, $fancyTableCellStyle)->addText('');
							}
						}
						$table1->addCell($cell_w2, $fancyTableCellStyle)->addText($textResult->sum, $cellTextStyle);
					}
					$section->addTextBreak(2, null, null);
				}
			} else if (in_array($schema->type, ['single', 'phase', 'multiple'])) {
				$oSchemaStat = $aStatResult[$schema->id];
				$data = [];
				foreach ($oSchemaStat->ops as $op) {
					if ((int) $op->c !== 0) {
						$data[] = (int) $op->c;
					}
				}
				if (empty($data)) {
					continue;
				}
				if (in_array($schema->type, ['single', 'phase'])) {
					// Create a pie pot
					if ($oSchemaStat->sum) {
						$graph = new \PieGraph($graphWidth, $graphHeight);
						$graph->SetShadow();
						$pie = new \PiePlot($data);
						$labels = [];
						for ($i = 0, $l = count($oSchemaStat->ops); $i < $l; $i++) {
							$op = $oSchemaStat->ops[$i];
							if ((int) $op->c !== 0) {
								if (isset($oPlConfig->label) && $oPlConfig->label === 'percentage') {
									$labels[] = '选项' . ($i + 1) . '：%.1f%%';
								} else {
									$labels[] = '选项' . ($i + 1) . '：' . $op->c;
								}
							}
						}
						$pie->value->SetFont(FF_SIMSUN, FS_NORMAL);
						$graph->Add($pie);
						$pie->ShowBorder();
						$pie->setSliceColors(['#F7A35C', '#8085E9', '#90ED7D', '#7CB5EC', '#434348']);
						$pie->SetColor(array(255, 255, 255));
						foreach ($labels as &$rec) {
							$rec = iconv("UTF-8", "GB2312//IGNORE", $rec);
						}
						$pie->SetLabels($labels, 1);
					}
				} else if ($schema->type === 'multiple') {
					// Create the graph. These two calls are always required
					$graph = new \Graph($graphWidth, $graphHeight);
					$graph->SetScale("textint");
					// Add a drop shadow
					$graph->SetShadow();
					// Adjust the margin a bit to make more room for titles
					$graph->img->SetMargin(40, 30, 20, 40);
					// Create a bar pot
					$labels = [];
					for ($i = 0, $l = count($oSchemaStat->ops); $i < $l; $i++) {
						$op = $oSchemaStat->ops[$i];
						if ((int) $op->c !== 0) {
							if (isset($oPlConfig->label) && $oPlConfig->label === 'percentage') {
								$labels[] = '选项' . ($i + 1) . '：' . round($op->c / $oSchemaStat->sum * 100, 2) . '%';
							} else {
								$labels[] = '选项' . ($i + 1) . '：' . $op->c;
							}
						}
					}
					$bar = new \BarPlot($data);
					$graph->Add($bar);
					// Setup the titles
					$graph->xaxis->title->Set(iconv("UTF-8", "GB2312//IGNORE", "选项"));
					$graph->yaxis->title->Set(iconv("UTF-8", "GB2312//IGNORE", "数量"));
					foreach ($labels as &$rec) {
						$rec = iconv("UTF-8", "GB2312//IGNORE", $rec);
					}
					$graph->xaxis->SetTickLabels($labels);
					$graph->xaxis->SetFont(FF_SIMSUN, FS_NORMAL);

					$graph->yaxis->title->SetFont(FF_SIMSUN, FS_NORMAL);
					$graph->xaxis->title->SetFont(FF_SIMSUN, FS_NORMAL);
				}
				if ($oSchemaStat->sum) {
					$graph->Stroke(_IMG_HANDLER);
					ob_start(); // start buffering
					$graph->img->Stream(); // print data to buffer
					$image_data = ob_get_contents(); // retrieve buffer contents
					ob_end_clean(); // stop buffer

					$section->addImage($image_data, $imgStyle);
				}
				$section->addTextBreak(1, null, null);
				$phpWord->addTableStyle("two", $fancyTableStyle, $firstStyle);
				$table2 = $section->addTable('two', $fancyTableStyle);
				$cell_w1 = 2.5 * 567;
				$cell_w3 = 1.6 * 567;
				$cell_w2 = $a4_width - $cell_w1 - $cell_w3;
				$table2->addRow(500);
				$table2->addCell($cell_w1, $fancyTableCellStyle)->addText('选项编号', $firstStyle, $paragraphStyle);
				$table2->addCell($cell_w2, $fancyTableCellStyle)->addText('选项内容', $firstStyle, $paragraphStyle);
				if (isset($oPlConfig->number) && $oPlConfig->number === 'Y') {
					$table2->addCell($cell_w3, $fancyTableCellStyle)->addText('数量', $firstStyle, $paragraphStyle);
				}
				if (isset($oPlConfig->percentage) && $oPlConfig->percentage === 'Y') {
					$table2->addCell($cell_w3, $fancyTableCellStyle)->addText('占比', $firstStyle, $paragraphStyle);
				}
				for ($i = 0, $l = count($oSchemaStat->ops); $i < $l; $i++) {
					$op = $oSchemaStat->ops[$i];
					$table2->addRow(500);
					$table2->addCell($cell_w1, $fancyTableCellStyle)->addText("选项" . ($i + 1), $cellTextStyle);
					$table2->addCell($cell_w2, $fancyTableCellStyle)->addText($op->l, $cellTextStyle);
					if (isset($oPlConfig->number) && $oPlConfig->number === 'Y') {
						$table2->addCell($cell_w3, $fancyTableCellStyle)->addText($op->c, $cellTextStyle);
					}
					if (isset($oPlConfig->percentage) && $oPlConfig->percentage === 'Y') {
						$table2->addCell($cell_w3, $fancyTableCellStyle)->addText(round($op->c / $oSchemaStat->sum * 100, 2) . '%', $cellTextStyle);
					}
				}
				$section->addTextBreak(2, null, null);
			} else if ('score' === $schema->type) {
				//
				$oSchemaStat = $aStatResult[$schema->id];
				$labels = [];
				$data = [];
				$totalScore = 0;
				for ($i = 0, $l = count($oSchemaStat->ops); $i < $l; $i++) {
					$labels[] = '打分项' . ($i + 1);
					$op = &$oSchemaStat->ops[$i];
					$op->c = round((float) $op->c, 2);
					$data[] = $op->c;
					$totalScore += $op->c;
				}
				if (count($data) > 1) {
					// 如果只有1个点，jpgraph会报错，所以跳过绘图。
					// Setup the graph
					$graph = new \Graph($graphWidth, $graphHeight);
					$graph->SetScale("textlin");

					$theme_class = new \UniversalTheme;

					$graph->SetTheme($theme_class);
					$graph->img->SetAntiAliasing(false);
					//$graph->title->Set($oSchemaStat->title);
					//$graph->title->SetFont(FF_SIMSUN, FS_NORMAL);
					$graph->SetBox(false);

					$graph->img->SetAntiAliasing();

					$graph->yaxis->HideZeroLabel();
					$graph->yaxis->HideLine(false);
					$graph->yaxis->HideTicks(false, false);

					$graph->xgrid->Show();
					$graph->xgrid->SetLineStyle("solid");
					foreach ($labels as &$rec) {
						$rec = iconv("UTF-8", "GB2312//IGNORE", $rec);
					}
					$graph->xaxis->SetTickLabels($labels);
					$graph->xgrid->SetColor('#E3E3E3');
					$graph->xaxis->SetFont(FF_SIMSUN, FS_NORMAL);

					$p1 = new \LinePlot($data);
					$graph->Add($p1);
					$p1->SetColor("#6495ED");

					// Output line
					$graph->Stroke(_IMG_HANDLER);
					ob_start(); // start buffering
					$graph->img->Stream(); // print data to buffer
					$image_data = ob_get_contents(); // retrieve buffer contents
					ob_end_clean(); // stop buffer

					$section->addImage($image_data, $imgStyle);
				}
				// table
				$phpWord->addTableStyle("three", $fancyTableStyle, $firstStyle);
				$table3 = $section->addTable('three', $fancyTableStyle);
				$table3->addRow(500);
				$cell_w1 = 3 * 567;
				$cell_w3 = 2 * 567;
				$cell_w2 = $a4_width - $cell_w1 - $cell_w3;
				$table3->addCell($cell_w1, $fancyTableCellStyle)->addText('打分项编号', $firstStyle, $paragraphStyle);
				$table3->addCell($cell_w2, $fancyTableCellStyle)->addText('打分项内容', $firstStyle, $paragraphStyle);
				$table3->addCell($cell_w3, $fancyTableCellStyle)->addText('平均分', $firstStyle, $paragraphStyle);

				for ($i = 0, $l = count($oSchemaStat->ops); $i < $l; $i++) {
					$op2 = $oSchemaStat->ops[$i];
					$table3->addRow(500);
					$table3->addCell($cell_w1, $fancyTableCellStyle)->addText($i + 1, $cellTextStyle);
					$table3->addCell($cell_w2, $fancyTableCellStyle)->addText($op2->l, $cellTextStyle);
					$table3->addCell($cell_w3, $fancyTableCellStyle)->addText($op2->c, $cellTextStyle);
				}
				$avgScore = round($totalScore / count($oSchemaStat->ops), 2);
				$table3->addRow(500);
				$table3->addCell($cell_w1, $fancyTableCellStyle)->addText('本项平均分', $cellTextStyle);
				$table3->addCell($cell_w2, $fancyTableCellStyle)->addText('');
				$table3->addCell($cell_w3, $fancyTableCellStyle)->addText($avgScore, $cellTextStyle);
				/*打分题汇总*/
				$scoreSummary[] = ['l' => $schema->title, 'c' => $avgScore];
				$totalScoreSummary += $avgScore;
			}
			$section->addTextBreak(2, null, null);
		}
		$avgScoreSummary = 0; //所有打分题的平均分
		if (count($scoreSummary)) {
			$avgScoreSummary = round($totalScoreSummary / count($scoreSummary), 2);
			$section->addText('打分项汇总', ['bold' => true, 'size' => 18]);
			$section->addTextBreak(1, null, null);
			$phpWord->addTableStyle("four", $fancyTableStyle, $firstStyle);
			$table4 = $section->addTable('four', $fancyTableStyle);
			$cell_fixed = 2 * 567;
			$cell_other = floor($a4_width - $cell_fixed);
			$table4->addRow(800);
			$table4->addCell($cell_other, $fancyTableCellStyle)->addText('打分项', $firstStyle, $paragraphStyle);
			$table4->addCell($cell_fixed, $fancyTableCellStyle)->addText('平均分', $firstStyle, $paragraphStyle);

			foreach ($scoreSummary as $op) {
				$table4->addRow(500);
				$table4->addCell($cell_other, $fancyTableCellStyle)->addText($op['l'], $cellTextStyle);
				$table4->addCell($cell_fixed, $fancyTableCellStyle)->addText($op['c'], $cellTextStyle);
			}
			$table4->addRow(500);
			$table4->addCell($cell_other, $fancyTableCellStyle)->addText('所有打分项总平均分', $cellTextStyle);
			$table4->addCell($cell_fixed, $fancyTableCellStyle)->addText($avgScoreSummary, $cellTextStyle);
			$table4->addRow(500);
			$table4->addCell($cell_other, $fancyTableCellStyle)->addText('所有打分项合计', $cellTextStyle);
			$table4->addCell($cell_fixed, $fancyTableCellStyle)->addText($totalScoreSummary, $cellTextStyle);
		}
		$section->addTextBreak(1, null, null);

		$name = !empty($oApp->title) ? $oApp->title : uniqid();
		$file = $name . '.docx';
		header("Content-Description: File Transfer");
		header('Content-Disposition: attachment; filename="' . $file . '"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Expires: 0');
		$xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
		$xmlWriter->save("php://output");

		die();
	}

	/**
	 * 根据HTML代码获取word文档内容
	 * 创建一个本质为mht的文档，该函数会分析文件内容并从远程下载页面中的图片资源
	 * 该函数依赖于类MhtMake
	 * 该函数会分析img标签，提取src的属性值。但是，src的属性值必须被引号包围，否则不能提取
	 *
	 * @param string $content HTML内容
	 * @param string $absolutePath 网页的绝对路径。如果HTML内容里的图片路径为相对路径，那么就需要填写这个参数，来让该函数自动填补成绝对路径。这个参数最后需要以/结束
	 * @param bool $isEraseLink 是否去掉HTML内容中的链接
	 */
	private function _MhtMake($content, &$images, $mimeType = 'image/png') {
		require_once TMS_APP_DIR . '/lib/Mhtmaker.class.php';

		$mht = new \Mhtmaker();

		$mht->AddContents("page.html", $mht->GetMimeType("page.html"), $content);

		foreach ($images as $filepath => $data) {
			$mht->AddContents($filepath, $mimeType, $data, 'base64');
		}

		return $mht->GetFile();
	}
	/**
	 *
	 */
	private function _saeExport($oApp, $rid = '') {
		require_once TMS_APP_DIR . '/lib/jpgraph/jpgraph.php';
		require_once TMS_APP_DIR . '/lib/jpgraph/jpgraph_bar.php';
		require_once TMS_APP_DIR . '/lib/jpgraph/jpgraph_pie.php';
		require_once TMS_APP_DIR . '/lib/jpgraph/jpgraph_line.php';

		if (isset($oApp->rpConfig->pl)) {
			$oPlConfig = $oApp->rpConfig->pl;
			$aExcludeSchemaIds = empty($oPlConfig->exclude) ? [] : $oPlConfig->exclude;
		} else {
			$aExcludeSchemaIds = [];
		}
		$schemas = [];
		$schemasById = [];
		foreach ($oApp->dataSchemas as $oSchema) {
			if (!in_array($oSchema->id, $aExcludeSchemaIds) && $oSchema->type !== 'html') {
				$schemas[] = $oSchema;
				$schemasById[$oSchema->id] = $oSchema;
			}
		}

		$aStatResult = $this->model('matter\enroll\record')->getStat($oApp, $rid);

		$html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
		$html .= '<head>';
		$html .= '<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>';
		$html .= "<style>table{width:100%;border-spacing:0;border-collapse:collapse;border:1px solid #ddd;}th,td{border:1px solid #ddd;}</style>";
		$html .= '</head>';
		$html .= '<body>';

		$mappingOfImages = [];
		$modelRec = $this->model('matter\enroll\record');

		$scoreSummary = []; //所有打分题汇总数据
		$totalScoreSummary = 0; //所有打分题的平局分合计
		foreach ($schemas as $index => $schema) {
			$html .= "<h3><span>{$schema->title}</span></h3>";
			if (in_array($schema->type, ['name', 'email', 'mobile', 'date', 'location', 'shorttext', 'longtext'])) {
				$textResult = $modelRec->list4Schema($oApp, $schema->id, ['rid' => $rid]);
				if (!empty($textResult->records)) {
					//数值型的饼图
					if (isset($schema->format) && $schema->format === 'number') {
						$data = [];
						foreach ($textResult->records as $record) {
							$schemaId = $schema->id;
							if (isset($record->data->$schemaId)) {
								$data[] = $record->data->$schemaId;
							}
						}
						if (empty($data)) {
							continue;
						}
						$graph = new \PieGraph(550, 300);
						$graph->SetShadow();
						$pie = new \PiePlot($data);
						$labels = [];
						for ($i = 0, $l = count($data); $i < $l; $i++) {
							$labels[] = $op = $data[$i] . '：%.1f%%';
						}
						$pie->value->SetFont(FF_SIMSUN, FS_NORMAL);
						$graph->Add($pie);
						$pie->ShowBorder();
						$pie->setSliceColors(['#F7A35C', '#8085E9', '#90ED7D', '#7CB5EC', '#434348']);
						$pie->SetColor(array(255, 255, 255));
						foreach ($labels as &$rec) {
							$rec = iconv("UTF-8", "GB2312//IGNORE", $rec);
						}
						$pie->SetLabels($labels, 1);

						//$graph->title->Set($schema->title);
						//$graph->title->SetFont(FF_SIMSUN, FS_NORMAL);

						$graph->Stroke(_IMG_HANDLER);
						ob_start(); // start buffering
						$graph->img->Stream(); // print data to buffer
						$image_data = ob_get_contents(); // retrieve buffer contents
						ob_end_clean(); // stop buffer
						$imageBase64 = chunk_split(base64_encode($image_data));
						//
						$mappingOfImages[$schema->id . '.base64'] = $imageBase64;
						//
						$html .= '<img src="' . $schema->id . '.base64" />';
					}
					//拼装表格
					$records = $textResult->records;
					$html .= "<table><thead><tr>";
					$html .= "<th>序号</th>";
					//$html .= "<th>轮次</th>";
					$sumNumber = 0; //数值型最后合计的列号
					//标识
					if (!empty($oApp->rp_config)) {
						$rpConfig = json_decode($oApp->rp_config);
						if (!empty($rpConfig->marks)) {
							foreach ($rpConfig->marks as $key => $mark) {
								if ($schema->title !== $mark->name) {
									$html .= "<th>" . $mark->name . "</th>";
									$sumNumber++;
								}
							}
						}
					} else {
						$html .= "<th>昵称</th>";
						$sumNumber++;
					}
					$html .= "<th>登记内容</th></tr></thead>";
					$html .= "<tbody>";
					for ($i = 0, $l = count($records); $i < $l; $i++) {
						$html .= "<tr>";
						$record = $records[$i];
						$html .= "<td>" . ($i + 1) . "</td>";
						//标识
						if (isset($rpConfig) && !empty($rpConfig->marks)) {
							foreach ($rpConfig->marks as $mark) {
								if ($schema->id !== $mark->id) {
									if ($mark->id === 'nickname') {
										$html .= "<td>" . $record->nickname . "</td>";
									} else {
										$markId = $mark->id;
										if (isset($record->data->$markId)) {
											$markSchema = $schemasById[$mark->id];
											if (in_array($markSchema->type, ['single', 'phase'])) {
												$label = '';
												foreach ($markSchema->ops as $op) {
													if ($op->v === $record->data->$markId) {
														$label = $op->l;
														break;
													}
												}
											} else {
												$label = $record->data->$markId;
											}
											$html .= "<td>" . $label . "</td>";

										} else {
											$html .= "<td></td>";
										}
									}
								}
							}
						} else {
							$html .= "<td>" . $record->nickname . "</td>";
						}
						$schemaId = $schema->id;
						if (isset($record->data->$schemaId)) {
							$html .= "<td>" . $record->data->$schemaId . "</td>";
						} else {
							$html .= "<td></td>";
						}
					}
					//数值型显示合计
					if (isset($textResult->sum)) {
						$html .= "<tr><td>合计</td>";
						if ($sumNumber > 0) {
							for ($i = 0, $j = $sumNumber + 1; $i < $j; $i++) {
								$html .= "<td> </td>";
							}
						}
						$html .= "<td>" . $textResult->sum . "</td></tr>";
					}
					$html .= "</tbody></table>";
				}
			} else if (in_array($schema->type, ['single', 'phase', 'multiple'])) {
				$oSchemaStat = $aStatResult[$schema->id];
				$data = [];
				foreach ($oSchemaStat->ops as $op) {
					if ((int) $op->c !== 0) {
						$data[] = (int) $op->c;
					}
				}
				if (empty($data)) {
					continue;
				}
				if (in_array($schema->type, ['single', 'phase'])) {
					// Create a pie pot
					if ($oSchemaStat->sum) {
						$graph = new \PieGraph(550, 300);
						$graph->SetShadow();
						$pie = new \PiePlot($data);
						$labels = [];
						for ($i = 0, $l = count($oSchemaStat->ops); $i < $l; $i++) {
							$op = $oSchemaStat->ops[$i];
							if ((int) $op->c !== 0) {
								if (isset($oPlConfig->label) && $oPlConfig->label === 'percentage') {
									$labels[] = '选项' . ($i + 1) . '：%.1f%%';
								} else {
									$labels[] = '选项' . ($i + 1) . '：' . $op->c;
								}
							}
						}
						$pie->value->SetFont(FF_SIMSUN, FS_NORMAL);
						$graph->Add($pie);
						$pie->ShowBorder();
						$pie->setSliceColors(['#F7A35C', '#8085E9', '#90ED7D', '#7CB5EC', '#434348']);
						$pie->SetColor(array(255, 255, 255));
						foreach ($labels as &$rec) {
							$rec = iconv("UTF-8", "GB2312//IGNORE", $rec);
						}
						$pie->SetLabels($labels, 1);
					}
				} else if ($schema->type === 'multiple') {
					// Create the graph. These two calls are always required
					$graph = new \Graph(550, 200);
					$graph->SetScale("textint");
					// Add a drop shadow
					$graph->SetShadow();
					// Adjust the margin a bit to make more room for titles
					$graph->img->SetMargin(40, 30, 20, 40);
					// Create a bar pot
					$labels = [];
					for ($i = 0, $l = count($oSchemaStat->ops); $i < $l; $i++) {
						$op = $oSchemaStat->ops[$i];
						if ((int) $op->c !== 0) {
							if (isset($oPlConfig->label) && $oPlConfig->label === 'percentage') {
								$labels[] = '选项' . ($i + 1) . '：' . round($op->c / $oSchemaStat->sum * 100, 2) . '%';
							} else {
								$labels[] = '选项' . ($i + 1) . '：' . $op->c;
							}
						}
					}
					$bar = new \BarPlot($data);
					$graph->Add($bar);
					// Setup the titles
					$graph->xaxis->title->Set(iconv("UTF-8", "GB2312//IGNORE", "选项"));
					$graph->yaxis->title->Set(iconv("UTF-8", "GB2312//IGNORE", "数量"));
					foreach ($labels as &$rec) {
						$rec = iconv("UTF-8", "GB2312//IGNORE", $rec);
					}
					$graph->xaxis->SetTickLabels($labels);
					$graph->xaxis->SetFont(FF_SIMSUN, FS_NORMAL);

					$graph->yaxis->title->SetFont(FF_SIMSUN, FS_NORMAL);
					$graph->xaxis->title->SetFont(FF_SIMSUN, FS_NORMAL);
				}
				if ($oSchemaStat->sum) {
					//$graph->title->Set($oSchemaStat->title);
					//$graph->title->SetFont(FF_SIMSUN, FS_NORMAL);

					$graph->Stroke(_IMG_HANDLER);
					ob_start(); // start buffering
					$graph->img->Stream(); // print data to buffer
					$image_data = ob_get_contents(); // retrieve buffer contents
					ob_end_clean(); // stop buffer
					$imageBase64 = chunk_split(base64_encode($image_data));
					//
					$mappingOfImages[$oSchemaStat->id . '.base64'] = $imageBase64;
					//
					$html .= '<img src="' . $oSchemaStat->id . '.base64" />';
				}
				$html .= "<table><thead><tr><th>选项编号</th><th>选项内容</th>";
				if (isset($oPlConfig->number) && $oPlConfig->number === 'Y') {
					$html .= "<th>数量</th>";
				}
				if (isset($oPlConfig->percentage) && $oPlConfig->percentage === 'Y') {
					$html .= "<th>占比</th>";
				}
				$html .= "</tr></thead><tbody>";
				for ($i = 0, $l = count($oSchemaStat->ops); $i < $l; $i++) {
					$op = $oSchemaStat->ops[$i];
					$html .= "<tr><td>选项" . ($i + 1) . "</td>";
					$html .= "<td>{$op->l}</td>";
					if (isset($oPlConfig->number) && $oPlConfig->number === 'Y') {
						$html .= "<td>{$op->c}</td>";
					}
					if (isset($oPlConfig->percentage) && $oPlConfig->percentage === 'Y') {
						$html .= '<td>' . round($op->c / $oSchemaStat->sum * 100, 2) . '%</td>';
					}
					$html .= "</tr>";
				}
				$html .= "</tbody></table>";
			} else if ('score' === $schema->type) {
				//
				$oSchemaStat = $aStatResult[$schema->id];
				$labels = [];
				$data = [];
				$totalScore = 0;
				for ($i = 0, $l = count($oSchemaStat->ops); $i < $l; $i++) {
					$labels[] = '打分项' . ($i + 1);
					$op = &$oSchemaStat->ops[$i];
					$op['c'] = round((float) $op->c, 2);
					$data[] = $op->c;
					$totalScore += $op->c;
				}
				if (count($data) > 1) {
					// 如果只有1个点，jpgraph会报错，所以跳过绘图。
					// Setup the graph
					$graph = new \Graph(550, 200);
					$graph->SetScale("textlin");

					$theme_class = new \UniversalTheme;

					$graph->SetTheme($theme_class);
					$graph->img->SetAntiAliasing(false);
					$graph->title->Set($oSchemaStat->title);
					$graph->title->SetFont(FF_SIMSUN, FS_NORMAL);
					$graph->SetBox(false);

					$graph->img->SetAntiAliasing();

					$graph->yaxis->HideZeroLabel();
					$graph->yaxis->HideLine(false);
					$graph->yaxis->HideTicks(false, false);

					$graph->xgrid->Show();
					$graph->xgrid->SetLineStyle("solid");
					$graph->xaxis->SetTickLabels($labels);
					$graph->xgrid->SetColor('#E3E3E3');
					$graph->xaxis->SetFont(FF_SIMSUN, FS_NORMAL);

					$p1 = new \LinePlot($data);
					$graph->Add($p1);
					$p1->SetColor("#6495ED");

					// Output line
					$graph->Stroke(_IMG_HANDLER);
					ob_start(); // start buffering
					$graph->img->Stream(); // print data to buffer
					$image_data = ob_get_contents(); // retrieve buffer contents
					ob_end_clean(); // stop buffer
					$imageBase64 = chunk_split(base64_encode($image_data));
					//
					$mappingOfImages[$oSchemaStat->id . '.base64'] = $imageBase64;
					//
					$html .= '<img src="' . $oSchemaStat->id . '.base64" />';
				}
				// table
				$html .= "<table><thead><tr><th>打分项编号</th><th>打分项内容</th><th>平均分</th></tr></thead>";
				$html .= "<tbody>";
				for ($i = 0, $l = count($oSchemaStat->ops); $i < $l; $i++) {
					$op2 = $oSchemaStat->ops[$i];
					$html .= "<tr><td>打分项" . ($i + 1) . "</td><td>{$op2['l']}</td><td>{$op2['c']}</td></tr>";
				}
				$avgScore = round($totalScore / count($oSchemaStat->ops), 2);
				$html .= "<tr><td>本项平均分</td><td>{$avgScore}</td></tr>";
				$html .= "</tbody></table>";
				/*打分题汇总*/
				$scoreSummary[] = ['l' => $schema->title, 'c' => $avgScore];
				$totalScoreSummary += $avgScore;
			}
			$html .= "<div>&nbsp;</div>";
		}
		$avgScoreSummary = 0; //所有打分题的平均分
		if (count($scoreSummary)) {
			$avgScoreSummary = round($totalScoreSummary / count($scoreSummary), 2);
			$html .= "<h3><span>打分项汇总</span></h3>";
			$html .= "<table><thead><tr><th>打分项</th><th>平均分</th></tr></thead>";
			$html .= "<tbody>";
			foreach ($scoreSummary as $op) {
				$html .= "<tr><td>{$op['l']}</td><td>{$op['c']}</td></tr>";
			}
			$html .= "<tr><td>所有打分项总平均分</td><td>{$avgScoreSummary}</td></tr>";
			$html .= "<tr><td>所有打分项合计</td><td>{$totalScoreSummary}</td></tr>";
			$html .= "</tbody></table>";
		}

		$html .= '</body>';
		$html .= '</html>';
		$html = $this->_MhtMake($html, $mappingOfImages); //生成mht内容

		header('pragma:public');
		header('Content-Type: application/vnd.ms-word;charset=utf-8;name=' . $oApp->title . '.doc');
		header('Content-Disposition: attachment;filename=' . $oApp->title . '.doc');
		echo $html;

		die();
	}
}