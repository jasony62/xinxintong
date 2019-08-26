<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/record_base.php';
/*
 * 修复活动记录数据
 */
class repair extends record_base {
    /**
     * 指定需要作为事物管理的方法
     */
    public function tmsRequireTransaction() {
        return [
            'userCoin',
        ];
    }
    /**
     * 更新记录数据分
     */
    private function _scoreRecord(&$oApp, &$oRecord, &$modelRec, &$modelRecDat) {
        $oMockUser = new \stdClass;
        $oMockUser->uid = $oRecord->userid;
        $oMockUser->group_id = $oRecord->group_id;

        $dbData = json_decode($oRecord->data);
        $oSetResult = $modelRecDat->setData($oMockUser, $oApp, $oRecord, $dbData);
        if (is_array($oSetResult) && false === $oSetResult[0]) {
            return [false, $oSetResult[1]];
        }
        /**
         * 更新记录上的数据
         */
        $jsonRecordData = $modelRecDat->escape($modelRecDat->toJson($oSetResult->dbData));
        $modelRecDat->update('xxt_enroll_record', ['data' => $jsonRecordData], ['enroll_key' => $oRecord->enroll_key]);
        /**
         * 处理用户按轮次汇总数据，行为分数据
         */
        $oMockUser = new \stdClass;
        $oMockUser->uid = $oRecord->userid;
        $oMockUser->group_id = $oRecord->group_id;
        $modelRec->setSummaryRec($oMockUser, $oApp, $oRecord->rid);

        return [true];
    }
    /**
     * 根据reocrd_data中的数据，修复record中的data字段
     */
    public function record_action($ek) {
        $modelRec = $this->model('matter\enroll\record');
        $oRecord = $modelRec->byId($ek);
        if (false === $oRecord) {
            return new \ParameterError();
        }

        $q = [
            'schema_id,value',
            'xxt_enroll_record_data',
            ['enroll_key' => $ek, 'state' => 1],
        ];
        $schemaValues = $modelRec->query_objs_ss($q);

        $oRecordData = new \stdClass;
        foreach ($schemaValues as $schemaValue) {
            if (strlen($schemaValue->value)) {
                if ($jsonVal = json_decode($schemaValue->value)) {
                    $oRecordData->{$schemaValue->schema_id} = $jsonVal;
                } else {
                    $oRecordData->{$schemaValue->schema_id} = $schemaValue->value;
                }
            }
        }

        $sRecordData = $modelRec->escape($modelRec->toJson($oRecordData));

        $rst = $modelRec->update('xxt_enroll_record', ['data' => $sRecordData], ['enroll_key' => $ek]);

        return new \ResponseData($rst);
    }
    /**
     * 根据用户的填写记录更新用户数据
     */
    public function user_action($rid = '', $onlyCheck = 'Y') {
        $modelUsr = $this->model('matter\enroll\user');
        $aUpdatedResult = $modelUsr->renew($this->app, $rid, $onlyCheck);

        return new \ResponseData($aUpdatedResult);
    }
    /**
     * 更新活动用户对应的分组信息
     */
    public function userGroup_action() {
        $oApp = $this->app;
        if (!isset($oApp->entryRule->group->id)) {
            return new \ResponseError('没有指定关联的分组活动');
        }

        $updatedCount = $this->model('matter\enroll\user')->repairGroup($oApp);

        return new \ResponseData($updatedCount);
    }
    /**
     * 更新指定活动下所有记录的数据分
     */
    public function recordScoreByRound_action($app, $rid = null) {
        $modelRec = $this->model('matter\enroll\record');

        $renewCount = 0;
        $q = ['id,state,enroll_key,enroll_at,rid,purpose,userid,nickname,group_id,data,score', 'xxt_enroll_record', ['aid' => $this->app->id]];
        if (!empty($rid)) {
            $q[2]['rid'] = $rid;
        }
        $records = $modelRec->query_objs_ss($q);
        if (count($records)) {
            $modelRecData = $this->model('matter\enroll\data');
            $aOptimizedFormulas = []; // 保存优化后的数据分计算公式
            foreach ($records as $oRecord) {
                if (!empty($oRecord->data)) {
                    $aResult = $this->_scoreRecord($this->app, $oRecord, $modelRec, $modelRecData);
                    if ($aResult[0] === true) {
                        $renewCount++;
                    }
                }
            }
            /**
             * 更新数据分题目排名
             */
            $modelRec->setScoreRank($this->app, $oRecord->rid);
            /**
             * 更新用户数据分排名
             */
            $modelEnlUsr = $this->model('matter\enroll\user');
            $modelEnlUsr->setScoreRank($this->app, $oRecord->rid);

            $modelUsr = $this->model('matter\enroll\user');
            $aUpdatedResult = $modelUsr->renew($this->app);
        }

        // 记录操作日志
        $this->model('matter\log')->matterOp($this->app->siteid, $this->user, $this->app, 'renewScore');

        return new \ResponseData($renewCount);
    }
    /**
     * 更新指定活动下指定记录的数据分
     */
    public function recordScore_action($app, $ek) {
        // 记录活动
        $modelRec = $this->model('matter\enroll\record');
        $q = [
            'id,state,enroll_key,enroll_at,rid,purpose,userid,nickname,group_id,data,score',
            'xxt_enroll_record',
            ['aid' => $this->app->id, 'enroll_key' => $ek],
        ];
        $oRecord = $modelRec->query_obj_ss($q);
        if ($oRecord) {
            if (!empty($oRecord->data)) {
                $modelRecDat = $this->model('matter\enroll\data');
                $aResult = $this->_scoreRecord($this->app, $oRecord, $modelRec, $modelRecDat);
            }
            /**
             * 更新数据分题目排名
             */
            $modelRec->setScoreRank($this->app, $oRecord->rid);

            $modelUsr = $this->model('matter\enroll\user');
            $aUpdatedResult = $modelUsr->renew($this->app, '', $oRecord->userid);
        }

        // 记录操作日志
        $this->model('matter\log')->matterOp($this->app->siteid, $this->user, $this->app, 'renewScore');

        return new \ResponseData('ok');
    }
    /**
     * 重置活动行为分
     */
    private function _resetEnlLog($log, $coin, &$modelEnlLog) {
        // 没有发生变化
        if ($log->earn_coin == $coin) {
            return false;
        }
        $transId = $this->tmsTransactionId();
        /**
         * 生成新记录
         */
        $now = $this->getRequestTime();
        $newLog = clone $log;
        unset($newLog->id);
        $newLog->g_transid = $transId;
        $newLog->reset_at = $now;
        $newLog->reset_event_id = $log->id;
        $newLog->earn_coin = $coin;
        $newLog->id = $modelEnlLog->insert($modelEnlLog->table(), $newLog, true);
        /**
         * 更新老记录
         */
        $modelEnlLog->update($modelEnlLog->table(), ['state' => 0], ['id' => $log->id]);

        return $newLog;
    }
    /**
     * 基于用户在活动中的行为日志，重置用户行为分
     * 更新xxt_enroll_user,xxt_enroll_group,xxt_mission_user,xxt_mission_group,xxt_enroll_log数据
     */
    public function userCoin_action($rid) {
        if (empty($rid)) {
            return new \ResponseError('请指定要重置的活动轮次');
        }
        $resetCount = 0; // 重置的记录数
        $oApp = $this->app;

        $modelCoinRule = $this->model('matter\enroll\coin');
        $modelEnlLog = $this->model('matter\enroll\log');
        /**
         * 更新用户活动行为日志
         */
        $aResetUsers = [];
        $aResetGroups = [];
        $aCacheCoinRules = []; // 缓存行为分规则
        $q = ['*', $modelEnlLog->table(), ['aid' => $oApp->id, 'rid' => $rid, 'state' => 1, 'coin_event' => 1]];
        $logs = $modelEnlLog->query_objs_ss($q);
        foreach ($logs as $log) {
            if (!isset($aCacheCoinRules[$log->event_name])) {
                $aCoinResult = $modelCoinRule->coinByMatter($log->event_name, $oApp);
                $coin = $aCoinResult[0] === true ? $aCoinResult[1] : 0;
                $aCacheCoinRules[$log->event_name] = $coin;
            }
            $done = $this->_resetEnlLog($log, $aCacheCoinRules[$log->event_name], $modelEnlLog);
            if ($done) {
                $resetCount++;
                if (!empty($log->userid)) {
                    $aResetUsers[$log->userid] = true;
                }
                if (!empty($log->group_id)) {
                    $aResetGroups[$log->group_id] = true;
                }
            }
        }
        /**
         * 更新用户汇总行为分
         */
        if (count($aResetUsers)) {
            $modelEnlUsr = $this->model('matter\enroll\user');
            foreach ($aResetUsers as $userid => $foo) {
                $modelEnlUsr->resetCoin($oApp, $rid, $userid);
            }
        }
        /**
         * 更新分组汇总行为分
         */
        if (count($aResetGroups)) {
            $modelEnlGrp = $this->model('matter\enroll\group');
            $modelEnlRec = $this->model('matter\enroll\record');
            foreach ($aResetGroups as $groupId => $foo) {
                // 分组提交行为分
                $oLastGrpRecord = $modelEnlRec->lastByGroup($oApp, $groupId);
                if ($oLastGrpRecord) {
                    $this->groupSubmitRecord($oApp, $oUser, $oLastGrpRecord, $oLastGrpRecord->enroll_at);
                }
                $modelEnlGrp->resetCoin($oApp, $rid, $groupId);
            }
        }
        /**
         * 更新项目用户累积行为分
         */
        if ($oApp->mission_id && (count($aResetUsers) || count($aResetGroups))) {
            $oMission = $this->model('matter\mission')->byId($oApp->mission_id, ['fields' => 'siteid,id,user_app_type,user_app_id']);
            if ($oMission) {
                $modelMisUsr = $this->model('matter\mission\user');
                foreach ($aResetUsers as $userid => $foo) {
                    $modelMisUsr->resetCoin($oMission, $userid);
                }
                if ($oMission->user_app_type === 'group') {
                    if ($this->getDeepValue($oApp, 'entryRule.group.id') === $oMission->user_app_id) {
                        $modelMisGrp = $this->model('matter\mission\group');
                        foreach ($aResetGroups as $groupId => $foo) {
                            $modelMisGrp->resetCoin($oMission, $groupId);
                        }
                    }
                }
            }
        }

        // 记录操作日志
        $this->model('matter\log')->matterOp($oApp->siteid, $this->user, $oApp, 'resetCoin', $resetCount);

        return new \ResponseData($resetCount);
    }
}