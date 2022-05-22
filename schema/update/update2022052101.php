<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE account add is_smscode_register tinyint(1) default 0 comment '验证码注册' after is_first_login";
//
foreach ($sqls as $sql) {
  if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: ' . $mysqli->error;
  }
}

echo "end update " . __FILE__ . PHP_EOL;
