<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/record_base.php';
/*
 * 修复活动记录数据
 */
class repair extends record_base {
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
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

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
     * 更新指定活动下所有记录的数据分
     */
    public function recordScoreByRound_action($app, $rid = null) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        // 记录活动
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $renewCount = 0;
        $q = ['id,state,enroll_key,enroll_at,rid,purpose,userid,nickname,group_id,data,score', 'xxt_enroll_record', ['aid' => $oApp->id]];
        if (!empty($rid)) {
            $q[2]['rid'] = $rid;
        }
        $records = $modelApp->query_objs_ss($q);
        if (count($records)) {
            $modelRec = $this->model('matter\enroll\record');
            $modelRecData = $this->model('matter\enroll\data');
            $aOptimizedFormulas = []; // 保存优化后的数据分计算公式
            foreach ($records as $oRecord) {
                if (!empty($oRecord->data)) {
                    $aResult = $this->_scoreRecord($oApp, $oRecord, $modelRec, $modelRecData);
                    if ($aResult[0] === true) {
                        $renewCount++;
                    }
                }
            }
            /**
             * 更新数据分题目排名
             */
            $modelRec->setScoreRank($oApp, $oRecord->rid);
            /**
             * 更新用户数据分排名
             */
            $modelEnlUsr = $this->model('matter\enroll\user');
            $modelEnlUsr->setScoreRank($oApp, $oRecord->rid);

            $modelUsr = $this->model('matter\enroll\user');
            $aUpdatedResult = $modelUsr->renew($oApp);
        }

        // 记录操作日志
        $this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'renewScore');

        return new \ResponseData($renewCount);
    }
    /**
     * 更新指定活动下指定记录的数据分
     */
    public function recordScore_action($app, $ek) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        // 记录活动
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $q = [
            'id,state,enroll_key,enroll_at,rid,purpose,userid,nickname,group_id,data,score',
            'xxt_enroll_record',
            ['aid' => $oApp->id, 'enroll_key' => $ek],
        ];
        $oRecord = $modelApp->query_obj_ss($q);
        if ($oRecord) {
            $modelRec = $this->model('matter\enroll\record');
            if (!empty($oRecord->data)) {
                $modelRecDat = $this->model('matter\enroll\data');
                $aResult = $this->_scoreRecord($oApp, $oRecord, $modelRec, $modelRecDat);
            }
            /**
             * 更新数据分题目排名
             */
            $modelRec->setScoreRank($oApp, $oRecord->rid);

            $modelUsr = $this->model('matter\enroll\user');
            $aUpdatedResult = $modelUsr->renew($oApp, '', $oRecord->userid);
        }

        // 记录操作日志
        $this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'renewScore');

        return new \ResponseData('ok');
    }
}