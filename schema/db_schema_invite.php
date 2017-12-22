<?php
require_once '../../db.php';

$sqls = array();
/**
 * 邀请（谁邀请参加什么）
 */
$sql = "create table if not exists xxt_invite (";
$sql .= "id bigint not null auto_increment";
$sql .= ",code char(6) not null"; // 短链接编码
$sql .= ",matter_siteid varchar(32) not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20)";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",matter_summary varchar(240) not null default ''";
$sql .= ",matter_pic text null";
$sql .= ",creator varchar(40) not null"; // 注册用户id，来源于account表
$sql .= ",creator_name varchar(255) not null default ''";
$sql .= ",creator_type char(1) not null default 'A'"; // S:site,U:account
$sql .= ",create_at int not null";
$sql .= ",expire_at int not null default 0";
$sql .= ",require_code char(1) not null default 'N'"; // 是否需要邀请码
$sql .= ",state int not null default 1"; // 1:可用
$sql .= ",from_invite_id bigint not null"; // 创建邀请的用户是通过哪个邀请接受的邀请
$sql .= ",from_invite_code_id bigint not null"; // 创建邀请的用户是通过哪个邀请码接受的邀请
$sql .= ",invitee_count int not null default 0"; // 直接邀请的用户数
$sql .= ",relay_invitee_count int not null default 0"; // 累计直接邀请的用户数
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 邀请码
 */
$sql = "create table if not exists xxt_invite_code (";
$sql .= "id bigint not null auto_increment";
$sql .= ",invite_id bigint not null";
$sql .= ",from_invite_code_id bigint not null"; // 创建邀请的用户是通过哪个邀请码接受的邀请
$sql .= ",code char(4) not null"; // 邀请码的值
$sql .= ",remark text null"; // 邀请码备注
$sql .= ",create_at int not null"; // 邀请码生产时间
$sql .= ",expire_at int not null default 0"; // 邀请码到期时间
$sql .= ",last_use_at int not null default 0"; // 邀请码最后使用时间
$sql .= ",max_count int not null default 0"; // 可以使用的次数
$sql .= ",used_count int not null default 0"; // 使用的次数
$sql .= ",relay_invitee_count int not null default 0"; // 累计直接邀请的用户数
$sql .= ",stop char(1) not null default 'N'"; // 停止使用
$sql .= ",state int not null default 1";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 邀请日志
 */
$sql = "create table if not exists xxt_invite_log (";
$sql .= "id bigint not null auto_increment";
$sql .= ",invite_id bigint not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20)";
$sql .= ",invite_code_id bigint not null"; // 通过哪个邀请码进入
$sql .= ",userid varchar(40) not null";
$sql .= ",nickname varchar(255) not null";
$sql .= ",use_at int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 邀请访问控制
 */
$sql = "create table if not exists xxt_invite_access (";
$sql .= "id bigint not null auto_increment";
$sql .= ",invite_id bigint not null";
$sql .= ",invite_log_id bigint not null"; // 邀请码通过日志id
$sql .= ",token varchar(32) not null"; // 验证通过的令牌
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20)";
$sql .= ",userid varchar(40) not null";
$sql .= ",access_at int not null";
$sql .= ",expire_at int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;