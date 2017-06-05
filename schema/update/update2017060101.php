<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_enroll_record_remark add siteid varchar(32) not null after id";
$sqls[] = "alter table xxt_enroll_record_remark add aid varchar(40) not null after siteid";
$sqls[] = "alter table xxt_enroll_record_remark add enroll_userid varchar(40) not null default '' after enroll_key";
$sqls[] = "update xxt_enroll_record_remark rr,xxt_enroll_record r set rr.siteid=r.siteid,rr.aid=r.aid,rr.enroll_userid=r.userid where rr.enroll_key=r.enroll_key";
//
$sql = "create table if not exists xxt_enroll_user(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
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
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
$sqls[] = "insert into xxt_enroll_user(siteid,aid,userid,nickname,last_enroll_at,enroll_num) select distinct siteid,aid,userid,nickname,max(enroll_at),count(*) from xxt_enroll_record where userid<>'' group by aid,userid";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;