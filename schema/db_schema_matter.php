<?php
require_once '../db.php';
/**
 * 文章
 */
$sql = "create table if not exists xxt_article(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mpid varchar(32) not null";
$sql .= ",entry text"; // 创建图文的入口，管理端，投稿活动等
$sql .= ",target_mps text"; // 发布到哪个子账号
$sql .= ",creater varchar(40) not null default ''"; //accountid/fid
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",creater_src char(1)"; //A:accouont|F:fans|M:member
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''"; //accountid/fid
$sql .= ",modifier_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modifier_src char(1)"; //A:accouont|F:fans|M:member
$sql .= ',modify_at int not null';
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ",state tinyint not null default 1"; //0:stop,1:normal
$sql .= ",title varchar(70) not null";
$sql .= ",author varchar(16) not null"; // 作者
$sql .= ",pic text"; // head image.
$sql .= ",mission_id int not null default 0"; // 所属项目
$sql .= ",hide_pic char(1) not null default 'N'"; // hide head image in body of article.
$sql .= ",can_picviewer char(1) not null default 'N'";
$sql .= ",can_share char(1) not null default 'N'";
$sql .= ",can_fullsearch char(1) not null default 'Y'"; // 是否可以进行全文检索
$sql .= ",summary varchar(240) not null";
$sql .= ",url text"; // 图文消息的原文地址，即点击“阅读原文”后的URL
$sql .= ",weight int default 0"; // 权重
$sql .= ",custom_body char(1) not null default 'N'";
$sql .= ",body text";
$sql .= ",page_id int not null default 0"; // 定制页，should remove
$sql .= ",body_page_name varchar(13) not null default ''"; // 定制页
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",finished char(1) not null default 'Y'"; // 完成编辑
$sql .= ",approved char(1) not null default 'Y'"; // 审核通过
$sql .= ",remark_notice char(1) not null default 'Y'"; // 接收评论提示
$sql .= ",remark_notice_all char(1) not null default 'N'"; // 通知所有参与评论的人有新评论
$sql .= ",read_num int not null default 0"; // 阅读数
$sql .= ",score int not null default 0"; // 点赞数
$sql .= ",remark_num int not null default 0"; // 评论数
$sql .= ",share_friend_num int not null default 0"; // 分享给好友数
$sql .= ",share_timeline_num int not null default 0"; // 分享朋友圈数
$sql .= ",has_attachment char(1) not null default 'N'";
$sql .= ",download_num int not null default 0"; // 附件下载数
$sql .= ",media_id varchar(256) not null default ''";
$sql .= ",upload_at int not null default 0";
$sql .= ",use_site_header char(1) not null default 'Y'"; // 使用站点页眉
$sql .= ",use_site_footer char(1) not null default 'Y'"; // 使用站点页脚
$sql .= ",use_mission_header char(1) not null default 'Y'"; // 使用项目页眉
$sql .= ",use_mission_footer char(1) not null default 'Y'"; // 使用项目页脚
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 文章的扩展信息
 */
$sql = "create table if not exists xxt_article_extinfo(";
$sql .= 'article_id int not null';
$sql .= ',occured_time int not null default 0';
$sql .= ',occured_year int not null default 0'; //yyyy
$sql .= ',occured_month int not null default 0'; // 1-12
$sql .= ',occured_day int not null default 0'; // 1-31
$sql .= ',occured_hour int not null default 0'; // 0-23
$sql .= ',occured_lat double(10,6) not null default 0';
$sql .= ',occured_lng double(10,6) not null default 0';
$sql .= ',occured_place text';
$sql .= ",primary key(article_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 文章的扩展信息（事件距离，临时表）
 */
$sql = "create table if not exists xxt_article_ext_distance(";
$sql .= 'article_id_a int not null';
$sql .= ',article_id_b int not null';
$sql .= ',distance int not null default 0';
$sql .= ",primary key(article_id_a,article_id_b)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 文章评论
 */
$sql = "create table if not exists xxt_article_remark(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ",siteid varchar(32) not null";
$sql .= ",userid varchar(40) not null";
$sql .= ',article_id int not null';
$sql .= ',article_title varchar(70) not null';
$sql .= ',fid varchar(32) not null';
$sql .= ',openid varchar(255) not null';
$sql .= ',nickname varchar(255) not null';
$sql .= ',create_at int not null';
$sql .= ',remark text';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 文章评分
 */
$sql = "create table if not exists xxt_article_score(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ",siteid varchar(32) not null";
$sql .= ",userid varchar(40) not null";
$sql .= ',vid varchar(32) not null';
$sql .= ',openid varchar(255) not null';
$sql .= ',nickname varchar(255) not null';
$sql .= ',article_id int not null';
$sql .= ',article_title varchar(70) not null';
$sql .= ',create_at int not null';
$sql .= ',score int not null default 0';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 文章附件
 */
$sql = "create table if not exists xxt_article_attachment(";
$sql .= 'id int not null auto_increment';
$sql .= ',article_id int not null';
$sql .= ',name varchar(255) not null';
$sql .= ',type varchar(255) not null';
$sql .= ',size int not null';
$sql .= ',last_modified bigint(13) not null';
$sql .= ',url text';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 文章发布过程日志
 */
$sql = "create table if not exists xxt_article_download_log(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",userid varchar(40) not null";
$sql .= ",vid varchar(32) not null";
$sql .= ",openid varchar(255) not null";
$sql .= ",nickname varchar(255) not null";
$sql .= ",download_at int not null";
$sql .= ",mpid varchar(32) not null";
$sql .= ",article_id int not null";
$sql .= ",attachment_id int not null";
$sql .= ",user_agent text";
$sql .= ",client_ip varchar(40) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 文章发布过程日志
 */
$sql = "create table if not exists xxt_article_review_log(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ",siteid varchar(32) not null";
$sql .= ',article_id int not null';
$sql .= ',seq int not null';
$sql .= ',mid varchar(32) not null';
$sql .= ',disposer_name varchar(255) not null';
$sql .= ',send_at int not null';
$sql .= ',receive_at int not null default 0';
$sql .= ',read_at int not null default 0';
$sql .= ',close_at int not null default 0';
$sql .= ',phase char(1) not null'; // Review|Typeset
$sql .= ",state char(1) not null"; // Pending|Disposing|Forward|Close
$sql .= ',remark text';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 外部链接
 */
$sql = "create table if not exists xxt_link(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mpid varchar(32) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",creater_src char(1) not null default 'A'"; //A:accouont|F:fans|M:member
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''"; //accountid/fid
$sql .= ",modifier_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modifier_src char(1) not null default 'A'"; //A:accouont|F:fans|M:member
$sql .= ',modify_at int not null';
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ',state tinyint not null default 1';
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
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
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
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 文本素材
 */
$sql = "create table if not exists xxt_text(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mpid varchar(32) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",creater_src char(1) default 'A'"; //A:accouont|F:fans|M:member
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''"; //accountid/fid
$sql .= ",modifier_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modifier_src char(1) default 'A'"; //A:accouont|F:fans|M:member
$sql .= ',modify_at int not null';
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ",state tinyint not null default 1"; //0:stop,1:normal
$sql .= ",content text";
$sql .= ",title text";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 多图文
 */
$sql = "create table if not exists xxt_news(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",creater_src char(1) not null default 'A'"; //A:accouont|F:fans|M:member
$sql .= ",modifier varchar(40) not null default ''"; //accountid/fid
$sql .= ",modifier_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modifier_src char(1)"; //A:accouont|F:fans|M:member
$sql .= ',modify_at int not null';
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ',state tinyint not null default 1'; //0:stop,1:normal
$sql .= ',title varchar(70) not null';
$sql .= ",pic text"; // head image.
$sql .= ",summary varchar(240) not null";
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",filter_by_matter_acl char(1) not null default 'Y'"; // 根据素材的访问控制进行过滤
$sql .= ',empty_reply_type varchar(20) not null';
$sql .= ',empty_reply_id varchar(40) not null';
$sql .= ",read_num int not null default 0"; // 阅读数
$sql .= ",share_friend_num int not null default 0"; // 分享给好友数
$sql .= ",share_timeline_num int not null default 0"; // 分享朋友圈数
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 多图文发布过程日志
 */
$sql = "create table if not exists xxt_news_review_log(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',news_id int not null';
$sql .= ',seq int not null';
$sql .= ',mid varchar(32) not null';
$sql .= ',send_at int not null';
$sql .= ',receive_at int not null default 0';
$sql .= ',read_at int not null default 0';
$sql .= ',close_at int not null default 0';
$sql .= ',phase char(1) not null'; // Review|Typeset
$sql .= ",state char(1) not null"; // Pending|Disposing|Forward|Close
$sql .= ',remark text';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 组成新闻的素材
 */
$sql = "create table if not exists xxt_news_matter(";
$sql .= 'news_id int not null';
$sql .= ',matter_id varchar(40) not null';
$sql .= ',matter_type varchar(20)'; //
$sql .= ',seq int not null';
$sql .= ",primary key(news_id,matter_id,matter_type)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 频道
 */
$sql = "create table if not exists xxt_channel(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",creater_src char(1) not null default 'A'"; //A:accouont|F:fans|M:member
$sql .= ",modifier varchar(40) not null default ''"; //accountid/fid
$sql .= ",modifier_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modifier_src char(1)"; //A:accouont|F:fans|M:member
$sql .= ',modify_at int not null';
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ',state tinyint not null default 1'; //0:stop,1:normal
$sql .= ',title varchar(70) not null';
$sql .= ",pic text"; // head image.
$sql .= ",summary varchar(240) not null";
$sql .= ',fixed_title varchar(70) not null'; //代替第一个图文的标题作为频道的固定标题
$sql .= ',matter_type varchar(20)'; // article,link
$sql .= ',volume int not null default 5';
$sql .= ',top_type varchar(20)'; // article,link
$sql .= ',top_id varchar(40)';
$sql .= ',bottom_type varchar(20)'; // article,link
$sql .= ',bottom_id varchar(40)';
$sql .= ",orderby varchar(20) not null default 'time'";
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",filter_by_matter_acl char(1) not null default 'Y'"; // 根据素材的访问控制进行过滤
$sql .= ",show_pic_in_page char(1) not null default 'Y'"; // 是否在页面中显示头图
$sql .= ",read_num int not null default 0"; // 阅读数
$sql .= ",share_friend_num int not null default 0"; // 分享给好友数
$sql .= ",share_timeline_num int not null default 0"; // 分享朋友圈数
$sql .= ',style_page_id int not null default 0'; // 样式
$sql .= ",style_page_name varchar(13) not null default ''"; // 样式
$sql .= ",header_page_id int not null default 0"; // 通用页头
$sql .= ",header_page_name varchar(13) not null default ''"; // 通用页头
$sql .= ",footer_page_id int not null default 0"; // 通用页尾
$sql .= ",footer_page_name varchar(13) not null default ''"; // 通用页尾
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 组成频道的素材
 */
$sql = "create table if not exists xxt_channel_matter(";
$sql .= 'channel_id int not null';
$sql .= ',creater varchar(40) not null';
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",creater_src char(1)"; //A:accouont|F:fans
$sql .= ',create_at int not null';
$sql .= ',matter_id varchar(40) not null';
$sql .= ',matter_type varchar(20)'; // article,kink
$sql .= ",primary key(channel_id,matter_id,matter_type)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
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
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 模板消息
 */
$sql = "create table if not exists xxt_tmplmsg(";
$sql .= 'id int not null auto_increment';
$sql .= ",siteid varchar(32) not null";
$sql .= ',mpid varchar(32) not null';
$sql .= ',templateid varchar(128) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ',state tinyint not null default 1';
$sql .= ',title varchar(70) not null';
$sql .= ',example text';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
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
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 模板消息映射关系
 */
$sql = "create table if not exists xxt_tmplmsg_mapping(";
$sql .= 'id int not null auto_increment';
$sql .= ',msgid int not null';
$sql .= ',mapping text';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 回复访问控制列表
 */
$sql = "create table if not exists xxt_matter_acl(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mpid varchar(32) not null";
$sql .= ",matter_type char(20) not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",identity varchar(100) not null";
$sql .= ",idsrc char(2) not null default ''";
$sql .= ",label varchar(255) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}

echo 'finish matter.' . PHP_EOL;
