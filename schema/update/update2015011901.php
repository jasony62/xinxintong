<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_ab_person_dept` ADD  `ab_id` INT NOT NULL";
$sqls[] = "update xxt_ab_person_dept pd,xxt_ab_dept d set pd.ab_id = d.ab_id where pd.dept_id=d.id";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
