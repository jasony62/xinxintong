<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "update xxt_enroll set success_matter_type='enroll' where success_matter_type='activity'"; 
$sqls[] = "update xxt_enroll set failure_matter_type='enroll' where failure_matter_type='activity'";
$sqls[] = "update xxt_log_mpsend set matter_type='enroll' where matter_type='activity'";
$sqls[] = "update xxt_log_matter_read set matter_type='enroll' where matter_type='activity'";
$sqls[] = "update xxt_log_matter_share set matter_type='enroll' where matter_type='activity'";
$sqls[] = "update xxt_log_matter_action set matter_type='enroll' where matter_type='activity'";
$sqls[] = "update xxt_news_matter set matter_type='enroll' where matter_type='activity'";
$sqls[] = "update xxt_channel set matter_type='enroll' where matter_type='activity'";
$sqls[] = "update xxt_channel set top_type='enroll' where top_type='activity'";
$sqls[] = "update xxt_channel set bottom_type='enroll' where bottom_type='activity'";
$sqls[] = "update xxt_channel_matter set matter_type='enroll' where matter_type='activity'";
$sqls[] = "update xxt_matter_acl set matter_type='enroll' where matter_type='activity'";
$sqls[] = "update xxt_call_text set matter_type='enroll' where matter_type='activity'";
$sqls[] = "update xxt_call_menu set matter_type='enroll' where matter_type='activity'";
$sqls[] = "update xxt_call_qrcode set matter_type='enroll' where matter_type='activity'";
$sqls[] = "update xxt_call_other set matter_type='enroll' where matter_type='activity'";
$sqls[] = "update xxt_shop_matter set matter_type='enroll' where matter_type='activity'";

$sqls[] = "update xxt_enroll set success_matter_type='enrollsignin' where success_matter_type='activitysignin'"; 
$sqls[] = "update xxt_enroll set failure_matter_type='enrollsignin' where failure_matter_type='activitysignin'";
$sqls[] = "update xxt_log_mpsend set matter_type='enrollsignin' where matter_type='activitysignin'";
$sqls[] = "update xxt_log_matter_read set matter_type='enrollsignin' where matter_type='activitysignin'";
$sqls[] = "update xxt_log_matter_share set matter_type='enrollsignin' where matter_type='activitysignin'";
$sqls[] = "update xxt_log_matter_action set matter_type='enrollsignin' where matter_type='activitysignin'";
$sqls[] = "update xxt_news_matter set matter_type='enrollsignin' where matter_type='activitysignin'";
$sqls[] = "update xxt_channel set matter_type='enrollsignin' where matter_type='activitysignin'";
$sqls[] = "update xxt_channel set top_type='enrollsignin' where top_type='activitysignin'";
$sqls[] = "update xxt_channel set bottom_type='enrollsignin' where bottom_type='activitysignin'";
$sqls[] = "update xxt_channel_matter set matter_type='enrollsignin' where matter_type='activitysignin'";
$sqls[] = "update xxt_matter_acl set matter_type='enrollsignin' where matter_type='activitysignin'";
$sqls[] = "update xxt_call_text set matter_type='enrollsignin' where matter_type='activitysignin'";
$sqls[] = "update xxt_call_menu set matter_type='enrollsignin' where matter_type='activitysignin'";
$sqls[] = "update xxt_call_qrcode set matter_type='enrollsignin' where matter_type='activitysignin'";
$sqls[] = "update xxt_call_other set matter_type='enrollsignin' where matter_type='activitysignin'";
$sqls[] = "update xxt_shop_matter set matter_type='enrollsignin' where matter_type='activitysignin'";

$sqls[] = "update xxt_enroll set success_matter_type='wall' where success_matter_type='discuss'"; 
$sqls[] = "update xxt_enroll set failure_matter_type='wall' where failure_matter_type='discuss'";
$sqls[] = "update xxt_log_mpsend set matter_type='wall' where matter_type='discuss'";
$sqls[] = "update xxt_log_matter_read set matter_type='wall' where matter_type='discuss'";
$sqls[] = "update xxt_log_matter_share set matter_type='wall' where matter_type='discuss'";
$sqls[] = "update xxt_log_matter_action set matter_type='wall' where matter_type='discuss'";
$sqls[] = "update xxt_news_matter set matter_type='wall' where matter_type='discuss'";
$sqls[] = "update xxt_channel set matter_type='wall' where matter_type='discuss'";
$sqls[] = "update xxt_channel set top_type='wall' where top_type='discuss'";
$sqls[] = "update xxt_channel set bottom_type='wall' where bottom_type='discuss'";
$sqls[] = "update xxt_channel_matter set matter_type='wall' where matter_type='discuss'";
$sqls[] = "update xxt_matter_acl set matter_type='wall' where matter_type='discuss'";
$sqls[] = "update xxt_call_text set matter_type='wall' where matter_type='discuss'";
$sqls[] = "update xxt_call_menu set matter_type='wall' where matter_type='discuss'";
$sqls[] = "update xxt_call_qrcode set matter_type='wall' where matter_type='discuss'";
$sqls[] = "update xxt_call_other set matter_type='wall' where matter_type='discuss'";
$sqls[] = "update xxt_shop_matter set matter_type='wall' where matter_type='discuss'";

$sqls[] = "update xxt_mppermission set permission='app' where permission='activity'";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
