<?php
require_once '../../db.php';

$sqls = [];
/**
 * 记录活动分组用户数据汇总
 */
$sql = "create table if not exists xxt_enroll_group(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null default ''"; // 登记轮次，ALL代表累计的数据，每个轮次有单独轮次的记录，如果没有设置轮次，轮次rid为空字符串
$sql .= ",group_id varchar(32) not null default ''"; // 用户分组id
$sql .= ",entry_num int not null default 0"; // 进入活动的次数
$sql .= ",total_elapse int not null default 0"; // 参与活动的总时长
$sql .= ",enroll_num int not null default 0"; // 填写记录的条数
$sql .= ",revise_num int not null default 0"; // 跨轮次修订的次数
$sql .= ",cowork_num int not null default 0"; // 获得协作填写的数量
$sql .= ",do_cowork_num int not null default 0"; // 进行协作填写的数量
$sql .= ",remark_num int not null default 0"; // 获得的评价条数
$sql .= ",remark_cowork_num int not null default 0"; // 协作填写获得的评价条数
$sql .= ",like_num int not null default 0"; // 登记内容获得点赞的次数
$sql .= ",like_cowork_num int not null default 0"; // 协作填写获得点赞的次数
$sql .= ",like_remark_num int not null default 0"; // 留言获得点赞的次数
$sql .= ",do_remark_num int not null default 0"; // 发表的评价条数
$sql .= ",do_like_num int not null default 0"; // 对登记内容进行点赞的次数
$sql .= ",do_like_cowork_num int not null default 0"; // 对协作进行点赞的次数
$sql .= ",do_like_remark_num int not null default 0"; // 对留言进行点赞的次数
$sql .= ",dislike_num int not null default 0"; // 登记内容获得点赞的次数
$sql .= ",dislike_cowork_num int not null default 0"; // 协作填写获得点赞的次数
$sql .= ",dislike_remark_num int not null default 0"; // 留言获得点赞的次数
$sql .= ",do_dislike_num int not null default 0"; // 对登记内容进行点赞的次数
$sql .= ",do_dislike_cowork_num int not null default 0"; // 对协作进行点赞的次数
$sql .= ",do_dislike_remark_num int not null default 0"; // 对留言进行点赞的次数
$sql .= ",agree_num int not null default 0"; // 获得推荐的次数
$sql .= ",agree_cowork_num int not null default 0"; // 协作获得推荐的次数
$sql .= ",agree_remark_num int not null default 0"; // 留言获得推荐的次数
$sql .= ",topic_num int not null default 0"; // 创建专题页的次数
$sql .= ",do_repos_read_num int not null default 0"; // 阅读共享页的次数
$sql .= ",do_repos_read_elapse int not null default 0"; // 阅读共享页的总时长
$sql .= ",do_topic_read_num int not null default 0"; // 阅读专题页的次数
$sql .= ",topic_read_num int not null default 0"; // 专题页被阅读的次数
$sql .= ",do_topic_read_elapse int not null default 0"; // 阅读专题页的时长
$sql .= ",topic_read_elapse int not null default 0"; // 专题页被阅读的总时长
$sql .= ",do_cowork_read_num int not null default 0"; // 阅读谈论页的次数
$sql .= ",cowork_read_num int not null default 0"; // 谈论页被阅读的次数
$sql .= ",do_cowork_read_elapse int not null default 0"; // 阅读谈论页的时长
$sql .= ",cowork_read_elapse int not null default 0"; //
$sql .= ",do_rank_read_num int not null default 0"; // 阅读排行榜的次数
$sql .= ",do_rank_read_elapse int not null default 0"; // 阅读排行榜的总时长
$sql .= ",vote_schema_num int not null default 0"; // 题目获得投票的次数
$sql .= ",vote_cowork_num int not null default 0"; // 协作填写获得投票的次数
$sql .= ",user_total_coin int not null default 0"; // 用户在活动中的轮次上的总行为分
$sql .= ",group_total_coin int not null default 0"; // 用户组在活动中的轮次上的总行为分
$sql .= ",score float default 0 COMMENT '数据分'"; //
$sql .= ",score_rank int not null default 0"; // 数据分在轮次中的排名
$sql .= ",state tinyint not null default 1"; //0:clean,1:normal,2:as invite log,100:后台删除
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 项目分组用户数据汇总
 */
$sql = "create table if not exists xxt_mission_group(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",group_id varchar(32) not null default ''"; // 用户分组id
$sql .= ",entry_num int not null default 0"; // 进入活动的次数
$sql .= ",total_elapse int not null default 0"; // 参与活动的总时长
$sql .= ",enroll_num int not null default 0"; // 登记记录的条数
$sql .= ",cowork_num int not null default 0"; // 获得协作填写的数量
$sql .= ",do_cowork_num int not null default 0"; // 进行协作填写的数量
$sql .= ",remark_num int not null default 0"; // 获得的评价条数
$sql .= ",remark_cowork_num int not null default 0"; // 协作填写获得的评价条数
$sql .= ",like_num int not null default 0"; // 登记内容获得点赞的次数
$sql .= ",like_cowork_num int not null default 0"; // 协作填写获得点赞的次数
$sql .= ",like_remark_num int not null default 0"; // 留言获得点赞的次数
$sql .= ",dislike_num int not null default 0"; // 登记内容获得点赞的次数
$sql .= ",dislike_cowork_num int not null default 0"; // 协作填写获得点赞的次数
$sql .= ",dislike_remark_num int not null default 0"; // 留言获得点赞的次数
$sql .= ",do_remark_num int not null default 0"; // 发表的评价条数
$sql .= ",do_like_num int not null default 0"; // 对登记内容进行点赞的次数
$sql .= ",do_like_cowork_num int not null default 0"; // 对协作进行点赞的次数
$sql .= ",do_like_remark_num int not null default 0"; // 对留言进行点赞的次数
$sql .= ",do_dislike_num int not null default 0"; // 对登记内容进行点赞的次数
$sql .= ",do_dislike_cowork_num int not null default 0"; // 对协作进行点赞的次数
$sql .= ",do_dislike_remark_num int not null default 0"; // 对留言进行点赞的次数
$sql .= ",agree_num int not null default 0"; // 获得推荐的次数
$sql .= ",agree_cowork_num int not null default 0"; // 协作获得推荐的次数
$sql .= ",agree_remark_num int not null default 0"; // 留言获得推荐的次数
$sql .= ",signin_num int not null default 0"; // 签到的次数
$sql .= ",topic_num int not null default 0"; // 创建专题页的次数
$sql .= ",do_repos_read_num int not null default 0"; // 阅读共享页的次数
$sql .= ",do_repos_read_elapse int not null default 0"; // 阅读共享页的总时长
$sql .= ",do_topic_read_num int not null default 0"; // 阅读专题页的次数
$sql .= ",topic_read_num int not null default 0"; // 专题页被阅读的次数
$sql .= ",do_topic_read_elapse int not null default 0"; // 阅读专题页的时长
$sql .= ",topic_read_elapse int not null default 0"; // 专题页被阅读的总时长
$sql .= ",do_cowork_read_num int not null default 0"; // 阅读谈论页的次数
$sql .= ",cowork_read_num int not null default 0"; // 谈论页被阅读的次数
$sql .= ",do_cowork_read_elapse int not null default 0"; // 阅读谈论页的时长
$sql .= ",cowork_read_elapse int not null default 0"; //
$sql .= ",do_rank_read_num int not null default 0"; // 阅读排行榜的次数
$sql .= ",do_rank_read_elapse int not null default 0"; // 阅读排行榜的总时长
$sql .= ",user_total_coin int not null default 0"; // 用户的总行为分
$sql .= ",group_total_coin int not null default 0"; // 用户组的总行为分
$sql .= ",score float not null default 0"; // 用户总数据分
$sql .= ",state tinyint not null default 1"; //0:clean,1:normal,100:后台删除
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: ' . $mysqli->error;
    }
}

echo "end update " . __FILE__ . PHP_EOL;