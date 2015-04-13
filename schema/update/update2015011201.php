<?php
require_once '../../db.php';

$sqls = array();
//$sqls[] = "drop table xxt_address_book";
$sqls[] = "insert into xxt_address_book(mpid,title,creater,create_at,modify_at) select mpid,'通讯录',creater,create_at,create_at from xxt_mpaccount";
$sqls[] = "delete from xxt_address_book where mpid not in (select distinct mpid from xxt_ab_person) ";
$sqls[] = "update xxt_ab_person p,xxt_address_book ab set p.ab_id=ab.id where p.mpid=ab.mpid";
$sqls[] = "update xxt_ab_dept d,xxt_address_book ab set d.ab_id=ab.id where d.mpid=ab.mpid";
$sqls[] = "update xxt_ab_title t,xxt_address_book ab set t.ab_id=ab.id where t.mpid=ab.mpid";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
