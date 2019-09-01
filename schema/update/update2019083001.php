<?php
require_once '../../db.php';

$sqls = [];
/**
 * 请假
 */
$sql = "create table if not exists xxt_group_leave(";
$sql .= "id int not null auto_increment";
$sql .= ",g_transid bigint not null default 0"; // 事物id
$sql .= ",aid varchar(40) not null";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",begin_at int not null default 0"; // 开始时间
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",apply_at int not null default 0"; // 申请时间
$sql .= ",approve_at int not null default 0"; // 批准时间
$sql .= ",cancel_at int not null default 0"; // 撤销时间
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: ' . $mysqli->error;
    }
}

echo "end update " . __FILE__ . PHP_EOL;