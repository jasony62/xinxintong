<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_code_page add siteid varchar(32) not null after id";
$sqls[] = "alter table xxt_code_page add name varchar(13) not null after siteid";
$sqls[] = "alter table xxt_code_page add modifier varchar(40) not null after create_at";
$sqls[] = "alter table xxt_code_page add version int not null default 1 after summary";
$sqls[] = "alter table xxt_code_page add published char(1) not null default 'N' after version";
$sqls[] = "alter table xxt_code_page add is_last char(1) not null default 'Y' after published";
$sqls[] = "alter table xxt_code_page add is_last_published char(1) not null default 'Y' after is_last";
$sqls[] = "update xxt_code_page set modifier=creater";
/*******/
$sqls[] = "update xxt_code_page p,xxt_article a set p.siteid=a.siteid where p.id=a.page_id";
$sqls[] = "update xxt_code_page p,xxt_channel c set p.siteid=c.siteid where p.id=c.style_page_id";
$sqls[] = "update xxt_code_page p,xxt_channel c set p.siteid=c.siteid where p.id=c.header_page_id";
$sqls[] = "update xxt_code_page p,xxt_channel c set p.siteid=c.siteid where p.id=c.footer_page_id";
$sqls[] = "update xxt_code_page p,xxt_enroll_page e set p.siteid=e.siteid where p.id=e.code_id";
$sqls[] = "update xxt_code_page p,xxt_signin_page s set p.siteid=s.siteid where p.id=s.code_id";
$sqls[] = "update xxt_code_page p,xxt_group g set p.siteid=g.siteid where p.id=g.page_code_id";
$sqls[] = "update xxt_code_page p,xxt_lottery l set p.siteid=l.siteid where p.id=l.page_id";
$sqls[] = "update xxt_code_page p,xxt_merchant_page m set p.siteid=m.siteid where p.id=m.code_id";
$sqls[] = "update xxt_code_page p,xxt_wall_page w set p.siteid=w.mpid where p.id=w.code_id";
$sqls[] = "update xxt_code_page p,xxt_site s set p.siteid=s.id where p.id=s.home_page_id";
$sqls[] = "update xxt_code_page p,xxt_site s set p.siteid=s.id where p.id=s.header_page_id";
$sqls[] = "update xxt_code_page p,xxt_site s set p.siteid=s.id where p.id=s.footer_page_id";
$sqls[] = "update xxt_code_page p,xxt_site s set p.siteid=s.id where p.id=s.shift2pc_page_id";
$sqls[] = "update xxt_code_page p,xxt_site_wx w set p.siteid=w.siteid where p.id=w.follow_page_id";
$sqls[] = "update xxt_code_page p,xxt_site_yx y set p.siteid=y.siteid where p.id=y.follow_page_id";
$sqls[] = "update xxt_code_page p,xxt_site_qy q set p.siteid=q.siteid where p.id=q.follow_page_id";
$sqls[] = "update xxt_code_page p,xxt_site_member_schema s set p.siteid=s.siteid where p.id=s.code_id";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
/*****/
$sql = "select id,create_at from xxt_code_page";
$dbResult = $mysqli->query($sql);
$objects = array();
while ($obj = $dbResult->fetch_object()) {
	$objects[] = $obj;
}
$dbResult->free();
foreach ($objects as $cp) {
	$name = uniqid();
	$sql2 = "update xxt_code_page set name='$name' where id='{$cp->id}'";
	$mysqli_w->query($sql2);
}

$sqls = array();
/*******/
$sqls[] = "alter table xxt_article add body_page_name varchar(13) not null default '' after page_id";
$sqls[] = "update xxt_article a,xxt_code_page p set a.body_page_name=p.name where p.id=a.page_id";
/*******/
$sqls[] = "alter table xxt_channel add style_page_name varchar(13) not null default '' after style_page_id";
$sqls[] = "update xxt_channel c,xxt_code_page p set c.style_page_name=p.name where p.id=c.style_page_id";
$sqls[] = "alter table xxt_channel add header_page_name varchar(13) not null default '' after header_page_id";
$sqls[] = "update xxt_channel c,xxt_code_page p set c.header_page_name=p.name where p.id=c.header_page_id";
$sqls[] = "alter table xxt_channel add footer_page_name varchar(13) not null default '' after footer_page_id";
$sqls[] = "update xxt_channel c,xxt_code_page p set c.footer_page_name=p.name where p.id=c.footer_page_id";
/*******/
$sqls[] = "alter table xxt_enroll_page add code_name varchar(13) not null default '' after code_id";
$sqls[] = "update xxt_enroll_page e,xxt_code_page p set e.code_name=p.name where p.id=e.code_id";
/*******/
$sqls[] = "alter table xxt_signin_page add code_name varchar(13) not null default '' after code_id";
$sqls[] = "update xxt_signin_page s,xxt_code_page p set s.code_name=p.name where p.id=s.code_id";
/*******/
$sqls[] = "alter table xxt_group add page_code_name varchar(13) not null default '' after page_code_id";
$sqls[] = "update xxt_group g,xxt_code_page p set g.page_code_name=p.name where p.id=g.page_code_id";
/*******/
$sqls[] = "alter table xxt_lottery add page_code_name varchar(13) not null default '' after page_id";
$sqls[] = "update xxt_lottery l,xxt_code_page p set l.page_code_name=p.name where p.id=l.page_id";
/*******/
$sqls[] = "alter table xxt_wall_page add code_name varchar(13) not null default '' after code_id";
$sqls[] = "update xxt_wall_page w,xxt_code_page p set w.code_name=p.name where p.id=w.code_id";
/*******/
$sqls[] = "alter table xxt_merchant_page add code_name varchar(13) not null default '' after code_id";
$sqls[] = "update xxt_merchant_page m,xxt_code_page p set m.code_name=p.name where p.id=m.code_id";
/*******/
$sqls[] = "alter table xxt_site add home_page_name varchar(13) not null default '' after home_page_id";
$sqls[] = "update xxt_site s,xxt_code_page p set s.home_page_name=p.name where p.id=s.home_page_id";
$sqls[] = "alter table xxt_site add header_page_name varchar(13) not null default '' after header_page_id";
$sqls[] = "update xxt_site s,xxt_code_page p set s.header_page_name=p.name where p.id=s.header_page_id";
$sqls[] = "alter table xxt_site add footer_page_name varchar(13) not null default '' after footer_page_id";
$sqls[] = "update xxt_site s,xxt_code_page p set s.footer_page_name=p.name where p.id=s.footer_page_id";
$sqls[] = "alter table xxt_site add shift2pc_page_name varchar(13) not null default '' after shift2pc_page_id";
$sqls[] = "update xxt_site s,xxt_code_page p set s.shift2pc_page_name=p.name where p.id=s.shift2pc_page_id";
/*******/
$sqls[] = "alter table xxt_site_wx add follow_page_name varchar(13) not null default '' after follow_page_id";
$sqls[] = "update xxt_site_wx w,xxt_code_page p set w.follow_page_name=p.name where p.id=w.follow_page_id";
/*******/
$sqls[] = "alter table xxt_site_yx add follow_page_name varchar(13) not null default '' after follow_page_id";
$sqls[] = "update xxt_site_yx y,xxt_code_page p set y.follow_page_name=p.name where p.id=y.follow_page_id";
/*******/
$sqls[] = "alter table xxt_site_qy add follow_page_name varchar(13) not null default '' after follow_page_id";
$sqls[] = "update xxt_site_qy q,xxt_code_page p set q.follow_page_name=p.name where p.id=q.follow_page_id";
/*******/
$sqls[] = "alter table xxt_site_member_schema add page_code_name varchar(13) not null default '' after code_id";
$sqls[] = "update xxt_site_member_schema s,xxt_code_page p set s.page_code_name=p.name where p.id=s.code_id";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;