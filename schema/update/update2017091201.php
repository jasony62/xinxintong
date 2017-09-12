<?php
require_once '../../db.php';

$sqls = array();
//
$sql = "create table if not exists xxt_mission_user(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",group_id varchar(32) not null default ''"; // 用户分组id
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",last_enroll_at int not null default 0"; // 最后一次登记时间
$sql .= ",enroll_num int not null default 0"; // 登记记录的条数
$sql .= ",last_remark_at int not null default 0"; // 最后一次获得评价的时间
$sql .= ",remark_num int not null default 0"; // 获得的评价条数
$sql .= ",last_like_at int not null default 0"; // 登记内容最后一次获得点赞的时间
$sql .= ",like_num int not null default 0"; // 登记内容获得点赞的次数
$sql .= ",last_like_remark_at int not null default 0"; // 评论最后一次获得点赞的时间
$sql .= ",like_remark_num int not null default 0"; // 评论获得点赞的次数
$sql .= ",last_remark_other_at int not null default 0"; // 最后一次发表评价的时间
$sql .= ",remark_other_num int not null default 0"; // 发表的评价条数
$sql .= ",last_like_other_at int not null default 0"; // 最后一次对登记内容进行点赞的时间
$sql .= ",like_other_num int not null default 0"; // 对登记内容进行点赞的次数
$sql .= ",last_like_other_remark_at int not null default 0"; // 最后一次对评论进行点赞的时间
$sql .= ",like_other_remark_num int not null default 0"; // 对评论进行点赞的次数
$sql .= ",last_signin_at int not null default 0"; // 最后一次签到时间
$sql .= ",signin_num int not null default 0"; // 签到的次数
$sql .= ",user_total_coin int not null default 0"; // 用户在某个活动中的总分数
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
//
$sqls[] = $sql;
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;