<?php
require_once '../db.php';
/**
 * 轮盘抽奖活动
 */
$sql = "create table if not exists xxt_lottery(";
$sql .= "id varchar(40) not null"; //轮盘抽奖活动的ID
$sql .= ",siteid varchar(32) not null";
$sql .= ',creater varchar(40) not null';
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",creater_src char(1)"; //A:accouont|F:fans
$sql .= ',create_at int not null';
$sql .= ",modifier varchar(40) not null default ''"; //accountid/fid
$sql .= ",modifier_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modifier_src char(1)"; //A:accouont|F:fans|M:member
$sql .= ",modify_at int not null";
$sql .= ",title varchar(70) not null";
$sql .= ',pic text'; // 分享或生成链接时的图片
$sql .= ',summary varchar(240) not null'; // 分享或生成链接时的摘要
$sql .= ",start_at int not null default 0"; // 开始时间
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",fans_only char(1) not null default 'N'"; //仅限关注用户抽奖
$sql .= ",fans_enter_only char(1) not null default 'N'"; //仅限关注用户进入
$sql .= ",access_control char(1) not null default 'N'"; //仅限认证用户
$sql .= ',authapis text';
$sql .= ",pretask char(1) not null default 'N'"; //前置活动连接
$sql .= ",pretaskdesc text"; //前置活动链接
$sql .= ",pretaskcount char(1) not null default 'F'"; //前置活动链接
$sql .= ",chance int not null default 1"; // 可以抽奖的次数
$sql .= ",period char(1) not null default 'A'"; // A:accumulate;D:day,W:week,M:month,Y:year
$sql .= ',nonfans_alert text'; //非关注用户提示
$sql .= ',nochance_alert text'; //没有抽奖机会提示
$sql .= ',nostart_alert text'; //活动没有开始提示
$sql .= ',hasend_alert text'; //活动没有结束提示
$sql .= ",show_greeting char(1) not null default 'Y'"; //是否显示中奖词
$sql .= ",show_winners char(1) not null default 'N'"; //显示获奖人名单
$sql .= ',page_id int not null default 0';
$sql .= ",page_code_name varchar(13) not null default ''";
$sql .= ",autostop char(1) not null default 'Y'";
$sql .= ",maxstep int not null default 60";
$sql .= ",active char(1) not null default 'N'"; //激活状态
$sql .= ",state tinyint not null default 1"; //0:删除,1:配置,2:运行
$sql .= ",read_num int not null default 0"; // 阅读数
$sql .= ",share_friend_num int not null default 0"; // 分享给好友数
$sql .= ",share_timeline_num int not null default 0"; // 分享朋友圈数
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_lottery): ' . $sql . ':' . $mysqli->error;
}
/**
 * 抽奖任务
 * 1、前置任务（canPlay），只有完成才允许抽奖
 * 2、机会任务（addChance），完成后增加
 */
$sql = "create table if not exists xxt_lottery_task(";
$sql .= "siteid varchar(32) not null";
$sql .= ",lid varchar(40) not null"; //抽奖活动的ID
$sql .= ",tid varchar(32) not null"; //任务ID
$sql .= ",title varchar(20) not null";
$sql .= ",task_type varchar(20) not null"; // 任务类型,can_play,add_chance
$sql .= ",task_name varchar(20) not null"; // 任务名称,sns_share
$sql .= ",task_params text"; // 任务参数
$sql .= ",description text"; // 任务提示
$sql .= ",primary key(tid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_lottery_task): ' . $sql . ':' . $mysqli->error;
}
//
$sql = "create table if not exists xxt_lottery_task_log(";
$sql .= "id int not null auto_increment";
$sql .= ",lid varchar(40) not null"; //轮盘抽奖活动的ID
$sql .= ",tid varchar(32) not null"; //任务ID
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ',create_at int not null'; // 抽奖的时间
$sql .= ",finished char(1) not null default 'N'"; // 任务是否已经完成
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_lottery_task_log): ' . $sql . ':' . $mysqli->error;
}
//
$sql = "create table if not exists xxt_lottery_award(";
$sql .= "siteid varchar(32) not null";
$sql .= ",lid varchar(40) not null"; //轮盘抽奖活动的ID
$sql .= ",aid varchar(40) not null"; //奖品的ID
$sql .= ",title varchar(20) not null";
$sql .= ",description text";
$sql .= ",pic text";
$sql .= ',prob int not null'; //奖品的概率
$sql .= ",type int not null default 0"; //奖品的类型，0：没有奖品；1：应用内积分；2：再来一次；3：执行任务；99：实物奖品
$sql .= ',taskid varchar(32) not null'; //任务ID，仅当type==3时有效
$sql .= ",period char(1) not null default 'A'"; // A:accumulate;D:day,W:week,M:month,Y:year
$sql .= ",quantity int not null default 0"; //奖品的参数，例如：【积分】的数量，【再来一次】的次数
$sql .= ",user_limit int not null default 0"; //用户获取奖品的数量限制
$sql .= ",takeaway int not null default 0"; //已经抽中的奖品数量
$sql .= ",takeaway_at int not null default 0";
$sql .= ",greeting text"; //中奖贺词
$sql .= ",get_prize_url text"; //获得兑奖url的url
$sql .= ",primary key(siteid,lid,aid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_lottery_award): ' . $sql . ':' . $mysqli->error;
}
/* 奖品的槽位 */
$sql = "create table if not exists xxt_lottery_plate(";
$sql .= "siteid varchar(32) not null";
$sql .= ",lid varchar(40) not null"; //轮盘抽奖活动的ID
$sql .= ",size int not null default 8"; //轮盘的格数
$sql .= ",a0 varchar(40) not null default ''";
$sql .= ",a1 varchar(40) not null default ''";
$sql .= ",a2 varchar(40) not null default ''";
$sql .= ",a3 varchar(40) not null default ''";
$sql .= ",a4 varchar(40) not null default ''";
$sql .= ",a5 varchar(40) not null default ''";
$sql .= ",a6 varchar(40) not null default ''";
$sql .= ",a7 varchar(40) not null default ''";
$sql .= ",a8 varchar(40) not null default ''";
$sql .= ",a9 varchar(40) not null default ''";
$sql .= ",a10 varchar(40) not null default ''";
$sql .= ",a11 varchar(40) not null default ''";
$sql .= ",primary key(siteid,lid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_lottery_plate): ' . $sql . ':' . $mysqli->error;
}
/* 抽奖结果记录 */
$sql = "create table if not exists xxt_lottery_log(";
$sql .= 'id int not null auto_increment';
$sql .= ',siteid varchar(32) not null';
$sql .= ',lid varchar(40) not null'; // 轮盘抽奖活动的ID
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",openid varchar(255) not null default ''";
$sql .= ',draw_at int not null'; // 抽奖的时间
$sql .= ',aid varchar(40) not null'; // 奖品的ID
$sql .= ',award_title varchar(20) not null';
$sql .= ",times_accumulated int not null default 1"; //有效时段内，累积的次数
$sql .= ",last char(1) not null default 'Y'"; // 最后一次抽奖记录。每一次抽奖动作都记录，记录最后一条便于计算。
$sql .= ",takeaway char(1) not null default 'N'"; // 奖品是否已经领取。只对非实物奖品有效。
$sql .= ",prize_url text"; //兑奖的地址
$sql .= ",enroll_key varchar(32) not null default ''"; //抽奖结果对应的登记活动的登记记录
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_lottery_log): ' . $sql . ':' . $mysqli->error;
}
//
echo 'lottery finish.' . PHP_EOL;