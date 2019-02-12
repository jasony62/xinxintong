<?php
require_once '../../config.php';
require_once '../../db.php';
require_once '../../tms/db.php';
require_once '../../tms/tms_model.php';

$model = TMS_MODEL::model();
/* 项目 */
$missions = $model->query_objs_ss(['id,entry_rule', 'xxt_mission', ['entry_rule' => (object) ['op' => 'like', 'pat' => '%"round":%']]]);
foreach ($missions as $oMission) {
	$oEntryRule = json_decode($oMission->entry_rule);
	if (isset($oEntryRule->group->round)) {
		$oEntryRule->group->team = $oEntryRule->group->round;
		unset($oEntryRule->group->round);
		$model->update('xxt_mission', ['entry_rule' => TMS_MODEL::toJson($oEntryRule)], ['id' => $oMission->id]);
		echo 'mission:' . $oMission->id . '<br>';
	}
}
/* 记录活动 */
$enlApps = $model->query_objs_ss(['id,entry_rule', 'xxt_enroll', ['entry_rule' => (object) ['op' => 'like', 'pat' => '%"round":%']]]);
foreach ($enlApps as $oEnlApp) {
	$oEntryRule = json_decode($oEnlApp->entry_rule);
	if (isset($oEntryRule->group->round)) {
		$oEntryRule->group->team = $oEntryRule->group->round;
		unset($oEntryRule->group->round);
		$model->update('xxt_enroll', ['entry_rule' => TMS_MODEL::toJson($oEntryRule)], ['id' => $oEnlApp->id]);
		echo 'enroll:' . $oEnlApp->id . '<br>';
	}
}
$enlApps = $model->query_objs_ss(['id,notify_config', 'xxt_enroll', ['notify_config' => (object) ['op' => 'like', 'pat' => '%"round":%']]]);
foreach ($enlApps as $oEnlApp) {
	$oNotifyConfig = json_decode($oEnlApp->notify_config);
	if (isset($oNotifyConfig->submit->receiver->group->round)) {
		$oNotifyConfig->submit->receiver->group->team = $oNotifyConfig->submit->receiver->group->round;
		unset($oNotifyConfig->submit->receiver->group->round);
		$model->update('xxt_enroll', ['notify_config' => TMS_MODEL::toJson($oNotifyConfig)], ['id' => $oEnlApp->id]);
		echo 'notify_config enroll:' . $oEnlApp->id . '<br>';
	}
	if (isset($oNotifyConfig->cowork->receiver->group->round)) {
		$oNotifyConfig->cowork->receiver->group->team = $oNotifyConfig->cowork->receiver->group->round;
		unset($oNotifyConfig->cowork->receiver->group->round);
		$model->update('xxt_enroll', ['notify_config' => TMS_MODEL::toJson($oNotifyConfig)], ['id' => $oEnlApp->id]);
		echo 'notify_config submit enroll:' . $oEnlApp->id . '<br>';
	}
	if (isset($oNotifyConfig->cowork->receiver->group->round)) {
		$oNotifyConfig->cowork->receiver->group->team = $oNotifyConfig->cowork->receiver->group->round;
		unset($oNotifyConfig->cowork->receiver->group->round);
		$model->update('xxt_enroll', ['notify_config' => TMS_MODEL::toJson($oNotifyConfig)], ['id' => $oEnlApp->id]);
		echo 'notify_config cowork enroll:' . $oEnlApp->id . '<br>';
	}
	if (isset($oNotifyConfig->remark->receiver->group->round)) {
		$oNotifyConfig->remark->receiver->group->team = $oNotifyConfig->remark->receiver->group->round;
		unset($oNotifyConfig->remark->receiver->group->round);
		$model->update('xxt_enroll', ['notify_config' => TMS_MODEL::toJson($oNotifyConfig)], ['id' => $oEnlApp->id]);
		echo 'notify_config remark enroll:' . $oEnlApp->id . '<br>';
	}
}
/* 签到活动 */
$sigApps = $model->query_objs_ss(['id,entry_rule', 'xxt_signin', ['entry_rule' => (object) ['op' => 'like', 'pat' => '%"round":%']]]);
foreach ($sigApps as $oSigApp) {
	$oEntryRule = json_decode($oSigApp->entry_rule);
	if (isset($oEntryRule->group->round)) {
		$oEntryRule->group->team = $oEntryRule->group->round;
		unset($oEntryRule->group->round);
		$model->update('xxt_signin', ['entry_rule' => TMS_MODEL::toJson($oEntryRule)], ['id' => $oSigApp->id]);
		echo 'signin:' . $oSigApp->id . '<br>';
	}
}
/* 计划活动 */
$plaApps = $model->query_objs_ss(['id,entry_rule', 'xxt_plan', ['entry_rule' => (object) ['op' => 'like', 'pat' => '%"round":%']]]);
foreach ($plaApps as $oPlaApp) {
	$oEntryRule = json_decode($oPlaApp->entry_rule);
	if (isset($oEntryRule->group->round)) {
		$oEntryRule->group->team = $oEntryRule->group->round;
		unset($oEntryRule->group->round);
		$model->update('xxt_plan', ['entry_rule' => TMS_MODEL::toJson($oEntryRule)], ['id' => $oPlaApp->id]);
		echo 'plan:' . $oPlaApp->id . '<br>';
	}
}
/* 图文 */
$articles = $model->query_objs_ss(['id,entry_rule', 'xxt_article', ['entry_rule' => (object) ['op' => 'like', 'pat' => '%"round":%']]]);
foreach ($articles as $oArticle) {
	$oEntryRule = json_decode($oArticle->entry_rule);
	if (isset($oEntryRule->group->round)) {
		$oEntryRule->group->team = $oEntryRule->group->round;
		unset($oEntryRule->group->round);
		$model->update('xxt_article', ['entry_rule' => TMS_MODEL::toJson($oEntryRule)], ['id' => $oArticle->id]);
		echo 'article:' . $oArticle->id . '<br>';
	}
}
/* 链接 */
$links = $model->query_objs_ss(['id,entry_rule', 'xxt_link', ['entry_rule' => (object) ['op' => 'like', 'pat' => '%"round":%']]]);
foreach ($links as $oLink) {
	$oEntryRule = json_decode($oLink->entry_rule);
	if (isset($oEntryRule->group->round)) {
		$oEntryRule->group->team = $oEntryRule->group->round;
		unset($oEntryRule->group->round);
		$model->update('xxt_link', ['entry_rule' => TMS_MODEL::toJson($oEntryRule)], ['id' => $oLink->id]);
		echo 'link:' . $oLink->id . '<br>';
	}
}
/* 定时任务 */
$timers = $model->query_objs_ss(['id,task_arguments', 'xxt_timer_task', ['task_arguments' => (object) ['op' => 'like', 'pat' => '%"round":%']]]);
foreach ($timers as $oTimer) {
	$oTaskArg = json_decode($oTimer->task_arguments);
	if (isset($oTaskArg->receiver->app->round)) {
		$oTaskArg->receiver->app->team = $oTaskArg->receiver->app->round;
		unset($oTaskArg->receiver->app->round);
		$model->update('xxt_timer_task', ['task_arguments' => TMS_MODEL::toJson($oTaskArg)], ['id' => $oTimer->id]);
		echo 'timer:' . $oTimer->id . '<br>';
	}
}

echo "end update " . __FILE__ . PHP_EOL;