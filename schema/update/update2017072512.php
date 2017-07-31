<?php
require_once '../../db.php';

$sql1 = "select res_id from xxt_article_tag group by res_id";
$rst = $mysqli->query($sql1);
$articles = [];
while ( $obj = $rst->fetch_object()) {
	$articles[] = $obj;
}

foreach($articles as $article){
	$sql2 = "select tag_id,sub_type from xxt_article_tag where res_id = $article->res_id";
	$rst = $mysqli->query($sql2);
	$tagsC = [];
	$tagsM = [];
	while ( $obj = $rst->fetch_object()) {
		if($obj->sub_type == "0"){
			$tagsC[] = (string)$obj->tag_id;
		}
		if($obj->sub_type == "1"){
			$tagsM[] = (string)$obj->tag_id;
		}
	}

	!empty($tagsC) && $tagsC = json_encode($tagsC);
	!empty($tagsM) && $tagsM = json_encode($tagsM);
	if(!empty($tagsC)){
		$sql3 = "update xxt_article set matter_cont_tag = '$tagsC' where id = $article->res_id";
		if (!$mysqli->query($sql3)) {
			header('HTTP/1.0 500 Internal Server Error');
			echo 'database error: ' . $mysqli->error;
		}
	}
	if(!empty($tagsM)){
		$sql4 = "update xxt_article set matter_mg_tag = '$tagsM' where id = $article->res_id";
		if (!$mysqli->query($sql4)) {
			header('HTTP/1.0 500 Internal Server Error');
			echo 'database error: ' . $mysqli->error;
		}
	}
}

$sqlTag = "select tag_id,count(tag_id) sum from xxt_article_tag group by tag_id";
$rstTag = $mysqli->query($sqlTag);
$tagsSum = [];
while ( $obj = $rstTag->fetch_object()) {
	$tagsSum[] = $obj;
}

foreach ($tagsSum as $tag) {
	$sql5 = "update xxt_tag set sum = $tag->sum where id = $tag->tag_id";
	if (!$mysqli->query($sql5)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;