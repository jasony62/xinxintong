<?php
require_once '../db.php';
/*function insert($table, $data) 
{
    foreach ($data AS $key => $val) {
        $insert_data['`' . $key . '`'] = "'" . $val . "'";
    }
    $sql = 'INSERT INTO `' . $table;
    $sql .= '` (' . implode(', ', array_keys($insert_data));
    $sql .= ') VALUES (' . implode(', ', $insert_data) . ')';

    $db_result = mysql_query($sql) || die('database error:'.$sql.';'.mysql_error());

    return true;
}*/
/**
 * create table.
 */
/*$sql = "CREATE TABLE IF NOT EXISTS x_fansinfo (";
$sql .= " mid varchar(32) NOT NULL DEFAULT '',";
$sql .= " open_id varchar(255) NOT NULL DEFAULT '',";
$sql .= " alias varchar(255) DEFAULT NULL,";
$sql .= " city varchar(255) DEFAULT NULL,";
$sql .= " country varchar(255) DEFAULT NULL,";
$sql .= " customer_type int(11) DEFAULT NULL,";
$sql .= " fakeId varchar(255) DEFAULT NULL,";
$sql .= " fansFlag int(11) DEFAULT NULL,";
$sql .= " groupid int(11) DEFAULT NULL,";
$sql .= " headimgurl text,";
$sql .= " internal int(11) DEFAULT NULL,";
$sql .= " language varchar(20) DEFAULT NULL,";
$sql .= " last_hold_time varchar(255) DEFAULT NULL,";
$sql .= " nickname varchar(255) DEFAULT NULL,";
$sql .= " other_id varchar(255) DEFAULT NULL,";
$sql .= " province varchar(255) DEFAULT NULL,";
$sql .= " sex tinyint(4) DEFAULT NULL,";
$sql .= " signature text,";
$sql .= " subscribe int(11) DEFAULT NULL,";
$sql .= " subscribeTime int(11) DEFAULT NULL,";
$sql .= " subscribe_time varchar(255) DEFAULT NULL,";
$sql .= " unsubscribe_time varchar(255) DEFAULT NULL,";
$sql .= " weixin varchar(255) DEFAULT NULL,";
$sql .= " crccreAccount varchar(255) DEFAULT NULL,";
$sql .= " PRIMARY KEY (open_id)";
$sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8;";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
echo 'finish create x_fansinfo.';*/
/**
 * insert data
 */
/*include_once dirname(__FILE__)."/x_fansinfo.php";

foreach ($x_fansinfo as $f) {
    $create_at = time();
    $mid = md5(uniqid().$create_at); //member's id
    $f['mid'] = $mid;
    insert('x_fansinfo', $f);
}
echo 'finish insert x_fansinfo.';*/
/**
 * 更新数据
 * todo 需要指定mpid
 * todo 需要指定authapi_id
 */
$sql = 'insert into xxt_member(mid,fid,mpid,ooid,osrc,authapi_id,authed_identity,create_at,name,nickname)';
$sql .= " select x.mid,f.fid,f.mpid,f.openid,'wx',1,x.crccreAccount,f.subscribe_at,x.crccreAccount,x.crccreAccount";
$sql .= ' from xxt_fans f,x_fansinfo x';
$sql .= " where f.mpid='acb98ae744dc305b8dc51c857982452f' and f.openid=x.open_id and x.crccreAccount!=''";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
echo 'finished update xxt_member';
/**
 * 删除数据
 */
/*$sql = 'drop table x_fansinfo';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}*/
/*$sql = 'delete from xxt_fans';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}*/
/*
$sql = 'delete from xxt_member';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}*/
echo 'finished x_fansinfo';
