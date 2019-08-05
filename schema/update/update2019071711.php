<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE account add pwd_error_num int default 0 after last_login";
$sqls[] = "ALTER TABLE account add login_limit_expire int default 0 after pwd_error_num";
//
foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: ' . $mysqli->error;
    }
}

echo "end update " . __FILE__ . PHP_EOL;