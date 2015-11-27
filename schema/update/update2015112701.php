<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = 'alter table xxt_enroll add lottery_page_id int not null default 0 after remark_notice_page';

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;