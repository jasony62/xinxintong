<?php
require_once '../../db.php';

$sqls = array();
$sql = "create table if not exists xxt_site_matter(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",creater_src char(1)";
$sql .= ",create_at int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20)";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",matter_category varchar(3) not null"; //app/doc
$sql .= ",scenario varchar(255) not null default ''";
$sql .= ",start_at int not null default 0";
$sql .= ",end_at int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sqls[] = "ALTER TABLE xxt_wall add creater_src char(1) not null default 'A' after creater_name";
$sqls[] = "ALTER TABLE xxt_wall add modifier varchar(40) not null default '' after create_at";
$sqls[] = "ALTER TABLE xxt_wall add modifier_name varchar(255) not null default '' after modifier";
$sqls[] = "ALTER TABLE xxt_wall add modifier_src char(1) not null default 'A' after modifier_name";
$sqls[] = "ALTER TABLE xxt_wall add modify_at int not null after modifier_src";
$sqls[] = "update xxt_wall set modifier=creater,modifier_name=creater_name,modifier_src=creater_src,modify_at=create_at";
//
$sqls[] = "insert into xxt_site_matter(siteid,mission_id,creater,creater_name,creater_src,create_at,matter_id,matter_type,matter_title,matter_category,scenario,start_at,end_at) select siteid,mission_id,creater,creater_name,creater_src,create_at,id,'article',title,'doc','',0,0 from xxt_article where custom_body='N' and state=1 and (isnull(entry) or entry='')";
$sqls[] = "insert into xxt_site_matter(siteid,mission_id,creater,creater_name,creater_src,create_at,matter_id,matter_type,matter_title,matter_category,scenario,start_at,end_at) select siteid,mission_id,creater,creater_name,creater_src,create_at,id,'custom',title,'doc','',0,0 from xxt_article where custom_body='Y' and state=1";
$sqls[] = "insert into xxt_site_matter(siteid,mission_id,creater,creater_name,creater_src,create_at,matter_id,matter_type,matter_title,matter_category,scenario,start_at,end_at) select siteid,0,creater,creater_name,creater_src,create_at,id,'link',title,'doc','',0,0 from xxt_link where state=1";
$sqls[] = "insert into xxt_site_matter(siteid,mission_id,creater,creater_name,creater_src,create_at,matter_id,matter_type,matter_title,matter_category,scenario,start_at,end_at) select siteid,0,creater,creater_name,creater_src,create_at,id,'news',title,'doc','',0,0 from xxt_news where state=1";
$sqls[] = "insert into xxt_site_matter(siteid,mission_id,creater,creater_name,creater_src,create_at,matter_id,matter_type,matter_title,matter_category,scenario,start_at,end_at) select siteid,0,creater,creater_name,creater_src,create_at,id,'channel',title,'doc','',0,0 from xxt_channel where state=1";
$sqls[] = "insert into xxt_site_matter(siteid,mission_id,creater,creater_name,creater_src,create_at,matter_id,matter_type,matter_title,matter_category,scenario,start_at,end_at) select siteid,0,creater,creater_name,creater_src,create_at,id,'contribute',title,'doc','',0,0 from xxt_contribute where state=1";
//
$sqls[] = "insert into xxt_site_matter(siteid,mission_id,creater,creater_name,creater_src,create_at,matter_id,matter_type,matter_title,matter_category,scenario,start_at,end_at) select siteid,mission_id,creater,creater_name,creater_src,create_at,id,'enroll',title,'app',scenario,start_at,end_at from xxt_enroll where state=1";
$sqls[] = "insert into xxt_site_matter(siteid,mission_id,creater,creater_name,creater_src,create_at,matter_id,matter_type,matter_title,matter_category,scenario,start_at,end_at) select siteid,mission_id,creater,creater_name,creater_src,create_at,id,'signin',title,'app','',start_at,end_at from xxt_signin where state=1";
$sqls[] = "insert into xxt_site_matter(siteid,mission_id,creater,creater_name,creater_src,create_at,matter_id,matter_type,matter_title,matter_category,scenario,start_at,end_at) select siteid,mission_id,creater,creater_name,creater_src,create_at,id,'group',title,'app',scenario,0,0 from xxt_group where state=1";
$sqls[] = "insert into xxt_site_matter(siteid,mission_id,creater,creater_name,creater_src,create_at,matter_id,matter_type,matter_title,matter_category,scenario,start_at,end_at) select siteid,mission_id,creater,creater_name,'',create_at,id,'wall',title,'app','',0,0 from xxt_wall where state=1";
$sqls[] = "insert into xxt_site_matter(siteid,mission_id,creater,creater_name,creater_src,create_at,matter_id,matter_type,matter_title,matter_category,scenario,start_at,end_at) select siteid,0,creater,creater_name,creater_src,create_at,id,'lottery',title,'app','',0,0 from xxt_lottery where state=1";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;