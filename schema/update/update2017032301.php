<?php
require_once '../../db.php';

$sqls = array();
//
$sql = "create table if not exists xxt_log_tmplmsg_plbatch(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null"; // 哪个站点发起的
$sql .= ",event_name varchar(255) not null default ''"; // 事件名称
$sql .= ",send_from varchar(255) not null default ''"; // 记录发起的源头
$sql .= ",tmplmsg_id int not null"; // 系统模板ID
$sql .= ",template_id varchar(255) not null"; // 微信模板ID
$sql .= ",params text"; // 发送的消息的参数
$sql .= ",user_num int not null"; // 送的用户数量
$sql .= ",success_user_num int not null default 0"; // 送成功的用户数量
$sql .= ",remark text"; // 发送内容说明
$sql .= ",create_at int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = "create table if not exists xxt_log_tmplmsg_pldetail(";
$sql .= "id bigint not null auto_increment";
$sql .= ",batch_id int not null"; // 消息批次id
$sql .= ",siteid varchar(32) not null"; // 通过哪个站点发送
$sql .= ",tmplmsg_id int not null"; // 系统模板ID
$sql .= ",msgid varchar(50) not null default ''"; // 消息ID
$sql .= ",userid varchar(40) not null";
$sql .= ",send_to char(2) not null"; //pl|wx|yx|qy
$sql .= ",openid varchar(255) not null default ''";
$sql .= ",data text"; // 发送的消息内容
$sql .= ",status varchar(255) not null default ''"; // success|failed:user block|failed:system failed
$sql .= ",close_at int not null default 0"; // 关闭消息事件
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