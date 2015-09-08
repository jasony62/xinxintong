<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_enroll  ADD can_autoenroll char(1) not null default 'N' after can_remark_record";
$sqls[] = "ALTER TABLE xxt_enroll_page ADD autoenroll_onenter char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_enroll_page ADD autoenroll_onshare char(1) not null default 'N'";
$sqls[] = "insert into xxt_enroll_page(mpid,aid,creater,create_at,type,title,code_id,name) select mpid,id,creater,create_at-1,'I','登记信息页',form_code_id,concat('z',cast((create_at-1) as char)) from xxt_enroll where form_code_id<>0";
$sqls[] = "update xxt_enroll set entry_rule=replace(entry_rule, 'form', concat('z',cast((create_at-1) as char))) where entry_rule like '%form%'";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;