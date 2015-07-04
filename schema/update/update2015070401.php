<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'app_merchant','N','N','N','N' from xxt_mppermission where permission='mpsetting'";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
