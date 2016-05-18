<?php
require_once '../db.php';
/*
 * 商店
 */
$sql = 'create table if not exists xxt_merchant_shop(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ",siteid varchar(32) not null";
$sql .= ',creater varchar(40) not null';
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ',modifier varchar(40) not null';
$sql .= ",modifier_name varchar(255) not null default ''";
$sql .= ",modify_at int not null";
$sql .= ',reviser varchar(40) not null';
$sql .= ",title varchar(70) not null";
$sql .= ',pic text';
$sql .= ',summary varchar(240) not null';
$sql .= ",approved char(1) not null default 'N'";
$sql .= ",buyer_api text"; // 记录客户信息的用户认证接口
$sql .= ",order_status text"; // 订单状态定义
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",payby varchar(255) not null default ''"; // 商店支持的支付方式，包括：coin（积分），wx（微信支付）
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 商店的页面
 */
$sql = 'create table if not exists xxt_merchant_page(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ",siteid varchar(32) not null";
$sql .= ',sid int not null'; //shop
$sql .= ",cate_id int not null default 0"; //catelog
$sql .= ",prod_id int not null default 0"; //product
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",type varchar(30) not null"; //shelf,order,orderlist,pay,payok,op.order,op.orderlist
$sql .= ",title varchar(70) not null default ''";
$sql .= ",name varchar(70) not null default ''";
$sql .= ",summary varchar(240) not null default ''";
$sql .= ',code_id int not null default 0'; // from xxt_code_page
$sql .= ",code_name varchar(13) not null default ''"; // from xxt_code_page
$sql .= ",seq int not null";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 客服人员
 */
$sql = "create table if not exists xxt_merchant_staff(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ",siteid varchar(32) not null";
$sql .= ',sid int not null';
$sql .= ',role char(1) not null';
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
$sql .= ",siteid varchar(32) not null";
$sql .= ',sid int not null'; // shop id
$sql .= ',creater varchar(40) not null';
$sql .= ",create_at int not null";
$sql .= ',reviser varchar(40) not null';
$sql .= ",modify_at int not null";
$sql .= ",parent_cate_id int not null default 0"; // 父分类ID
$sql .= ",pattern varchar(20) not null default 'basic'"; //basic:实物，place:场所，servie:服务
$sql .= ",name varchar(70) not null";
$sql .= ",has_validity char(1) not null default 'N'"; //是否有有效期
$sql .= ",submit_order_tmplmsg int not null default 0";
$sql .= ",pay_order_tmplmsg int not null default 0";
$sql .= ",feedback_order_tmplmsg int not null default 0";
$sql .= ",finish_order_tmplmsg int not null default 0";
$sql .= ",cancel_order_tmplmsg int not null default 0"; // 客服取消订单
$sql .= ",cus_cancel_order_tmplmsg int not null default 0"; // 客户取消订单
$sql .= ",used char(1) not null default 'N'"; //是否已经使用过
$sql .= ",disabled char(1) not null default 'N'"; //被禁用
$sql .= ",active char(1) not null default 'N'"; //是否已激活
$sql .= ",pages text"; //定制页配置状态
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 产品分类属性定义。定义商品的属性
 */
$sql = 'create table if not exists xxt_merchant_catelog_property(';
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null';
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",reviser varchar(40) not null";
$sql .= ",modify_at int not null";
$sql .= ",name varchar(255) not null";
$sql .= ",seq int not null default 0";
$sql .= ",used char(1) not null default 'N'"; //是否已经使用过
$sql .= ",disabled char(1) not null default 'N'"; //被禁用
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
$sql .= ",siteid varchar(32) not null";
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
 * 产品分类sku定义。定义sku的属性
 */
$sql = 'create table if not exists xxt_merchant_catelog_sku(';
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null'; // catelog id
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",reviser varchar(40) not null";
$sql .= ",modify_at int not null";
$sql .= ",name varchar(255) not null";
$sql .= ",has_validity char(1) not null default 'N'"; //是否有有效期
$sql .= ",require_pay char(1) not null default 'N'"; //是否需要进行支付
$sql .= ",can_autogen char(1) not null default 'N'"; //支持自动生成
$sql .= ",autogen_rule text"; //自动生成规则
$sql .= ",seq int not null default 0";
$sql .= ",used char(1) not null default 'N'"; //是否已经使用过
$sql .= ",disabled char(1) not null default 'N'"; //被禁用
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
$sql .= ",siteid varchar(32) not null";
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null'; // catelog id
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
$sql .= ",siteid varchar(32) not null";
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null'; // 所属分类ID
$sql .= ',creater varchar(40) not null';
$sql .= ",creater_src char(1) not null default 'A'";
$sql .= ",create_at int not null";
$sql .= ',reviser varchar(40) not null';
$sql .= ",modify_at int not null";
$sql .= ",name varchar(70) not null";
$sql .= ",main_img text";
$sql .= ",img text";
$sql .= ",detail_text text";
$sql .= ",detail_img text";
$sql .= ",buy_limit int not null default 0";
$sql .= ",status int not null default 0"; // 0:未上架|1:已上架
$sql .= ",prop_value text"; // 属性ID及属性值ID
$sql .= ",sku_info text"; // 产品对应的sku定义
$sql .= ",used char(1) not null default 'N'"; //是否已经使用过
$sql .= ",disabled char(1) not null default 'N'"; //被禁用
$sql .= ",active char(1) not null default 'N'"; //是否已激活
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
$sql .= ",siteid varchar(32) not null";
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null'; // 所属分类ID
$sql .= ',prod_id int not null'; // 所属商品
$sql .= ',cate_sku_id int not null'; // 分类sku定义
$sql .= ',creater varchar(40) not null';
$sql .= ",create_at int not null";
$sql .= ',reviser varchar(40) not null';
$sql .= ",modify_at int not null";
$sql .= ",summary text";
$sql .= ',sku_value text';
$sql .= ",ori_price int not null default 0";
$sql .= ",price int not null default 0";
$sql .= ",icon_url text";
$sql .= ",unlimited_quantity char(1) not null default 'N'"; //没有数量限制
$sql .= ",quantity int not null default 0";
$sql .= ",has_validity char(1) not null default 'N'"; //是否有有效期
$sql .= ",validity_begin_at int not null default 0";
$sql .= ",validity_end_at int not null default 0";
$sql .= ",product_code varchar(255) not null default ''";
$sql .= ",required char(1) not null default 'N'"; //必选项
$sql .= ",used char(1) not null default 'N'"; //是否已经使用过
$sql .= ",disabled char(1) not null default 'N'"; //被禁用
$sql .= ",active char(1) not null default 'N'"; //是否已激活
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 自动生成商品sku日志
 */
$sql = 'create table if not exists xxt_merchant_product_gensku_log(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ",siteid varchar(32) not null";
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null'; // 所属分类ID
$sql .= ',prod_id int not null'; // 所属商品
$sql .= ',creater varchar(40) not null';
$sql .= ",create_at int not null";
$sql .= ",begin_at int not null default 0";
$sql .= ",end_at int not null default 0";
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
$sql .= ",siteid varchar(32) not null";
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
$sql .= ",siteid varchar(32) not null";
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
 * 产品订单属性定义
 */
$sql = 'create table if not exists xxt_merchant_order_property(';
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null';
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",reviser varchar(40) not null";
$sql .= ",modify_at int not null";
$sql .= ",name varchar(255) not null";
$sql .= ",seq int not null default 0";
$sql .= ",used char(1) not null default 'N'"; //是否已经使用过
$sql .= ",disabled char(1) not null default 'N'"; //被禁用
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 产品订单反馈属性定义
 */
$sql = 'create table if not exists xxt_merchant_order_feedback_property(';
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null';
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",reviser varchar(40) not null";
$sql .= ",modify_at int not null";
$sql .= ",name varchar(255) not null";
$sql .= ",seq int not null default 0";
$sql .= ",used char(1) not null default 'N'"; //是否已经使用过
$sql .= ",disabled char(1) not null default 'N'"; //被禁用
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
$sql .= ',trade_no varchar(32) not null'; // 订单号
$sql .= ',mpid varchar(32) not null';
$sql .= ",siteid varchar(32) not null";
$sql .= ',sid varchar(32) not null';
$sql .= ",products text"; //订单包含的产品信息
$sql .= ',order_status int not null'; // 1-已提交（未支付），2-待发货（已支付）, 3-已发货（已确认）, 5-已完成, 8-维权中，-1-客服取消订单，-2，用户取消订单
$sql .= ",order_total_price int not null";
$sql .= ',order_create_time int not null';
$sql .= ',order_express_price int not null';
$sql .= ",ext_prop_value text"; // 扩展属性ID及属性值ID
$sql .= ",buyer_openid varchar(255) not null default ''";
$sql .= ",buyer_userid varchar(40) not null default ''";
$sql .= ',buyer_nick varchar(255) not null';
$sql .= ',receiver_name varchar(255) not null';
$sql .= ',receiver_province varchar(20) not null';
$sql .= ',receiver_city varchar(20) not null';
$sql .= ',receiver_zone varchar(40) not null';
$sql .= ',receiver_addresss varchar(255) not null';
$sql .= ',receiver_mobile varchar(20) not null';
$sql .= ',receiver_phone varchar(20) not null';
$sql .= ",receiver_email varchar(255) not null";
$sql .= ",delivery_id int not null default 0";
$sql .= ",delivery_company varchar(255) not null default ''";
$sql .= ",trans_id varchar(255) not null default ''";
$sql .= ",feedback text"; //反馈信息
$sql .= ",payby varchar(255) not null default ''"; // 订单的支付方式，商店支持的支付方式中的一种
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 订单包含的库存
 */
$sql = "create table if not exists xxt_merchant_order_sku(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",sid varchar(32) not null"; //商铺号
$sql .= ",oid int not null"; //订单号
$sql .= ",cate_id int not null";
$sql .= ",cate_sku_id int not null";
$sql .= ",prod_id int not null";
$sql .= ",sku_id int not null";
$sql .= ",sku_price int not null default 0";
$sql .= ",sku_count int not null default 1";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}

echo 'finish merchant.' . PHP_EOL;