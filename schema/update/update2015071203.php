<?php
require_once '../../db.php';

$rule0 = '{"otherwise":{"entry":"form"},"member":{"entry":"form"},"member_outacl":{"entry":"form","enroll":"Y","remark":"Y"},"fan":{"entry":"form","remark":"Y","enroll":"Y"},"nonfan":{"entry":"$mp_follow","enroll":"$mp_follow"}}';
$rule1 = '{"otherwise":{"entry":"form"},"member":{"entry":"form"},"member_outacl":{"entry":"$authapi_outacl","enroll":"$authapi_outacl","remark":"$authapi_outacl"},"fan":{"entry":"$authapi_auth","remark":"$authapi_auth","enroll":"$authapi_auth"},"nonfan":{"entry":"$mp_follow","enroll":"$mp_follow"}}';

$sqls = array();
$sqls[] = "update xxt_enroll set entry_rule='$rule0' where access_control='N'";
$sqls[] = "update xxt_enroll set entry_rule='$rule1' where access_control='Y'";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
