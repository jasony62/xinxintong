<?php
require_once '../../db.php';
// 获得正文中的图片
function getBodyPic($body) {
	$pregRule = "/<img[^>]*\/>/";
	// 获取body中所有的图片地址
	preg_match_all($pregRule, $body, $imgs);
	$imgs = $imgs[0];
	foreach ($imgs as &$img) {// $img = string(88) "<img src=/kcfinder/upload/57dc4f6c25eca6c77cef54bc65c0d61b/%E5%9B%BE%E7%89%87/003.jpg />"
		if (strpos($img, 'http') === false) {
			$img = str_replace('src="', 'src="http://developer.189.cn', $img);
		}
	}

	return $imgs;
}
//
$sql = "select id,title,body from xxt_article where body like '%<img src=%'";
$db_result = $mysqli->query($sql);
$objects = array();
while ($obj = $db_result->fetch_object()) {
	$objects[] = $obj;
}
$sum = 0;
$imgs = [];
foreach ($objects as $object) {
	$imgs[$object->id] = getBodyPic($object->body);
	$sum++;
}

foreach ($imgs as $key => $vals) {
	echo "<<<<<<<<<<<<<<<-ID-" . $key . "->>>>>>>>>>>>>>>";
	echo "<br/>";
	foreach ($vals as $img) {
		echo $img;
	}
	echo "<br/>";
}
echo "<br/>";
echo ">>>>>>>>>>" . $sum . ">>>>>>>>>>";
echo "end update " . __FILE__ . PHP_EOL;
