<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table account add from_siteid varchar(32) not null default '' comment '从哪个团队发起的注册id' after uid";
$sqls[] = "alter table account add headimgurl varchar(255) not null default '' after nickname";
//
$sqls[] = "insert into account(uid,from_siteid,authed_from,authed_id,email,nickname,headimgurl,password,salt,reg_time,reg_ip,last_login,last_ip,last_active,forbidden,is_first_login) select unionid,from_siteid,'xxt_site',uname,uname,nickname,headimgurl,password,salt,reg_time,reg_ip,last_login,last_ip,last_active,forbidden,is_first_login from xxt_site_registration";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;