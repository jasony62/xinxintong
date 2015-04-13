<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_activity` ADD  `intro_page_id` INT NOT NULL DEFAULT  '0'";
$sqls[] = "ALTER TABLE  `xxt_activity` ADD  `form_page_id` INT NOT NULL DEFAULT  '0'";
$sqls[] = "ALTER TABLE  `xxt_activity` ADD  `result_page_id` INT NOT NULL DEFAULT  '0'";
$sqls[] = "insert into xxt_code_page(creater,create_at,modify_at,title,summary,html,css,js) select creater,create_at,create_at,aid,'intro',intro_ele,intro_css,intro_js from xxt_activity";
$sqls[] = "update xxt_activity a,xxt_code_page p set a.intro_page_id=p.id where a.aid=p.title and p.summary='intro'";
$sqls[] = "update xxt_code_page p,xxt_activity a set p.title=a.title where p.id=a.intro_page_id";
$sqls[] = "insert into xxt_code_page(creater,create_at,modify_at,title,summary,html,css,js) select creater,create_at,create_at,aid,'form',enroll_ele,enroll_css,enroll_js from xxt_activity";
$sqls[] = "update xxt_activity a,xxt_code_page p set a.form_page_id=p.id where a.aid=p.title and p.summary='form'";
$sqls[] = "update xxt_code_page p,xxt_activity a set p.title=a.title where p.id=a.form_page_id";
$sqls[] = "insert into xxt_code_page(creater,create_at,modify_at,title,summary,html,css,js) select creater,create_at,create_at,aid,'result',state_ele,state_css,state_js from xxt_activity";
$sqls[] = "update xxt_activity a,xxt_code_page p set a.result_page_id=p.id where a.aid=p.title and p.summary='result'";
$sqls[] = "update xxt_code_page p,xxt_activity a set p.title=a.title where p.id=a.result_page_id";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `intro_css`";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `intro_ele`";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `intro_js`";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `enroll_css`";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `enroll_ele`";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `enroll_js`";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `state_css`";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `state_ele`";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `state_js`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
