<?php
require_once '../db.php';
/**
 * log
 */
//$sql = "delete from xxt_log";
//$mysql_query($sql);
    
$sql[] = "select * from xxt_log order by id desc limit 0,100";
/**
 * 执行操作
 */
echo 'beging...';
foreach ($sql as $s) {
    if (!($db_result=mysql_query($s))) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
    $objects = array();
    while ($obj = mysql_fetch_object($db_result)){
        $objects[] = $obj;
    }
    echo json_encode($objects).PHP_EOL;
}
echo 'finished.';
