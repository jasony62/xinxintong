<?php
require_once '../../db.php';

$sqls = array();
$sql = 'ALTER TABLE `xxt_site_qy` ADD `can_menu` VARCHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N' COMMENT '自定义菜单' AFTER `follow_page_name`;';
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;