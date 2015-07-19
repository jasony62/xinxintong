<?php
require_once '../../db.php';

$sqls = array();
//
$sql = "create table if not exists xxt_mpaccount2(";
$sql .= 'mpid varchar(32) not null';
$sql .= ",mpsrc char(2) not null";
$sql .= ",yx_mpid varchar(255) not null default ''";
$sql .= ",wx_mpid varchar(255) not null default ''";
$sql .= ",asparent char(1) not null default 'N'";
$sql .= ",parent_mpid varchar(32) not null default ''";
$sql .= ',name varchar(50) not null';
$sql .= ',token varchar(40) not null';
$sql .= ',yx_appid varchar(255) not null';
$sql .= ',yx_appsecret varchar(255) not null';
$sql .= ",yx_cardname varchar(50) not null default ''";
$sql .= ",yx_cardid varchar(255) not null default ''";
$sql .= ",yx_joined char(1) not null default 'N'";
$sql .= ',yx_token text';
$sql .= ',yx_token_expire_at int not null';
$sql .= ',wx_appid varchar(255) not null';
$sql .= ',wx_appsecret varchar(255) not null';
$sql .= ",wx_cardname varchar(50) not null default ''";
$sql .= ",wx_cardid varchar(36) not null default ''";
$sql .= ",wx_joined char(1) not null default 'N'";
$sql .= ",wx_token text";
$sql .= ",wx_token_expire_at int not null";
$sql .= ',wx_jsapi_ticket text';
$sql .= ',wx_jsapi_ticket_expire_at int not null';
$sql .= ",qy_corpid varchar(255) not null default ''";
$sql .= ",qy_secret varchar(255) not null default ''";
$sql .= ",qy_encodingaeskey varchar(43) not null default ''";
$sql .= ",qy_agentid int not null";
$sql .= ",qy_joined char(1) not null default 'N'";
$sql .= ",qy_token text";
$sql .= ",qy_token_expire_at int not null";
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ',state tinyint not null default 1';
$sql .= ",primary key(mpid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = 'insert into xxt_mpaccount2 select * from xxt_mpaccount';
$sqls[] = $sql;
$sql = 'drop table xxt_mpaccount';
$sqls[] = $sql;
//
$sql = "create table if not exists xxt_mpaccount(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ",mpsrc char(2) not null";
$sql .= ",yx_mpid varchar(255) not null default ''";
$sql .= ",wx_mpid varchar(255) not null default ''";
$sql .= ",asparent char(1) not null default 'N'";
$sql .= ",parent_mpid varchar(32) not null default ''";
$sql .= ',name varchar(50) not null';
$sql .= ',token varchar(40) not null';
$sql .= ',yx_appid varchar(255) not null';
$sql .= ',yx_appsecret varchar(255) not null';
$sql .= ",yx_cardname varchar(50) not null default ''";
$sql .= ",yx_cardid varchar(255) not null default ''";
$sql .= ",yx_joined char(1) not null default 'N'";
$sql .= ',yx_token text';
$sql .= ',yx_token_expire_at int not null';
$sql .= ',wx_appid varchar(255) not null';
$sql .= ',wx_appsecret varchar(255) not null';
$sql .= ",wx_cardname varchar(50) not null default ''";
$sql .= ",wx_cardid varchar(36) not null default ''";
$sql .= ",wx_joined char(1) not null default 'N'";
$sql .= ",wx_token text";
$sql .= ",wx_token_expire_at int not null";
$sql .= ',wx_jsapi_ticket text';
$sql .= ',wx_jsapi_ticket_expire_at int not null';
$sql .= ",qy_corpid varchar(255) not null default ''";
$sql .= ",qy_secret varchar(255) not null default ''";
$sql .= ",qy_encodingaeskey varchar(43) not null default ''";
$sql .= ",qy_agentid int not null";
$sql .= ",qy_joined char(1) not null default 'N'";
$sql .= ",qy_token text";
$sql .= ",qy_token_expire_at int not null";
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ',state tinyint not null default 1';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
$sqls[] = 'insert into xxt_mpaccount(mpid,mpsrc,yx_mpid,wx_mpid,asparent,parent_mpid,name,token,yx_appid,yx_appsecret,yx_cardname,yx_cardid,yx_joined,yx_token,yx_token_expire_at,wx_appid,wx_appsecret,wx_cardname,wx_cardid,wx_joined,wx_token,wx_token_expire_at,wx_jsapi_ticket,wx_jsapi_ticket_expire_at,qy_corpid,qy_secret,qy_encodingaeskey,qy_agentid,qy_joined,qy_token,qy_token_expire_at,creater,create_at,state
) select mpid,mpsrc,yx_mpid,wx_mpid,asparent,parent_mpid,name,token,yx_appid,yx_appsecret,yx_cardname,yx_cardid,yx_joined,yx_token,yx_token_expire_at,wx_appid,wx_appsecret,wx_cardname,wx_cardid,wx_joined,wx_token,wx_token_expire_at,wx_jsapi_ticket,wx_jsapi_ticket_expire_at,qy_corpid,qy_secret,qy_encodingaeskey,qy_agentid,qy_joined,qy_token,qy_token_expire_at,creater,create_at,state from xxt_mpaccount2';
//$sqls[] = 'drop table xxt_mpaccount2';
//
foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        die('database error: '.$mysqli->error);
    }
}
echo "end update ".__FILE__.PHP_EOL;
