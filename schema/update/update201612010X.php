<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table xxt_wall_enroll add ufrom char(5) not null default '' after openid";
$sqls[] = "alter table xxt_wall_enroll add userid varchar(40) not null default '' after ufrom";
$sqls[] = "alter table xxt_wall_enroll add nickname varchar(255) not null default '' after userid";
$sqls[] = "alter table xxt_wall_enroll drop primary key";
$sqls[] = "ALTER TABLE `xxt_wall_enroll` ADD `id` INT NOT NULL AUTO_INCREMENT FIRST,ADD PRIMARY KEY ( `id` ) ";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;