<?php
require_once '../../config.php';
require_once '../../db.php';
require_once '../../tms/db.php';
require_once '../../tms/tms_model.php';

$model = TMS_MODEL::model();
/**
 * 从数据库中获得数据
 */
$oMissions = $model->query_objs_ss(['id,entry_rule', 'xxt_mission', ['1' => 1]]);
/**
 * 处理获得的数据
 */
foreach ($oMissions as $oMission) {
	if (!empty($oMission->entry_rule)) {
		$oRule = json_decode($oMission->entry_rule);
		if (isset($oRule->scope)) {
			if (is_string($oRule->scope)) {
				$oScope = new \stdClass;
				if ($oRule->scope !== 'none') {
					$oScope->{$oRule->scope} = 'Y';
				}
			} else if (is_object($oRule->scope)) {
				$oScope = $oRule->scope;
			}
		}
		if (!isset($oScope)) {
			continue;
		}
		if (isset($oRule->sns) && is_object($oRule->sns)) {
			foreach ($oRule->sns as $oSnsRule) {
				if (isset($oSnsRule->entry)) {
					$oSnsRule->entry = 'Y';
				}
			}
		}

		unset($oRule->other);

		$oRule->scope = $oScope;

		$sRule = $model->escape($model->toJson($oRule));
		$model->update('xxt_mission', ['entry_rule' => $sRule], ['id' => $oMission->id]);
	}
}

echo "end update " . __FILE__ . PHP_EOL;