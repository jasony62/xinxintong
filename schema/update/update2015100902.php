<?php
require_once '../../db.php';

$sqls = array();
$sql = "create table if not exists xxt_tmplmsg_mapping(";
$sql .= 'id int not null auto_increment';
$sql .= ',msgid int not null';
$sql .= ',mapping text';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;