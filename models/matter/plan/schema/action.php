<?php
namespace matter\plan\schema;
/**
 *
 */
class action_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byId($actionId, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_plan_action_schema',
			['id' => $actionId],
		];
		$oAction = $this->query_obj_ss($q);
		if ($oAction) {
			if (isset($oAction->check_schemas)) {
				$oAction->checkSchemas = empty($oAction->check_schemas) ? [] : json_decode($oAction->check_schemas);
				unset($oAction->check_schemas);
			}
		}

		return $oAction;
	}
	/**
	 *
	 */
	public function add($oNewAction) {
		$oNewAction->state = 1;
		$oNewAction->action_seq = $this->lastSeq($oNewAction->task_schema_id) + 1;
		$oNewAction->action_desc = '行动-' . $oNewAction->action_seq;
		$oNewAction->check_schemas = '[]';

		$oNewAction->id = $this->insert('xxt_plan_action_schema', $oNewAction, true);
		$oNewAction->checkSchemas = [];

		return $oNewAction;
	}
	/**
	 * 模板任务的最大序号
	 */
	public function lastSeq($taskId) {
		$q = [
			'max(action_seq)',
			'xxt_plan_action_schema',
			['task_schema_id' => $taskId, 'state' => 1],
		];
		$lastSeq = (int) $this->query_val_ss($q);

		return $lastSeq;
	}
	/**
	 *
	 */
	public function byTask($taskId, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_plan_action_schema',
			['task_schema_id' => $taskId, 'state' => 1],
		];
		$q2 = ['o' => 'action_seq'];

		$actions = $this->query_objs_ss($q, $q2);
		foreach ($actions as $oAction) {
			if (property_exists($oAction, 'check_schemas')) {
				$oAction->checkSchemas = empty($oAction->check_schemas) ? [] : json_decode($oAction->check_schemas);
				unset($oAction->check_schemas);
			}
		}

		return $actions;
	}
}