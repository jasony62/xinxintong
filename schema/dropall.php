<?php
require_once '../db.php';
die('danger');
/**
 * account
 */
$sql[] = "drop table if exists account";
$sql[] = "drop table if exists account_group";
$sql[] = "drop table if exists account_in_group";
/**
 * app
 */
//$sql[] = "drop table if exists xxt_contribute";
//$sql[] = "drop table if exists xxt_contribute_user";
/**
 * code
 */
$sql[] = 'drop table if exists xxt_code_page';
$sql[] = 'drop table if exists xxt_code_external';
/**
 * coin
 */
$sql[] = 'drop table if exists xxt_coin_log';
$sql[] = 'drop table if exists xxt_coin_rule';
/*
 * enroll
 */
$sql[] = 'drop table if exists xxt_enroll';
$sql[] = 'drop table if exists xxt_enroll_page';
//$sql[] = 'drop table if exists xxt_enroll_record_schema';
$sql[] = 'drop table if exists xxt_enroll_round';
$sql[] = 'drop table if exists xxt_enroll_record';
//$sql[] = 'drop table if exists xxt_enroll_signin_log';
$sql[] = 'drop table if exists xxt_enroll_record_score';
$sql[] = 'drop table if exists xxt_enroll_record_remark';
$sql[] = 'drop table if exists xxt_enroll_record_data';
$sql[] = 'drop table if exists xxt_enroll_record_stat';
//$sql[] = 'drop table if exists xxt_enroll_lottery_round';
//$sql[] = 'drop table if exists xxt_enroll_lottery';
//
$sql[] = 'drop table if exists xxt_signin';
$sql[] = 'drop table if exists xxt_signin_round';
$sql[] = 'drop table if exists xxt_signin_page';
$sql[] = 'drop table if exists xxt_signin_record';
$sql[] = 'drop table if exists xxt_signin_record_data';
$sql[] = 'drop table if exists xxt_signin_log';
//
$sql[] = 'drop table if exists xxt_group';
$sql[] = 'drop table if exists xxt_group_round';
$sql[] = 'drop table if exists xxt_group_record';
$sql[] = 'drop table if exists xxt_group_record_data';
/**
 * log
 */
$sql[] = "drop table if exists xxt_log";
$sql[] = "drop table if exists xxt_log_mpa";
$sql[] = "drop table if exists xxt_log_mpreceive";
$sql[] = "drop table if exists xxt_log_mpsend";
$sql[] = "drop table if exists xxt_log_matter_read";
$sql[] = "drop table if exists xxt_log_matter_share";
$sql[] = "drop table if exists xxt_log_massmsg";
$sql[] = "drop table if exists xxt_log_tmplmsg";
$sql[] = "drop table if exists xxt_log_user_action";
$sql[] = "drop table if exists xxt_log_user_matter";
$sql[] = "drop table if exists xxt_log_matter_action";
$sql[] = "drop table if exists xxt_log_timer";
$sql[] = "drop table if exists xxt_log_matter_op";
/**
 * lottery
 */
$sql[] = "drop table if exists xxt_lottery";
$sql[] = "drop table if exists xxt_lottery_task";
$sql[] = "drop table if exists xxt_lottery_task_log";
$sql[] = "drop table if exists xxt_lottery_award";
$sql[] = "drop table if exists xxt_lottery_plate";
$sql[] = "drop table if exists xxt_lottery_log";
/**
 * matter
 */
$sql[] = "drop table if exists xxt_article";
$sql[] = "drop table if exists xxt_article_attachment";
$sql[] = "drop table if exists xxt_article_download_log";
//$sql[] = "drop table if exists xxt_article_review_log";
//
$sql[] = "drop table if exists xxt_link";
$sql[] = "drop table if exists xxt_link_param";
//
$sql[] = "drop table if exists xxt_text";
//
$sql[] = "drop table if exists xxt_news";
$sql[] = "drop table if exists xxt_news_review_log";
$sql[] = "drop table if exists xxt_news_matter";
//
$sql[] = "drop table if exists xxt_channel";
$sql[] = "drop table if exists xxt_channel_matter";
//
$sql[] = "drop table if exists xxt_inner";
//
$sql[] = "drop table if exists xxt_tmplmsg";
$sql[] = "drop table if exists xxt_tmplmsg_param";
$sql[] = "drop table if exists xxt_tmplmsg_mapping";
//
$sql[] = "drop table if exists xxt_matter_acl";
/**
 * mission
 */
$sql[] = "drop table if exists xxt_mission";
$sql[] = "drop table if exists xxt_mission_matter";
$sql[] = "drop table if exists xxt_mission_phase";
/**
 * reply
 */
$sql[] = "drop table if exists xxt_call_text_wx";
$sql[] = "drop table if exists xxt_call_text_qy";
//
$sql[] = "drop table if exists xxt_call_menu_wx";
$sql[] = "drop table if exists xxt_call_menu_qy";
//
$sql[] = "drop table if exists xxt_call_qrcode_wx";
//
$sql[] = "drop table if exists xxt_call_other_wx";
$sql[] = "drop table if exists xxt_call_other_qy";
//
$sql[] = "drop table if exists xxt_timer_task";
//
$sql[] = "drop table if exists xxt_call_relay_wx";
$sql[] = "drop table if exists xxt_call_relay_qy";
/**
 * shop
 */
$sql[] = "drop table if exists xxt_shop_matter";
/**
 * site
 */
$sql[] = "drop table if exists xxt_site";
$sql[] = "drop table if exists xxt_site_admin";
$sql[] = "drop table if exists xxt_site_account";
$sql[] = "drop table if exists xxt_site_favor";
$sql[] = "drop table if exists xxt_site_member_schema";
$sql[] = "drop table if exists xxt_site_member";
$sql[] = "drop table if exists xxt_site_member_department";
$sql[] = "drop table if exists xxt_site_member_tag";
/**
 *sns
 */
$sql[] = "drop table if exists xxt_site_wx";
$sql[] = "drop table if exists xxt_site_wxfan";
$sql[] = "drop table if exists xxt_site_wxfangroup";
//
$sql[] = "drop table if exists xxt_site_qy";
/**
 * tag
 */
$sql[] = 'drop table if exists xxt_tag';
$sql[] = 'drop table if exists xxt_article_tag';
/**
 * task
 */
$sql[] = 'drop table if exists xxt_task';
/*
 * wall
 */
$sql[] = 'drop table if exists xxt_wall';
$sql[] = 'drop table if exists xxt_wall_page';
$sql[] = 'drop table if exists xxt_wall_enroll';
$sql[] = 'drop table if exists xxt_wall_log';
/**
 * 执行操作
 */
// foreach ($sql as $s) {
//     if (!$mysqli->query($s)) {
//         header('HTTP/1.0 500 Internal Server Error');
//         echo 'database error: ' . $mysqli->error;
//     }
// }

echo 'drop all finished.';