<?php
require_once '../../db.php';
$sqls = array();
//
/**
 * 运营任务访问控制列表，记录任务的所有访问关系
 */
$sql = "create table if not exists xxt_mission_acl (";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null"; // 任务所属的站点
$sql .= ",mission_id int not null";
$sql .= ",title varchar(70) not null"; // 任务的标题
$sql .= ",summary varchar(240) not null"; // 任务摘要
$sql .= ",pic text"; // 任务图片
$sql .= ",creater varchar(40) not null default ''"; // 任务的创建者
$sql .= ",create_at int not null"; // 任务的创建时间
$sql .= ",inviter varchar(40) not null default ''"; // 邀请人
$sql .= ",inviter_label varchar(255) not null default ''";
$sql .= ",invite_at int not null"; // 邀请时间
$sql .= ",coworker varchar(40) not null default ''"; // 合作者
$sql .= ",coworker_label varchar(255) not null default ''";
$sql .= ",join_at int not null"; // 加入时间
$sql .= ",state tinyint not null default 1"; //0:stop,1:normal
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
//
$sqls[] = $sql;
$sqls[] = "insert into xxt_mission_acl(siteid,mission_id,title,summary,pic,creater,create_at,inviter,invite_at,coworker,join_at,state) select siteid,id,title,summary,pic,creater,create_at,creater,create_at,creater,create_at,state from xxt_mission";
$sqls[] = "update xxt_mission_acl ma,account a set ma.inviter_label=a.email where ma.inviter=a.uid";
$sqls[] = "update xxt_mission_acl ma,account a set ma.coworker_label=a.email where ma.coworker=a.uid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;