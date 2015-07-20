<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "update xxt_text_call_reply set matter_type = lcase(matter_type)";
$sqls[] = "update xxt_menu_reply set matter_type = lcase(matter_type)";
$sqls[] = "update xxt_other_call_reply set matter_type = lcase(matter_type)";
$sqls[] = "update xxt_activity set success_matter_type=lcase(success_matter_type), failure_matter_type=lcase(failure_matter_type)";
$sqls[] = "update xxt_news_matter set matter_type=lcase(matter_type)";
$sqls[] = "update xxt_news set empty_reply_type=lcase(empty_reply_type)";
$sqls[] = "update xxt_channel set top_type=lcase(top_type),bottom_type=lcase(bottom_type)";
$sqls[] = "ALTER TABLE  `xxt_matter_acl` CHANGE  `matter_type`  `matter_type` CHAR( 14 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ";
$sqls[] = "update xxt_matter_acl set matter_type = 'article' where matter_type='A'";
$sqls[] = "update xxt_matter_acl set matter_type = 'channel' where matter_type='C'";
$sqls[] = "update xxt_matter_acl set matter_type = 'news' where matter_type='N'";
$sqls[] = "update xxt_matter_acl set matter_type = 'link' where matter_type='L'";
$sqls[] = "update xxt_matter_acl set matter_type = 'addressbook' where matter_type='B'";
$sqls[] = "insert into xxt_matter_acl(mpid,matter_type,matter_id,identity,idsrc,label) select mpid,'activity',act_id,identity,idsrc,label from xxt_act_acl where act_type='A'";
$sqls[] = "insert into xxt_matter_acl(mpid,matter_type,matter_id,identity,idsrc,label) select mpid,'wall',act_id,identity,idsrc,label from xxt_act_acl where act_type='W'";
$sqls[] = "insert into xxt_matter_acl(mpid,matter_type,matter_id,identity,idsrc,label) select mpid,'lottery',act_id,identity,idsrc,label from xxt_act_acl where act_type='L'";
//$sqls[] = "DROP TABLE xxt_act_acl";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_address_book` TO  `xxt`.`xxt_addressbook`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_app_contribute` TO  `xxt`.`xxt_contribute`";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
