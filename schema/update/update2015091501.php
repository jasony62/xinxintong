<?php
require_once '../../db.php';

$sqls = array();
$sql = "create table if not exists xxt_log_user_matter(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',openid varchar(255) not null';
$sql .= ',nickname varchar(255) not null';
$sql .= ',matter_id varchar(40) not null';
$sql .= ',matter_type varchar(20) not null';
$sql .= ',matter_title varchar(70) not null';
$sql .= ',last_action_at int not null';
$sql .= ',read_num int not null default 0';
$sql .= ',share_friend_num int not null default 0';
$sql .= ',share_timeline_num int not null default 0';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sqls[] = "ALTER TABLE xxt_article_score ADD mpid varchar(32) not null default '' after id";
$sqls[] = "ALTER TABLE xxt_article_score ADD openid varchar(255) not null default '' after vid";
$sqls[] = "ALTER TABLE xxt_article_score ADD nickname varchar(255) not null default '' after openid";
$sqls[] = "ALTER TABLE xxt_article_score ADD article_title varchar(70) not null after article_id";
$sqls[] = "update xxt_article_score s,xxt_visitor v set s.mpid=v.mpid where s.vid=v.vid";
$sqls[] = "update xxt_article_score s,xxt_article a set s.article_title=a.title where s.article_id=a.id";
$sqls[] = "update xxt_article_score s,xxt_visitor v,xxt_fans f set s.openid=f.openid,s.nickname=f.nickname where s.vid=v.vid and v.fid=f.fid";
//
$sqls[] = "ALTER TABLE xxt_article_remark ADD mpid varchar(32) not null default '' after id";
$sqls[] = "ALTER TABLE xxt_article_remark ADD article_title varchar(70) not null after article_id";
$sqls[] = "update xxt_article_remark r,xxt_fans f set r.mpid=f.mpid where r.fid=f.fid";
$sqls[] = "update xxt_article_remark r,xxt_article a set r.article_title=a.title where r.article_id=a.id";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;