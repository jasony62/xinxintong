<?php
require_once '../../config.php';
require_once '../../db.php';
require_once '../../tms/db.php';
require_once '../../tms/tms_model.php';

$model = TMS_MODEL::model();
/**
 * 从数据库中获得数据
 */
$oEnlApps = $model->query_objs_ss(['id,data_schemas', 'xxt_enroll', ['data_schemas' => (object) ['op' => 'like', 'pat' => '%"schema_id":%']]]);
/**
 * 处理获得的数据
 */
foreach ($oEnlApps as $oEnlApp) {
	$dataSchemas = json_decode($oEnlApp->data_schemas);
	foreach ($dataSchemas as $oSchema) {
		if (isset($oSchema->schema_id)) {
			$oSchema->mschema_id = $oSchema->schema_id;
			unset($oSchema->schema_id);
		}
	}
	$sDataSchemas = $model->escape($model->toJson($dataSchemas));
	$model->update('xxt_enroll', ['data_schemas' => $sDataSchemas], ['id' => $oEnlApp->id]);
}
$oEnlPages = $model->query_objs_ss(['id,data_schemas', 'xxt_enroll_page', ['data_schemas' => (object) ['op' => 'like', 'pat' => '%"schema_id":%']]]);
/**
 * 处理获得的数据
 */
foreach ($oEnlPages as $oEnlPage) {
	$dataSchemas = json_decode($oEnlPage->data_schemas);
	foreach ($dataSchemas as $oSchemaWrap) {
		if (isset($oSchemaWrap->schema->schema_id)) {
			$oSchemaWrap->schema->mschema_id = $oSchemaWrap->schema->schema_id;
			unset($oSchemaWrap->schema->schema_id);
		}
	}
	$sDataSchemas = $model->escape($model->toJson($dataSchemas));
	$model->update('xxt_enroll_page', ['data_schemas' => $sDataSchemas], ['id' => $oEnlPage->id]);
}
/*****************/
/**
 * 从数据库中获得数据
 */
$oSigApps = $model->query_objs_ss(['id,data_schemas', 'xxt_signin', ['data_schemas' => (object) ['op' => 'like', 'pat' => '%"schema_id":%']]]);
/**
 * 处理获得的数据
 */
foreach ($oSigApps as $oSigApp) {
	$dataSchemas = json_decode($oSigApp->data_schemas);
	foreach ($dataSchemas as $oSchema) {
		if (isset($oSchema->schema_id)) {
			$oSchema->mschema_id = $oSchema->schema_id;
			unset($oSchema->schema_id);
		}
	}
	$sDataSchemas = $model->escape($model->toJson($dataSchemas));
	$model->update('xxt_signin', ['data_schemas' => $sDataSchemas], ['id' => $oSigApp->id]);
}
$oSigPages = $model->query_objs_ss(['id,data_schemas', 'xxt_signin_page', ['data_schemas' => (object) ['op' => 'like', 'pat' => '%"schema_id":%']]]);
/**
 * 处理获得的数据
 */
foreach ($oSigPages as $oSigPage) {
	$dataSchemas = json_decode($oSigPage->data_schemas);
	foreach ($dataSchemas as $oSchemaWrap) {
		if (isset($oSchemaWrap->schema->schema_id)) {
			$oSchemaWrap->schema->mschema_id = $oSchemaWrap->schema->schema_id;
			unset($oSchemaWrap->schema->schema_id);
		}
	}
	$sDataSchemas = $model->escape($model->toJson($dataSchemas));
	$model->update('xxt_signin_page', ['data_schemas' => $sDataSchemas], ['id' => $oSigPage->id]);
}
/*****************/
/**
 * 从数据库中获得数据
 */
$oGrpApps = $model->query_objs_ss(['id,data_schemas', 'xxt_group', ['data_schemas' => (object) ['op' => 'like', 'pat' => '%"schema_id":%']]]);
/**
 * 处理获得的数据
 */
foreach ($oGrpApps as $oGrpApp) {
	$dataSchemas = json_decode($oGrpApp->data_schemas);
	foreach ($dataSchemas as $oSchema) {
		if (isset($oSchema->schema_id)) {
			$oSchema->mschema_id = $oSchema->schema_id;
			unset($oSchema->schema_id);
		}
	}
	$sDataSchemas = $model->escape($model->toJson($dataSchemas));
	$model->update('xxt_group', ['data_schemas' => $sDataSchemas], ['id' => $oGrpApp->id]);
}

echo "end update " . __FILE__ . PHP_EOL;