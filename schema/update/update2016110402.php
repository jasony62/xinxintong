<?php
require_once '../../db.php';

$sqls = array();
//
$sql = 'create table if not exists xxt_template (';
$sql .= 'id int not null auto_increment';
$sql .= ",siteid varchar(32) not null";
$sql .= ",site_name varchar(50) not null";
$sql .= ',creater varchar(40) not null';
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ',put_at int not null';
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",scenario varchar(255) not null default ''";
$sql .= ",title varchar(70) not null default ''";
$sql .= ',pic text';
$sql .= ',summary varchar(240) not null';
$sql .= ",visible_scope char(1) not null default 'A'";
$sql .= ",push_home char(1) not null default 'N'";
$sql .= ",weight int not null default 0";
$sql .= ",score int not null default 0";
$sql .= ",copied_num int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = 'create table if not exists xxt_template_order (';
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ',buyer varchar(40) not null';
$sql .= ",buyer_name varchar(255) not null default ''";
$sql .= ",template_id int not null";
$sql .= ",from_siteid varchar(32) not null";
$sql .= ",from_site_name varchar(50) not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",scenario varchar(255) not null default ''";
$sql .= ",title varchar(70) not null default ''";
$sql .= ',pic text';
$sql .= ',summary varchar(240) not null';
$sql .= ",share char(1) not null default 'N'";
$sql .= ',share_at int not null';
$sql .= ",favor char(1) not null default 'N'";
$sql .= ',favor_at int not null';
$sql .= ",purchase char(1) not null default 'N'";
$sql .= ',purchase_at int not null';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sqls[] = 'drop table xxt_shop_matter';
$sqls[] = 'drop table xxt_shop_matter_acl';

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;