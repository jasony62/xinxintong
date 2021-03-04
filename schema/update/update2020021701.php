<?php
require_once '../../db.php';

$sqls = [];
/**
 * 后台进程执行任务
 */
$sql = "create table if not exists xxt_enroll_daemon_submit_record(";
$sql .= "id bigint not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null default ''";
$sql .= ",record_id int not null"; // 记录的id
$sql .= ",params text null"; // 参数
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",create_at int not null default 0"; // 创建事件
$sql .= ",summary_rec_at int not null default 0"; // 更新汇总轮次数据
$sql .= ",schema_score_rank_at int not null default 0"; // 题目数据分排行汇总
$sql .= ",summary_behavior_at int not null default 0"; // 行为数据汇总
$sql .= ",user_score_rank_at int not null default 0"; // 用户数据分排行
$sql .= ",notice_at int not null default 0"; // 发送通知（内部，微信）
$sql .= ",state tinyint not null default 1"; //1:waiting,0:finished
$sql .= ",reason varchar(255) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: ' . $mysqli->error;
    }
}

echo "end update " . __FILE__ . PHP_EOL;