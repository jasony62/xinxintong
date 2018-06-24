<?php
require_once '../../config.php';
require_once '../../db.php';
require_once '../../tms/db.php';
require_once '../../tms/tms_model.php';

$model = TMS_MODEL::model();

$logMethod = 'update2018062301';
$batchSize = 100; // 一次执行多少条

$oUpdatedLog = $model->query_obj_ss(['*', 'xxt_log', ['method' => $logMethod]]);
if ($oUpdatedLog) {
	$lastRowNo = (int) $oUpdatedLog->data;
} else {
	$lastRowNo = -1;
}

$apps = $model->query_objs_ss(['select id,data_schemas from xxt_enroll limit ' . ($lastRowNo + 1) . ',' . $batchSize]);
$fnUpdateSchema = function (&$oSchema) {
	if (!is_object($oSchema) || empty($oSchema->type) || isset($oSchema->scoreMode)) {
		return false;
	}
	$bModified = false;
	if (isset($oSchema->requireScore) && $oSchema->requireScore === 'Y') {
		$oSchema->scoreMode = 'question';
		$bModified = true;
	} else if ($oSchema->type === 'shorttext' && isset($oSchema->format) && $oSchema->format === 'number') {
		$oSchema->requireScore = 'Y';
		$oSchema->scoreMode = 'evaluation';
		$bModified = true;
	}

	return $bModified;
};
foreach ($apps as $oApp) {
	$dataSchemas = json_decode($oApp->data_schemas);
	$bModified = false;
	/* 更新活动 */
	foreach ($dataSchemas as $oSchema) {
		$bModified = $fnUpdateSchema($oSchema);
	}
	if ($bModified) {
		$model->update('xxt_enroll', ['data_schemas' => $model->escape($model->toJson($dataSchemas))], ['id' => $oApp->id]);
		/* 更新页面 */
		$pages = $model->query_objs_ss(['id,type,data_schemas', 'xxt_enroll_page', ['aid' => $oApp->id, 'type' => ['I', 'V', 'L']]]);
		foreach ($pages as $oPage) {
			$pageDataSchemas = json_decode($oPage->data_schemas);
			switch ($oPage->type) {
			case 'I':
			case 'V':
				foreach ($pageDataSchemas as $oPageDataSchema) {
					$oSchema = $oPageDataSchema->schema;
					$fnUpdateSchema($oSchema);
				}
				break;
			case 'L':
				foreach ($pageDataSchemas as $oPageDataSchema) {
					if (!empty($oPageDataSchema->schemas) && is_array($oPageDataSchema->schemas)) {
						foreach ($oPageDataSchema->schemas as $oSchema) {
							$fnUpdateSchema($oSchema);
						}
					}
				}
				break;
			}
			$model->update('xxt_enroll_page', ['data_schemas' => $model->escape($model->toJson($pageDataSchemas))], ['id' => $oPage->id]);
		}
	}
}

if ($oUpdatedLog) {
	$model->update('xxt_log', ['data' => $lastRowNo + count($apps)], ['id' => $oUpdatedLog->id]);
} else {
	$model->insert('xxt_log', ['mpid' => 'maintain', 'method' => $logMethod, 'data' => $lastRowNo + count($apps)], false);
}

header('Content-Type: text/plain; charset=utf-8');
if (count($apps) < $batchSize) {
	echo "end update " . __FILE__ . PHP_EOL;
} else {
	echo "请继续重复执行更新操作，直到提示完成所有更新 " . __FILE__ . PHP_EOL;
}