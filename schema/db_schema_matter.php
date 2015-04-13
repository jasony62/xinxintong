<?php
require_once '../db.php';
/**
 * 文章
 */
$sql = "create table if not exists xxt_article(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",writer varchar(255) not null default ''"; //openid
$sql .= ",src char(2) not null default ''";
$sql .= ',create_at int not null';
$sql .= ',modify_at int not null';
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ',state tinyint not null default 1'; //0:stop,1:normal
$sql .= ',used int not null default 0';
$sql .= ',code varchar(6) not null'; //文章的编码
$sql .= ',title varchar(70) not null';
$sql .= ',pic text'; // head image.
$sql .= ",hide_pic char(1) not null default 'N'"; // hide head image in body of article.
$sql .= ",can_carousel char(1) not null default 'N'";
$sql .= ",can_picviewer char(1) not null default 'N'";
$sql .= ",can_share char(1) not null default 'N'";
$sql .= ',summary varchar(240) not null';
$sql .= ',url text';
$sql .= ",custom_body char(1) not null default 'N'";
$sql .= ',body text';
$sql .= ',css text';
$sql .= ',page_id int not null default 0';
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",approved char(1) not null default 'Y'"; // 审核通过
$sql .= ",remark_notice char(1) not null default 'Y'"; // 接收评论提示 
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 文章所属的频道
 */
$sql = "create table if not exists xxt_article_channel(";
$sql .= 'article_id int not null';
$sql .= ',channel_id int not null';
$sql .= ',create_at int not null';
$sql .= ",primary key(article_id,channel_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 文章评论
 */
$sql = "create table if not exists xxt_article_remark(";
$sql .= 'id int not null auto_increment';
$sql .= ',article_id int not null';
$sql .= ',mid varchar(32) not null';
$sql .= ',create_at int not null';
$sql .= ',remark text';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 文章评分
 */
$sql = "create table if not exists xxt_article_score(";
$sql .= 'id int not null auto_increment';
$sql .= ',vid varchar(32) not null';
$sql .= ',article_id int not null';
$sql .= ',create_at int not null';
$sql .= ',score int not null default 0';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 外部链接
 */ 
$sql = "create table if not exists xxt_link(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ',state tinyint not null default 1';
$sql .= ',used int not null default 0';
$sql .= ',title varchar(70) not null';
$sql .= ',pic text';
$sql .= ',summary varchar(240) not null';
$sql .= ",urlsrc int not null default '0' COMMENT 'url的来源，0：外部，1：多图文'";
$sql .= ',url text';
$sql .= ',method varchar(6) not null default "GET"';
$sql .= ",open_directly char(1) not null default 'N'";
$sql .= ",return_data char(1) not null default 'N'"; // 是否直接执行链接并返回数据
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",fans_only char(1) not null default 'N'"; // 仅限关注用户打开
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * {{mpid}}
 * {{src}}
 * {{openid}}
 * {{authed_identity}}
 */
$sql = "create table if not exists xxt_link_param(";
$sql .= 'id int not null auto_increment';
$sql .= ',link_id int not null';
$sql .= ',pname varchar(20) not null';
$sql .= ',pvalue varchar(255) not null';
$sql .= ',authapi_id int'; // id from xxt_member_authapi
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
$sql = "create table if not exists xxt_link_channel(";
$sql .= 'link_id int not null';
$sql .= ',channel_id int not null';
$sql .= ',create_at int not null';
$sql .= ",primary key(link_id,channel_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 文本素材
 */ 
$sql = "create table if not exists xxt_text(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ',state tinyint not null default 1'; //0:stop,1:normal
$sql .= ',used int not null default 0';
$sql .= ',content text';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 多图文
 */ 
$sql = "create table if not exists xxt_news(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ',state tinyint not null default 1'; //0:stop,1:normal
$sql .= ',used int not null default 0';
$sql .= ',title varchar(70) not null';
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",filter_by_matter_acl char(1) not null default 'Y'"; // 根据素材的访问控制进行过滤
$sql .= ',empty_reply_type varchar(14) not null';
$sql .= ',empty_reply_id varchar(128) not null';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
// 组成新闻的文章
$sql = "create table if not exists xxt_news_matter(";
$sql .= 'news_id int not null';
$sql .= ',matter_id varchar(128) not null';
$sql .= ',matter_type varchar(10)'; // Article,Link
$sql .= ',seq int not null';
$sql .= ",primary key(news_id,matter_id,matter_type)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 频道
 */ 
$sql = "create table if not exists xxt_channel(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ',state tinyint not null default 1'; //0:stop,1:normal
$sql .= ',used int not null default 0';
$sql .= ',title varchar(70) not null';
$sql .= ',fixed_title varchar(70) not null'; //代替第一个图文的标题作为频道的固定标题
$sql .= ',volume int not null default 5';
$sql .= ',top_type varchar(10)'; // Article,Link
$sql .= ',top_id int';
$sql .= ',bottom_type varchar(10)'; // Article,Link
$sql .= ',bottom_id int';
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 内置素材
 * 1、通讯录
 * 2、个人身份信息（已去除）
 * 3、翻译
 * 4、按关键字搜索文章
 * 5、用户注册
 * 6、投稿箱
 * 7、按编号搜索文章
 * 8、按编号搜索活动
 * 9、我发起的活动
 */ 
$sql = "create table if not exists xxt_inner(";
$sql .= 'id int not null';
$sql .= ',title varchar(70) not null';
$sql .= ',name varchar(30) not null';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
// 回复访问控制列表 
// A:article,N:news,C:channel,L:link
$sql = "create table if not exists xxt_matter_acl(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',matter_type char(1) not null';
$sql .= ',matter_id varchar(128) not null';
$sql .= ',identity varchar(100) not null'; 
$sql .= ",idsrc char(2) not null default ''";
$sql .= ",label varchar(255) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 模板消息 
 */ 
$sql = "create table if not exists xxt_tmplmsg(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',templateid varchar(128) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ',state tinyint not null default 1';
$sql .= ',title varchar(70) not null';
$sql .= ',example text';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 模板消息参数
 */
$sql = "create table if not exists xxt_tmplmsg_param(";
$sql .= 'id int not null auto_increment';
$sql .= ',tmplmsg_id int not null';
$sql .= ',pname varchar(128) not null';
$sql .= ',plabel varchar(255) not null';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
echo 'finish matter.'.PHP_EOL;
