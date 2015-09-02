<?php
require_once '../db.php';
/*
 * 商店
 */
$sql = 'create table if not exists xxt_merchant_shop(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ',reviser varchar(40) not null';
$sql .= ",modify_at int not null";
$sql .= ",title varchar(70) not null";
$sql .= ',pic text';
$sql .= ',summary varchar(240) not null';
$sql .= ",approved char(1) not null default 'N'";
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 登记信息通知接收人
 */
$sql = "create table if not exists xxt_merchant_staff(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',shopid varchar(40) not null'; // contribute's id
$sql .= ',role char(1) not null'; // Initiator|Reviewer|Typesetter
$sql .= ',identity varchar(100) not null';
$sql .= ",idsrc char(2) not null default ''";
$sql .= ",label varchar(255) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 产品分类
 */
$sql = 'create table if not exists xxt_merchant_catelog(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',sid int not null'; // shop id
$sql .= ',creater varchar(40) not null';
$sql .= ",create_at int not null";
$sql .= ',reviser varchar(40) not null';
$sql .= ",modify_at int not null";
$sql .= ",parent_cate_id int not null default 0"; // 父分类ID
$sql .= ",name varchar(70) not null";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 产品分类属性定义
 */
$sql = 'create table if not exists xxt_merchant_catelog_property(';
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null';
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",reviser varchar(40) not null";
$sql .= ",modify_at int not null";
$sql .= ",name varchar(255) not null";
$sql .= ",seq int not null default 0";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 产品分类属性-值定义
 */
$sql = 'create table if not exists xxt_merchant_catelog_property_value(';
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null';
$sql .= ',prop_id int not null';
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",reviser varchar(40) not null";
$sql .= ",modify_at int not null";
$sql .= ",name varchar(255) not null";
$sql .= ",weight int not null default 0";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 产品分类sku定义
 */
$sql = 'create table if not exists xxt_merchant_catelog_sku(';
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null';
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",reviser varchar(40) not null";
$sql .= ",modify_at int not null";
$sql .= ",name varchar(255) not null";
$sql .= ",seq int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 产品分类属性-值定义
 */
$sql = 'create table if not exists xxt_merchant_catelog_sku_value(';
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null';
$sql .= ',sku_id int not null';
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",reviser varchar(40) not null";
$sql .= ",modify_at int not null";
$sql .= ",name varchar(255) not null";
$sql .= ",weight int not null default 0";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 产品
 */
$sql = 'create table if not exists xxt_merchant_product(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null'; // 所属分类ID
$sql .= ',creater varchar(40) not null';
$sql .= ",create_at int not null";
$sql .= ',reviser varchar(40) not null';
$sql .= ",modify_at int not null";
$sql .= ",name varchar(70) not null";
$sql .= ",main_img text";
$sql .= ",img text";
$sql .= ",detail_text varchar(240) not null";
$sql .= ",detail_img text";
$sql .= ",buy_limit int not null default 0";
$sql .= ",status int not null default 0"; // 0:未上架|1:已上架
$sql .= ",prop_value text"; // 属性ID及属性值ID
$sql .= ",sku_info text"; // 产品对应的sku定义
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 产品sku
 */
$sql = 'create table if not exists xxt_merchant_product_sku(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null'; // 所属分类ID
$sql .= ',prod_id int not null'; // 所属商品
$sql .= ',creater varchar(40) not null';
$sql .= ",create_at int not null";
$sql .= ',reviser varchar(40) not null';
$sql .= ",modify_at int not null";
$sql .= ',sku_value text';
$sql .= ",ori_price int not null default 0";
$sql .= ",price int not null default 0";
$sql .= ",icon_url text";
$sql .= ",quantity int not null default 0";
$sql .= ",product_code varchar(255) not null default ''";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 产品分组
 */
$sql = 'create table if not exists xxt_merchant_group(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',sid varchar(32) not null'; // shop id
$sql .= ',creater varchar(40) not null';
$sql .= ",create_at int not null";
$sql .= ',reviser varchar(40) not null';
$sql .= ",modify_at int not null";
$sql .= ",name varchar(70) not null";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 产品分组——产品
 */
$sql = 'create table if not exists xxt_merchant_group_product(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',sid varchar(32) not null'; // shop id
$sql .= ',group_id int not null';
$sql .= ",product_id int not null";
$sql .= ',seq int not null';
$sql .= ",modify_at int not null";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 产品订单
 */
$sql = 'create table if not exists xxt_merchant_order(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',sid varchar(32) not null';
$sql .= ',order_status int not null'; // 2-待发货, 3-已发货, 5-已完成, 8-维权中
$sql .= ",order_total_price int not null";
$sql .= ',order_create_time int not null';
$sql .= ',order_express_price int not null';
$sql .= ",buyer_openid varchar(255) not null default ''";
$sql .= ',buyer_nick varchar(255) not null';
$sql .= ',receiver_name varchar(255) not null';
$sql .= ',receiver_province varchar(20) not null';
$sql .= ',receiver_city varchar(20) not null';
$sql .= ',receiver_zone varchar(40) not null';
$sql .= ',receiver_addresss varchar(255) not null';
$sql .= ',receiver_mobile varchar(20) not null';
$sql .= ',receiver_phone varchar(20) not null';
$sql .= ',product_id int not null';
$sql .= ',product_name varchar(70) not null';
$sql .= ',product_price int not null';
$sql .= ',product_sku int not null';
$sql .= ',product_count int not null';
$sql .= ',product_img text';
$sql .= ",delivery_id int not null default 0";
$sql .= ",delivery_company varchar(255) not null default ''";
$sql .= ",trans_id varchar(255) not null default ''";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}

echo 'finish merchant.' . PHP_EOL;
