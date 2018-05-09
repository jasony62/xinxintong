<?php
require_once '../../config.php';
require_once '../../db.php';
require_once '../../tms/db.php';
require_once '../../tms/tms_model.php';

$model = TMS_MODEL::model();

$apps = $model->query_objs_ss(['select id,round_cron from xxt_enroll where round_cron<>""']);
foreach ($apps as $oApp) {
	$rules = json_decode($oApp->round_cron);
	foreach ($rules as $oRule) {
		$oRule->pattern = 'period';
	}
	$model->update('xxt_enroll', ['round_cron' => json_encode($rules)], ['id' => $oApp->id]);
}

echo "end update " . __FILE__ . PHP_EOL;