<?php
require_once '../db.php';
/**
 * address book
 */
$sql = "create table if not exists xxt_addressbook(";
$sql .= "id int not null auto_increment";
$sql .= ',mpid varchar(32) not null';
$sql .= ",title varchar(70) not null";
$sql .= ',pic text';
$sql .= ',summary varchar(240) not null';
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ',modify_at int not null';
$sql .= ',state tinyint not null default 1';
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",limit_view_detail char(1) not null default 'N'";
$sql .= ",limit_view_detail_authapis text";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error(xxt_addressbook): '.$mysqli->error;
}
/**
 * 部门
 */
$sql = "create table if not exists xxt_ab_dept(";
$sql .= "id int not null auto_increment";
$sql .= ',mpid varchar(32) not null';
$sql .= ",name varchar(60) not null";
$sql .= ",pid int not null default 0";
$sql .= ",seq int not null default 0"; // 在父节点下的排列顺序
$sql .= ",fullpath text";
$sql .= ",ab_id int not null";
$sql .= ",primary key(id)";
$sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error(xxt_ab_dept): '.$mysqli->error;
}
/**
 * tags
 */
$sql = "create table if not exists xxt_ab_tag(";
$sql .= "id int not null auto_increment";
$sql .= ',mpid varchar(32) not null';
$sql .= ",ab_id int not null";
$sql .= ",name varchar(64) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}
/**
 * person
 */
$sql = "create table if not exists xxt_ab_person(";
$sql .= "id int not null auto_increment";
$sql .= ',mpid varchar(32) not null';
$sql .= ",ab_id int not null";
$sql .= ",name varchar(20) not null";
$sql .= ",pinyin varchar(100) not null";
$sql .= ",email text";
$sql .= ",tels text";
$sql .= ",remark text";
$sql .= ",tags text";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error(xxt_ab_person): '.$mysqli->error;
}
/**
 * 部门人员对应关系
 */
$sql = "create table if not exists xxt_ab_person_dept(";
$sql .= "id int not null auto_increment";
$sql .= ',mpid varchar(32) not null';
$sql .= ",ab_id int not null";
$sql .= ",person_id int not null";
$sql .= ",dept_id int null";
$sql .= ",primary key(id)";
$sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error(xxt_ab_person_dept): '.$mysqli->error;
}
//
echo 'finish addressbook.'.PHP_EOL;
