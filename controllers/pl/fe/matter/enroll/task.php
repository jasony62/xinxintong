<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 记录活动用户的行为轨迹
 */
class task extends main_base {
    /**
     * 当前活动的所有任务
     */
    public function list_action($app, $type = null, $state = null, $rid = null) {
        $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N', 'appRid' => $rid]);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        /* 有效的任务类型 */
        $aTaskTypes = ['baseline', 'question', 'answer', 'vote', 'score'];
        if (!empty($type)) {
            if (!in_array($type, $aTaskTypes)) {
                return new \ParameterError('没有指定有效的任务类型');
            }
            $aTaskTypes = [$type];
        }
        /* 有效的任务状态 */
        $aTaskStates = ['IP', 'BS', 'AE'];
        if (!empty($state)) {
            if (!in_array($state, $aTaskStates)) {
                return new \ParameterError('没有指定有效的任务状态');
            }
            $aTaskStates = [$state];
        }
        // 获取所有任务
        $modelTsk = $this->model('matter\enroll\task', $oApp);
        $tasks = $modelTsk->byApp($aTaskTypes, $aTaskStates);
        if ($tasks[0] === false) {
            return new \ResponseError($tasks[1]);
        }
        $tasks = $tasks[1];
        /* 按照任务的开始时间排序 */
        usort($tasks, function ($a, $b) {
            if ($a->start_at === $b->start_at) {
                return 0;
            }
            return $a->start_at < $b->start_at ? -1 : 1;
        });

        return new \ResponseData($tasks);
    }
}