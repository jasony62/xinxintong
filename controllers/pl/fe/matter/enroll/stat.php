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
	 * return
	 * name => array(l=>label,c=>count)
	 *
	 */
	private function _getResult($site, $appId, $rid = '', $renewCache = 'Y') {
		if (empty($rid)) {
			$app = $this->model('matter\enroll')->byId($appId, ['cascaded' => 'N']);
			if ($activeRound = $this->model('matter\enroll\round')->getActive($app)) {
				$rid = $activeRound->rid;
			}
		}

		$current = time();
		$modelRec = $this->model('matter\enroll\record');
		$rid = $modelRec->escape($rid);
		if ($renewCache === 'Y') {
			/* 上一次保留统计结果的时间 */
			$q = [
				'create_at',
				'xxt_enroll_record_stat',
				['aid' => $appId, 'rid' => $rid],
			];

			$q2 = ['r' => ['o' => 0, 'l' => 1]];
			$last = $modelRec->query_objs_ss($q, $q2);
			/* 上次统计后的新登记记录数 */
			if (count($last) === 1) {
				$last = $last[0];
				$q = [
					'count(*)',
					'xxt_enroll_record',
					"aid='$appId' and enroll_at>={$last->create_at}",
				];
				if ($rid !== 'ALL' && !empty($rid)) {
					$q[2] .= " and rid = '$rid'";
				}

				$newCnt = (int) $modelRec->query_val_ss($q);
			} else {
				$newCnt = 999;
			}
			// 如果更新的登记数据，重新计算统计结果
			if ($newCnt > 0) {
				$result = $modelRec->getStat($appId, $rid);
				// 保存统计结果
				$modelRec->delete(
					'xxt_enroll_record_stat',
					['aid' => $appId, 'rid' => $rid]
				);
				foreach ($result as $id => $stat) {
					foreach ($stat['ops'] as $op) {
						$r = [
							'siteid' => $site,
							'aid' => $appId,
							'create_at' => $current,
							'id' => $id,
							'title' => $stat['title'],
							'v' => $op->v,
							'l' => $op->l,
							'c' => $op->c,
							'rid' => $rid,
						];
						$modelRec->insert('xxt_enroll_record_stat', $r);
					}
				}
			} else {
				/* 从缓存中获取统计数据 */
				$result = [];
				$q = [
					'id,title,v,l,c',
					'xxt_enroll_record_stat',
					['aid' => $appId, 'rid' => $rid],
				];

				$cached = $modelRec->query_objs_ss($q);
				foreach ($cached as $data) {
					if (empty($result[$data->id])) {
						$item = [
							'id' => $data->id,
							'title' => $data->title,
							'ops' => [],
						];
						$result[$data->id] = $item;
					}
					$op = [
						'v' => $data->v,
						'l' => $data->l,
						'c' => $data->c,
					];
					$result[$data->id]['ops'][] = $op;
				}
			}
		} else {
			$result = $modelRec->getStat($appId, $rid);
		}

		return $result;
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
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$result = $this->_getResult($site, $app, $rid, $renewCache);

		return new \ResponseData($result);
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
	public function export_action($site, $app, $rid = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		require_once TMS_APP_DIR . '/lib/jpgraph/jpgraph.php';
		require_once TMS_APP_DIR . '/lib/jpgraph/jpgraph_bar.php';
		require_once TMS_APP_DIR . '/lib/jpgraph/jpgraph_pie.php';
		require_once TMS_APP_DIR . '/lib/jpgraph/jpgraph_line.php';

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);

		$schemas = json_decode($oApp->data_schemas);
		$schemasById = [];
		foreach ($schemas as $schema) {
			$schemasById[$schema->id] = $schema;
		}

		$statResult = $this->_getResult($site, $oApp->id, $rid);

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
			$html .= "<h3><span>第" . ($index + 1) . "项：</span><span>{$schema->title}</span></h3>";
			if (in_array($schema->type, ['name', 'email', 'mobile', 'date', 'location', 'shorttext', 'longtext'])) {
				$textResult = $modelRec->list4Schema($oApp, $schema->id, ['rid' => $rid]);
				if (!empty($textResult->records)) {
					//数值型的饼图
					if (isset($schema->number) && $schema->number === 'Y') {
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
						$pie->value->SetFont(FF_CHINESE, FS_NORMAL);
						$graph->Add($pie);
						$pie->ShowBorder();
						$pie->setSliceColors(['#F7A35C', '#8085E9', '#90ED7D', '#7CB5EC', '#434348']);
						$pie->SetColor(array(255, 255, 255));
						$pie->SetLabels($labels, 1);

						$graph->title->Set($schema->title);
						$graph->title->SetFont(FF_CHINESE, FS_NORMAL);

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
						// if ($ridName = $this->model('matter\enroll\round')->byId($record->rid, ['fields' => 'title'])) {
						// 	$html .= "<td>" . $ridName->title . "</td>";
						// } else {
						// 	$html .= "<td>无</td>";
						// }
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
				$item = $statResult[$schema->id];
				$data = [];
				$sum = 0;
				foreach ($item['ops'] as $op) {
					if ((int) $op['c'] !== 0) {
						$data[] = (int) $op['c'];
						$sum += (int) $op['c'];
					}
				}
				if (empty($data)) {
					continue;
				}
				if (in_array($schema->type, ['single', 'phase'])) {
					// Create a pie pot
					if ($sum) {
						$graph = new \PieGraph(550, 300);
						$graph->SetShadow();
						$pie = new \PiePlot($data);
						$labels = [];
						for ($i = 0, $l = count($item['ops']); $i < $l; $i++) {
							$op = $item['ops'][$i];
							if ((int) $op['c'] !== 0) {
								$labels[] = '选项' . ($i + 1) . '：%.1f%%';
							}
						}
						$pie->value->SetFont(FF_CHINESE, FS_NORMAL);
						$graph->Add($pie);
						$pie->ShowBorder();
						$pie->setSliceColors(['#F7A35C', '#8085E9', '#90ED7D', '#7CB5EC', '#434348']);
						$pie->SetColor(array(255, 255, 255));
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
					for ($i = 0, $l = count($item['ops']); $i < $l; $i++) {
						$op = $item['ops'][$i];
						if ((int) $op['c'] !== 0) {
							$labels[] = '选项' . ($i + 1);
						}
					}
					$bar = new \BarPlot($data);
					$graph->Add($bar);
					// Setup the titles
					$graph->xaxis->title->Set("选项");
					$graph->yaxis->title->Set("数量");
					$graph->xaxis->SetTickLabels($labels);
					$graph->xaxis->SetFont(FF_CHINESE, FS_NORMAL);

					$graph->yaxis->title->SetFont(FF_CHINESE, FS_NORMAL);
					$graph->xaxis->title->SetFont(FF_CHINESE, FS_NORMAL);
				}
				if ($sum) {
					$graph->title->Set($item['title']);
					$graph->title->SetFont(FF_CHINESE, FS_NORMAL);

					$graph->Stroke(_IMG_HANDLER);
					ob_start(); // start buffering
					$graph->img->Stream(); // print data to buffer
					$image_data = ob_get_contents(); // retrieve buffer contents
					ob_end_clean(); // stop buffer
					$imageBase64 = chunk_split(base64_encode($image_data));
					//
					$mappingOfImages[$item['id'] . '.base64'] = $imageBase64;
					//
					$html .= '<img src="' . $item['id'] . '.base64" />';
				}
				$html .= "<table><thead><tr><th>选项编号</th><th>选项内容</th><th>数量</th></tr></thead>";
				$html .= "<tbody>";
				for ($i = 0, $l = count($item['ops']); $i < $l; $i++) {
					$op = $item['ops'][$i];
					$html .= "<tr><td>选项" . ($i + 1) . "</td><td>{$op['l']}</td><td>{$op['c']}</td></tr>";
				}
				$html .= "</tbody></table>";
			} else if ('score' === $schema->type) {
				//
				$item = $statResult[$schema->id];
				$labels = [];
				$data = [];
				$totalScore = 0;
				for ($i = 0, $l = count($item['ops']); $i < $l; $i++) {
					$labels[] = '打分项' . ($i + 1);
					$op = &$item['ops'][$i];
					$op['c'] = round((float) $op['c'], 2);
					$data[] = $op['c'];
					$totalScore += $op['c'];
				}
				if (count($data) > 1) {
					// 如果只有1个点，jpgraph会报错，所以跳过绘图。
					// Setup the graph
					$graph = new \Graph(550, 200);
					$graph->SetScale("textlin");

					$theme_class = new \UniversalTheme;

					$graph->SetTheme($theme_class);
					$graph->img->SetAntiAliasing(false);
					$graph->title->Set($item['title']);
					$graph->title->SetFont(FF_CHINESE, FS_NORMAL);
					$graph->SetBox(false);

					$graph->img->SetAntiAliasing();

					$graph->yaxis->HideZeroLabel();
					$graph->yaxis->HideLine(false);
					$graph->yaxis->HideTicks(false, false);

					$graph->xgrid->Show();
					$graph->xgrid->SetLineStyle("solid");
					$graph->xaxis->SetTickLabels($labels);
					$graph->xgrid->SetColor('#E3E3E3');
					$graph->xaxis->SetFont(FF_CHINESE, FS_NORMAL);

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
					$mappingOfImages[$item['id'] . '.base64'] = $imageBase64;
					//
					$html .= '<img src="' . $item['id'] . '.base64" />';
				}
				// table
				$html .= "<table><thead><tr><th>打分项编号</th><th>打分项内容</th><th>平均分</th></tr></thead>";
				$html .= "<tbody>";
				for ($i = 0, $l = count($item['ops']); $i < $l; $i++) {
					$op2 = $item['ops'][$i];
					$html .= "<tr><td>打分项" . ($i + 1) . "</td><td>{$op2['l']}</td><td>{$op2['c']}</td></tr>";
				}
				$avgScore = round($totalScore / count($item['ops']), 2);
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

		//header('pragma:public');
		//header("Content-Type: application/vnd.ms-word;charset=utf-8;name=welcome.doc");
		//header("Content-Disposition: attachment;filename=welcome.doc");
		//echo $html;

		return new \ResponseData($html);
	}
}