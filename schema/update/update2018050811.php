<?php
require_once '../../db.php';
// 默认图片
$image = [
	'/kcfinder/upload/7cf590b4b88039da85d998f885b2f3a7/pic/333.gif?_=1525760434734',
	'/kcfinder/upload/7cf590b4b88039da85d998f885b2f3a7/pic/111.jpg?_=1525760642079',
	'/kcfinder/upload/7cf590b4b88039da85d998f885b2f3a7/pic/222.jpg?_=1525760644446',
	'/kcfinder/upload/7cf590b4b88039da85d998f885b2f3a7/pic/444.jpg?_=1525760646599',
];
//随机图片
function randPic($image) {
	$key = array_rand($image);
	$val = $image[$key];

	return $val;
}
// 替换正文中的图片
function replaceBodyPic($body, $image) {
	$pregRule = "/<img[^>]*\/>/";
	// 获取body中所有的图片地址
	preg_match_all($pregRule, $body, $imgs);
	$imgs = $imgs[0];
	// 替换body中的图片，如果有多个图片随机获取新图片
	foreach ($imgs as $img) {
		$body = str_replace($img, "<img src=" . randPic($image) . " />", $body);
	}

	return $body;
}
//
$sql = "select id,pic,body from xxt_article where (pic <> '' or body <> '')";
$db_result = $mysqli->query($sql);
$objects = array();
while ($obj = $db_result->fetch_object()) {
	$objects[] = $obj;
}
foreach ($objects as $object) {
	$update = [];
	if (!empty($object->pic)) {
		$update[] = "pic = '" . randPic($image) . "'";
	}
	if (!empty($object->body)) {
		$body = replaceBodyPic($object->body, $image);
		$update[] = "body = '" . $mysqli->real_escape_string($body) . "'";
	}
	if (!empty($update)) {
		$set = implode(', ', $update);
		$sql2 = "update xxt_article set {$set} where id = {$object->id}";
		if (!$mysqli->query($sql2)) {
			header('HTTP/1.0 500 Internal Server Error');
			echo 'database error: ' . $mysqli->error;
			echo "----------";
			echo $object->id;
			echo "<br/>";
		}
	}
}

echo "<br/>";
echo "end update " . __FILE__ . PHP_EOL;
