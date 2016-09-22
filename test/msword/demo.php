<?php
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
function MhtMake($content, $absolutePath = "", $isEraseLink = true) {
	$mht = new Mhtmaker();
	if ($isEraseLink) {
		$content = preg_replace('/<a\s*.*?\s*>(\s*.*?\s*)<\/a>/i', '$1', $content); //去掉链接
	}
	$images = array();
	$files = array();
	$matches = array();

	//这个算法要求src后的属性值必须使用引号括起来
	if (preg_match_all('/<img.*?src\s*?=\s*?[\"\'](.*?)[\"\'].*?\/>/i', $content, $matches)) {
		//die('zzzz:' . json_encode($matches));
		$arrPath = $matches[1];
		for ($i = 0; $i < count($arrPath); $i++) {
			$path = $arrPath[$i];
			$imgPath = trim($path);
			if ($imgPath != "") {
				$files[] = $imgPath;
				if (substr($imgPath, 0, 7) == 'http://') {
					//绝对链接，不加前缀
				} else {
					$imgPath = $absolutePath . $imgPath;
				}
				$images[] = $imgPath;
			}
		}
	}
	$mht->AddContents("tmp.html", $mht->GetMimeType("tmp.html"), $content);
	for ($i = 0; $i < count($images); $i++) {
		$image = $images[$i];
		if (@fopen($image, 'r')) {
			$imgcontent = @file_get_contents($image);
			if ($content) {
				$mht->AddContents($files[$i], $mht->GetMimeType($image), $imgcontent);
			}

		} else {
			echo "file:" . $image . " not exist!<br />";
		}
	}
	return $mht->GetFile();
}

$imgUrl = "http://" . $_SERVER['HTTP_HOST'] . "/static/img/bg1.jpg";

$html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
$html .= '<head>';
$html .= '<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>';
$html .= "<style>table{width:100%;border-spacing:0;border-collapse:collapse;border:1px solid #ddd;}th,td{border:1px solid #ddd;}</style>";
$html .= '</head>';
$html .= '<body>';
$html .= "<div>你好abc</div><img src='{$imgUrl}' /><div>abc测试</div>";
$html .= "<table><thead><tr><th>登记项</th><th>数值</th></tr></thead><tbody><tr><td>第1项</td><td>1</td></tr><tr><td>第2项</td><td>3</td></tr></tbody></table>";
$html .= '</body>';
$html .= '</html>';

$html = MhtMake($html); //生成word内容

header('pragma:public');
header("Content-Type: application/vnd.ms-word;charset=utf-8;name=hello.doc");
header("Content-Disposition: attachment;filename=hello.doc");
echo $html;