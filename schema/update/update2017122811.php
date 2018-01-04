<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_enroll_record_data add multitext_seq int not null default 0 after schema_id";
$sqls[] = "ALTER TABLE xxt_enroll_record_remark add data_id int not null default 0 after schema_id";
//
$sqls[] = "update xxt_enroll_record_remark r set r.data_id = (select d.id from xxt_enroll_record_data d where d.enroll_key = r.enroll_key and d.schema_id = r.schema_id and d.aid = r.aid and d.rid = r.rid)";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;