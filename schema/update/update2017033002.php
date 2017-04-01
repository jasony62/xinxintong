<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "CREATE TABLE `xxt_account_topmatter` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `siteid` varchar(32) NOT NULL,
  `userid` varchar(32) NOT NULL COMMENT '置顶操作的用户',
  `top` enum('0','1') NOT NULL DEFAULT '0' COMMENT '置顶',
  `top_at` int(11) NOT NULL COMMENT '置顶时间',
  `matter_id` varchar(40) NOT NULL,
  `matter_type` varchar(20) NOT NULL,
  `matter_title` varchar(70) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='素材置顶表';
";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;