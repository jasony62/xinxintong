<?php
require_once '../db.php';
// 评论
$sqls = [];
/*
 * 评论主题
 */
$sql = "create table if not exists xxt_discuss_thread(";
$sql .= "id int not null auto_increment";
$sql .= ",domain varchar(255) not null";
$sql .= ",thread_key varchar(255) not null";
$sql .= ",title text"; // 标题
$sql .= ",excerpt text"; // 摘要
$sql .= ",create_at int not null";
$sql .= ",comments int not null default 0";
$sql .= ",likes int not null default 0";
$sql .= ",dislikes int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/*
 * 评论主题的回复
 */
$sql = "create table if not exists xxt_discuss_post(";
$sql .= "id bigint not null auto_increment";
$sql .= ",post_key varchar(255) not null default ''";
$sql .= ",thread_id int not null";
$sql .= ",parent_id int not null default 0";
$sql .= ",root_id int not null default 0";
$sql .= ",create_at int not null";
$sql .= ",is_anonymous char(1) not null default 'N'";
$sql .= ",author_key varchar(255) not null";
$sql .= ",author_name varchar(255) not null";
$sql .= ",status tinyint not null default 0"; //0:pending;1:approved;2:spam
$sql .= ",message text"; //回复的内容
$sql .= ",comments int not null default 0";
$sql .= ",likes int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/*
 * 参与主题的用户
 */
$sql = "create table if not exists xxt_discuss_thread_user(";
$sql .= "id bigint not null auto_increment";
$sql .= ",thread_id int not null";
$sql .= ",user_key varchar(255) not null";
$sql .= ",user_name varchar(255) not null";
$sql .= ",vote char(1) not null default ''";
$sql .= ",posts text"; // 用户发表的评论
$sql .= ",like_posts text"; // 用户喜欢的评论
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/*
 * 评论操作的日志
 */
$sql = "create table if not exists xxt_discuss_log(";
$sql .= "id bigint not null auto_increment";
$sql .= ",thread_id int not null";
$sql .= ",thread_key varchar(255) not null";
$sql .= ",post_id int not null default 0";
$sql .= ",action int not null default 0"; //0:create;approve:1;2:spam;3:delete;4:delete-forever
$sql .= ",action_at int not null";
$sql .= ",user_id varchar(255) not null";
$sql .= ",user_agent text";
$sql .= ",user_ip varchar(128) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo 'finish discuss.' . PHP_EOL;