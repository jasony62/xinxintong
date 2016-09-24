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
	private function _getResult($site, $appId, $renewCache = 'Y') {
		$current = time();
		$model = $this->model();
		if ($renewCache === 'Y') {
			/* 上一次保留统计结果的时间 */
			$q = [
				'create_at',
				'xxt_enroll_record_stat',
				["aid" => $appId],
			];
			$q2 = ['r' => ['o' => 0, 'l' => 1]];
			$last = $model->query_objs_ss($q, $q2);
			/* 上次统计后的新登记记录数 */
			if (count($last) === 1) {
				$last = $last[0];
				$q = [
					'count(*)',
					'xxt_enroll_record',
					"aid='$appId' and enroll_at>={$last->create_at}",
				];
				$newCnt = (int) $model->query_val_ss($q);
			} else {
				$newCnt = 999;
			}
			// 如果更新的登记数据，重新计算统计结果
			if ($newCnt > 0) {
				$result = $this->model('matter\enroll\record')->getStat($appId);
				// 保存统计结果
				$model->delete(
					'xxt_enroll_record_stat',
					"aid='$appId'"
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
						];
						$model->insert('xxt_enroll_record_stat', $r);
					}
				}
			} else {
				/* 从缓存中获取统计数据 */
				$result = [];
				$q = [
					'id,title,v,l,c',
					'xxt_enroll_record_stat',
					"aid='$appId'",
				];
				$cached = $model->query_objs_ss($q);
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
			$result = $this->model('matter\enroll\record')->getStat($appId);
		}

		return $result;
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
	public function get_action($site, $app, $renewCache = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$result = $this->_getResult($site, $app, $renewCache);

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
		require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/Mhtmaker.class.php';

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
	public function export_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/jpgraph/jpgraph.php';
		require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/jpgraph/jpgraph_bar.php';
		require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/jpgraph/jpgraph_pie.php';

		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$schemas = json_decode($app->data_schemas);

		$statResult = $this->_getResult($site, $app->id);

		$html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
		$html .= '<head>';
		$html .= '<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>';
		$html .= "<style>table{width:100%;border-spacing:0;border-collapse:collapse;border:1px solid #ddd;}th,td{border:1px solid #ddd;}</style>";
		$html .= '</head>';
		$html .= '<body>';

		$mappingOfImages = [];
		$modelRec = $this->model('matter\enroll\record');

		foreach ($schemas as $index => $schema) {
			$html .= "<h3><span>第" . ($index + 1) . "项：</span><span>{$schema->title}</span></h3>";
			if (in_array($schema->type, ['name', 'email', 'mobile', 'date', 'location', 'shorttext', 'longtext'])) {
				$textResult = $modelRec->list4Schema($site, $app, $schema->id);
				$records = $textResult->records;
				$html .= "<table><thead><tr><th>登记内容</th></tr></thead>";
				$html .= "<tbody>";
				foreach ($records as $record) {
					$html .= "<tr><td>{$record->value}</td></tr>";
				}
				$html .= "</tbody></table>";
			} else if (in_array($schema->type, ['single', 'phase', 'multiple'])) {
				$item = $statResult[$schema->id];
				$data = [];
				$sum = 0;
				foreach ($item['ops'] as $op) {
					$data[] = (int) $op['c'];
					$sum += (int) $op['c'];
				}

				if (in_array($schema->type, ['single', 'phase'])) {
					// Create a pie pot
					if ($sum) {
						$graph = new \PieGraph(400, 200);
						$graph->SetShadow();
						$pie = new \PiePlot($data);
						$labels = [];
						foreach ($item['ops'] as $op) {
							$labels[] = str_replace('%', '%%', $op['l']) . '：%.1f%%';
						}
						$pie->SetLabels($labels, 0.8);
						$pie->value->SetFont(FF_CHINESE, FS_NORMAL);
						$pie->SetGuideLines(true);
						$graph->Add($pie);
					}
				} else if ($schema->type === 'multiple') {
					// Create the graph. These two calls are always required
					$graph = new \Graph(400, 200);
					$graph->SetScale("textint");
					// Add a drop shadow
					$graph->SetShadow();
					// Adjust the margin a bit to make more room for titles
					$graph->img->SetMargin(40, 30, 20, 40);
					// Create a bar pot
					$labels = [];
					foreach ($item['ops'] as $op) {
						$labels[] = $op['l'];
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
				$html .= "<table><thead><tr><th>选项</th><th>数量</th></tr></thead>";
				$html .= "<tbody>";
				foreach ($item['ops'] as $op) {
					$html .= "<tr><td>{$op['l']}</td><td>{$op['c']}</td></tr>";
				}
				$html .= "</tbody></table>";
			}
			$html .= "<div>&nbsp;</div>";
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