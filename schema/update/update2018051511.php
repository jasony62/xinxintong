<?php
require_once '../../db.php';
//
$sql = "select * from xxt_enroll_record where data like '%\"member\":{%'";
$db_result = $mysqli->query($sql);
while ($obj = $db_result->fetch_object()) {
	$sql2 = "select count(id) sum from xxt_enroll_record_data where enroll_key = '{$obj->enroll_key}' and schema_id = 'member'";
	$db_result2 = $mysqli->query($sql2);
	$obj2 = $db_result2->fetch_object();
	if ($obj2->sum == 0) {
		$data = json_decode($obj->data);
		$newData = json_encode($data->member);
		$sql3 = "insert into xxt_enroll_record_data (aid,rid,group_id,enroll_key,submit_at,userid,nickname,schema_id,value) values ('{$obj->aid}','{$obj->rid}','{$obj->group_id}','{$obj->enroll_key}',{$obj->enroll_at},'{$obj->userid}','{$obj->nickname}','member','{$newData}')";
		if (!$mysqli->query($sql3)) {
			header('HTTP/1.0 500 Internal Server Error');
			echo 'database error: ' . $mysqli->error . ';;';
		}
	}
}
echo "end update " . __FILE__ . PHP_EOL;
