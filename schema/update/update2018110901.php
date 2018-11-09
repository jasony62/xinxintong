<?php
require_once '../../config.php';
require_once '../../db.php';
require_once '../../tms/db.php';
require_once '../../tms/tms_model.php';

$model = TMS_MODEL::model();
/**
 * 从数据库中获得数据
 */
$oEnlApps = $model->query_objs_ss(['id,scenario_config', 'xxt_enroll', ['can_coin' => 'Y']]);
/**
 * 处理获得的数据
 */
foreach ($oEnlApps as $oEnlApp) {
	if (empty($oEnlApp->scenario_config)) {
		$oConfig = new \stdClass;
	} else {
		$oConfig = json_decode($oEnlApp->scenario_config);
	}
	$oConfig->can_coin = 'Y';
	$sConfig = $model->escape($model->toJson($oConfig));
	$model->update('xxt_enroll', ['scenario_config' => $sConfig], ['id' => $oEnlApp->id]);
}

echo "end update " . __FILE__ . PHP_EOL;