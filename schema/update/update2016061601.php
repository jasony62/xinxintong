<?php
require_once '../../db.php';
$sqls = array();
//
$sqls[] = "alter table xxt_article change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_article_remark change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_article_score change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_article_download_log change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_article_review_log change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_link change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_text change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_news change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_news_review_log change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_channel change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_tmplmsg change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_matter_acl change mpid mpid varchar(32) not null default ''";
//
$sqls[] = "alter table xxt_enroll change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_enroll_page change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_enroll_round change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_enroll_receiver change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_enroll_record change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_enroll_signin_log change mpid mpid varchar(32) not null default ''";
//
$sqls[] = "alter table xxt_contribute change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_contribute_user change mpid mpid varchar(32) not null default ''";
//
$sqls[] = "alter table xxt_merchant_shop change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_page change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_staff change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_catelog change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_catelog_property change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_catelog_property_value change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_catelog_sku change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_catelog_sku_value change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_product change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_product_sku change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_product_gensku_log change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_group change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_group_product change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_order_property change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_order_feedback_property change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_order change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_merchant_order_sku change mpid mpid varchar(32) not null default ''";
//
$sqls[] = "alter table xxt_coin_log change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_coin_rule change mpid mpid varchar(32) not null default ''";
//
$sqls[] = "alter table xxt_mission change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_mission_matter change mpid mpid varchar(32) not null default ''";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;