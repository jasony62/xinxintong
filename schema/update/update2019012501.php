<?php
require_once '../../config.php';
require_once '../../db.php';
require_once '../../tms/db.php';
require_once '../../tms/tms_model.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_enroll_record_data add is_multitext_root char(1) not null default 'N' after schema_id";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

$model = TMS_MODEL::model();
/**
 * 从数据库中获得数据
 */
$oEnlApps = $model->query_objs_ss(['id,data_schemas', 'xxt_enroll', ['data_schemas' => (object) ['op' => 'like', 'pat' => '%:"multitext"%']]]);
/**
 * 处理获得的数据
 */
foreach ($oEnlApps as $oEnlApp) {
	$schemas = json_decode($oEnlApp->data_schemas);
	$schemaIds = array_map(function ($oSchema) {return $oSchema->id;}, array_filter($schemas, function ($oSchema) {return $oSchema->type === 'multitext';}));
	$model->update('xxt_enroll_record_data', ['is_multitext_root' => 'Y'], ['aid' => $oEnlApp->id, 'multitext_seq' => 0, 'schema_id' => $schemaIds]);
}

echo "end update " . __FILE__ . PHP_EOL;