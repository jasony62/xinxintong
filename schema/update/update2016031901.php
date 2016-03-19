<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_site_qy add public_id varchar(20) not null default '' after qrcode";
$sqls[] = "alter table xxt_site_qy add jsapi_ticket text after access_token_expire_at";
$sqls[] = "alter table xxt_site_qy add jsapi_ticket_expire_at int not null default 0 after jsapi_ticket";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;