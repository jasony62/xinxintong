<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/db.php';
require_once dirname(dirname(dirname(__FILE__))) . '/tms/db.php';
require_once dirname(dirname(dirname(__FILE__))) . '/tms/tms_model.php';

set_time_limit(0);

$model = TMS_MODEL::model();

$count = 0;
$apps = $model->query_objs_ss(['id,entry_rule,group_app_id', 'xxt_enroll', ['1' => '1']]);
foreach ($apps as $oApp) {
	$oER = empty($oApp->entry_rule) ? [] : json_decode($oApp->entry_rule);
	if (empty($oER)) {
		$oER = new \stdClass;
	}
	$bModified = false;
	if (empty($oApp->group_app_id)) {
		if (!isset($oER->scope->group) || $oER->scope->group !== 'Y') {
			if (isset($oER->group)) {
				unset($oER->group);
				$bModified = true;
			}
		}
	} else {
		$oGrpApp = $model->query_obj_ss(['id,title', 'xxt_group', ['id' => $oApp->group_app_id]]);
		if ($oGrpApp) {
			if (empty($oER->scope) || !is_object($oER->scope)) {
				$oER->scope = new \stdClass;
			}
			if (empty($oER->scope->group) || $oER->scope->group !== 'Y') {
				$oER->scope->group = 'Y';
				$oER->group = $oGrpApp;
				$bModified = true;
			} else {
				if (!isset($oER->group->id)) {
					$oER->group = $oGrpApp;
					$bModified = true;
				} else {
					if ($oER->group->id !== $oGrpApp->id) {
						$oER->group = $oGrpApp;
						$bModified = true;
					}
				}
			}
		}
	}
	if ($bModified) {
		$model->update('xxt_enroll', ['entry_rule' => $model->escape($model->toJson($oER))], ['id' => $oApp->id]);
	}

	$count++;
}

header('Content-Type: text/plain; charset=utf-8');
echo "end update($count) " . __FILE__ . PHP_EOL;