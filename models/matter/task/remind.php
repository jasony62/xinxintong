<?php
namespace matter\task;
/**
 * 提醒事件
 */
class remind_model extends \TMS_MODEL {
    /**
     * 执行活动任务提醒任务
     *
     * @param object $oMatter
     * @param mix $arguments
     *
     * @return array
     */
    public function exec($oMatter, $arguments = null) {
        switch ($oMatter->type) {
        case 'mission':
            $aResult = $this->_mission($oMatter, $arguments);
            break;
        case 'enroll':
            $arguments = empty($arguments) ? new \stdClass : (is_object($arguments) ? $arguments : json_decode($arguments));
            $aResult = $this->_enroll($oMatter, $arguments);
            break;
        case 'plan':
            $aResult = $this->_plan($oMatter, $arguments);
            break;
        default:
            return [false, '不支持的活动类型【' . $oMatter->type . '】'];
        }

        if (false === $aResult[0]) {
            return $aResult;
        }
        list($bState, $oMatter, $noticeURL, $receivers, $oTmplTimerTaskParams) = $aResult;
        $noticeName = 'timer.' . $oMatter->type . '.remind';

        /*获取模板消息id*/
        $aTmplOptions = ['onlySite' => false, 'noticeURL' => $noticeURL];
        if (!empty($oTmplTimerTaskParams)) {
            $aTmplOptions['timerTask'] = $oTmplTimerTaskParams;
        }

        $tmpConfig = $this->model('matter\tmplmsg\config')->getTmplConfig($oMatter, $noticeName, $aTmplOptions);
        if ($tmpConfig[0] === false) {
            return [false, $tmpConfig[1]];
        }
        $tmpConfig = $tmpConfig[1];

        $modelTmplBat = $this->model('matter\tmplmsg\batch');
        $oCreator = new \stdClass;
        $oCreator->uid = $noticeName;
        $oCreator->name = 'timer';
        $oCreator->src = 'pl';
        $modelTmplBat->send($oMatter->siteid, $tmpConfig->tmplmsgId, $oCreator, $receivers, $tmpConfig->oParams, ['send_from' => $oMatter->type . ':' . $oMatter->id]);

        return [true];
    }
    /**
     * 项目提醒通知
     */
    private function _mission($oMatter, $arguments) {
        $modelMis = $this->model('matter\mission');
        $oMatter = $modelMis->byId($oMatter->id);
        if (false === $oMatter) {
            return [false, '指定的项目不存在'];
        }
        if (isset($oMatter->state) && $oMatter->state === '0') {
            return [false, '指定的项目已经不可用'];
        }

        /* 获得活动的进入链接 */
        $noticeURL = $oMatter->entryUrl;
        $noticeURL .= '&origin=timer';

        /* 获得用户 */
        if (empty($oMatter->user_app_id)) {
            $receivers = $this->model('matter\mission\user')->enrolleeByMission($oMatter, ['fields' => 'distinct userid']);
        } else {
            switch ($oMatter->user_app_type) {
            case 'group':
                $q = [
                    'distinct userid,enroll_key assoc_with',
                    'xxt_group_record',
                    ['state' => 1, 'aid' => $oMatter->user_app_id],
                ];
                $receivers = $modelMis->query_objs_ss($q);
                break;
            case 'enroll':
                $matterEnroll = new \stdClass;
                $matterEnroll->id = $oMatter->user_app_id;
                $modelEnlUsr = $this->model('matter\enroll\user');
                $aOptions = [
                    'rid' => 'ALL',
                    'onlyEnrolled' => 'Y',
                    'fields' => 'userid',
                    'cascaded' => 'N',
                ];
                $enrollUsers = $modelEnlUsr->enrolleeByApp($matterEnroll, '', '', $aOptions);
                $receivers = $enrollUsers->users;
                break;
            case 'signin':
                $matterSignin = new \stdClass;
                $matterSignin->id = $oMatter->user_app_id;
                $receivers = $this->model('matter\signin\record')->enrolleeByApp($matterSignin, ['fields' => 'distinct userid,enroll_key assoc_with']);
                break;
            case 'mschema':
                $receivers = $this->model('site\user\member')->byMschema($oMatter->user_app_id, ['fields' => 'userid']);
                break;
            }
        }
        if (empty($receivers)) {
            return [false, '没有填写人'];
        }

        return [true, $oMatter, $noticeURL, $receivers, null];
    }
    /**
     * 记录活动提醒通知
     */
    private function _enroll($oMatter, $oArguments) {
        $modelEnl = $this->model('matter\enroll');
        $oMatter = $modelEnl->byId($oMatter->id, ['cascaded' => 'N']);
        if (false === $oMatter || $oMatter->state !== '1') {
            return [false, '指定的活动不存在，或已不可用'];
        }

        $oTmplTimerTaskParams = new \stdClass; // 模板消息中和定时任务相关的参数

        /* 获得活动的进入链接 */
        $noticeURL = $oMatter->entryUrl;
        $noticeURL .= '&origin=timer';
        /* 进入的活动页面 */
        if (!empty($oArguments->page)) {
            $noticeURL .= '&page=' . $oArguments->page;
            $aPageNames = ['repos' => '共享页', 'rank' => '排行榜', 'event' => '动态页', 'stat' => '统计页'];
            if (isset($aPageNames[$oArguments->page])) {
                $oTmplTimerTaskParams->page = $aPageNames[$oArguments->page];
            }
        } else if (!empty($oArguments->taskConfig->id) && !empty($oArguments->taskConfig->type)) {
            /* 进入任务的专题页 */
            $modelTsk = $this->model('matter\enroll\task', $oMatter);
            $rules = $modelTsk->getRule($oArguments->taskConfig->type, false);
            if (!empty($rules)) {
                $oRule = tms_array_search($rules, function ($oRule) use ($oArguments) {
                    return $oRule->id === $oArguments->taskConfig->id && $oRule->state === 'IP';
                });
                if ($oRule) {
                    $oTask = $modelTsk->byRule($oRule, ['createIfNone' => true]);
                    if ($oTask) {
                        tms_object_merge($oTask, $oRule, ['type', 'state', 'limit', 'groups', 'schemas']);
                        $modelTop = $this->model('matter\enroll\topic', $oMatter);
                        $oTopic = $modelTop->byTask($oTask, ['createIfNone' => true]);
                    }
                }
            }
            if (in_array($oArguments->taskConfig->type, ['question', 'vote', 'answer', 'score'])) {
                if (empty($oTopic)) {
                    return [false, '指定的任务专题(' . $oArguments->taskConfig->id . ')不可用'];
                }
                $noticeURL .= '&page=topic&topic=' . $oTopic->id;
            } else if ($oArguments->taskConfig->type === 'baseline') {
                if (empty($oTask)) {
                    return [false, '指定的任务(' . $oArguments->taskConfig->id . ')不可用'];
                }
                $noticeURL .= '&page=enroll&rid=' . $oTask->rid;
            }
        }
        /* 通知接收人范围 */
        $receiverScope = empty($oArguments->receiver->scope) ? 'enroll' : $oArguments->receiver->scope;

        switch ($receiverScope) {
        case 'mschema':
            /* 优先发送给通讯录中的用户 */
            if (isset($oMatter->entryRule->scope->member) && $oMatter->entryRule->scope->member === 'Y' && isset($oMatter->entryRule->member)) {
                $modelMs = $this->model('site\user\memberschema');
                $modelMem = $this->model('site\user\member');
                $receivers = [];
                $oTmplTimerTaskParams->receiver = [];
                foreach ($oMatter->entryRule->member as $mschemaId => $oRule) {
                    $oMschema = $modelMs->byId($mschemaId, ['fields' => 'title,is_wx_fan', 'cascaded' => 'N']);
                    if ($oMschema->is_wx_fan === 'Y') {
                        $aOnce = $modelMem->byMschema($mschemaId, ['fields' => 'userid']);
                        $receivers = array_merge($receivers, $aOnce);
                        $oTmplTimerTaskParams->receiver[] = $oMschema->title;
                    }
                }
                if (empty($oTmplTimerTaskParams->receiver)) {
                    unset($oTmplTimerTaskParams->receiver);
                } else {
                    $oTmplTimerTaskParams->receiver = implode(',', $oTmplTimerTaskParams->receiver);
                }
            }
            break;
        case 'group':
            if (isset($oArguments->receiver->app)) {
                $oRecvApp = $oArguments->receiver->app;
                if (!empty($oRecvApp->id)) {
                    $oGrpApp = $this->model('matter\group')->byId($oRecvApp->id, ['fields' => 'title']);
                    if ($oGrpApp) {
                        $modelGrpTeam = $this->model('matter\group\team');
                        if (isset($oRecvApp->team->id)) {
                            $oGrpAppTeam = $modelGrpTeam->byId($oRecvApp->team->id);
                            if ($oGrpAppTeam) {
                                $receivers = $this->model('matter\group\record')->byTeam($oGrpAppTeam->team_id, ['fields' => 'userid']);
                                $oTmplTimerTaskParams->receiver = $oGrpAppTeam->title;
                            }
                        } else if (isset($oRecvApp->teams->id)) {
                            if (is_array($oRecvApp->teams->id) && count($oRecvApp->teams->id)) {
                                $receivers = [];
                                $receiverTeams = [];
                                foreach ($oRecvApp->teams->id as $teamId) {
                                    $oGrpAppTeam = $modelGrpTeam->byId($teamId);
                                    if ($oGrpAppTeam) {
                                        $teamReceivers = $this->model('matter\group\record')->byTeam($oGrpAppTeam->team_id, ['fields' => 'userid']);
                                        $receivers = array_merge($receivers, $teamReceivers);
                                        $receiverTeams[] = $oGrpAppTeam->title;
                                    }
                                }
                                $oTmplTimerTaskParams->receiver = implode(',', $receiverTeams);
                            }
                        } else {
                            $q = [
                                'distinct userid',
                                'xxt_group_record',
                                ['state' => 1, 'aid' => $oRecvApp->id],
                            ];
                            $receivers = $modelGrpTeam->query_objs_ss($q);
                            $oTmplTimerTaskParams->receiver = $oGrpApp->title;
                        }
                    }
                }
            } else if (!empty($oMatter->entryRule->group->id)) {
                $oGrpApp = $this->model('matter\group')->byId($oMatter->entryRule->group->id, ['fields' => 'title']);
                if ($oGrpApp) {
                    if (empty($oMatter->entryRule->group->team->id)) {
                        $q = [
                            'distinct userid',
                            'xxt_group_record',
                            ['state' => 1, 'aid' => $oMatter->entryRule->group->id],
                        ];
                        $receivers = $modelEnl->query_objs_ss($q);
                        $oTmplTimerTaskParams->receiver = $oGrpApp->title;
                    } else {
                        $oGrpAppTeam = $this->model('matter\group\team')->byId($oMatter->entryRule->group->team->id);
                        if ($oGrpAppTeam) {
                            $receivers = $this->model('matter\group\record')->byTeam($oMatter->entryRule->group->team->id, ['fields' => 'userid']);
                            $oTmplTimerTaskParams->receiver = $oGrpAppTeam->title;
                        }
                    }
                }
            }
            break;
        case 'enroll':
            /* 发送给记录填写人 */
            $modelUsr = $this->model('matter\enroll\user');
            $aOptions = [
                'rid' => 'ALL',
                'onlyEnrolled' => 'Y',
                'fields' => 'userid',
                'cascaded' => 'N',
            ];
            $enrollUsers = $modelUsr->enrolleeByApp($oMatter, '', '', $aOptions);
            $receivers = $enrollUsers->users;
            $oTmplTimerTaskParams->receiver = '全体填写人';
            break;
        }

        if (count($receivers) === 0) {
            return [false, '指定活动中没有接收人'];
        }

        return [true, $oMatter, $noticeURL, $receivers, $oTmplTimerTaskParams];
    }
    /**
     * 计划活动通知提醒
     */
    private function _plan($oMatter, $arguments) {
        $modelPlan = $this->model('matter\plan');
        $oMatter = $modelPlan->byId($oMatter->id, ['fields' => 'id,state,siteid,title,summary']);
        if (false === $oMatter || $oMatter->state !== '1') {
            return [false, '指定的活动不存在'];
        }
        /* 获得活动的进入链接 */
        if ($inviteUrl = $modelPlan->getInviteUrl($oMatter->id, $oMatter->siteid)) {
            $noticeURL = $inviteUrl;
        } else {
            $noticeURL = $oMatter->entryUrl;
        }
        $noticeURL .= '&origin=timer';

        /* 处理要发送的填写人 */
        $modelUsr = $this->model('matter\plan\user');
        $planUsers = $modelUsr->byApp($oMatter);
        $receivers = $planUsers->users;
        if (count($receivers) === 0) {
            return [false, '没有填写人'];
        }

        return [true, $oMatter, $noticeURL, $receivers, null];
    }
}