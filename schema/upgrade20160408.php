<?php
require_once '../db.php';
//
$sql = "xxt_mpaccount";
$sql = "xxt_mpsetting";
$sql = "xxt_mpadministrator";
//
$sql = "xxt_call_text";
$sql = "insert into xxt_call_text_yx select from xxt_call_text_yx where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sql = "xxt_call_menu";
$sql = "xxt_call_qrcode";
$sql = "xxt_call_other";
//
$sql = "update set siteid=mpid from xxt_log_matter_read";
$sql = "update set siteid=mpid from xxt_log_matter_share";
$sql = "update set siteid=mpid from xxt_log_user_action";
$sql = "update set siteid=mpid from xxt_log_user_matter";
$sql = "update set siteid=mpid from xxt_log_matter_action";
//
$sql = "update set siteid=mpid from xxt_contribute";
$sql = "update set siteid=mpid from xxt_contribute_user";
//
$sql = "update set siteid=mpid from xxt_enroll";
$sql = "update set siteid=mpid from xxt_enroll_record";
$sql = "update set siteid=mpid from xxt_enroll_signin_log";
//
$sql = "update set siteid=mpid from xxt_lottery";
$sql = "update set siteid=mpid from xxt_lottery_task";
$sql = "update set siteid=mpid from xxt_lottery_award";
$sql = "update set siteid=mpid from xxt_lottery_plate";
$sql = "update set siteid=mpid from xxt_lottery_log";
//
$sql = "update set siteid=mpid from xxt_article";
$sql = "update set siteid=mpid from xxt_article_remark";
$sql = "update set siteid=mpid from xxt_article_score";
$sql = "update set siteid=mpid from xxt_article_review_log";
//
$sql = "update set siteid=mpid from xxt_news";
$sql = "update set siteid=mpid from xxt_channel";
$sql = "update set siteid=mpid from xxt_channel";
//
$sql = "update set siteid=mpid from xxt_mission";
//
