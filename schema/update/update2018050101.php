<?php
//
$sqls = [];
$sqls[] = "ALTER TABLE xxt_enroll add notify_config text null after remark_notice";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
//
require_once '../../config.php';
require_once '../../db.php';
require_once '../../tms/db.php';
require_once '../../tms/tms_model.php';

$model = TMS_MODEL::model();

$apps = $model->query_objs_ss(['id,notify_submit', 'xxt_enroll', ['notify_submit' => 'Y']]);
foreach ($apps as $oApp) {
	$oConfig = new stdClass;
	$oConfig->submit = (object) ['valid' => true, 'page' => 'console'];
	$model->update('xxt_enroll', ['notify_config' => json_encode($oConfig)], ['id' => $oApp->id]);
}

//
$sqls = [];
$sqls[] = "ALTER TABLE xxt_enroll drop notify_submit";
$sqls[] = "ALTER TABLE xxt_enroll drop remark_notice";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;