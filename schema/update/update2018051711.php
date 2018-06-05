<?php
require_once '../../db.php';
// 默认图片
$image = [
	'fangan' => '/kcfinder/upload/57dc4f6c25eca6c77cef54bc65c0d61b/%E5%9B%BE%E7%89%87/fangan01.jpg?_=1526525152721',//方案
	'gongju' => '/kcfinder/upload/57dc4f6c25eca6c77cef54bc65c0d61b/%E5%9B%BE%E7%89%87/gongju01.jpg?_=1526525320611', // 工具
	'jishu' => '/kcfinder/upload/57dc4f6c25eca6c77cef54bc65c0d61b/%E5%9B%BE%E7%89%87/jishu01.jpg?_=1526525997059', //技术
	'shichang' => '/kcfinder/upload/57dc4f6c25eca6c77cef54bc65c0d61b/%E5%9B%BE%E7%89%87/shichang01.jpg?_=1526526112427', // 市场
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
		$body = str_replace($img, '<img src="' . randPic($image) . '" />', $body);
	}

	return $body;
}
//
// $sql = "select id,pic,body,title from xxt_article where (pic <> '' or body like '%<img src=%')";
$sql = "select id,pic,body,title from xxt_article where pic <> ''";
$sql .= " and id in (4716,4574,4769,4767,4762,4759,4760,4758,4739,4755,4754,4751,4748,4746,4743,4742,4735,4736,4734,4732,4729,4728,4699,4723,4720,4719,4717,4714,4713,4707,4710,4708,4709,4706,4704,4703,4702,4688,4698,4690,4689,4683,4687,4686,4685,4675,4684,4682,4681,4672,4678,4677,4676,4668,4674,4673,4671,4661,4667,4666,4665,4573,4663,4660,4659,4655,4656,4650,4649,4644,4645,4640,4642,4641,4634,4600,4630,4629,4597,4623,4620,4610,4607,4601,4594,4590,4589,4588,4584,4571,4568,4553,4538,4540,4486,4481,4479,4474,4478,4477,4470,4467,4466,4460,4459,4458,4454,4456,4455,4451,4450,4448,4445,4442,4444,4443,4441,4424,4428,4427,4414,4415,4416,4411,4405,4412,4404,4402,4393,4392,4387,4386,4382,4364,4363,4357,4355,4326,4325,4318,4308,4292,4291,4271,4267,4135,4129,4202,4178,4653,4652,4651,4239,4449,4391,4381,4243,4244,4246,4248,4249,4250,4251,4252,4253,4254,4255,4256,4228,4199,4360,4518,4517,4516,4515,4514,4513,4512,4511,4510,4509,4508,4507,4506,4505,4519,4501,4499,4496,4495,4494,4492,4383,4265,4263,4261,4260,4259,4258,4226,4208,4206,4204,4201,4264,4775,4774,4773,4768)";
$db_result = $mysqli->query($sql);
$objects = array();
while ($obj = $db_result->fetch_object()) {
	$objects[] = $obj;
}
$sum = 0;
foreach ($objects as $object) {
	$update = [];
	// 替换头图
	if (!empty($object->pic)) {
		if (strpos($object->title, '【市场】') !== false) {
			$update[] = "pic = '" . $image['shichang'] . "'";
		} else if (strpos($object->title, '【工具】') !== false) {
			$update[] = "pic = '" . $image['gongju'] . "'";
		} else if (strpos($object->title, '【技术】') !== false) {
			$update[] = "pic = '" . $image['jishu'] . "'";
		} else if (strpos($object->title, '【方案】') !== false) {
			$update[] = "pic = '" . $image['fangan'] . "'";
		} else {
			// $update[] = "pic = '" . randPic($image) . "'";
		}
	}
	// 替换内容中的图片
	// if (!empty($object->body)) {
	// 	$body = replaceBodyPic($object->body, $image);
	// 	$update[] = "body = '" . $mysqli->real_escape_string($body) . "'";
	// }
	if (!empty($update)) {
		$sum++;
		$set = implode(', ', $update);
		$sql2 = "update xxt_article set {$set} where id = {$object->id}";
		if (!$mysqli->query($sql2)) {
			header('HTTP/1.0 500 Internal Server Error');
			echo 'database error: ' . $mysqli->error;
			echo "----------";
			echo $object->id;
		}
	}
}

echo ">>>>>>>>>>" . $sum . ">>>>>>>>>>";
echo "end update " . __FILE__ . PHP_EOL;
