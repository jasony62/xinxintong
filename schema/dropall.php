<?php
require_once '../db.php';
/**
 * account
 */
$sql[] = "drop table if exists account";
$sql[] = "drop table if exists account_group";
$sql[] = "drop table if exists account_in_group";
/**
 * 公众账号
 */
$sql[] = "drop table if exists xxt_mpaccount";
$sql[] = "drop table if exists xxt_mpsetting";
$sql[] = "drop table if exists xxt_mprelay";
$sql[] = "drop table if exists xxt_mpadministrator";
$sql[] = "drop table if exists xxt_mppermission";
/**
 * 素材 
 */
$sql[] = "drop table if exists xxt_article";
$sql[] = "drop table if exists xxt_article_channel";
$sql[] = "drop table if exists xxt_article_remark";
$sql[] = "drop table if exists xxt_article_score";
$sql[] = "drop table if exists xxt_link";
$sql[] = "drop table if exists xxt_link_param";
$sql[] = "drop table if exists xxt_link_channel";
$sql[] = "drop table if exists xxt_text";
$sql[] = "drop table if exists xxt_news";
$sql[] = "drop table if exists xxt_news_matter";
$sql[] = "drop table if exists xxt_channel";
$sql[] = "drop table if exists xxt_inner";
$sql[] = "drop table if exists xxt_matter_acl";
/*
 * tags
 */
$sql[] = 'drop table if exists xxt_tag';
$sql[] = 'drop table if exists xxt_article_tag';
/**
 * address book
 */
$sql[] = "drop table if exists xxt_address_book";
$sql[] = "drop table if exists xxt_ab_dept";
$sql[] = "drop table if exists xxt_ab_title";
$sql[] = "drop table if exists xxt_ab_person";
$sql[] = "drop table if exists xxt_ab_person_dept";
/**
 * 回复设置
 */
$sql[] = "drop table if exists xxt_menu_reply";
$sql[] = "drop table if exists xxt_text_call_reply";
$sql[] = "drop table if exists xxt_qrcode_call_reply";
$sql[] = "drop table if exists xxt_other_call_reply";
$sql[] = "drop table if exists xxt_call_acl";
/**
 * 用户 
 */
$sql[] = 'drop table if exists xxt_visitor';
$sql[] = "drop table if exists xxt_fans";
$sql[] = "drop table if exists xxt_fansgroup";
$sql[] = 'drop table if exists xxt_member';
$sql[] = 'drop table if exists xxt_member_authapi';
$sql[] = 'drop table if exists xxt_member_card';
$sql[] = "drop table if exists xxt_access_token";
/**
 * log
 */
$sql[] = "drop table if exists xxt_log";
$sql[] = "drop table if exists xxt_mpreceive_log";
$sql[] = "drop table if exists xxt_mpsend_log";
$sql[] = "drop table if exists xxt_matter_read_log";
$sql[] = "drop table if exists xxt_shareaction_log";
$sql[] = "drop table if exists xxt_templatemsg_log";
$sql[] = "drop table if exists xxt_user_action_log";
$sql[] = "drop table if exists xxt_matter_action_log";
/*
 * 活动
 */
$sql[] = 'drop table if exists xxt_activity';
$sql[] = 'drop table if exists xxt_activity_enroll';
$sql[] = 'drop table if exists xxt_activity_enroll_cusdata';
/**
 * 轮盘抽奖活动
 */
$sql[] = "drop table if exists xxt_roulette";
$sql[] = "drop table if exists xxt_roulette_award";
$sql[] = "drop table if exists xxt_roulette_plate";
$sql[] = "drop table if exists xxt_roulette_log";
/**
 * bbs
 */
$sql[] = 'drop table if exists xxt_bbs';
$sql[] = 'drop table if exists xxt_bbs_subject';
$sql[] = 'drop table if exists xxt_bbs_reply';
/**
 * 签到
 */
$sql[] = 'drop table if exists xxt_checkin';
$sql[] = 'drop table if exists xxt_checkin_log';
/*
 * 微信墙
 */
$sql[] = 'drop table if exists  xxt_wall';
$sql[] = 'drop table if exists  xxt_wall_enroll';
$sql[] = 'drop table if exists  xxt_wall_log';
/**
 * 投稿人工具箱
 */
$sql[] = 'drop table if exists xxt_writer_box';
/**
 * 执行操作
 */
foreach ($sql as $s) {
    if (!mysql_query($s)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo 'finished.';
