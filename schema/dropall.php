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
$sql[] = "drop table if exists xxt_article_remark";
$sql[] = "drop table if exists xxt_article_score";
$sql[] = "drop table if exists xxt_link";
$sql[] = "drop table if exists xxt_link_param";
$sql[] = "drop table if exists xxt_text";
$sql[] = "drop table if exists xxt_news";
$sql[] = "drop table if exists xxt_news_matter";
$sql[] = "drop table if exists xxt_channel";
$sql[] = "drop table if exists xxt_channel_matter";
$sql[] = "drop table if exists xxt_inner";
$sql[] = "drop table if exists xxt_matter_acl";
$sql[] = "drop table if exists xxt_tmplmsg";
$sql[] = "drop table if exists xxt_tmplmsg_param";
/*
 * tags
 */
$sql[] = 'drop table if exists xxt_tag';
$sql[] = 'drop table if exists xxt_article_tag';
/**
 * address book
 */
$sql[] = "drop table if exists xxt_addressbook";
$sql[] = "drop table if exists xxt_ab_dept";
$sql[] = "drop table if exists xxt_ab_title";
$sql[] = "drop table if exists xxt_ab_person";
$sql[] = "drop table if exists xxt_ab_person_dept";
/**
 * 回复设置
 */
$sql[] = "drop table if exists xxt_call_menu";
$sql[] = "drop table if exists xxt_call_text";
$sql[] = "drop table if exists xxt_call_qrcode";
$sql[] = "drop table if exists xxt_call_other";
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
$sql[] = 'drop table if exists xxt_member_tag';
$sql[] = "drop table if exists xxt_access_token";
/**
 * log
 */
$sql[] = "drop table if exists xxt_log";
$sql[] = "drop table if exists xxt_log_mpreceive";
$sql[] = "drop table if exists xxt_log_mpsend";
$sql[] = "drop table if exists xxt_log_matter_read";
$sql[] = "drop table if exists xxt_log_matter_share";
$sql[] = "drop table if exists xxt_log_tmplmsg";
$sql[] = "drop table if exists xxt_log_user_action";
$sql[] = "drop table if exists xxt_log_matter_action";
/*
 * 活动
 */
$sql[] = 'drop table if exists xxt_enroll';
$sql[] = 'drop table if exists xxt_enroll_page';
$sql[] = 'drop table if exists xxt_enroll_round';
$sql[] = 'drop table if exists xxt_enroll_receiver';
$sql[] = 'drop table if exists xxt_enroll_record';
$sql[] = 'drop table if exists xxt_enroll_record_data';
$sql[] = 'drop table if exists xxt_enroll_record_remark';
$sql[] = 'drop table if exists xxt_enroll_record_score';
$sql[] = 'drop table if exists xxt_enroll_lottery';
$sql[] = 'drop table if exists xxt_enroll_lottery_round';
/**
 * 轮盘抽奖活动
 */
$sql[] = "drop table if exists xxt_lottery";
$sql[] = "drop table if exists xxt_lottery_award";
$sql[] = "drop table if exists xxt_lottery_plate";
$sql[] = "drop table if exists xxt_lottery_log";
$sql[] = "drop table if exists xxt_lottery_task_log";
/**
 * 签到
 */
$sql[] = 'drop table if exists xxt_checkin';
$sql[] = 'drop table if exists xxt_checkin_log';
/*
 * 微信墙
 */
$sql[] = 'drop table if exists xxt_wall';
$sql[] = 'drop table if exists xxt_wall_enroll';
$sql[] = 'drop table if exists xxt_wall_log';
/**
 * 素材共享
 */
$sql[] = 'drop table if exists xxt_shop_matter';
/**
 * 执行操作
 */
foreach ($sql as $s) {
	if (!$mysqli->query($s)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo 'drop all finished.';