<?php
namespace matter\enroll;
/**
 * 记录活动用户事件
 */
class event_model extends \TMS_MODEL {
    /**
     * 提交记录事件名称
     */
    const SUBMIT_EVENT_NAME = 'site.matter.enroll.submit';
    /**
     * 用户分组提交记录事件名称
     */
    const GROUP_SUBMIT_EVENT_NAME = 'site.matter.enroll.group.submit';
    /**
     * 保存记录事件名称
     */
    const SAVE_EVENT_NAME = 'site.matter.enroll.save';
    /**
     * 用户A提交的填写记录获得新协作填写数据项
     */
    const GET_SUBMIT_COWORK_EVENT_NAME = 'site.matter.enroll.cowork.get.submit';
    /**
     * 用户A提交新协作填写记录
     */
    const DO_SUBMIT_COWORK_EVENT_NAME = 'site.matter.enroll.cowork.do.submit';
    /**
     * 用户A填写数据被点评
     */
    const GET_REMARK_EVENT_NAME = 'site.matter.enroll.data.get.remark';
    /**
     * 用户A填写的协作数据获得点评
     */
    const GET_REMARK_COWORK_EVENT_NAME = 'site.matter.enroll.cowork.get.remark';
    /**
     * 用户A点评别人的填写数据
     */
    const DO_REMARK_EVENT_NAME = 'site.matter.enroll.do.remark';
    /**
     * 用户A填写数据被赞同
     */
    const GET_LIKE_EVENT_NAME = 'site.matter.enroll.data.get.like';
    /**
     * 用户A填写数据被反对
     */
    const GET_DISLIKE_EVENT_NAME = 'site.matter.enroll.data.get.dislike';
    /**
     * 用户A赞同别人的填写数据
     */
    const DO_LIKE_EVENT_NAME = 'site.matter.enroll.data.do.like';
    /**
     * 用户A不赞同别人的填写数据
     */
    const DO_DISLIKE_EVENT_NAME = 'site.matter.enroll.data.do.dislike';
    /**
     * 用户A填写数据被赞同
     */
    const GET_LIKE_COWORK_EVENT_NAME = 'site.matter.enroll.cowork.get.like';
    /**
     * 用户A填写数据被反对
     */
    const GET_DISLIKE_COWORK_EVENT_NAME = 'site.matter.enroll.cowork.get.dislike';
    /**
     * 用户A赞同别人的填写的协作数据
     */
    const DO_LIKE_COWORK_EVENT_NAME = 'site.matter.enroll.cowork.do.like';
    /**
     * 用户A不赞同别人的填写的协作数据
     */
    const DO_DISLIKE_COWORK_EVENT_NAME = 'site.matter.enroll.cowork.do.dislike';
    /**
     * 用户A留言被赞同
     */
    const GET_LIKE_REMARK_EVENT_NAME = 'site.matter.enroll.remark.get.like';
    /**
     * 用户A留言被反对
     */
    const GET_DISLIKE_REMARK_EVENT_NAME = 'site.matter.enroll.remark.get.dislike';
    /**
     * 用户A赞同别人的留言
     */
    const DO_LIKE_REMARK_EVENT_NAME = 'site.matter.enroll.remark.do.like';
    /**
     * 用户A不赞同别人的留言
     */
    const DO_DISLIKE_REMARK_EVENT_NAME = 'site.matter.enroll.remark.do.dislike';
    /**
     * 推荐记录事件名称
     */
    const GET_AGREE_EVENT_NAME = 'site.matter.enroll.data.get.agree';
    /**
     * 推荐留言事件名称
     */
    const GET_AGREE_COWORK_EVENT_NAME = 'site.matter.enroll.cowork.get.agree';
    /**
     * 推荐留言事件名称
     */
    const GET_AGREE_REMARK_EVENT_NAME = 'site.matter.enroll.remark.get.agree';
    /**
     * 将用户留言转换设置为协作数据
     */
    const DO_REMARK_AS_COWORK_EVENT_NAME = 'site.matter.enroll.remark.as.cowork';
    /**
     * 获得题目投票
     */
    const GET_VOTE_SCHEMA_EVENT_NAME = 'site.matter.enroll.schema.get.vote';
    /**
     * 获得协作填写投票
     */
    const GET_VOTE_COWORK_EVENT_NAME = 'site.matter.enroll.cowork.get.vote';
    /**
     * 用户搜索记录
     */
    const SEARCH_RECORD_EVENT_NAME = 'site.matter.enroll.search';
    /**
     *
     */
    private function _getOperatorId($oOperator) {
        $operatorId = isset($oOperator->uid) ? $oOperator->uid : (isset($oOperator->userid) ? $oOperator->userid : (isset($oOperator->id) ? $oOperator->id : ''));
        return $operatorId;
    }
    /**
     *
     */
    private function _getOperatorName($oOperator) {
        $operatorName = isset($oOperator->nickname) ? $oOperator->nickname : (isset($oOperator->name) ? $oOperator->name : '');
        return $operatorName;
    }
    /**
     * 记录事件日志
     */
    public function _logEvent($oApp, $rid, $ek, $oTarget, $oEvent, $oOwnerEvent = null) {
        $oNewLog = new \stdClass;
        /* 事件 */
        $oNewLog->event_name = $oEvent->name;
        $oNewLog->event_op = $oEvent->op;
        $oNewLog->event_at = $oEvent->at;
        $oNewLog->earn_coin = isset($oEvent->coin) ? $oEvent->coin : 0;

        /* 活动 */
        $oNewLog->aid = $oApp->id;
        $oNewLog->siteid = $oApp->siteid;
        $oNewLog->rid = $rid;
        $oNewLog->enroll_key = $ek;

        /* 发起事件的用户 */
        $oOperator = $oEvent->user;
        $oOperatorId = $this->_getOperatorId($oOperator);
        $oNewLog->group_id = isset($oOperator->group_id) ? $oOperator->group_id : '';
        $oNewLog->userid = $oOperatorId;
        $oNewLog->nickname = $this->escape($this->_getOperatorName($oOperator));

        /* 事件操作的对象 */
        $oNewLog->target_id = $oTarget->id;
        $oNewLog->target_type = $oTarget->type;

        /* 事件操作的对象的创建用户 */
        if (isset($oOwnerEvent)) {
            $oOwner = $oOwnerEvent->user;
            $oNewLog->owner_userid = $oOwner->uid;
            if (!isset($oOwner->nickname)) {
                $modelUsr = $this->model('matter\enroll\user');
                $oOwnerUsr = $modelUsr->byId($oApp, $oOwner->uid, ['fields' => 'nickname']);
                if ($oOwnerUsr) {
                    $oNewLog->owner_nickname = $this->escape($oOwnerUsr->nickname);
                }
            } else {
                $oNewLog->owner_nickname = $this->escape($oOwner->nickname);
            }
            $oNewLog->owner_earn_coin = isset($oOwnerEvent->coin) ? $oOwnerEvent->coin : 0;
        }

        $oNewLog->id = $this->insert('xxt_enroll_log', $oNewLog, true);

        return $oNewLog;
    }
    /**
     * 更新用户汇总数据
     */
    public function _updateUsrData($oApp, $rid, $bJumpCreate, $oUser, $oUsrEventData, $fnUsrRndData = null, $fnUsrAppData = null, $fnUsrMisData = null) {
        $userid = $this->_getOperatorId($oUser);

        /* 记录活动中需要额外更新的数据 */
        $oUpdatedEnlUsrData = clone $oUsrEventData;
        if (isset($oUser->group_id)) {
            $oUpdatedEnlUsrData->group_id = $oUser->group_id;
        }

        /* 更新发起留言的活动用户轮次数据 */
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
        $oEnlUsrRnd = $modelUsr->byId($oApp, $userid, ['fields' => '*', 'rid' => $rid]);
        if (false === $oEnlUsrRnd) {
            if (!$bJumpCreate) {
                $oUpdatedEnlUsrData->rid = $rid;
                $oUpdateUsrData1 = clone $oUpdatedEnlUsrData;
                $oEnlUsrRnd = $modelUsr->add($oApp, $oUser, $oUpdateUsrData1);
            }
        } else {
            if (isset($fnUsrRndData)) {
                $oResult = $fnUsrRndData($oEnlUsrRnd);
                if ($oResult) {
                    $oUpdatedRndUsrData = clone $oUpdatedEnlUsrData;
                    foreach ($oResult as $k => $v) {
                        $oUpdatedRndUsrData->{$k} = $v;
                    }
                }
            }
            if (isset($oUpdatedRndUsrData)) {
                $oUpdateUsrData1 = $oUpdatedRndUsrData;
            } else {
                $oUpdateUsrData1 = $oUpdatedEnlUsrData;
            }
            if ($oEnlUsrRnd->state == 0) {
                $oUpdateUsrData1->state = 1;
            }
            $modelUsr->modify($oEnlUsrRnd, $oUpdateUsrData1);
        }
        /** 更新用户分组汇总数据 */
        if (isset($oEnlUsrRnd) && isset($oUpdateUsrData1)) {
            $this->model('matter\enroll\group')->modify($oEnlUsrRnd, $oUpdateUsrData1);
        }

        /* 如果存在匹配的汇总轮次，进行数据汇总 */
        $modelRnd = $this->model('matter\enroll\round');
        $oAssignedRnd = $modelRnd->byId($rid, ['fields' => 'rid,start_at']);
        if (false === $oAssignedRnd) {
            return 0;
        }
        $sumRnds = $modelRnd->getSummary($oApp, $oAssignedRnd->start_at, ['fields' => 'id,rid,title,start_at,state', 'includeRounds' => 'N']);
        if (!empty($sumRnds)) {
            foreach ($sumRnds as $oSumRnd) {
                if ($oSumRnd && $oSumRnd->state === '1') {
                    $oUpdatedEnlUsrSumData = $modelUsr->sumByRound($oApp, $oUser, $oSumRnd, $oUpdatedEnlUsrData);
                    if ($oUpdatedEnlUsrSumData) {
                        /* 用户在汇总轮次中的数据汇总 */
                        $oEnlUsrSum = $modelUsr->byId($oApp, $userid, ['fields' => '*', 'rid' => $oSumRnd->rid]);
                        if (false === $oEnlUsrSum) {
                            if (!$bJumpCreate) {
                                $oUpdatedEnlUsrSumData->rid = $oSumRnd->rid;
                                $oEnlUsrSum = $modelUsr->add($oApp, $oUser, $oUpdatedEnlUsrSumData);
                            }
                        } else {
                            if ($oEnlUsrSum->state == 0) {
                                $oUpdatedEnlUsrSumData->state = 1;
                            }
                            $modelUsr->modify($oEnlUsrSum, $oUpdatedEnlUsrSumData);
                        }
                        /** 更新用户分组汇总数据 */
                        if (isset($oEnlUsrRnd) && isset($oUpdatedEnlUsrSumData)) {
                            $this->model('matter\enroll\group')->modify($oEnlUsrSum, $oUpdatedEnlUsrSumData);
                        }
                    }
                }
            }
        }
        /* 用户在活动中的数据汇总 */
        $oEnlUsrApp = $modelUsr->byId($oApp, $userid, ['fields' => '*', 'rid' => 'ALL']);
        if (false === $oEnlUsrApp) {
            if (!$bJumpCreate) {
                $oUpdatedEnlUsrData->rid = 'ALL';
                $oUpdatedUsrData2 = clone $oUpdatedEnlUsrData;
                $oEnlUsrApp = $modelUsr->add($oApp, $oUser, $oUpdatedUsrData2);
            }
        } else {
            if (isset($fnUsrAppData)) {
                $oResult = $fnUsrAppData($oEnlUsrApp);
                if ($oResult) {
                    $oUpdatedAppUsrData = clone $oUpdatedEnlUsrData;
                    foreach ($oResult as $k => $v) {
                        $oUpdatedAppUsrData->{$k} = $v;
                    }
                }
            }
            if (isset($oUpdatedAppUsrData)) {
                $oUpdatedUsrData2 = $oUpdatedAppUsrData;
            } else {
                $oUpdatedUsrData2 = $oUpdatedEnlUsrData;
            }
            if ($oEnlUsrApp->state == 0) {
                $oUpdatedUsrData2->state = 1;
            }
            $modelUsr->modify($oEnlUsrApp, $oUpdatedUsrData2);
        }
        /** 更新用户分组汇总数据 */
        if (isset($oEnlUsrApp) && isset($oUpdatedUsrData2)) {
            $this->model('matter\enroll\group')->modify($oEnlUsrApp, $oUpdatedUsrData2);
        }

        /* 更新项目用户数据 */
        if (!empty($oApp->mission_id)) {
            $modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
            /* 项目中需要额外更新的数据 */
            $oUpdatedMisUsrData = clone $oUsrEventData;

            $oMission = $this->model('matter\mission')->byId($oApp->mission_id, ['fields' => 'siteid,id,user_app_type,user_app_id']);
            $oMisUser = $modelMisUsr->byId($oMission, $userid, ['fields' => '*']);
            /* 用户在项目中的所属分组 */
            if ($oMission->user_app_type === 'group') {
                $oMisUsrGrpApp = (object) ['id' => $oMission->user_app_id];
                $oMisGrpUser = $this->model('matter\group\record')->byUser($oMisUsrGrpApp, $oUser->uid, ['onlyOne' => true, 'team_id']);
                if (isset($oMisGrpUser->team_id)) {
                    $oUpdatedMisUsrData->group_id = $oMisGrpUser->team_id;
                }
            }
            if (false === $oMisUser) {
                if (!$bJumpCreate) {
                    $oMisUser = $modelMisUsr->add($oMission, $oUser, $oUpdatedMisUsrData);
                }
            } else {
                if (isset($fnUsrMisData)) {
                    $oResult = $fnUsrMisData($oMisUser);
                    if ($oResult) {
                        foreach ($oResult as $k => $v) {
                            $oUpdatedMisUsrData->{$k} = $v;
                        }
                    }
                }
                $modelMisUsr->modify($oMisUser, $oUpdatedMisUsrData);
            }
            /** 更新用户分组汇总数据 */
            if (isset($oEnlUsrRnd) && isset($oUpdatedMisUsrData)) {
                $this->model('matter\mission\group')->modify($oMisUser, $oUpdatedMisUsrData);
            }
        }

        return true;
    }
    /**
     * 更新用户分组汇总数据
     */
    public function _updateGrpData($oApp, $rid, $groupId, $oGrpEventData) {
        /* 记录活动中需要额外更新的数据 */
        $oUpdatedEnlGrpData = clone $oGrpEventData;

        /* 更新发起留言的活动用户轮次数据 */
        $modelGrp = $this->model('matter\enroll\group')->setOnlyWriteDbConn(true);
        $oEnlGrpRnd = $modelGrp->byId($oApp, $groupId, ['fields' => '*', 'rid' => $rid]);
        if (false === $oEnlGrpRnd) {
            return false;
        }
        if ($oEnlGrpRnd->state == 0) {
            $oUpdatedEnlGrpData->state = 1;
        }
        $modelGrp->modify($oEnlGrpRnd, $oUpdatedEnlGrpData);

        /* 用户在活动中的数据汇总 */
        $oEnlGrpApp = $modelGrp->byId($oApp, $groupId, ['fields' => '*', 'rid' => 'ALL']);
        if (false === $oEnlGrpApp) {
            return false;
        }
        if ($oEnlGrpApp->state == 0) {
            $oUpdatedEnlGrpData->state = 1;
        }
        $modelGrp->modify($oEnlGrpApp, $oUpdatedEnlGrpData);

        /* 更新项目用户数据 */
        if (!empty($oApp->mission_id)) {
            $oMission = $this->model('matter\mission')->byId($oApp->mission_id, ['fields' => 'siteid,id,user_app_type,user_app_id']);
            if ($oMission) {
                $modelMisGrp = $this->model('matter\mission\group')->setOnlyWriteDbConn(true);
                $oMisGrp = $modelMisGrp->byId($oMission, $groupId, ['fields' => '*']);
                if ($oMisGrp) {
                    $modelMisGrp->modify($oMisGrp, $oUpdatedEnlGrpData);
                }
            }
        }

        return true;
    }
    /**
     * 更新用户汇总数据
     */
    public function updateMisUsrData($oMission, $bJumpCreate, $oUser, $oUsrEventData, $fnUsrMisData = null) {
        $modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
        /* 项目中需要额外更新的数据 */
        $oUpdatedMisUsrData = clone $oUsrEventData;
        // unset($oUpdatedMisUsrData->modify_log);

        $oMisUser = $modelMisUsr->byId($oMission, $oUser->uid, ['fields' => '*']);
        /* 用户在项目中的所属分组 */
        if ($oMission->user_app_type === 'group') {
            $oMisUsrGrpApp = (object) ['id' => $oMission->user_app_id];
            $oMisGrpUser = $this->model('matter\group\record')->byUser($oMisUsrGrpApp, $oUser->uid, ['onlyOne' => true, 'team_id']);
            if (isset($oMisGrpUser->team_id)) {
                $oUpdatedMisUsrData->group_id = $oMisGrpUser->team_id;
            }
        }
        if (false === $oMisUser) {
            if (!$bJumpCreate) {
                $modelMisUsr->add($oMission, $oUser, $oUpdatedMisUsrData);
            }
        } else {
            if (isset($fnUsrMisData)) {
                $oResult = $fnUsrMisData($oMisUser);
                if ($oResult) {
                    foreach ($oResult as $k => $v) {
                        $oUpdatedMisUsrData->{$k} = $v;
                    }
                }
            }
            $modelMisUsr->modify($oMisUser, $oUpdatedMisUsrData);
        }

        return true;
    }
    /**
     * 用户提交记录
     */
    public function submitRecord($oApp, $oRecord, $oUser, $bSubmitNewRecord, $bReviseRecordBeyondRound = false) {
        $eventAt = isset($oRecord->enroll_at) ? $oRecord->enroll_at : time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
        $modelRnd = $this->model('matter\enroll\round');
        $oRecRnd = $modelRnd->byId($oRecord->rid, ['fields' => 'purpose,start_at,end_at,state']);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oUser->uid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::SUBMIT_EVENT_NAME;
        $oNewModifyLog->args = (object) ['id' => $oRecord->id];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->nickname = $this->escape($oUser->nickname);
        $oUpdatedUsrData->last_enroll_at = $eventAt;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        /* 只有常规轮次才将记录得分计入用户总分 */
        if (in_array($oRecRnd->purpose, ['C', 'S'])) {
            if (isset($oRecord->score->sum)) {
                $oUpdatedUsrData->score = $oRecord->score->sum;
            }
        }
        if ($oRecRnd->purpose === 'C') {
            /* 提交新记录 */
            if (true === $bSubmitNewRecord) {
                $oNewModifyLog->op .= '_New';
                /* 提交记录的积分奖励 */
                $aCoinResult = $modelUsr->awardCoin($oApp, $oUser->uid, $oRecord->rid, self::SUBMIT_EVENT_NAME);
                if (!empty($aCoinResult[1])) {
                    $oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
                }
                $oUpdatedUsrData->enroll_num = 1;
            } else if (true === $bReviseRecordBeyondRound) {
                $oUpdatedUsrData->revise_num = 1;
            }
        }
        /* 更新用户汇总数据 */
        $fnUpdateRndUser = function ($oUserData) use ($oRecord, $oUser) {
            $oResult = new \stdClass;
            if (isset($oUser->group_id)) {
                $oResult->group_id = $oUser->group_id;
            }
            return $oResult;
        };
        $fnUpdateAppUser = function ($oUserData) use ($oApp, $oRecord, $oUser) {
            $oResult = new \stdClass;

            $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
            $sumScore = $modelUsr->query_val_ss([
                'sum(score)',
                'xxt_enroll_user',
                ['siteid' => $oApp->siteid, 'aid' => $oApp->id, 'userid' => $oUser->uid, 'state' => 1, 'purpose' => 'C', 'rid' => (object) ['op' => '<>', 'pat' => 'ALL']],
            ]);

            $oResult->score = $sumScore;

            return $oResult;
        };
        // 更新用户汇总数据
        $this->_updateUsrData($oApp, $oRecord->rid, false, $oUser, $oUpdatedUsrData, $fnUpdateRndUser, $fnUpdateAppUser);

        // 如果日志插入失败需要重新增加
        if (isset($aCoinResult) && $aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $oUser->uid, $oRecord->rid, self::SUBMIT_EVENT_NAME);
        }
        // 如果分组内用户全都提交，用户分组获得的全员提交积分
        if (!empty($oUser->group_id)) {
            $modelGrp = $this->model('matter\enroll\group');
            $aCoinResult = $modelGrp->awardCoin($oApp, $oUser->group_id, $oRecord->rid, self::GROUP_SUBMIT_EVENT_NAME);
            if (!empty($aCoinResult[1])) {
                // 检查是否组内所有人都提交了记录
                $aResult = $modelGrp->isAllSubmit($oApp, $oRecord->rid, $oUser->group_id);
                if (true === $aResult[0]) {
                    $this->_updateGrpData($oApp, $oRecord->rid, $oUser->group_id, (object) ['group_total_coin' => $aCoinResult[1]]);
                }
            }
        }

        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecord->id;
        $oTarget->type = 'record';
        $oEvent = new \stdClass;
        $oEvent->name = self::SUBMIT_EVENT_NAME;
        $oEvent->op = $bSubmitNewRecord ? 'New' : 'Update';
        $oEvent->at = $eventAt;
        $oEvent->user = $oUser;
        $oEvent->coin = isset($oUpdatedUsrData->user_total_coin) ? $oUpdatedUsrData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent);

        return true;
    }
    /**
     * 用户保存记录
     */
    public function saveRecord($oApp, $oRecord, $oUser) {
        $eventAt = isset($oRecord->enroll_at) ? $oRecord->enroll_at : time();

        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecord->id;
        $oTarget->type = 'record';

        $oEvent = new \stdClass;
        $oEvent->name = self::SAVE_EVENT_NAME;
        $oEvent->op = 'Save';
        $oEvent->at = $eventAt;
        $oEvent->user = $oUser;
        $oEvent->coin = 0;

        $this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent);

        return true;
    }
    /**
     * 填写记录获得提交协作填写项
     */
    public function submitCowork($oApp, $oRecData, $oItem, $oOperator, $bSubmitNewItem = true) {
        $oOperatorData = $this->_doSubmitCowork($oApp, $oItem, $oOperator, $bSubmitNewItem);
        $oOwnerData = $this->_getSubmitCowork($oApp, $oRecData, $oItem, $oOperator, $bSubmitNewItem);

        $eventAt = isset($oItem->submit_at) ? $oItem->submit_at : time();

        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oItem->id;
        $oTarget->type = 'cowork';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_SUBMIT_COWORK_EVENT_NAME;
        $oEvent->op = 'New';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
        $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        return $oOperatorData;
    }
    /**
     * 执行提交协作填写项
     */
    private function _doSubmitCowork($oApp, $oItem, $oUser, $bSubmitNewItem = true) {
        $eventAt = isset($oItem->submit_at) ? $oItem->submit_at : time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->op = self::DO_SUBMIT_COWORK_EVENT_NAME;
        $oNewModifyLog->userid = $oUser->uid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->args = (object) ['id' => $oItem->id];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->nickname = $this->escape($oUser->nickname);
        $oUpdatedUsrData->last_do_cowork_at = $eventAt;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        /* 提交新协作数据项 */
        if (true === $bSubmitNewItem) {
            $oNewModifyLog->op .= '_New';
            /* 提交记录的积分奖励 */
            $aCoinResult = $modelUsr->awardCoin($oApp, $oUser->uid, $oItem->rid, self::DO_SUBMIT_COWORK_EVENT_NAME);
            if (!empty($aCoinResult[1])) {
                $oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
            }
            $oUpdatedUsrData->do_cowork_num = 1;
        }

        $this->_updateUsrData($oApp, $oItem->rid, false, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if (isset($aCoinResult) && $aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $oUser->uid, $oItem->rid, self::DO_SUBMIT_COWORK_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 填写记录获得提交协作填写项
     */
    private function _getSubmitCowork($oApp, $oRecData, $oItem, $oOperator, $bSubmitNewItem = true) {
        if (empty($oRecData->userid)) {
            return false;
        }
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oOperator->uid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_SUBMIT_COWORK_EVENT_NAME;
        $oNewModifyLog->args = (object) ['id' => $oItem->id];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_cowork_at = $eventAt;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;
        /* 提交新协作数据项 */
        if (true === $bSubmitNewItem) {
            $oNewModifyLog->op .= '_New';
            /* 提交记录的积分奖励 */
            $aCoinResult = $modelUsr->awardCoin($oApp, $oOperator->uid, $oItem->rid, self::GET_SUBMIT_COWORK_EVENT_NAME);
            if (!empty($aCoinResult[1])) {
                $oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
            }
            $oUpdatedUsrData->cowork_num = 1;
        }

        $oUser = (object) ['uid' => $oRecData->userid];

        $this->_updateUsrData($oApp, $oRecData->rid, true, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if (isset($aCoinResult) && $aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $oOperator->uid, $oItem->rid, self::GET_SUBMIT_COWORK_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 评论转成协作数据
     */
    public function remarkAsCowork($oApp, $oRecData, $oItem, $oRemark, $oOperator) {
        //$oOperatorData = $this->_doSubmitCowork($oApp, $oItem, $oOperator, true);
        //$oOwnerData = $this->_getSubmitCowork($oApp, $oRecData, $oItem, $oOperator, true);

        $eventAt = isset($oItem->submit_at) ? $oItem->submit_at : time();

        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oItem->id;
        $oTarget->type = 'cowork';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_REMARK_AS_COWORK_EVENT_NAME;
        $oEvent->op = 'New';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        //$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
        //$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
    }
    /**
     * 撤销协作填写项
     */
    public function removeCowork($oApp, $oRecData, $oItem, $oOperator) {
        $this->_unDoSubmitCowork($oApp, $oItem, $oOperator);
        $this->_unGetSubmitCowork($oApp, $oRecData, $oItem, $oOperator);

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oItem->id;
        $oTarget->type = 'cowork';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_SUBMIT_COWORK_EVENT_NAME;
        $oEvent->op = 'Del';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];

        $oLog = $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        /* 更新被撤销的事件 */
        $this->update(
            'xxt_enroll_log',
            ['undo_event_id' => $oLog->id],
            ['target_id' => $oItem->id, 'target_type' => 'cowork', 'event_name' => self::DO_SUBMIT_COWORK_EVENT_NAME, 'event_op' => 'New', 'undo_event_id' => 0]
        );
    }
    /**
     * 撤销协作填写项
     */
    private function _unDoSubmitCowork($oApp, $oItem, $oUser) {
        $eventAt = time();
        /* 日志回退函数 */
        $fnRollback = function ($oUserData) use ($oItem) {
            $aResult = [];
            $oLastestModifyLog = null; // 最近一次事件日志
            $oBeforeModifyLog = null; // 操作指定对象对应的事件日志
            foreach ($oUserData->modify_log as $oLog) {
                if (isset($oLog->op) && $oLog->op === self::DO_SUBMIT_COWORK_EVENT_NAME . '_New') {
                    if (isset($oLog->args->id)) {
                        if (!isset($oLastestModifyLog)) {
                            $oLastestModifyLog = $oLog;
                        }
                        if ($oLog->args->id === $oItem->id) {
                            $oBeforeModifyLog = $oLog;
                            break;
                        }
                    }
                }
            }
            /* 回退积分奖励 */
            if (!empty($oBeforeModifyLog->coin)) {
                $aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
            }
            /* 最后一次事件发生时间 */
            if (!empty($oLastestModifyLog->at) && !empty($oUserData->last_do_cowork_at) && (int) $oLastestModifyLog->at > (int) $oUserData->last_do_cowork_at) {
                $aResult['last_do_cowork_at'] = $oLastestModifyLog->at;
            } else if ($oLastestModifyLog === $oBeforeModifyLog) {
                $aResult['last_do_cowork_at'] = 0;
            }
            if (count($aResult) === 0) {
                return false;
            }

            return (object) $aResult;
        };

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oUser->uid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::DO_SUBMIT_COWORK_EVENT_NAME . '_Del';
        $oNewModifyLog->args = (object) ['id' => $oItem->id];
        /* 更新的数据 */
        $oUpdatedData = (object) [
            'do_cowork_num' => -1,
            'modify_log' => $oNewModifyLog,
        ];

        $this->_updateUsrData($oApp, $oItem->rid, false, $oUser, $oUpdatedData, $fnRollback, $fnRollback, $fnRollback);

        return $oUpdatedData;
    }
    /**
     * 撤销协作填写数据项
     */
    private function _unGetSubmitCowork($oApp, $oRecData, $oItem, $oOperator) {
        if (empty($oRecData->userid)) {
            return false;
        }
        $eventAt = time();
        /* 日志回退函数 */
        $fnRollback = function ($oUserData) use ($oItem) {
            $aResult = [];
            $oLastestModifyLog = null; // 最近一次事件日志
            $oBeforeModifyLog = null; // 操作指定对象对应的事件日志
            foreach ($oUserData->modify_log as $oLog) {
                if (isset($oLog->op) && $oLog->op === self::GET_SUBMIT_COWORK_EVENT_NAME . '_New') {
                    if (isset($oLog->args->id)) {
                        if (!isset($oLastestModifyLog)) {
                            $oLastestModifyLog = $oLog;
                        }
                        if ($oLog->args->id === $oItem->id) {
                            $oBeforeModifyLog = $oLog;
                            break;
                        }
                    }
                }
            }
            /* 回退积分奖励 */
            if (!empty($oBeforeModifyLog->coin)) {
                $aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
            }
            /* 最后一次事件发生时间 */
            if (!empty($oLastestModifyLog->at) && !empty($oUserData->last_cowork_at) && (int) $oLastestModifyLog->at > (int) $oUserData->last_cowork_at) {
                $aResult['last_cowork_at'] = $oLastestModifyLog->at;
            } else if ($oLastestModifyLog === $oBeforeModifyLog) {
                $aResult['last_cowork_at'] = 0;
            }
            if (count($aResult) === 0) {
                return false;
            }

            return (object) $aResult;
        };

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oOperator->uid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_SUBMIT_COWORK_EVENT_NAME . '_Del';
        $oNewModifyLog->args = (object) ['id' => $oItem->id];
        /* 更新的数据 */
        $oUpdatedData = (object) [
            'cowork_num' => -1,
            'modify_log' => $oNewModifyLog,
        ];

        $oUser = (object) ['uid' => $oRecData->userid];

        $this->_updateUsrData($oApp, $oItem->rid, false, $oUser, $oUpdatedData, $fnRollback, $fnRollback, $fnRollback);

        return true;
    }
    /**
     * 留言填写记录
     */
    public function remarkRecord($oApp, $oRecord, $oRemark, $oOperator) {
        $oOperatorData = $this->_doRemarkRecOrData($oApp, $oRecord, $oRemark, $oOperator, 'record');
        $oOwnerData = $this->_getRemarkRecOrData($oApp, $oRecord, $oRemark, $oOperator, 'record');

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecord->id;
        $oTarget->type = 'record';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_REMARK_EVENT_NAME;
        $oEvent->op = 'New';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecord->userid];
        $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        return $oOperatorData;
    }
    /**
     * 留言填写数据
     */
    public function remarkRecData($oApp, $oRecOrData, $oRemark, $oOperator) {
        $oOperatorData = $this->_doRemarkRecOrData($oApp, $oRecOrData, $oRemark, $oOperator, 'record.data');
        $oOwnerData = $this->_getRemarkRecOrData($oApp, $oRecOrData, $oRemark, $oOperator, 'record.data');

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecOrData->id;
        $oTarget->type = 'record.data';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_REMARK_EVENT_NAME;
        $oEvent->op = 'New';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecOrData->userid];
        $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRecOrData->rid, $oRecOrData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        return $oOperatorData;
    }
    /**
     * 留言填写数据
     */
    public function remarkCowork($oApp, $oCowork, $oRemark, $oOperator) {
        $oOperatorData = $this->_doRemarkRecOrData($oApp, $oCowork, $oRemark, $oOperator, 'cowork');
        $oOwnerData = $this->_getRemarkCowork($oApp, $oCowork, $oRemark, $oOperator);

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oCowork->id;
        $oTarget->type = 'cowork';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_REMARK_EVENT_NAME;
        $oEvent->op = 'New';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oCowork->userid];
        $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oCowork->rid, $oCowork->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        return $oOperatorData;
    }
    /**
     * 留言填写记录或数据
     */
    private function _doRemarkRecOrData($oApp, $oRecOrData, $oRemark, $oOperator, $logArgType) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::DO_REMARK_EVENT_NAME . '_New';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_do_remark_at = $eventAt;
        $oUpdatedUsrData->do_remark_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $aCoinResult = $modelUsr->awardCoin($oApp, $operatorId, $oRemark->rid, self::DO_REMARK_EVENT_NAME);
        if (!empty($aCoinResult[1])) {
            $oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
        }

        $this->_updateUsrData($oApp, $oRemark->rid, false, $oOperator, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $operatorId, $oRemark->rid, self::DO_REMARK_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 填写记录或数据获得留言
     */
    private function _getRemarkRecOrData($oApp, $oRecOrData, $oRemark, $oOperator, $logArgType) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_REMARK_EVENT_NAME . '_New';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_remark_at = $eventAt;
        $oUpdatedUsrData->remark_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $aCoinResult = $modelUsr->awardCoin($oApp, $operatorId, $oRemark->rid, self::GET_REMARK_EVENT_NAME);
        if (!empty($aCoinResult[1])) {
            $oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
        }

        $oUser = (object) ['uid' => $oRecOrData->userid];

        $this->_updateUsrData($oApp, $oRemark->rid, false, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $operatorId, $oRemark->rid, self::GET_REMARK_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 填写协作数据获得留言
     */
    private function _getRemarkCowork($oApp, $oRecOrData, $oRemark, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_REMARK_COWORK_EVENT_NAME . '_New';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_remark_cowork_at = $eventAt;
        $oUpdatedUsrData->remark_cowork_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $aCoinResult = $modelUsr->awardCoin($oApp, $operatorId, $oRemark->rid, self::GET_REMARK_COWORK_EVENT_NAME);
        if (!empty($aCoinResult[1])) {
            $oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
        }

        $oUser = (object) ['uid' => $oRecOrData->userid];

        $this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $operatorId, $oRemark->rid, self::GET_REMARK_COWORK_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 更新留言
     */
    public function updateRemark($oApp, $oRemark, $oOperator) {
        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRemark->id;
        $oTarget->type = 'remark';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_REMARK_EVENT_NAME;
        $oEvent->op = 'Upd';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = 0;

        $this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent);
    }
    /**
     * 撤销留言
     */
    public function removeRemark($oApp, $oRemark, $oOperator) {
        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRemark->id;
        $oTarget->type = 'remark';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_REMARK_EVENT_NAME;
        $oEvent->op = 'Del';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = 0;

        $this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent);
    }
    /**
     * 赞同填写记录
     * 同一条记录只有第一次点赞时才给积分奖励
     */
    public function likeRecord($oApp, $oRecord, $oOperator) {
        $oOperatorData = $this->_doLikeRecOrData($oApp, $oRecord, $oOperator, 'record');
        $oOwnerData = $this->_getLikeRecOrData($oApp, $oRecord, $oOperator, 'record');

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecord->id;
        $oTarget->type = 'record';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_LIKE_EVENT_NAME;
        $oEvent->op = 'Y';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecord->userid];
        $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        return $oOperatorData;
    }
    /**
     * 不赞同填写记录
     *
     */
    public function dislikeRecord($oApp, $oRecord, $oOperator) {
        $oOperatorData = $this->_doDislikeRecOrData($oApp, $oRecord, $oOperator, 'record');
        $oOwnerData = $this->_getDislikeRecOrData($oApp, $oRecord, $oOperator, 'record');

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecord->id;
        $oTarget->type = 'record';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_DISLIKE_EVENT_NAME;
        $oEvent->op = 'Y';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecord->userid];
        $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        return $oOperatorData;
    }
    /**
     * 赞同填写记录数据
     */
    public function likeRecData($oApp, $oRecData, $oOperator) {
        $oOperatorData = $this->_doLikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
        $oOwnerData = $this->_getLikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecData->id;
        $oTarget->type = 'record.data';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_LIKE_EVENT_NAME;
        $oEvent->op = 'Y';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
        $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
    }
    /**
     * 反对填写记录数据
     */
    public function dislikeRecData($oApp, $oRecData, $oOperator) {
        $oOperatorData = $this->_doDislikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
        $oOwnerData = $this->_getDislikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecData->id;
        $oTarget->type = 'record.data';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_DISLIKE_EVENT_NAME;
        $oEvent->op = 'Y';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
        $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
    }
    /**
     * 赞同填写协作记录数据
     */
    public function likeCowork($oApp, $oRecData, $oOperator) {
        $oOperatorData = $this->_doLikeCowork($oApp, $oRecData, $oOperator);
        $oOwnerData = $this->_getLikeCowork($oApp, $oRecData, $oOperator);

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecData->id;
        $oTarget->type = 'cowork';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_LIKE_COWORK_EVENT_NAME;
        $oEvent->op = 'Y';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
        $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
    }
    /**
     * 反对填写协作记录数据
     */
    public function dislikeCowork($oApp, $oRecData, $oOperator) {
        $oOperatorData = $this->_doDislikeCowork($oApp, $oRecData, $oOperator);
        $oOwnerData = $this->_getDislikeCowork($oApp, $oRecData, $oOperator);

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecData->id;
        $oTarget->type = 'cowork';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_DISLIKE_COWORK_EVENT_NAME;
        $oEvent->op = 'Y';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
        $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
    }
    /**
     *
     */
    private function _doLikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::DO_LIKE_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_do_like_at = $eventAt;
        $oUpdatedUsrData->do_like_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $this->_updateUsrData($oApp, $oRecOrData->rid, false, $oOperator, $oUpdatedUsrData);

        return $oUpdatedUsrData;
    }
    /**
     *
     */
    private function _doDislikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::DO_DISLIKE_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_do_dislike_at = $eventAt;
        $oUpdatedUsrData->do_dislike_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $this->_updateUsrData($oApp, $oRecOrData->rid, false, $oOperator, $oUpdatedUsrData);

        return $oUpdatedUsrData;
    }
    /**
     *
     */
    private function _doLikeCowork($oApp, $oRecOrData, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::DO_LIKE_COWORK_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_do_like_cowork_at = $eventAt;
        $oUpdatedUsrData->do_like_cowork_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $this->_updateUsrData($oApp, $oRecOrData->rid, false, $oOperator, $oUpdatedUsrData);

        return $oUpdatedUsrData;
    }
    /**
     *
     */
    private function _doDislikeCowork($oApp, $oRecOrData, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::DO_DISLIKE_COWORK_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_do_dislike_cowork_at = $eventAt;
        $oUpdatedUsrData->do_dislike_cowork_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $this->_updateUsrData($oApp, $oRecOrData->rid, false, $oOperator, $oUpdatedUsrData);

        return $oUpdatedUsrData;
    }
    /**
     * 填写记录或数据被点赞
     */
    private function _getLikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRecOrData->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_LIKE_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_like_at = $eventAt;
        $oUpdatedUsrData->like_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;
        $aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GET_LIKE_EVENT_NAME);
        if (!empty($aCoinResult[1])) {
            $oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
        }
        $oUser = (object) ['uid' => $oRecOrData->userid];

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GET_LIKE_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 填写记录或数据被反对
     */
    private function _getDislikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRecOrData->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_DISLIKE_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_dislike_at = $eventAt;
        $oUpdatedUsrData->dislike_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;
        $aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GET_DISLIKE_EVENT_NAME);
        if (!empty($aCoinResult[1])) {
            $oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
        }
        $oUser = (object) ['uid' => $oRecOrData->userid];

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GET_DISLIKE_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 填写记录或数据被点赞
     */
    private function _getLikeCowork($oApp, $oRecOrData, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRecOrData->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_LIKE_COWORK_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_like_cowork_at = $eventAt;
        $oUpdatedUsrData->like_cowork_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;
        $aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GET_LIKE_COWORK_EVENT_NAME);
        if (!empty($aCoinResult[1])) {
            $oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
        }
        $oUser = (object) ['uid' => $oRecOrData->userid];

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GET_LIKE_COWORK_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 填写记录或数据被反对
     */
    private function _getDislikeCowork($oApp, $oRecOrData, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRecOrData->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_DISLIKE_COWORK_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_dislike_cowork_at = $eventAt;
        $oUpdatedUsrData->dislike_cowork_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;
        $aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GET_DISLIKE_COWORK_EVENT_NAME);
        if (!empty($aCoinResult[1])) {
            $oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
        }
        $oUser = (object) ['uid' => $oRecOrData->userid];

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GET_DISLIKE_COWORK_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 撤销填写记录点赞
     */
    public function undoLikeRecord($oApp, $oRecord, $oOperator) {
        $this->_undoLikeRecOrData($oApp, $oRecord, $oOperator, 'record');
        $this->_undoGetLikeRecOrData($oApp, $oRecord, $oOperator, 'record');

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecord->id;
        $oTarget->type = 'record';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_LIKE_EVENT_NAME;
        $oEvent->op = 'N';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecord->userid];

        $oLog = $this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        /* 更新被撤销的事件 */
        $this->update(
            'xxt_enroll_log',
            ['undo_event_id' => $oLog->id],
            ['target_id' => $oRecord->id, 'target_type' => 'record', 'event_name' => self::DO_LIKE_EVENT_NAME, 'event_op' => 'Y', 'undo_event_id' => 0]
        );
    }
    /**
     * 撤销填写记录反对
     */
    public function undoDislikeRecord($oApp, $oRecord, $oOperator) {
        $this->_undoDislikeRecOrData($oApp, $oRecord, $oOperator, 'record');
        $this->_undoGetDislikeRecOrData($oApp, $oRecord, $oOperator, 'record');

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecord->id;
        $oTarget->type = 'record';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_DISLIKE_EVENT_NAME;
        $oEvent->op = 'N';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecord->userid];

        $oLog = $this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        /* 更新被撤销的事件 */
        $this->update(
            'xxt_enroll_log',
            ['undo_event_id' => $oLog->id],
            ['target_id' => $oRecord->id, 'target_type' => 'record', 'event_name' => self::DO_DISLIKE_EVENT_NAME, 'event_op' => 'Y', 'undo_event_id' => 0]
        );
    }
    /**
     * 撤销填写数据点赞
     */
    public function undoLikeRecData($oApp, $oRecData, $oOperator) {
        $this->_undoLikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
        $this->_undoGetLikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecData->id;
        $oTarget->type = 'record.data';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_LIKE_EVENT_NAME;
        $oEvent->op = 'N';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];

        $oLog = $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        /* 更新被撤销的事件 */
        $this->update(
            'xxt_enroll_log',
            ['undo_event_id' => $oLog->id],
            ['target_id' => $oRecData->id, 'target_type' => 'record.data', 'event_name' => self::DO_LIKE_EVENT_NAME, 'event_op' => 'Y', 'undo_event_id' => 0]
        );
    }
    /**
     * 撤销填写数据反对
     */
    public function undoDislikeRecData($oApp, $oRecData, $oOperator) {
        $this->_undoDislikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
        $this->_undoGetDislikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecData->id;
        $oTarget->type = 'record.data';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_DISLIKE_EVENT_NAME;
        $oEvent->op = 'N';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];

        $oLog = $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        /* 更新被撤销的事件 */
        $this->update(
            'xxt_enroll_log',
            ['undo_event_id' => $oLog->id],
            ['target_id' => $oRecData->id, 'target_type' => 'record.data', 'event_name' => self::DO_DISLIKE_EVENT_NAME, 'event_op' => 'Y', 'undo_event_id' => 0]
        );
    }
    /**
     * 撤销填写数据点赞
     */
    public function undoLikeCowork($oApp, $oCowork, $oOperator) {
        $this->_undoLikeCowork($oApp, $oCowork, $oOperator);
        $this->_undoGetLikeCowork($oApp, $oCowork, $oOperator);

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oCowork->id;
        $oTarget->type = 'cowork';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_LIKE_COWORK_EVENT_NAME;
        $oEvent->op = 'N';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oCowork->userid];

        $oLog = $this->_logEvent($oApp, $oCowork->rid, $oCowork->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        /* 更新被撤销的事件 */
        $this->update(
            'xxt_enroll_log',
            ['undo_event_id' => $oLog->id],
            ['target_id' => $oCowork->id, 'target_type' => 'cowork', 'event_name' => self::DO_LIKE_EVENT_NAME, 'event_op' => 'Y', 'undo_event_id' => 0]
        );
    }
    /**
     * 撤销填写数据反对
     */
    public function undoDislikeCowork($oApp, $oCowork, $oOperator) {
        $this->_undoDislikeCowork($oApp, $oCowork, $oOperator);
        $this->_undoGetDislikeCowork($oApp, $oCowork, $oOperator);

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oCowork->id;
        $oTarget->type = 'cowork';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_DISLIKE_COWORK_EVENT_NAME;
        $oEvent->op = 'N';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oCowork->userid];

        $oLog = $this->_logEvent($oApp, $oCowork->rid, $oCowork->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        /* 更新被撤销的事件 */
        $this->update(
            'xxt_enroll_log',
            ['undo_event_id' => $oLog->id],
            ['target_id' => $oCowork->id, 'target_type' => 'cowork', 'event_name' => self::DO_DISLIKE_EVENT_NAME, 'event_op' => 'Y', 'undo_event_id' => 0]
        );
    }
    /**
     * 撤销赞同操作
     */
    private function _undoLikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
        $operatorId = $this->_getOperatorId($oOperator);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = time();
        $oNewModifyLog->op = self::DO_LIKE_EVENT_NAME . '_N';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->do_like_num = -1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        /* 日志回退函数 */
        $fnRollback = function ($oUserData) use ($oRecOrData, $logArgType) {
            $aResult = []; // 要更新的数据
            if ($oUserData && count($oUserData->modify_log)) {
                $oLastestModifyLog = null; // 最近一次事件日志
                $oBeforeModifyLog = null; // 操作指定对象对应的事件日志
                $aRollbackLogs = []; // 插销操作日志
                foreach ($oUserData->modify_log as $oLog) {
                    if ($oLog->op === self::DO_LIKE_EVENT_NAME . '_Y') {
                        if (isset($oLog->args->type) && isset($oLog->args->id)) {
                            /* 检查是否是已经撤销的操作 */
                            $bRollbacked = false;
                            foreach ($aRollbackLogs as $oRollbackLog) {
                                if ($oLog->args->type === $oRollbackLog->args->type && $oLog->args->id === $oRollbackLog->args->id) {
                                    $bRollbacked = true;
                                    break;
                                }
                            }
                            if ($bRollbacked) {
                                continue;
                            }
                            /* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
                            $oLastestModifyLog = $oLog;
                            /* 由撤销的操作产生的日志 */
                            if (empty($oBeforeModifyLog)) {
                                if ($oLog->args->id === $oRecOrData->id && $oLog->args->type === $logArgType) {
                                    $oBeforeModifyLog = $oLog;
                                }
                            }
                            if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
                                break;
                            }
                        }
                    } else if ($oLog->op === self::DO_LIKE_EVENT_NAME . '_N') {
                        $aRollbackLogs[] = $oLog;
                    }
                }
                /* 最后一次事件发生时间 */
                if ($oBeforeModifyLog === $oLastestModifyLog) {
                    $aResult['last_do_like_at'] = 0;
                } else if (!empty($oLastestModifyLog->at)) {
                    $aResult['last_do_like_at'] = $oLastestModifyLog->at;
                }
            }
            if (empty($aResult)) {
                return false;
            }
            return (object) $aResult;
        };

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oOperator, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

        return $oUpdatedUsrData;
    }
    /**
     * 撤销反对操作
     */
    private function _undoDislikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
        $operatorId = $this->_getOperatorId($oOperator);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = time();
        $oNewModifyLog->op = self::DO_DISLIKE_EVENT_NAME . '_N';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->do_dislike_num = -1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        /* 日志回退函数 */
        $fnRollback = function ($oUserData) use ($oRecOrData, $logArgType) {
            $aResult = []; // 要更新的数据
            if ($oUserData && count($oUserData->modify_log)) {
                $oLastestModifyLog = null; // 最近一次事件日志
                $oBeforeModifyLog = null; // 操作指定对象对应的事件日志
                $aRollbackLogs = []; // 插销操作日志
                foreach ($oUserData->modify_log as $oLog) {
                    if ($oLog->op === self::DO_DISLIKE_EVENT_NAME . '_Y') {
                        if (isset($oLog->args->type) && isset($oLog->args->id)) {
                            /* 检查是否是已经撤销的操作 */
                            $bRollbacked = false;
                            foreach ($aRollbackLogs as $oRollbackLog) {
                                if ($oLog->args->type === $oRollbackLog->args->type && $oLog->args->id === $oRollbackLog->args->id) {
                                    $bRollbacked = true;
                                    break;
                                }
                            }
                            if ($bRollbacked) {
                                continue;
                            }
                            /* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
                            $oLastestModifyLog = $oLog;
                            /* 由撤销的操作产生的日志 */
                            if (empty($oBeforeModifyLog)) {
                                if ($oLog->args->id === $oRecOrData->id && $oLog->args->type === $logArgType) {
                                    $oBeforeModifyLog = $oLog;
                                }
                            }
                            if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
                                break;
                            }
                        }
                    } else if ($oLog->op === self::DO_DISLIKE_EVENT_NAME . '_N') {
                        $aRollbackLogs[] = $oLog;
                    }
                }
                /* 最后一次事件发生时间 */
                if ($oBeforeModifyLog === $oLastestModifyLog) {
                    $aResult['last_do_dislike_at'] = 0;
                } else if (!empty($oLastestModifyLog->at)) {
                    $aResult['last_do_dislike_at'] = $oLastestModifyLog->at;
                }
            }
            if (empty($aResult)) {
                return false;
            }
            return (object) $aResult;
        };

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oOperator, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

        return $oUpdatedUsrData;
    }
    /**
     * 撤销赞同操作
     */
    private function _undoLikeCowork($oApp, $oRecOrData, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = time();
        $oNewModifyLog->op = self::DO_LIKE_COWORK_EVENT_NAME . '_N';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->do_like_cowork_num = -1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        /* 日志回退函数 */
        $fnRollback = function ($oUserData) use ($oRecOrData) {
            $aResult = []; // 要更新的数据
            if ($oUserData && count($oUserData->modify_log)) {
                $oLastestModifyLog = null; // 最近一次事件日志
                $oBeforeModifyLog = null; // 操作指定对象对应的事件日志
                $aRollbackLogs = []; // 插销操作日志
                foreach ($oUserData->modify_log as $oLog) {
                    if ($oLog->op === self::DO_LIKE_COWORK_EVENT_NAME . '_Y') {
                        if (isset($oLog->args->id)) {
                            /* 检查是否是已经撤销的操作 */
                            $bRollbacked = false;
                            foreach ($aRollbackLogs as $oRollbackLog) {
                                if ($oLog->args->id === $oRollbackLog->args->id) {
                                    $bRollbacked = true;
                                    break;
                                }
                            }
                            if ($bRollbacked) {
                                continue;
                            }
                            /* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
                            $oLastestModifyLog = $oLog;
                            /* 由撤销的操作产生的日志 */
                            if (empty($oBeforeModifyLog)) {
                                if ($oLog->args->id === $oRecOrData->id) {
                                    $oBeforeModifyLog = $oLog;
                                }
                            }
                            if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
                                break;
                            }
                        }
                    } else if ($oLog->op === self::DO_LIKE_COWORK_EVENT_NAME . '_N') {
                        $aRollbackLogs[] = $oLog;
                    }
                }
                /* 最后一次事件发生时间 */
                if ($oBeforeModifyLog === $oLastestModifyLog) {
                    $aResult['last_do_like_cowork_at'] = 0;
                } else if (!empty($oLastestModifyLog->at)) {
                    $aResult['last_do_like_cowork_at'] = $oLastestModifyLog->at;
                }
            }
            if (empty($aResult)) {
                return false;
            }
            return (object) $aResult;
        };

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oOperator, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

        return $oUpdatedUsrData;
    }
    /**
     * 撤销反对操作
     */
    private function _undoDislikeCowork($oApp, $oRecOrData, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = time();
        $oNewModifyLog->op = self::DO_DISLIKE_COWORK_EVENT_NAME . '_N';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->do_dislike_cowork_num = -1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        /* 日志回退函数 */
        $fnRollback = function ($oUserData) use ($oRecOrData) {
            $aResult = []; // 要更新的数据
            if ($oUserData && count($oUserData->modify_log)) {
                $oLastestModifyLog = null; // 最近一次事件日志
                $oBeforeModifyLog = null; // 操作指定对象对应的事件日志
                $aRollbackLogs = []; // 插销操作日志
                foreach ($oUserData->modify_log as $oLog) {
                    if ($oLog->op === self::DO_DISLIKE_COWORK_EVENT_NAME . '_Y') {
                        if (isset($oLog->args->id)) {
                            /* 检查是否是已经撤销的操作 */
                            $bRollbacked = false;
                            foreach ($aRollbackLogs as $oRollbackLog) {
                                if ($oLog->args->id === $oRollbackLog->args->id) {
                                    $bRollbacked = true;
                                    break;
                                }
                            }
                            if ($bRollbacked) {
                                continue;
                            }
                            /* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
                            $oLastestModifyLog = $oLog;
                            /* 由撤销的操作产生的日志 */
                            if (empty($oBeforeModifyLog)) {
                                if ($oLog->args->id === $oRecOrData->id) {
                                    $oBeforeModifyLog = $oLog;
                                }
                            }
                            if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
                                break;
                            }
                        }
                    } else if ($oLog->op === self::DO_DISLIKE_COWORK_EVENT_NAME . '_N') {
                        $aRollbackLogs[] = $oLog;
                    }
                }
                /* 最后一次事件发生时间 */
                if ($oBeforeModifyLog === $oLastestModifyLog) {
                    $aResult['last_do_dislike_cowork_at'] = 0;
                } else if (!empty($oLastestModifyLog->at)) {
                    $aResult['last_do_dislike_cowork_at'] = $oLastestModifyLog->at;
                }
            }
            if (empty($aResult)) {
                return false;
            }
            return (object) $aResult;
        };

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oOperator, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

        return $oUpdatedUsrData;
    }
    /**
     * 取消被点赞
     * 取消获得的积分
     */
    private function _undoGetLikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRecOrData->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_LIKE_EVENT_NAME . '_N';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->like_num = -1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        /* 日志回退函数 */
        $fnRollback = function ($oUserData) use ($oRecOrData, $logArgType, $operatorId) {
            $aResult = []; // 要更新的数据
            if ($oUserData && count($oUserData->modify_log)) {
                $oLastestModifyLog = null; // 最近一次事件日志
                $oBeforeModifyLog = null; // 操作指定对象对应的事件日志
                $aRollbackLogs = []; // 插销操作日志
                foreach ($oUserData->modify_log as $oLog) {
                    if ($oLog->op === self::GET_LIKE_EVENT_NAME . '_Y') {
                        if (isset($oLog->args->type) && isset($oLog->args->id) && isset($oLog->args->operator)) {
                            /* 检查是否是已经撤销的操作 */
                            $bRollbacked = false;
                            foreach ($aRollbackLogs as $oRollbackLog) {
                                if ($oLog->args->type === $oRollbackLog->args->type && $oLog->args->id === $oRollbackLog->args->id && $oLog->args->operator === $oRollbackLog->args->operator) {
                                    $bRollbacked = true;
                                    break;
                                }
                            }
                            if ($bRollbacked) {
                                continue;
                            }
                            /* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
                            $oLastestModifyLog = $oLog;
                            /* 由撤销的操作产生的日志 */
                            if (empty($oBeforeModifyLog)) {
                                if ($oLog->args->id === $oRecOrData->id && $oLog->args->type === $logArgType && $oLog->args->operator === $operatorId) {
                                    $oBeforeModifyLog = $oLog;
                                }
                            }
                            if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
                                break;
                            }
                        }
                    } else if ($oLog->op === self::GET_LIKE_EVENT_NAME . '_N') {
                        $aRollbackLogs[] = $oLog;
                    }
                }
                /* 回退积分奖励 */
                if (!empty($oBeforeModifyLog->coin)) {
                    $aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
                }
                /* 最后一次事件发生时间 */
                if ($oBeforeModifyLog === $oLastestModifyLog) {
                    $aResult['last_like_at'] = 0;
                } else if (!empty($oLastestModifyLog->at)) {
                    $aResult['last_like_at'] = $oLastestModifyLog->at;
                }
            }
            if (empty($aResult)) {
                return false;
            }
            return (object) $aResult;
        };

        $oUser = (object) ['uid' => $oRecOrData->userid];

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

        return $oUpdatedUsrData;
    }
    /**
     * 取消被反对
     * 取消获得的积分
     */
    private function _undoGetDislikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRecOrData->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_DISLIKE_EVENT_NAME . '_N';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->dislike_num = -1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        /* 日志回退函数 */
        $fnRollback = function ($oUserData) use ($oRecOrData, $logArgType, $operatorId) {
            $aResult = []; // 要更新的数据
            if ($oUserData && count($oUserData->modify_log)) {
                $oLastestModifyLog = null; // 最近一次事件日志
                $oBeforeModifyLog = null; // 操作指定对象对应的事件日志
                $aRollbackLogs = []; // 插销操作日志
                foreach ($oUserData->modify_log as $oLog) {
                    if ($oLog->op === self::GET_DISLIKE_EVENT_NAME . '_Y') {
                        if (isset($oLog->args->type) && isset($oLog->args->id) && isset($oLog->args->operator)) {
                            /* 检查是否是已经撤销的操作 */
                            $bRollbacked = false;
                            foreach ($aRollbackLogs as $oRollbackLog) {
                                if ($oLog->args->type === $oRollbackLog->args->type && $oLog->args->id === $oRollbackLog->args->id && $oLog->args->operator === $oRollbackLog->args->operator) {
                                    $bRollbacked = true;
                                    break;
                                }
                            }
                            if ($bRollbacked) {
                                continue;
                            }
                            /* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
                            $oLastestModifyLog = $oLog;
                            /* 由撤销的操作产生的日志 */
                            if (empty($oBeforeModifyLog)) {
                                if ($oLog->args->id === $oRecOrData->id && $oLog->args->type === $logArgType && $oLog->args->operator === $operatorId) {
                                    $oBeforeModifyLog = $oLog;
                                }
                            }
                            if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
                                break;
                            }
                        }
                    } else if ($oLog->op === self::GET_DISLIKE_EVENT_NAME . '_N') {
                        $aRollbackLogs[] = $oLog;
                    }
                }
                /* 回退积分奖励 */
                if (!empty($oBeforeModifyLog->coin)) {
                    $aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
                }
                /* 最后一次事件发生时间 */
                if ($oBeforeModifyLog === $oLastestModifyLog) {
                    $aResult['last_dislike_at'] = 0;
                } else if (!empty($oLastestModifyLog->at)) {
                    $aResult['last_dislike_at'] = $oLastestModifyLog->at;
                }
            }
            if (empty($aResult)) {
                return false;
            }
            return (object) $aResult;
        };

        $oUser = (object) ['uid' => $oRecOrData->userid];

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

        return $oUpdatedUsrData;
    }
    /**
     * 取消被点赞
     * 取消获得的积分
     */
    private function _undoGetLikeCowork($oApp, $oRecOrData, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRecOrData->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_LIKE_COWORK_EVENT_NAME . '_N';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->like_cowork_num = -1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        /* 日志回退函数 */
        $fnRollback = function ($oUserData) use ($oRecOrData, $operatorId) {
            $aResult = []; // 要更新的数据
            if ($oUserData && count($oUserData->modify_log)) {
                $oLastestModifyLog = null; // 最近一次事件日志
                $oBeforeModifyLog = null; // 操作指定对象对应的事件日志
                $aRollbackLogs = []; // 插销操作日志
                foreach ($oUserData->modify_log as $oLog) {
                    if ($oLog->op === self::GET_LIKE_COWORK_EVENT_NAME . '_Y') {
                        if (isset($oLog->args->id) && isset($oLog->args->operator)) {
                            /* 检查是否是已经撤销的操作 */
                            $bRollbacked = false;
                            foreach ($aRollbackLogs as $oRollbackLog) {
                                if ($oLog->args->id === $oRollbackLog->args->id && $oLog->args->operator === $oRollbackLog->args->operator) {
                                    $bRollbacked = true;
                                    break;
                                }
                            }
                            if ($bRollbacked) {
                                continue;
                            }
                            /* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
                            $oLastestModifyLog = $oLog;
                            /* 由撤销的操作产生的日志 */
                            if (empty($oBeforeModifyLog)) {
                                if ($oLog->args->id === $oRecOrData->id && $oLog->args->operator === $operatorId) {
                                    $oBeforeModifyLog = $oLog;
                                }
                            }
                            if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
                                break;
                            }
                        }
                    } else if ($oLog->op === self::GET_LIKE_COWORK_EVENT_NAME . '_N') {
                        $aRollbackLogs[] = $oLog;
                    }
                }
                /* 回退积分奖励 */
                if (!empty($oBeforeModifyLog->coin)) {
                    $aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
                }
                /* 最后一次事件发生时间 */
                if ($oBeforeModifyLog === $oLastestModifyLog) {
                    $aResult['last_like_cowork_at'] = 0;
                } else if (!empty($oLastestModifyLog->at)) {
                    $aResult['last_like_cowork_at'] = $oLastestModifyLog->at;
                }
            }
            if (empty($aResult)) {
                return false;
            }
            return (object) $aResult;
        };

        $oUser = (object) ['uid' => $oRecOrData->userid];

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

        return $oUpdatedUsrData;
    }
    /**
     * 取消被点赞
     * 取消获得的积分
     */
    private function _undoGetDislikeCowork($oApp, $oRecOrData, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRecOrData->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_DISLIKE_COWORK_EVENT_NAME . '_N';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->dislike_cowork_num = -1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        /* 日志回退函数 */
        $fnRollback = function ($oUserData) use ($oRecOrData, $operatorId) {
            $aResult = []; // 要更新的数据
            if ($oUserData && count($oUserData->modify_log)) {
                $oLastestModifyLog = null; // 最近一次事件日志
                $oBeforeModifyLog = null; // 操作指定对象对应的事件日志
                $aRollbackLogs = []; // 插销操作日志
                foreach ($oUserData->modify_log as $oLog) {
                    if ($oLog->op === self::GET_DISLIKE_COWORK_EVENT_NAME . '_Y') {
                        if (isset($oLog->args->id) && isset($oLog->args->operator)) {
                            /* 检查是否是已经撤销的操作 */
                            $bRollbacked = false;
                            foreach ($aRollbackLogs as $oRollbackLog) {
                                if ($oLog->args->id === $oRollbackLog->args->id && $oLog->args->operator === $oRollbackLog->args->operator) {
                                    $bRollbacked = true;
                                    break;
                                }
                            }
                            if ($bRollbacked) {
                                continue;
                            }
                            /* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
                            $oLastestModifyLog = $oLog;
                            /* 由撤销的操作产生的日志 */
                            if (empty($oBeforeModifyLog)) {
                                if ($oLog->args->id === $oRecOrData->id && $oLog->args->operator === $operatorId) {
                                    $oBeforeModifyLog = $oLog;
                                }
                            }
                            if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
                                break;
                            }
                        }
                    } else if ($oLog->op === self::GET_DISLIKE_COWORK_EVENT_NAME . '_N') {
                        $aRollbackLogs[] = $oLog;
                    }
                }
                /* 回退积分奖励 */
                if (!empty($oBeforeModifyLog->coin)) {
                    $aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
                }
                /* 最后一次事件发生时间 */
                if ($oBeforeModifyLog === $oLastestModifyLog) {
                    $aResult['last_dislike_cowork_at'] = 0;
                } else if (!empty($oLastestModifyLog->at)) {
                    $aResult['last_dislike_cowork_at'] = $oLastestModifyLog->at;
                }
            }
            if (empty($aResult)) {
                return false;
            }
            return (object) $aResult;
        };

        $oUser = (object) ['uid' => $oRecOrData->userid];

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

        return $oUpdatedUsrData;
    }
    /**
     * 留言点赞
     * 同一条留言只有第一次点赞时才给积分奖励
     */
    public function likeRemark($oApp, $oRemark, $oOperator) {
        $oOperatorData = $this->_doLikeRemark($oApp, $oRemark, $oOperator);
        $oOwnerData = $this->_getLikeRemark($oApp, $oRemark, $oOperator);

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRemark->id;
        $oTarget->type = 'remark';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_LIKE_REMARK_EVENT_NAME;
        $oEvent->op = 'Y';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRemark->userid];
        $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
    }
    /**
     * 留言点踩
     */
    public function dislikeRemark($oApp, $oRemark, $oOperator) {
        $oOperatorData = $this->_doDislikeRemark($oApp, $oRemark, $oOperator);
        $oOwnerData = $this->_getDislikeRemark($oApp, $oRemark, $oOperator);

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRemark->id;
        $oTarget->type = 'remark';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_DISLIKE_REMARK_EVENT_NAME;
        $oEvent->op = 'Y';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRemark->userid];
        $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
    }
    /**
     * 留言点赞
     */
    private function _doLikeRemark($oApp, $oRemark, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::DO_LIKE_REMARK_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRemark->id];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_do_like_remark_at = $eventAt;
        $oUpdatedUsrData->do_like_remark_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $this->_updateUsrData($oApp, $oRemark->rid, false, $oOperator, $oUpdatedUsrData);

        return $oUpdatedUsrData;
    }
    /**
     * 留言点踩
     */
    private function _doDislikeRemark($oApp, $oRemark, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::DO_DISLIKE_REMARK_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRemark->id];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_do_dislike_remark_at = $eventAt;
        $oUpdatedUsrData->do_dislike_remark_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $this->_updateUsrData($oApp, $oRemark->rid, false, $oOperator, $oUpdatedUsrData);

        return $oUpdatedUsrData;
    }
    /**
     * 留言被点赞
     */
    private function _getLikeRemark($oApp, $oRemark, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRemark->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_LIKE_REMARK_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRemark->id, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_like_remark_at = $eventAt;
        $oUpdatedUsrData->like_remark_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;
        $aCoinResult = $modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::GET_LIKE_REMARK_EVENT_NAME);
        if (!empty($aCoinResult[1])) {
            $oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
        }

        $oUser = (object) ['uid' => $oRemark->userid];

        $this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::GET_LIKE_REMARK_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 留言被反对
     */
    private function _getDislikeRemark($oApp, $oRemark, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRemark->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_DISLIKE_REMARK_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRemark->id, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_dislike_remark_at = $eventAt;
        $oUpdatedUsrData->dislike_remark_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;
        $aCoinResult = $modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::GET_DISLIKE_REMARK_EVENT_NAME);
        if (!empty($aCoinResult[1])) {
            $oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
        }

        $oUser = (object) ['uid' => $oRemark->userid];

        $this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::GET_DISLIKE_REMARK_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 撤销发起对留言点赞
     */
    public function undoLikeRemark($oApp, $oRemark, $oOperator) {
        $this->_undoLikeRemark($oApp, $oRemark, $oOperator);
        $this->_undoGetLikeRemark($oApp, $oRemark, $oOperator);

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRemark->id;
        $oTarget->type = 'remark';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_LIKE_REMARK_EVENT_NAME;
        $oEvent->op = 'N';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRemark->userid];

        $oLog = $this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        /* 更新被撤销的事件 */
        $this->update(
            'xxt_enroll_log',
            ['undo_event_id' => $oLog->id],
            ['target_id' => $oRemark->id, 'target_type' => 'remark', 'event_name' => self::DO_LIKE_EVENT_NAME, 'event_op' => 'Y', 'undo_event_id' => 0]
        );
    }
    /**
     * 撤销发起对留言点踩
     */
    public function undoDislikeRemark($oApp, $oRemark, $oOperator) {
        $this->_undoDislikeRemark($oApp, $oRemark, $oOperator);
        $this->_undoGetDislikeRemark($oApp, $oRemark, $oOperator);

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRemark->id;
        $oTarget->type = 'remark';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::DO_DISLIKE_REMARK_EVENT_NAME;
        $oEvent->op = 'N';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRemark->userid];

        $oLog = $this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        /* 更新被撤销的事件 */
        $this->update(
            'xxt_enroll_log',
            ['undo_event_id' => $oLog->id],
            ['target_id' => $oRemark->id, 'target_type' => 'remark', 'event_name' => self::DO_DISLIKE_EVENT_NAME, 'event_op' => 'Y', 'undo_event_id' => 0]
        );
    }
    /**
     * 撤销发起对留言点赞
     */
    private function _undoLikeRemark($oApp, $oRemark, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::DO_LIKE_REMARK_EVENT_NAME . '_N';
        $oNewModifyLog->args = (object) ['id' => $oRemark->id];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->do_like_remark_num = -1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $this->_updateUsrData($oApp, $oRemark->rid, true, $oOperator, $oUpdatedUsrData);

        return $oUpdatedUsrData;
    }
    /**
     * 撤销发起对留言点踩
     */
    private function _undoDislikeRemark($oApp, $oRemark, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::DO_DISLIKE_REMARK_EVENT_NAME . '_N';
        $oNewModifyLog->args = (object) ['id' => $oRemark->id];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->do_dislike_remark_num = -1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $this->_updateUsrData($oApp, $oRemark->rid, true, $oOperator, $oUpdatedUsrData);

        return $oUpdatedUsrData;
    }
    /**
     * 撤销留言被点赞
     */
    private function _undoGetLikeRemark($oApp, $oRemark, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRemark->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_LIKE_REMARK_EVENT_NAME . '_N';
        $oNewModifyLog->args = (object) ['id' => $oRemark->id, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->like_remark_num = -1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $oEnlUsrRnd = $modelUsr->byId($oApp, $oRemark->userid, ['fields' => 'id,modify_log', 'rid' => $oRemark->rid]);
        /* 撤销获得的积分 */
        if ($oEnlUsrRnd && count($oEnlUsrRnd->modify_log)) {
            for ($i = 0; $i < count($oEnlUsrRnd->modify_log); $i++) {
                $oLog = $oEnlUsrRnd->modify_log[$i];
                if ($oLog->op === self::GET_LIKE_REMARK_EVENT_NAME . '_Y') {
                    if (isset($oLog->args->id) && isset($oLog->args->operator)) {
                        if ($oLog->args->id === $oRemark->id && $oLog->args->operator === $operatorId) {
                            if (!empty($oLog->coin)) {
                                $oUpdatedUsrData->user_total_coin = -1 * (int) $oLog->coin;
                            }
                            break;
                        }
                    }
                }
            }
        }

        $oUser = (object) ['uid' => $oRemark->userid];

        $this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);

        return $oUpdatedUsrData;
    }
    /**
     * 撤销留言被点踩
     */
    private function _undoGetDislikeRemark($oApp, $oRemark, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRemark->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_DISLIKE_REMARK_EVENT_NAME . '_N';
        $oNewModifyLog->args = (object) ['id' => $oRemark->id, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->dislike_remark_num = -1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $oEnlUsrRnd = $modelUsr->byId($oApp, $oRemark->userid, ['fields' => 'id,modify_log', 'rid' => $oRemark->rid]);
        /* 撤销获得的积分 */
        if ($oEnlUsrRnd && count($oEnlUsrRnd->modify_log)) {
            for ($i = 0; $i < count($oEnlUsrRnd->modify_log); $i++) {
                $oLog = $oEnlUsrRnd->modify_log[$i];
                if ($oLog->op === self::GET_DISLIKE_REMARK_EVENT_NAME . '_Y') {
                    if (isset($oLog->args->id) && isset($oLog->args->operator)) {
                        if ($oLog->args->id === $oRemark->id && $oLog->args->operator === $operatorId) {
                            if (!empty($oLog->coin)) {
                                $oUpdatedUsrData->user_total_coin = -1 * (int) $oLog->coin;
                            }
                            break;
                        }
                    }
                }
            }
        }

        $oUser = (object) ['uid' => $oRemark->userid];

        $this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);

        return $oUpdatedUsrData;
    }
    /**
     * 对记录执行推荐相关操作
     */
    public function agreeRecord($oApp, $oRecord, $oOperator, $value) {
        if ('Y' === $value) {
            $oOwnerData = $this->_getAgreeRecOrData($oApp, $oRecord, $oOperator, 'record');
            $eventAt = time();
            /* 记录事件日志 */
            $oTarget = new \stdClass;
            $oTarget->id = $oRecord->id;
            $oTarget->type = 'record';
            //
            $oEvent = new \stdClass;
            $oEvent->name = self::GET_AGREE_EVENT_NAME;
            $oEvent->op = 'Y';
            $oEvent->at = $eventAt;
            $oEvent->user = $oOperator;
            //
            $oOwnerEvent = new \stdClass;
            $oOwnerEvent->user = (object) ['uid' => $oRecord->userid];
            $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

            $this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
        } else if ('Y' === $oRecord->agreed) {
            $oOwnerData = $this->_undoGetAgreeRecOrData($oApp, $oRecord, $oOperator, $value, 'record');
            $eventAt = time();
            /* 记录事件日志 */
            $oTarget = new \stdClass;
            $oTarget->id = $oRecord->id;
            $oTarget->type = 'record';
            //
            $oEvent = new \stdClass;
            $oEvent->name = self::GET_AGREE_EVENT_NAME;
            $oEvent->op = $value;
            $oEvent->at = $eventAt;
            $oEvent->user = $oOperator;
            //
            $oOwnerEvent = new \stdClass;
            $oOwnerEvent->user = (object) ['uid' => $oRecord->userid];

            $oLog = $this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

            /* 更新被撤销的事件 */
            $this->update(
                'xxt_enroll_log',
                ['undo_event_id' => $oLog->id],
                ['target_id' => $oRecord->id, 'target_type' => 'record', 'event_name' => self::GET_AGREE_EVENT_NAME, 'event_op' => 'Y', 'undo_event_id' => 0]
            );
        }
    }
    /**
     * 对记录数据执行推荐相关操作
     */
    public function agreeRecData($oApp, $oRecData, $oOperator, $value) {
        if ('Y' === $value) {
            $oOwnerData = $this->_getAgreeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
            $eventAt = time();
            /* 记录事件日志 */
            $oTarget = new \stdClass;
            $oTarget->id = $oRecData->id;
            $oTarget->type = 'record.data';
            //
            $oEvent = new \stdClass;
            $oEvent->name = self::GET_AGREE_EVENT_NAME;
            $oEvent->op = 'Y';
            $oEvent->at = $eventAt;
            $oEvent->user = $oOperator;
            //
            $oOwnerEvent = new \stdClass;
            $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
            $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

            $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
        } else if ('Y' === $oRecData->agreed) {
            $oOwnerData = $this->_undoGetAgreeRecOrData($oApp, $oRecData, $oOperator, $value, 'record.data');
            $eventAt = time();
            /* 记录事件日志 */
            $oTarget = new \stdClass;
            $oTarget->id = $oRecData->id;
            $oTarget->type = 'record.data';
            //
            $oEvent = new \stdClass;
            $oEvent->name = self::GET_AGREE_EVENT_NAME;
            $oEvent->op = $value;
            $oEvent->at = $eventAt;
            $oEvent->user = $oOperator;
            //
            $oOwnerEvent = new \stdClass;
            $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];

            $oLog = $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

            /* 更新被撤销的事件 */
            $this->update(
                'xxt_enroll_log',
                ['undo_event_id' => $oLog->id],
                ['target_id' => $oRecData->id, 'target_type' => 'record.data', 'event_name' => self::GET_AGREE_EVENT_NAME, 'event_op' => 'Y', 'undo_event_id' => 0]
            );
        }
    }
    /**
     * 赞同填写记录或数据
     */
    private function _getAgreeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_AGREE_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

        /* 奖励积分 */
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
        $aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GET_AGREE_EVENT_NAME);
        if (!empty($aCoinResult[1])) {
            $oNewModifyLog->coin = $aCoinResult[1];
        }
        /* 更新的数据 */
        $oUpdatedUsrData = (object) [
            'last_agree_at' => $eventAt,
            'agree_num' => 1,
            'user_total_coin' => $aCoinResult[0] === true ? $aCoinResult[1] : 0,
            'modify_log' => $oNewModifyLog,
        ];

        $oUser = (object) ['uid' => $oRecOrData->userid];

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GET_AGREE_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 取消赞同记录数据
     */
    private function _undoGetAgreeRecOrData($oApp, $oRecOrData, $oOperator, $value, $logArgType) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_AGREE_EVENT_NAME . '_' . $value;
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];
        /* 更新的数据 */
        $oUpdatedUsrData = (object) [
            'agree_num' => -1,
            'modify_log' => $oNewModifyLog,
        ];

        /* 日志回退函数 */
        $fnRollback = function ($oUserData) use ($oRecOrData, $logArgType) {
            $aResult = []; // 要更新的数据
            if ($oUserData && count($oUserData->modify_log)) {
                $oLastestModifyLog = null; // 最近一次事件日志
                $oBeforeModifyLog = null; // 操作指定对象对应的事件日志
                $aRollbackLogs = []; // 插销操作日志
                foreach ($oUserData->modify_log as $oLog) {
                    if ($oLog->op === self::GET_AGREE_EVENT_NAME . '_Y') {
                        if (isset($oLog->args->type) && isset($oLog->args->id)) {
                            /* 检查是否是已经撤销的操作 */
                            $bRollbacked = false;
                            foreach ($aRollbackLogs as $oRollbackLog) {
                                if ($oLog->args->type === $oRollbackLog->args->type && $oLog->args->id === $oRollbackLog->args->id) {
                                    $bRollbacked = true;
                                    break;
                                }
                            }
                            if ($bRollbacked) {
                                continue;
                            }
                            /* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
                            $oLastestModifyLog = $oLog;
                            /* 由撤销的操作产生的日志 */
                            if (empty($oBeforeModifyLog)) {
                                if ($oLog->args->id === $oRecOrData->id && $oLog->args->type === $logArgType) {
                                    $oBeforeModifyLog = $oLog;
                                }
                            }
                            if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
                                break;
                            }
                        }
                    } else if (strpos($oLog->op, self::GET_AGREE_EVENT_NAME) === 0 && $oLog->op !== self::GET_AGREE_EVENT_NAME . '_Y') {
                        $aRollbackLogs[] = $oLog;
                    }
                }
                /* 回退积分奖励。只要做了赞同的操作就给积分，不论结果是什么 */
                if (!empty($oBeforeModifyLog->coin)) {
                    $aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
                }
                /* 最后一次事件发生时间 */
                if ($oBeforeModifyLog === $oLastestModifyLog) {
                    $aResult['last_agree_at'] = 0;
                } else if (!empty($oLastestModifyLog->at)) {
                    $aResult['last_agree_at'] = $oLastestModifyLog->at;
                }
            }
            if (empty($aResult)) {
                return false;
            }
            return (object) $aResult;
        };

        $oUser = (object) ['uid' => $oRecOrData->userid];

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

        return $oUpdatedUsrData;
    }
    /**
     * 对记录数据执行推荐相关操作
     */
    public function agreeCowork($oApp, $oRecData, $oOperator, $value) {
        if ('Y' === $value) {
            $oOwnerData = $this->_getAgreeCowork($oApp, $oRecData, $oOperator);
            $eventAt = time();
            /* 记录事件日志 */
            $oTarget = new \stdClass;
            $oTarget->id = $oRecData->id;
            $oTarget->type = 'cowork';
            //
            $oEvent = new \stdClass;
            $oEvent->name = self::GET_AGREE_COWORK_EVENT_NAME;
            $oEvent->op = 'Y';
            $oEvent->at = $eventAt;
            $oEvent->user = $oOperator;
            //
            $oOwnerEvent = new \stdClass;
            $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
            $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

            $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
        } else if ('Y' === $oRecData->agreed) {
            $oOwnerData = $this->_undoGetAgreeCowork($oApp, $oRecData, $oOperator, $value);
            $eventAt = time();
            /* 记录事件日志 */
            $oTarget = new \stdClass;
            $oTarget->id = $oRecData->id;
            $oTarget->type = 'cowork';
            //
            $oEvent = new \stdClass;
            $oEvent->name = self::GET_AGREE_COWORK_EVENT_NAME;
            $oEvent->op = $value;
            $oEvent->at = $eventAt;
            $oEvent->user = $oOperator;
            //
            $oOwnerEvent = new \stdClass;
            $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];

            $oLog = $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

            /* 更新被撤销的事件 */
            $this->update(
                'xxt_enroll_log',
                ['undo_event_id' => $oLog->id],
                ['target_id' => $oRecData->id, 'target_type' => 'cowork', 'event_name' => self::GET_AGREE_COWORK_EVENT_NAME, 'event_op' => 'Y', 'undo_event_id' => 0]
            );
        }
    }
    /**
     * 赞同填写记录或数据
     */
    private function _getAgreeCowork($oApp, $oRecData, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_AGREE_COWORK_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRecData->id];

        /* 奖励积分 */
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
        $aCoinResult = $modelUsr->awardCoin($oApp, $oRecData->userid, $oRecData->rid, self::GET_AGREE_COWORK_EVENT_NAME);
        if (!empty($aCoinResult[1])) {
            $oNewModifyLog->coin = $aCoinResult[1];
        }
        /* 更新的数据 */
        $oUpdatedUsrData = (object) [
            'last_agree_cowork_at' => $eventAt,
            'agree_cowork_num' => 1,
            'user_total_coin' => $aCoinResult[0] === true ? $aCoinResult[1] : 0,
            'modify_log' => $oNewModifyLog,
        ];

        $oUser = (object) ['uid' => $oRecData->userid];

        $this->_updateUsrData($oApp, $oRecData->rid, true, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $oRecData->userid, $oRecData->rid, self::GET_AGREE_COWORK_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 取消赞同记录数据
     */
    private function _undoGetAgreeCowork($oApp, $oRecData, $oOperator, $value) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_AGREE_COWORK_EVENT_NAME . '_' . $value;
        $oNewModifyLog->args = (object) ['id' => $oRecData->id];
        /* 更新的数据 */
        $oUpdatedUsrData = (object) [
            'agree_cowork_num' => -1,
            'modify_log' => $oNewModifyLog,
        ];

        /* 日志回退函数 */
        $fnRollback = function ($oUserData) use ($oRecData) {
            $aResult = []; // 要更新的数据
            if ($oUserData && count($oUserData->modify_log)) {
                $oLastestModifyLog = null; // 最近一次事件日志
                $oBeforeModifyLog = null; // 操作指定对象对应的事件日志
                $aRollbackLogs = []; // 插销操作日志
                foreach ($oUserData->modify_log as $oLog) {
                    if ($oLog->op === self::GET_AGREE_COWORK_EVENT_NAME . '_Y') {
                        if (isset($oLog->args->type) && isset($oLog->args->id)) {
                            /* 检查是否是已经撤销的操作 */
                            $bRollbacked = false;
                            foreach ($aRollbackLogs as $oRollbackLog) {
                                if ($oLog->args->type === $oRollbackLog->args->type && $oLog->args->id === $oRollbackLog->args->id) {
                                    $bRollbacked = true;
                                    break;
                                }
                            }
                            if ($bRollbacked) {
                                continue;
                            }
                            /* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
                            $oLastestModifyLog = $oLog;
                            /* 由撤销的操作产生的日志 */
                            if (empty($oBeforeModifyLog)) {
                                if ($oLog->args->id === $oRecData->id && $oLog->args->type === $logArgType) {
                                    $oBeforeModifyLog = $oLog;
                                }
                            }
                            if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
                                break;
                            }
                        }
                    } else if (strpos($oLog->op, self::GET_AGREE_COWORK_EVENT_NAME) === 0 && $oLog->op !== self::GET_AGREE_COWORK_EVENT_NAME . '_Y') {
                        $aRollbackLogs[] = $oLog;
                    }
                }
                /* 回退积分奖励。只要做了赞同的操作就给积分，不论结果是什么 */
                if (!empty($oBeforeModifyLog->coin)) {
                    $aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
                }
                /* 最后一次事件发生时间 */
                if ($oBeforeModifyLog === $oLastestModifyLog) {
                    $aResult['last_agree_cowork_at'] = 0;
                } else if (!empty($oLastestModifyLog->at)) {
                    $aResult['last_agree_cowork_at'] = $oLastestModifyLog->at;
                }
            }
            if (empty($aResult)) {
                return false;
            }
            return (object) $aResult;
        };

        $oUser = (object) ['uid' => $oRecData->userid];

        $this->_updateUsrData($oApp, $oRecData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

        return $oUpdatedUsrData;
    }
    /**
     * 对记录执行推荐相关操作
     */
    public function agreeRemark($oApp, $oRemark, $oOperator, $value) {
        if ('Y' === $value) {
            $oOwnerData = $this->_getAgreeRemark($oApp, $oRemark, $oOperator);
            $eventAt = time();
            /* 记录事件日志 */
            $oTarget = new \stdClass;
            $oTarget->id = $oRemark->id;
            $oTarget->type = 'remark';
            //
            $oEvent = new \stdClass;
            $oEvent->name = self::GET_AGREE_REMARK_EVENT_NAME;
            $oEvent->op = 'Y';
            $oEvent->at = $eventAt;
            $oEvent->user = $oOperator;
            //
            $oOwnerEvent = new \stdClass;
            $oOwnerEvent->user = (object) ['uid' => $oRemark->userid];
            $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

            $this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
        } else if ('Y' === $oRemark->agreed) {
            $oOwnerData = $this->_undoGetAgreeRemark($oApp, $oRemark, $oOperator, $value);
            $eventAt = time();
            /* 记录事件日志 */
            $oTarget = new \stdClass;
            $oTarget->id = $oRemark->id;
            $oTarget->type = 'remark';
            //
            $oEvent = new \stdClass;
            $oEvent->name = self::GET_AGREE_REMARK_EVENT_NAME;
            $oEvent->op = $value;
            $oEvent->at = $eventAt;
            $oEvent->user = $oOperator;
            //
            $oOwnerEvent = new \stdClass;
            $oOwnerEvent->user = (object) ['uid' => $oRemark->userid];

            $oLog = $this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

            /* 更新被撤销的事件 */
            $this->update(
                'xxt_enroll_log',
                ['undo_event_id' => $oLog->id],
                ['target_id' => $oRemark->id, 'target_type' => 'remark', 'event_name' => self::GET_AGREE_REMARK_EVENT_NAME, 'event_op' => 'Y', 'undo_event_id' => 0]
            );
        }
    }
    /**
     * 赞同填写记录或数据
     */
    private function _getAgreeRemark($oApp, $oRemark, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_AGREE_REMARK_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRemark->id];

        /* 奖励积分 */
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
        $aCoinResult = $modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::GET_AGREE_REMARK_EVENT_NAME);
        if (!empty($aCoinResult[1])) {
            $oNewModifyLog->coin = $aCoinResult[1];
        }

        /* 更新的数据 */
        $oUpdatedUsrData = (object) [
            'last_agree_remark_at' => $eventAt,
            'agree_remark_num' => 1,
            'user_total_coin' => $aCoinResult[0] === true ? $aCoinResult[1] : 0,
            'modify_log' => $oNewModifyLog,
        ];

        $oUser = (object) ['uid' => $oRemark->userid];

        $this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::GET_AGREE_REMARK_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 取消赞同记录数据
     */
    private function _undoGetAgreeRemark($oApp, $oRemark, $oOperator, $value) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $operatorId;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_AGREE_REMARK_EVENT_NAME . '_' . $value;
        $oNewModifyLog->args = (object) ['id' => $oRemark->id];
        /* 更新的数据 */
        $oUpdatedUsrData = (object) [
            'agree_remark_num' => -1,
            'modify_log' => $oNewModifyLog,
        ];

        /* 日志回退函数 */
        $fnRollback = function ($oUserData) use ($oRemark) {
            $aResult = []; // 要更新的数据
            if ($oUserData && count($oUserData->modify_log)) {
                $oLastestModifyLog = null; // 最近一次事件日志
                $oBeforeModifyLog = null; // 操作指定对象对应的事件日志
                $aRollbackLogs = []; // 插销操作日志
                foreach ($oUserData->modify_log as $oLog) {
                    if ($oLog->op === self::GET_AGREE_REMARK_EVENT_NAME . '_Y') {
                        if (isset($oLog->args->id)) {
                            /* 检查是否是已经撤销的操作 */
                            $bRollbacked = false;
                            foreach ($aRollbackLogs as $oRollbackLog) {
                                if ($oLog->args->id === $oRollbackLog->args->id) {
                                    $bRollbacked = true;
                                    break;
                                }
                            }
                            if ($bRollbacked) {
                                continue;
                            }
                            /* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
                            $oLastestModifyLog = $oLog;
                            /* 由撤销的操作产生的日志 */
                            if (empty($oBeforeModifyLog)) {
                                if ($oLog->args->id === $oRemark->id) {
                                    $oBeforeModifyLog = $oLog;
                                }
                            }
                            if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
                                break;
                            }
                        }
                    } else if (strpos($oLog->op, self::GET_AGREE_REMARK_EVENT_NAME) === 0 && $oLog->op !== self::GET_AGREE_REMARK_EVENT_NAME . '_Y') {
                        $aRollbackLogs[] = $oLog;
                    }
                }
                /* 回退积分奖励。只要做了赞同的操作就给积分，不论结果是什么 */
                if (!empty($oBeforeModifyLog->coin)) {
                    $aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
                }
                /* 最后一次事件发生时间 */
                if ($oBeforeModifyLog === $oLastestModifyLog) {
                    $aResult['last_agree_remark_at'] = 0;
                } else if (!empty($oLastestModifyLog->at)) {
                    $aResult['last_agree_remark_at'] = $oLastestModifyLog->at;
                }
            }
            if (empty($aResult)) {
                return false;
            }
            return (object) $aResult;
        };

        $oUser = (object) ['uid' => $oRemark->userid];

        $this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

        return $oUpdatedUsrData;
    }
    /**
     * 对协作填写进行投票
     */
    public function voteRecCowork($oApp, $oRecData, $oOperator) {
        $oOperatorData = $this->_doVoteCowork($oApp, $oRecData, $oOperator);
        $oOwnerData = $this->_getVoteCowork($oApp, $oRecData, $oOperator);

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecData->id;
        $oTarget->type = 'record.data';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::GET_VOTE_COWORK_EVENT_NAME;
        $oEvent->op = 'Y';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
        $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
    }
    /**
     * 对题目进行投票
     */
    public function voteRecSchema($oApp, $oRecData, $oOperator) {
        $oOperatorData = $this->_doVoteSchema($oApp, $oRecData, $oOperator);
        $oOwnerData = $this->_getVoteSchema($oApp, $oRecData, $oOperator);

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecData->id;
        $oTarget->type = 'record.data';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::GET_VOTE_SCHEMA_EVENT_NAME;
        $oEvent->op = 'Y';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        $oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
        $oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

        $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
    }
    /**
     * 撤销对协作填写的投票
     */
    public function unvoteRecCowork($oApp, $oRecData, $oOperator) {
        $this->_undoVoteCowork($oApp, $oRecData, $oOperator);
        $this->_undoGetVoteCowork($oApp, $oRecData, $oOperator);

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecData->id;
        $oTarget->type = 'record.data';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::GET_VOTE_COWORK_EVENT_NAME;
        $oEvent->op = 'N';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];

        $oLog = $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        /* 更新被撤销的事件 */
        $this->update(
            'xxt_enroll_log',
            ['undo_event_id' => $oLog->id],
            ['target_id' => $oRecData->id, 'target_type' => 'remark', 'event_name' => self::GET_VOTE_COWORK_EVENT_NAME, 'event_op' => 'Y', 'undo_event_id' => 0]
        );
    }
    /**
     * 撤销对题目的投票
     */
    public function unvoteRecSchema($oApp, $oRecData, $oOperator) {
        $this->_undoVoteSchema($oApp, $oRecData, $oOperator);
        $this->_undoGetVoteSchema($oApp, $oRecData, $oOperator);

        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $oRecData->id;
        $oTarget->type = 'record.data';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::GET_VOTE_SCHEMA_EVENT_NAME;
        $oEvent->op = 'N';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;
        //
        $oOwnerEvent = new \stdClass;
        $oOwnerEvent->user = (object) ['uid' => $oRecData->userid];

        $oLog = $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

        /* 更新被撤销的事件 */
        $this->update(
            'xxt_enroll_log',
            ['undo_event_id' => $oLog->id],
            ['target_id' => $oRecData->id, 'target_type' => 'remark', 'event_name' => self::GET_VOTE_SCHEMA_EVENT_NAME, 'event_op' => 'Y', 'undo_event_id' => 0]
        );
    }
    /**
     * 对协作填写进行投票
     */
    private function _doVoteCowork($oApp, $oRecOrData, $oOperator) {
        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;

        return $oUpdatedUsrData;
    }
    /**
     * 协作填写获得投票
     */
    private function _getVoteCowork($oApp, $oRecOrData, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRecOrData->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_VOTE_COWORK_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_vote_cowork_at = $eventAt;
        $oUpdatedUsrData->vote_cowork_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;
        $aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GET_VOTE_COWORK_EVENT_NAME);
        if (!empty($aCoinResult[1])) {
            $oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
        }
        $oUser = (object) ['uid' => $oRecOrData->userid];

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GET_VOTE_COWORK_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 对题目进行投票
     */
    private function _doVoteSchema($oApp, $oRecOrData, $oOperator) {
        $oUpdatedUsrData = new \stdClass;

        return $oUpdatedUsrData;
    }
    /**
     * 题目获得投票
     */
    private function _getVoteSchema($oApp, $oRecOrData, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRecOrData->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_VOTE_SCHEMA_EVENT_NAME . '_Y';
        $oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->last_vote_schema_at = $eventAt;
        $oUpdatedUsrData->vote_schema_num = 1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;
        $aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GET_VOTE_SCHEMA_EVENT_NAME);
        if (!empty($aCoinResult[1])) {
            $oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
        }
        $oUser = (object) ['uid' => $oRecOrData->userid];

        $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
        // 如果日志插入失败需要重新增加
        if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
            $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GET_VOTE_SCHEMA_EVENT_NAME);
        }

        return $oUpdatedUsrData;
    }
    /**
     * 撤销发起对协作填写的投票
     */
    private function _undoVoteCowork($oApp, $oRecOrData, $oOperator) {
        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;

        return $oUpdatedUsrData;
    }
    /**
     * 撤销留言被点赞
     */
    private function _undoGetVoteCowork($oApp, $oRecData, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRecData->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_VOTE_COWORK_EVENT_NAME . '_N';
        $oNewModifyLog->args = (object) ['id' => $oRecData->id, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->vote_cowork_num = -1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $oEnlUsrRnd = $modelUsr->byId($oApp, $oRecData->userid, ['fields' => 'id,modify_log', 'rid' => $oRecData->rid]);
        /* 撤销获得的积分 */
        if ($oEnlUsrRnd && count($oEnlUsrRnd->modify_log)) {
            for ($i = 0; $i < count($oEnlUsrRnd->modify_log); $i++) {
                $oLog = $oEnlUsrRnd->modify_log[$i];
                if ($oLog->op === self::GET_VOTE_COWORK_EVENT_NAME . '_Y') {
                    if (isset($oLog->args->id) && isset($oLog->args->operator)) {
                        if ($oLog->args->id === $oRecData->id && $oLog->args->operator === $operatorId) {
                            if (!empty($oLog->coin)) {
                                $oUpdatedUsrData->user_total_coin = -1 * (int) $oLog->coin;
                            }
                            break;
                        }
                    }
                }
            }
        }

        $oUser = (object) ['uid' => $oRecData->userid];

        $this->_updateUsrData($oApp, $oRecData->rid, true, $oUser, $oUpdatedUsrData);

        return $oUpdatedUsrData;
    }
    /**
     * 撤销发起对协作填写的投票
     */
    private function _undoVoteSchema($oApp, $oRecOrData, $oOperator) {
        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;

        return $oUpdatedUsrData;
    }
    /**
     * 撤销留言被点赞
     */
    private function _undoGetVoteSchema($oApp, $oRecData, $oOperator) {
        $operatorId = $this->_getOperatorId($oOperator);
        $eventAt = time();
        $modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

        /* 记录修改日志 */
        $oNewModifyLog = new \stdClass;
        $oNewModifyLog->userid = $oRecData->userid;
        $oNewModifyLog->at = $eventAt;
        $oNewModifyLog->op = self::GET_VOTE_SCHEMA_EVENT_NAME . '_N';
        $oNewModifyLog->args = (object) ['id' => $oRecData->id, 'operator' => $operatorId];

        /* 更新的数据 */
        $oUpdatedUsrData = new \stdClass;
        $oUpdatedUsrData->vote_schema_num = -1;
        $oUpdatedUsrData->modify_log = $oNewModifyLog;

        $oEnlUsrRnd = $modelUsr->byId($oApp, $oRecData->userid, ['fields' => 'id,modify_log', 'rid' => $oRecData->rid]);
        /* 撤销获得的积分 */
        if ($oEnlUsrRnd && count($oEnlUsrRnd->modify_log)) {
            for ($i = 0; $i < count($oEnlUsrRnd->modify_log); $i++) {
                $oLog = $oEnlUsrRnd->modify_log[$i];
                if ($oLog->op === self::GET_VOTE_SCHEMA_EVENT_NAME . '_Y') {
                    if (isset($oLog->args->id) && isset($oLog->args->operator)) {
                        if ($oLog->args->id === $oRecData->id && $oLog->args->operator === $operatorId) {
                            if (!empty($oLog->coin)) {
                                $oUpdatedUsrData->user_total_coin = -1 * (int) $oLog->coin;
                            }
                            break;
                        }
                    }
                }
            }
        }

        $oUser = (object) ['uid' => $oRecData->userid];

        $this->_updateUsrData($oApp, $oRecData->rid, true, $oUser, $oUpdatedUsrData);

        return $oUpdatedUsrData;
    }
    /*
     *
     */
    public function searchRecord($oApp, $search, $oOperator) {
        $rid = $oApp->appRound->rid;
        $eventAt = time();
        /* 记录事件日志 */
        $oTarget = new \stdClass;
        $oTarget->id = $search->id;
        $oTarget->type = 'search';
        //
        $oEvent = new \stdClass;
        $oEvent->name = self::SEARCH_RECORD_EVENT_NAME;
        $oEvent->op = 'Use';
        $oEvent->at = $eventAt;
        $oEvent->user = $oOperator;

        $this->_logEvent($oApp, $rid, '', $oTarget, $oEvent);
    }
    /**
     * 返回活动事件日志
     */
    public function logByApp($oApp, $oOptions = []) {
        $fields = empty($oOptions['fields']) ? '*' : $oOptions['fields'];
        $q = [
            $fields,
            'xxt_enroll_log',
            "aid='{$oApp->id}'",
        ];

        /* 按用户筛选 */
        if (isset($oOptions['user']) && is_object($oOptions['user'])) {
            $oUser = $oOptions['user'];
            if (!empty($oUser->uid)) {
                $q[2] .= " and(userid='{$oUser->uid}' or owner_userid='{$oUser->uid}')";
            }
        }
        if (isset($oOptions['notTargetType'])) {
            if (is_array($oOptions['notTargetType'])) {
                $notType = implode("','", $oOptions['notTargetType']);
                $q[2] .= " and target_type not in ('" . $notType . "')";
            }
        }
        $q2 = ['o' => 'event_at desc'];

        /* 查询结果分页 */
        if (isset($oOptions['page']) && is_object($oOptions['page'])) {
            $oPage = $oOptions['page'];
        } else {
            $oPage = (object) ['at' => 1, 'size' => 30];
        }
        $q2['r'] = ['o' => ((int) $oPage->at - 1) * (int) $oPage->size, 'l' => (int) $oPage->size];

        $logs = $this->query_objs_ss($q, $q2);

        $oResult = new \stdClass;
        $oResult->logs = $logs;
        /* 符合条件的数据总数 */
        if (count($logs) < (int) $oPage->size) {
            $oResult->total = ((int) $oPage->at - 1) * (int) $oPage->size + count($logs);
        } else {
            $q[0] = 'count(*)';
            $total = (int) $this->query_val_ss($q);
            $oResult->total = $total;
        }

        return $oResult;
    }
}