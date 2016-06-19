<?php
require_once '../../db.php';
$sqls = array();
//
$sql = "create table if not exists xxt_site_notice(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",event_name varchar(255) not null"; // 事件名称
$sql .= ",tmplmsg_config_id int not null default 0"; // 对应的模版消息ID，引用xxt_tmplmsg_mapping
$sql .= ",active char(1) not null default 'N'"; //是否已激活
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sqls[] = "alter table xxt_tmplmsg_param add siteid varchar(32) not null";
$sqls[] = "alter table xxt_tmplmsg_mapping add siteid varchar(32) not null";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;