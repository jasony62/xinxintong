<?php
require_once '../db.php';
// account
$sql = 'CREATE TABLE IF NOT EXISTS `account` (
    `uid` varchar(40) NOT NULL COMMENT \'用户的 UID\',
    `authed_from` varchar(20) DEFAULT \'xxt\' COMMENT \'哪个第三方应用\',
    `authed_id` varchar(255) DEFAULT NULL COMMENT \'在第三方应用中的标识\',
    `nickname` varchar(50) DEFAULT NULL COMMENT \'用户昵称\',
    `email` varchar(255) DEFAULT NULL COMMENT \'EMAIL\',
    `password` varchar(64) DEFAULT NULL COMMENT \'用户密码\',
    `salt` varchar(32) DEFAULT NULL COMMENT \'用户附加混淆码\',
    `reg_time` int DEFAULT NULL COMMENT \'注册时间\',
    `reg_ip` varchar(128) DEFAULT NULL COMMENT \'注册IP\',
    `last_login` int DEFAULT \'0\' COMMENT \'最后登录时间\',
    `last_ip` varchar(128) DEFAULT NULL COMMENT \'最后登录 IP\',
    `online_time` int DEFAULT \'0\' COMMENT \'在线时间 (分钟)\',
    `last_active` int DEFAULT NULL COMMENT \'最后活跃时间\',
    `forbidden` tinyint(3) DEFAULT \'0\' COMMENT \'是否禁止用户\',
    `is_first_login` tinyint(1) DEFAULT \'1\' COMMENT \'首次登录标记\',
    PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}
/**
 * account group and group's permissions.
 */
$sql = 'CREATE TABLE IF NOT EXISTS `account_group` (
    `group_id` int NOT NULL COMMENT \'用户组的 ID\',
    `group_name` varchar(50) NOT NULL COMMENT \'用户组名\',
    `asdefault` tinyint not null default 0 comment \'作为缺省用户组\',
    `p_mpgroup_create` tinyint not null default 0 comment \'创建公众号群\',
    `p_mp_create` tinyint not null default 0 comment \'创建公众号\',
    `p_mp_permission` tinyint not null default 0 comment \'设置公众号权限\',
    PRIMARY KEY (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}
/**
 * relation of acount and group.
 */
$sql = 'CREATE TABLE IF NOT EXISTS `account_in_group` (
    `account_uid` varchar(40) NOT NULL COMMENT \'用户的 ID\',
    `group_id` int NOT NULL COMMENT \'用户组的 ID\',
    PRIMARY KEY (`account_uid`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}

echo 'finish account.'.PHP_EOL;
