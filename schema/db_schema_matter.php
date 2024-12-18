<?php
require_once '../db.php';
/**
 * 文章
 */
$sql = "create table if not exists xxt_article(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",entry text"; // 创建图文的入口，管理端，投稿活动等
$sql .= ",creater varchar(40) not null default ''"; //accountid/fid
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''"; //accountid/fid
$sql .= ",modifier_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modify_at int not null";
$sql .= ",public_visible char(1) not null default 'N'"; // should be removed
$sql .= ",state tinyint not null default 1"; //0:stop,1:normal
$sql .= ",title varchar(70) not null";
$sql .= ",start_at int not null default 0"; // 发布时间
$sql .= ",author varchar(16) not null"; // 作者
$sql .= ",pic text null"; // head image.
$sql .= ",pic2 text null"; // head image. 用于微信分享卡片中
$sql .= ",thumbnail longtext null"; // 缩略图
$sql .= ",entry_rule text null";
$sql .= ",download_rule text null";
$sql .= ",mission_id int not null default 0"; // 所属项目
$sql .= ",hide_pic char(1) not null default 'N'"; // hide head image in body of article.
$sql .= ",can_picviewer char(1) not null default 'N'";
$sql .= ",can_share char(1) not null default 'N'";
$sql .= ",can_fullsearch char(1) not null default 'Y'"; // 是否可以进行全文检索
$sql .= ",can_discuss char(1) not null default 'N'"; // 是否可以进行留言
$sql .= ",can_coinpay char(1) not null default 'N'"; // 是否可以进行打赏
$sql .= ",can_siteuser char(1) not null default 'Y'"; // 是否可以进入用户个人中心
$sql .= ",summary varchar(240) not null default ''";
$sql .= ",url text null"; // 图文消息的原文地址，即点击“阅读原文”后的URL
$sql .= ",weight int default 0"; // 权重
$sql .= ",custom_body char(1) not null default 'N'";
$sql .= ",body longtext null";
$sql .= ",is_markdown char(1) not null default 'N'"; // 是否为Markdown格式
$sql .= ",body_md longtext null"; // Markdown格式的内容
$sql .= ",page_id int not null default 0"; // 定制页，should remove
$sql .= ",body_page_name varchar(13) not null default ''"; // 定制页
$sql .= ",finished char(1) not null default 'Y'"; // 完成编辑
$sql .= ",approved char(1) not null default 'Y'"; // 审核通过
$sql .= ",remark_notice char(1) not null default 'Y'"; // 接收留言提示 should be removed
$sql .= ",remark_notice_all char(1) not null default 'N'"; // 通知所有参与留言的人有新留言 should be removed
$sql .= ",read_num int not null default 0"; // 阅读数
$sql .= ",score int not null default 0"; // 点赞数
$sql .= ",remark_num int not null default 0"; // 留言数
$sql .= ",share_friend_num int not null default 0"; // 分享给好友数
$sql .= ",share_timeline_num int not null default 0"; // 分享朋友圈数
$sql .= ",has_attachment char(1) not null default 'N'";
$sql .= ",download_num int not null default 0"; // 附件下载数
$sql .= ",copy_num int not null default 0"; // 复制数
$sql .= ",media_id varchar(256) not null default ''";
$sql .= ",upload_at int not null default 0";
$sql .= ",use_site_header char(1) not null default 'Y'"; // 使用站点页眉
$sql .= ",use_site_footer char(1) not null default 'Y'"; // 使用站点页脚
$sql .= ",use_mission_header char(1) not null default 'Y'"; // 使用项目页眉
$sql .= ",use_mission_footer char(1) not null default 'Y'"; // 使用项目页脚
$sql .= ",from_mode char(1) not null default 'O'"; // 素材来源类型O:origin、C:cite、D:duplicate、S(同一个团队中的复制)
$sql .= ",from_siteid varchar(32) not null default ''";
$sql .= ",from_site_name varchar(50) not null default ''";
$sql .= ",from_id int not null default 0";
$sql .= ",matter_cont_tag varchar(255) not null default ''";
$sql .= ",matter_mg_tag varchar(255) not null default ''";
$sql .= ",config text null"; // 页面设置
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
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''"; //accountid/fid
$sql .= ",modifier_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modify_at int not null";
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ",state tinyint not null default 1";
$sql .= ",title varchar(70) not null";
$sql .= ",start_at int not null default 0"; // 发布时间
$sql .= ",pic text null";
$sql .= ",pic2 text null";
$sql .= ",summary varchar(240) not null default ''";
$sql .= ",mission_id int not null default 0"; // 所属项目
$sql .= ",urlsrc int not null default '0' COMMENT 'url的来源，0：外部，1：多图文，2：频道'";
$sql .= ",url text";
$sql .= ",method varchar(6) not null default 'GET'";
$sql .= ",open_directly char(1) not null default 'N'";
$sql .= ",return_data char(1) not null default 'N'"; // 是否直接执行链接并返回数据
$sql .= ",embedded char(1) not null default 'N'"; // 将链接嵌入到页面中
$sql .= ",matter_mg_tag varchar(255) not null default ''";
$sql .= ",entry_rule text null"; // 参与规则
$sql .= ",config text null"; // 页面设置
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
  header('HTTP/1.0 500 Internal Server Error');
  echo 'database error: ' . $mysqli->error;
}
/**
 * 链接参数
 */
$sql = "create table if not exists xxt_link_param(";
$sql .= "id int not null auto_increment";
$sql .= ",link_id int not null";
$sql .= ",pname varchar(20) not null";
$sql .= ",pvalue varchar(255) not null";
$sql .= ",authapi_id int"; // id from xxt_member_authapi
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
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''"; //accountid/fid
$sql .= ",modifier_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modify_at int not null";
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ",state tinyint not null default 1"; //0:stop,1:normal
$sql .= ",content text";
$sql .= ",title text";
$sql .= ",matter_mg_tag varchar(255) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
  header('HTTP/1.0 500 Internal Server Error');
  echo 'database error: ' . $mysqli->error;
}
/**
 * 频道
 */
$sql = "create table if not exists xxt_channel(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modifier varchar(40) not null default ''"; //accountid/fid
$sql .= ",modifier_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modify_at int not null";
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ",state tinyint not null default 1"; //0:stop,1:normal
$sql .= ",title varchar(70) not null";
$sql .= ",start_at int not null default 0"; // 发布时间
$sql .= ",pic text null"; // head image.
$sql .= ",pic2 text null"; // head image.
$sql .= ",summary varchar(240) not null default ''";
$sql .= ",mission_id int not null default 0"; // 所属项目
$sql .= ",fixed_title varchar(70) not null default ''"; //代替第一个图文的标题作为频道的固定标题
$sql .= ",matter_type varchar(20)"; // article,link
$sql .= ",volume int not null default 5";
$sql .= ",top_type varchar(20)"; // article,link
$sql .= ",top_id varchar(40)";
$sql .= ",bottom_type varchar(20)"; // article,link
$sql .= ",bottom_id varchar(40)";
$sql .= ",orderby varchar(20) not null default 'time'";
$sql .= ",filter_by_matter_acl char(1) not null default 'Y'"; // 根据素材的访问控制进行过滤
$sql .= ",show_pic_in_page char(1) not null default 'Y'"; // 是否在页面中显示头图
$sql .= ",read_num int not null default 0"; // 阅读数
$sql .= ",share_friend_num int not null default 0"; // 分享给好友数
$sql .= ",share_timeline_num int not null default 0"; // 分享朋友圈数
$sql .= ",style_page_id int not null default 0"; // 样式
$sql .= ",style_page_name varchar(13) not null default ''"; // 样式
$sql .= ",header_page_id int not null default 0"; // 通用页头
$sql .= ",header_page_name varchar(13) not null default ''"; // 通用页头
$sql .= ",footer_page_id int not null default 0"; // 通用页尾
$sql .= ",footer_page_name varchar(13) not null default ''"; // 通用页尾
$sql .= ",matter_mg_tag varchar(255) not null default ''";
$sql .= ",config text null"; // 页面设置
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
  header('HTTP/1.0 500 Internal Server Error');
  echo 'database error: ' . $mysqli->error;
}
/**
 * 组成频道的素材
 */
$sql = "create table if not exists xxt_channel_matter(";
$sql .= "channel_id int not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",create_at int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20)"; // article,kink
$sql .= ",seq int not null default 10000"; // 置顶小于10000， 置底大于20000
$sql .= ",primary key(channel_id,matter_id,matter_type)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
  header('HTTP/1.0 500 Internal Server Error');
  echo 'database error: ' . $mysqli->error;
}
/**
 * 模板消息
 */
$sql = "create table if not exists xxt_tmplmsg(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",templateid varchar(128) not null default ''";
$sql .= ",creator varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",state tinyint not null default 1";
$sql .= ",title varchar(70) not null";
$sql .= ",example text";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
  header('HTTP/1.0 500 Internal Server Error');
  echo 'database error: ' . $mysqli->error;
}
/**
 * 模板消息参数
 */
$sql = "create table if not exists xxt_tmplmsg_param(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",tmplmsg_id int not null";
$sql .= ",pname varchar(128) not null default ''";
$sql .= ",plabel varchar(255) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
  header('HTTP/1.0 500 Internal Server Error');
  echo 'database error: ' . $mysqli->error;
}
/**
 * 模板消息映射关系
 */
$sql = "create table if not exists xxt_tmplmsg_mapping(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null"; // 模版消息用在哪个站点，不一定是模版消息的站点
$sql .= ",msgid int not null";
$sql .= ",mapping text";
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
/**
 * 素材附件
 */
$sql = "create table if not exists xxt_matter_attachment(";
$sql .= "id int not null auto_increment";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type char(20) not null";
$sql .= ",name varchar(255) not null";
$sql .= ",type varchar(255) not null";
$sql .= ",size int not null";
$sql .= ",last_modified bigint(13) not null";
$sql .= ",url text null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
  header('HTTP/1.0 500 Internal Server Error');
  echo 'database error: ' . $mysqli->error;
}

echo 'finish matter.' . PHP_EOL;
