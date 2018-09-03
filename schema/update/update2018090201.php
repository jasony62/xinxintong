<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/db.php';
require_once dirname(dirname(dirname(__FILE__))) . '/tms/db.php';
require_once dirname(dirname(dirname(__FILE__))) . '/tms/tms_model.php';

set_time_limit(0);

$model = TMS_MODEL::model();

$count = 0;
$apps = $model->query_objs_ss(['id,entry_rule', 'xxt_signin', ['1' => '1']]);
foreach ($apps as $oApp) {
	if (empty($oApp->entry_rule)) {
		continue;
	}
	$oRule = json_decode($oApp->entry_rule);
	if (empty($oRule->scope)) {
		continue;
	}
	if (is_object($oRule->scope)) {
		continue;
	}

	unset($oRule->scope);
	unset($oRule->sns);
	unset($oRule->member);

	$model->update('xxt_signin', ['entry_rule' => json_encode($oRule)], ['id' => $oApp->id]);

	$count++;
}

header('Content-Type: text/plain; charset=utf-8');
echo "end update($count) " . __FILE__ . PHP_EOL;