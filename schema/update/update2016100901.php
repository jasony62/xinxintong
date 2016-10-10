<?php
require_once '../../db.php';

$sqls = array();
//
$sql = 'create table if not exists xxt_matter_tag(';
$sql .= 'id int not null auto_increment';
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ',title varchar(255) not null';
$sql .= ",matter_type varchar(20)"; //enroll,signin,group
$sql .= ",sub_type int not null default 0";
$sql .= ',primary key(id)';
$sqls[] = $sql;
//
$sqls[] = "alter table xxt_enroll add category_tags text after tags";
$sqls[] = "alter table xxt_signin add category_tags text after pic";
$sqls[] = "alter table xxt_group add category_tags text after pic";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;