<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/jpgraph/jpgraph.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/jpgraph/jpgraph_bar.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/Mhtmaker.class.php';

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
function MhtMake($content, &$images, $mimeType = 'image/png') {
	$mht = new Mhtmaker();

	$mht->AddContents("page.html", $mht->GetMimeType("page.html"), $content);

	foreach ($images as $filepath => $data) {
		$mht->AddContents($filepath, $mimeType, $data, 'base64');
	}

	return $mht->GetFile();
}

// reference the Dompdf namespace

$datay = [12, 8, 19, 3, 10, 5];

// Create the graph. These two calls are always required
$graph = new Graph(300, 200);
$graph->SetScale("textlin");

// Add a drop shadow
$graph->SetShadow();

// Adjust the margin a bit to make more room for titles
$graph->img->SetMargin(40, 30, 20, 40);

// Create a bar pot
$bplot = new BarPlot($datay);
$graph->Add($bplot);

// Setup the titles
$graph->title->Set("简单的条形图");
$graph->xaxis->title->Set("X轴");
$graph->yaxis->title->Set("Y轴");

$graph->title->SetFont(FF_CHINESE, FS_NORMAL);
$graph->yaxis->title->SetFont(FF_CHINESE, FS_NORMAL);
$graph->xaxis->title->SetFont(FF_CHINESE, FS_NORMAL);

$graph->Stroke(_IMG_HANDLER);
ob_start(); // start buffering
$graph->img->Stream(); // print data to buffer
$image_data = ob_get_contents(); // retrieve buffer contents
ob_end_clean(); // stop buffer

$imageBase64 = chunk_split(base64_encode($image_data));
//
$mappingOfImages = [];
$mappingOfImages['image1.base64'] = $imageBase64;
//
$html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
$html .= '<head>';
$html .= '<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>';
$html .= "<style>table{width:100%;border-spacing:0;border-collapse:collapse;border:1px solid #ddd;}th,td{border:1px solid #ddd;}</style>";
$html .= '</head>';
$html .= '<body>';
$html .= "<div>你好abc</div><img src='image1.base64' /><div>abc测试</div>";
$html .= "<table><thead><tr><th>登记项</th><th>数值</th></tr></thead><tbody><tr><td>第1项</td><td>1</td></tr><tr><td>第2项</td><td>3</td></tr></tbody></table>";
$html .= '</body>';
$html .= '</html>';

$html = MhtMake($html, $mappingOfImages); //生成word内容

header('pragma:public');
header("Content-Type: application/vnd.ms-word;charset=utf-8;name=welcome.doc");
header("Content-Disposition: attachment;filename=welcome.doc");
echo $html;