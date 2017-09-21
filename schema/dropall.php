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
$sql[] = "drop table if exists xxt_contribute";
$sql[] = "drop table if exists xxt_contribute_user";
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
$sql[] = 'drop table if exists xxt_enroll_receiver';
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
$sql[] = 'drop table if exists xxt_group_player';
$sql[] = 'drop table if exists xxt_group_player_data';
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
$sql[] = "drop table if exists xxt_article_extinfo";
$sql[] = "drop table if exists xxt_article_ext_distance";
$sql[] = "drop table if exists xxt_article_remark";
$sql[] = "drop table if exists xxt_article_score";
$sql[] = "drop table if exists xxt_article_attachment";
$sql[] = "drop table if exists xxt_article_download_log";
$sql[] = "drop table if exists xxt_article_review_log";
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
 * merchant
 */
$sql[] = "drop table if exists xxt_merchant_shop";
$sql[] = "drop table if exists xxt_merchant_page";
$sql[] = "drop table if exists xxt_merchant_staff";
$sql[] = "drop table if exists xxt_merchant_catelog";
$sql[] = "drop table if exists xxt_merchant_catelog_property";
$sql[] = "drop table if exists xxt_merchant_catelog_property_value";
$sql[] = "drop table if exists xxt_merchant_catelog_sku";
$sql[] = "drop table if exists xxt_merchant_catelog_sku_value";
$sql[] = "drop table if exists xxt_merchant_product";
$sql[] = "drop table if exists xxt_merchant_product_sku";
$sql[] = "drop table if exists xxt_merchant_product_gensku_log";
$sql[] = "drop table if exists xxt_merchant_group";
$sql[] = "drop table if exists xxt_merchant_group_product";
$sql[] = "drop table if exists xxt_merchant_order_property";
$sql[] = "drop table if exists xxt_merchant_order_feedback_property";
$sql[] = "drop table if exists xxt_merchant_order";
$sql[] = "drop table if exists xxt_merchant_order_sku";
/**
 * mission
 */
$sql[] = "drop table if exists xxt_mission";
$sql[] = "drop table if exists xxt_mission_matter";
$sql[] = "drop table if exists xxt_mission_phase";
/**
 * reply
 */
$sql[] = "drop table if exists xxt_call_text";
$sql[] = "drop table if exists xxt_call_text_yx";
$sql[] = "drop table if exists xxt_call_text_wx";
$sql[] = "drop table if exists xxt_call_text_qy";
//
$sql[] = "drop table if exists xxt_call_menu";
$sql[] = "drop table if exists xxt_call_menu_yx";
$sql[] = "drop table if exists xxt_call_menu_wx";
$sql[] = "drop table if exists xxt_call_menu_qy";
//
$sql[] = "drop table if exists xxt_call_qrcode";
$sql[] = "drop table if exists xxt_call_qrcode_yx";
$sql[] = "drop table if exists xxt_call_qrcode_wx";
//
$sql[] = "drop table if exists xxt_call_other";
$sql[] = "drop table if exists xxt_call_other_yx";
$sql[] = "drop table if exists xxt_call_other_wx";
$sql[] = "drop table if exists xxt_call_other_qy";
//
$sql[] = "drop table if exists xxt_call_acl";
$sql[] = "drop table if exists xxt_timer_push";
//
$sql[] = "drop table if exists xxt_call_relay_yx";
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
$sql[] = "drop table if exists xxt_site_yx";
$sql[] = "drop table if exists xxt_site_yxfan";
$sql[] = "drop table if exists xxt_site_yxfangroup";
//
$sql[] = "drop table if exists xxt_site_qy";
//
$sql[] = "drop table if exists xxt_pl_wx";
$sql[] = "drop table if exists xxt_pl_wxfan";
$sql[] = "drop table if exists xxt_pl_wxfangroup";
//
$sql[] = "drop table if exists xxt_pl_yx";
$sql[] = "drop table if exists xxt_pl_yxfan";
$sql[] = "drop table if exists xxt_pl_yxfangroup";
/**
 * tag
 */
$sql[] = 'drop table if exists xxt_tag';
$sql[] = 'drop table if exists xxt_article_tag';
/**
 * task
 */
$sql[] = 'drop table if exists xxt_task';
/**
 * user
 */
$sql[] = 'drop table if exists xxt_visitor';
$sql[] = "drop table if exists xxt_fans";
$sql[] = "drop table if exists xxt_fansgroup";
$sql[] = 'drop table if exists xxt_member';
$sql[] = 'drop table if exists xxt_member_authapi';
$sql[] = 'drop table if exists xxt_member_department';
$sql[] = 'drop table if exists xxt_member_tag';
$sql[] = 'drop table if exists xxt_member_card';
$sql[] = "drop table if exists xxt_access_token";
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
// 	if (!$mysqli->query($s)) {
// 		header('HTTP/1.0 500 Internal Server Error');
// 		echo 'database error: ' . $mysqli->error;
// 	}
// }

echo 'drop all finished.';