<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE `xxt_member_authapi` ADD `auth_code_id` INT NOT NULL DEFAULT '0'";
$sqls[] = "ALTER TABLE `xxt_member_authapi` DROP `auth_html`";
$sqls[] = "ALTER TABLE `xxt_member_authapi` DROP `auth_css`";
$sqls[] = "ALTER TABLE `xxt_member_authapi` DROP `auth_js`";
//
foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
