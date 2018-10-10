<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/db.php';
require_once dirname(dirname(dirname(__FILE__))) . '/tms/db.php';
require_once dirname(dirname(dirname(__FILE__))) . '/tms/tms_model.php';

set_time_limit(0);

$model = TMS_MODEL::model();

$count = 0;
$apps = $model->query_objs_ss(['id,round_cron', 'xxt_enroll', ['1' => '1']]);
foreach ($apps as $oApp) {
	$oRC = empty($oApp->round_cron) ? [] : json_decode($oApp->round_cron);
	if (empty($oRC)) {
		continue;
	}
	foreach ($oRC as $oRule) {
		if (!isset($oRule->id)) {
			$oRule->id = uniqid();
		}
		unset($oRule->case);
	}
	$model->update('xxt_enroll', ['round_cron' => json_encode($oRC)], ['id' => $oApp->id]);

	$count++;
}

header('Content-Type: text/plain; charset=utf-8');
echo "end update($count) " . __FILE__ . PHP_EOL;