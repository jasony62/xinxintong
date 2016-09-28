<?php
// include autoloader
require_once dirname(dirname(dirname(__FILE__))) . '/lib/dompdf/autoload.inc.php';

// reference the Dompdf namespace
use Dompdf\Dompdf;
use Dompdf\Options;

//$imgUrl = "/Users/yangyue/project/xinxintong/1/static/img/bg1.jpg";
$imgUrl = "http://" . $_SERVER['HTTP_HOST'] . "/static/img/bg1.jpg";

//$imgUrl = "http://" . $_SERVER['HTTP_HOST'] . "/kcfinder/upload/51855414326b5ff2b1b4a3b5d366393c/%E5%9B%BE%E7%89%87/%E7%81%AB%E7%8B%90%E6%88%AA%E5%9B%BE_2016-02-26T10-23-49.189Z.png";

$imageBase64 = chunk_split(base64_encode(file_get_contents($imgUrl)));
$mimeType = 'image/png';
$src = 'data:' . $mimeType . ';base64,' . $imageBase64;

// instantiate and use the dompdf class
$options = new Options();
$options->set('isRemoteEnabled', TRUE);
$options->set('defaultFont', 'FangSong_GB2312');
$options->set('isFontSubsettingEnabled', false);
$dompdf = new Dompdf($options);

$html = '<html>';
$html .= '<head>';
//$html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
//@see https://github.com/dompdf/dompdf/wiki/UnicodeHowTo
//$html .= "<style>@font-face{font-family:'SimHei';font-style:normal;font-weight:normal;src:url(http://localhost/static/fonts/SimHei.ttf) format('truetype')}</style>";
$html .= "<style>img{width:100%}table{width:100%;border-spacing:0;border-collapse:collapse;border:1px solid #ddd;}th,td{border:1px solid #ddd;}</style>";
$html .= '</head>';
$html .= '<body>';
$html .= "<div>你好abc</div><img style='width:100%;' src='{$src}'><div>abc测试</div>";
$html .= "<table><thead><tr><th>登记项</th><th>数值</th></tr></thead><tbody><tr><td>第1项</td><td>1</td></tr><tr><td>第2项</td><td>3</td></tr></tbody></table>";
$html .= '</body>';
$html .= '</html>';
$dompdf->loadHtml($html, 'UTF-8');

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream('hello');