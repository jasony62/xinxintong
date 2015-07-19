<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_mppermission` CHANGE  `permission`  `permission` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'user','N','N','N','N' from xxt_mppermission where permission='fans'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'mpsetting_setting','N','N','N','N' from xxt_mppermission where permission='mpsetting'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'mpsetting_feature','N','N','N','N' from xxt_mppermission where permission='mpsetting'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'mpsetting_customapi','N','N','N','N' from xxt_mppermission where permission='mpsetting'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'mpsetting_permission','N','N','N','N' from xxt_mppermission where permission='mpsetting'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'mpsetting_administrator','N','N','N','N' from xxt_mppermission where permission='mpsetting'";
//
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'matter_article','N','N','N','N' from xxt_mppermission where permission='mpsetting'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'matter_text','N','N','N','N' from xxt_mppermission where permission='mpsetting'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'matter_news','N','N','N','N' from xxt_mppermission where permission='mpsetting'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'matter_channel','N','N','N','N' from xxt_mppermission where permission='mpsetting'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'matter_link','N','N','N','N' from xxt_mppermission where permission='mpsetting'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'matter_tmplmsg','N','N','N','N' from xxt_mppermission where permission='mpsetting'";
//
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'user_received','N','N','N','N' from xxt_mppermission where permission='user'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'user_send','N','N','N','N' from xxt_mppermission where permission='user'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'user_fans','N','N','N','N' from xxt_mppermission where permission='user'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'user_member','N','N','N','N' from xxt_mppermission where permission='user'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'user_department','N','N','N','N' from xxt_mppermission where permission='user'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'user_tag','N','N','N','N' from xxt_mppermission where permission='user'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'user_fansgroup','N','N','N','N' from xxt_mppermission where permission='user'";
//
$sqls[] = "delete from xxt_mppermission where permission='mpsecurity'";
$sqls[] = "delete from xxt_mppermission where permission='fans'";
$sqls[] = "delete from xxt_mppermission where permission='member'";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
