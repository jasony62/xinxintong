<?php
// die('danger');
/**
 * create all tables.
 */
include_once dirname(__FILE__) . '/db_schema_account.php';
// include_once dirname(__FILE__) . '/db_schema_code.php';
include_once dirname(__FILE__) . '/db_schema_coin.php';
include_once dirname(__FILE__) . '/db_schema_enroll.php';
// include_once dirname(__FILE__) . '/db_schema_invite.php';
include_once dirname(__FILE__) . '/db_schema_log.php';
// include_once dirname(__FILE__) . '/db_schema_lottery.php';
// include_once dirname(__FILE__) . '/db_schema_matter.php';
// include_once dirname(__FILE__) . '/db_schema_merchant.php';
// include_once dirname(__FILE__) . '/db_schema_mission.php';
// include_once dirname(__FILE__) . '/db_schema_reply.php';
// include_once dirname(__FILE__) . '/db_schema_shop.php';
include_once dirname(__FILE__) . '/db_schema_site.php';
// include_once dirname(__FILE__) . '/db_schema_sns.php';
// include_once dirname(__FILE__) . '/db_schema_tag.php';
include_once dirname(__FILE__) . '/db_schema_task.php';
// include_once dirname(__FILE__) . '/db_schema_wall.php';
/**
 * init data.
 */
$sql = array();
/*用户组*/
$sql[] = "delete from account_group";
$sql[] = "INSERT INTO account_group(group_id,group_name,asdefault,p_mpgroup_create,p_mp_create,p_mp_permission,p_platform_manage) VALUES(1,'初级用户',1,0,1,0,0),(3,'开发用户',0,1,1,1,1),(9,'平台运营',0,1,1,1,1)";
/*内置应用（xxt_inner）*/
// $sql[] = "delete from xxt_inner";
// $sql[] = "INSERT INTO xxt_inner(id,title,name) VALUES(3, '翻译', 'translate')";
// $sql[] = "INSERT INTO xxt_inner(id,title,name) VALUES(4, '按关键字搜索文章', 'fullsearch')";
//
$sql[] = "insert into xxt_site(id,name,creater,create_at) values('platform','信信通','sys'," . time() . ")";
/*执行*/
foreach ($sql as $s) {
	if (!$mysqli->query($s)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo "database error: " . $mysqli->error;
	}
}