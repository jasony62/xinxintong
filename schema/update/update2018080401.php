<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/db.php';
require_once dirname(dirname(dirname(__FILE__))) . '/tms/db.php';
require_once dirname(dirname(dirname(__FILE__))) . '/tms/tms_model.php';

set_time_limit(0);

$model = TMS_MODEL::model();

$timers = $model->query_objs_ss(['id,task_arguments,matter_id', 'xxt_timer_task', ['matter_type' => 'enroll', 'task_model' => 'remind']]);
foreach ($timers as $oTimer) {
	$oArgs = empty($oTimer->task_arguments) ? (object) ['page' => ''] : json_decode($oTimer->task_arguments);
	$oApp = $model->query_obj_ss(['id,entry_rule', 'xxt_enroll', ['id' => $oTimer->matter_id]]);
	$oApp->entryRule = empty($oApp->entry_rule) ? new \stdClass : json_decode($oApp->entry_rule);
	if (isset($oApp->entryRule->scope->member) && $oApp->entryRule->scope->member === 'Y' && isset($oApp->entryRule->member) && count((array) $oApp->entryRule->member)) {
		$oArgs->receiver = (object) ['scope' => 'mschema'];
	} else {
		$oArgs->receiver = (object) ['scope' => 'enroll'];
	}
	$model->update('xxt_timer_task', ['task_arguments' => json_encode($oArgs)], ['id' => $oTimer->id]);
}

header('Content-Type: text/plain; charset=utf-8');
echo "end update " . __FILE__ . PHP_EOL;