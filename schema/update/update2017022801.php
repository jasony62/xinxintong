<?php
require_once '../../db.php';

$sqls = array();
//
$sql = "create table if not exists xxt_template_enroll (";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null"; // 提供模板的站点
$sql .= ",version varchar(10) not null";//模板版本号
$sql .= ",create_at int not null";
$sql .= ",template_id int not null"; 
$sql .= ",scenario_config text"; // 登记活动场景的配置参数
$sql .= ",multi_rounds char(1) not null default 'N'"; // 支持轮次
$sql .= ",enrolled_entry_page varchar(20) not null default ''";//已填写时进入
$sql .= ",open_lastroll char(1) not null default 'Y'"; // 打开最后一条登记记录，还是编辑新的
$sql .= ",data_schemas longtext"; // 登记项定义
$sql .= ",up_said text"; // 版本更新说明
$sql .= ",pub_status char(1) not null default 'N'"; //发布状态
$sql .= ",state tinyint not null default 1"; 
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sqls[] = "alter table xxt_template change matter_id matter_id varchar(40) not null default ''";
$sqls[] = "alter table xxt_template change matter_type matter_type varchar(20) not null default ''";
$sqls[] = "alter table xxt_template add pub_version varchar(10) not null default '' after summary";
$sqls[] = "alter table xxt_template add last_version varchar(10) not null default '' after pub_version";
$sqls[] = "alter table xxt_template add favor_num int not null default 0 after copied_num";
$sqls[] = "alter table xxt_template add state tinyint not null default 1";
//
$sqls[] = "alter table xxt_template_acl change matter_id matter_id varchar(40) not null default ''";
$sqls[] = "alter table xxt_template_acl change matter_type matter_type varchar(20) not null default ''";
//
$sqls[] = "alter table xxt_template_order change matter_id matter_id varchar(40) not null default ''";
$sqls[] = "alter table xxt_template_order change matter_type matter_type varchar(20) not null default ''";
$sqls[] = "alter table xxt_template_order change summary summary varchar(240) not null default ''";
$sqls[] = "alter table xxt_template_order add template_version varchar(10) not null after template_id";
//
$sqls[] = "alter table xxt_enroll add template_version varchar(10) not null after template_id";


foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;