<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "delete from xxt_member_auth where mpid not in (select mpid from xxt_member_authapi)";
$sqls[] = "ALTER TABLE `xxt_member_auth` ADD `authapi_id` INT NOT NULL AFTER `mpid` ";
$sqls[] = "ALTER TABLE `xxt_member_auth` CHANGE `nickname` `nickname` CHAR( 6 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '100000'";
$sqls[] = "ALTER TABLE `xxt_member_auth` CHANGE `birthday` `birthday` CHAR( 6 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '100000'";
$sqls[] = "update xxt_member_auth au,xxt_member_authapi ai set au.authapi_id=ai.authid where au.mpid=ai.mpid and ai.type='inner'";
$sqls[] = "ALTER TABLE `xxt_member_auth` DROP PRIMARY KEY, ADD PRIMARY KEY(`authapi_id`)";
$sqls[] = "insert into xxt_member_auth(mpid,authapi_id) select mpid,authid from xxt_member_authapi";
$sqls[] = "ALTER TABLE `xxt_member_authapi` ADD `field_mobile` CHAR(6) NOT NULL DEFAULT '001000' AFTER `validity`";
$sqls[] = "ALTER TABLE `xxt_member_authapi` ADD `field_email` CHAR(6) NOT NULL DEFAULT '001000' AFTER `field_mobile`";
$sqls[] = "ALTER TABLE `xxt_member_authapi` ADD `field_name` CHAR(6) NOT NULL DEFAULT '000000' AFTER `field_email`";
$sqls[] = "ALTER TABLE `xxt_member_authapi` ADD `field_nickname` CHAR(6) NOT NULL DEFAULT '100000' AFTER `field_name`";
$sqls[] = "ALTER TABLE `xxt_member_authapi` ADD `field_birthday` CHAR(6) NOT NULL DEFAULT '000000' AFTER `field_nickname`";
$sqls[] = "ALTER TABLE `xxt_member_authapi` ADD `auth_html` TEXT NOT NULL AFTER `notpass_statement`";
$sqls[] = "ALTER TABLE `xxt_member_authapi` ADD `auth_css` TEXT NOT NULL AFTER `auth_html`";
$sqls[] = "ALTER TABLE `xxt_member_authapi` ADD `auth_js` TEXT NOT NULL AFTER `auth_css`";
$sqls[] = "update xxt_member_authapi ai,xxt_member_auth au set ai.field_mobile=au.mobile,ai.field_email=au.email,ai.field_name=au.name,ai.field_nickname=au.nickname,ai.field_birthday=au.birthday,ai.auth_html=au.auth_ele,ai.auth_css=au.auth_css,ai.auth_js=au.auth_js where ai.mpid=au.mpid and ai.authid=au.authapi_id";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
