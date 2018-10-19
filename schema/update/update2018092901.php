<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/db.php';
require_once dirname(dirname(dirname(__FILE__))) . '/tms/db.php';
require_once dirname(dirname(dirname(__FILE__))) . '/tms/tms_model.php';

set_time_limit(0);

$model = TMS_MODEL::model();

$count = 0;
$apps = $model->query_objs_ss(['id,can_repos,can_rank,can_cowork,scenario_config', 'xxt_enroll', ['1' => '1']]);
foreach ($apps as $oApp) {
	$oSC = empty($oApp->scenario_config) ? new \stdClass : json_decode($oApp->scenario_config);
	$oSC->can_repos = $oApp->can_repos === 'Y' ? 'Y' : 'N';
	$oSC->can_rank = $oApp->can_rank === 'Y' ? 'Y' : 'N';
	$oSC->can_cowork = $oApp->can_cowork === 'Y' ? 'Y' : 'N';

	$model->update('xxt_enroll', ['scenario_config' => json_encode($oSC)], ['id' => $oApp->id]);

	$count++;
}

header('Content-Type: text/plain; charset=utf-8');
echo "end update($count) " . __FILE__ . PHP_EOL;