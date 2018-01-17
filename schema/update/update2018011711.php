<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "update xxt_enroll_record_remark r set r.data_id = CASE WHEN ((select d.id from xxt_enroll_record_data d where d.enroll_key = r.enroll_key and d.schema_id = r.schema_id and d.aid = r.aid and d.rid = r.rid and d.multitext_seq = 0) is null) THEN 0 else (select d.id from xxt_enroll_record_data d where d.enroll_key = r.enroll_key and d.schema_id = r.schema_id and d.aid = r.aid and d.rid = r.rid and d.multitext_seq = 0) END";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;