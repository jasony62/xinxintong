<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 记录活动的记录
 */
class record extends main_base {
    /**
     * 获得指定记录
     */
    public function get_action($ek) {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $mdoelRec = $this->model('matter\enroll\record');
        $oRecord = $mdoelRec->byId($ek, ['verbose' => 'Y']);
        if ($oRecord) {
            $modelApp = $this->model('matter\enroll');
            $oApp = $modelApp->byId($oRecord->aid);
            $dataSchemas = new \stdClass;
            foreach ($oApp->dataSchemas as $schema) {
                $dataSchemas->{$schema->id} = $schema;
            }
            foreach ($oRecord->data as $k => $data) {
                if (isset($dataSchemas->{$k}) && $dataSchemas->{$k}->type === 'multitext') {
                    $verboseVals = json_decode($oRecord->verbose->{$k}->value);
                    $items = [];
                    foreach ($verboseVals as $verboseVal) {
                        $res = $this->model('matter\enroll\data')->byId($verboseVal->id);
                        $items[] = $res;
                    }
                    $oRecord->verbose->{$k}->items = $items;
                }
            }
        }

        return new \ResponseData($oRecord);
    }
    /**
     * 活动的记录
     */
    public function list_action($app, $page = 1, $size = 30) {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }
        // 记录活动
        $modelApp = $this->model('matter\enroll');
        $oEnrollApp = $modelApp->byId($app, ['cascaded' => 'N']);

        // 登记数据过滤条件
        $oCriteria = $this->getPostJson();

        // 填写记录过滤条件
        $aOptions = [
            'page' => $page,
            'size' => $size,
        ];
        if (!empty($oCriteria->keyword)) {
            $aOptions->keyword = $oCriteria->keyword;
            unset($oCriteria->keyword);
        }

        // 查询结果
        $modelRec = $this->model('matter\enroll\record');
        $oResult = $modelRec->byApp($oEnrollApp, $aOptions, $oCriteria);

        return new \ResponseData($oResult);
    }
    /**
     * 指定活动轮次的记录的数量
     */
    public function countByRound_action($round) {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $modelRnd = $this->model('matter\enroll\round');
        $oRound = $modelRnd->byId($round, ['fields' => 'rid']);
        if (false === $oRound) {
            return new \ObjectNotFoundError();
        }

        $modelRec = $this->model('matter\enroll\record');
        $count = $modelRec->byRound($oRound->rid, ['fields' => 'count(*)']);

        return new \ResponseData($count);
    }
    /**
     * 计算指定登记项所有记录的合计
     * 若不指定登记项，则返回活动中所有数值型登记项的合集
     * 若指定的登记项不是数值型，返回0
     */
    public function sum4Schema_action($app, $rid = '', $gid = '') {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        // 记录活动
        $modelApp = $this->model('matter\enroll');
        $enrollApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $enrollApp) {
            return new \ObjectNotFoundError();
        }

        $rid = empty($rid) ? [] : explode(',', $rid);

        // 查询结果
        $modelRec = $this->model('matter\enroll\record');
        $result = $modelRec->sum4Schema($enrollApp, $rid, $gid);

        return new \ResponseData($result);
    }
    /**
     * 计算指定登记项的得分
     *
     * @param string $gid 分组id
     *
     */
    public function score4Schema_action($app, $rid = '', $gid = '') {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        // 记录活动
        $modelApp = $this->model('matter\enroll');
        $enrollApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $enrollApp || $enrollApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $rid = empty($rid) ? [] : explode(',', $rid);

        // 查询结果
        $modelRec = $this->model('matter\enroll\record');
        $oResult = $modelRec->score4Schema($enrollApp, $rid, $gid);

        return new \ResponseData($oResult);
    }
    /**
     * 更新指定活动下所有记录的得分
     */
    public function renewScoreByRound_action($app, $rid = null) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        // 记录活动
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $schemasById = $this->model('matter\enroll\schema')->asAssoc($oApp->dynaDataSchemas);

        $renewCount = 0;
        $q = ['id,enroll_key,rid,userid,group_id,data,score', 'xxt_enroll_record', ['aid' => $oApp->id]];
        if (!empty($rid)) {
            $q[2]['rid'] = $rid;
        }
        $records = $modelApp->query_objs_ss($q);
        if (count($records)) {
            $modelRec = $this->model('matter\enroll\record');
            $modelRecData = $this->model('matter\enroll\data');
            $aOptimizedFormulas = []; // 保存优化后的得分计算公式
            foreach ($records as $oRecord) {
                if (!empty($oRecord->data)) {
                    $dbData = json_decode($oRecord->data);
                    /* 题目的得分 */
                    $oRecordScore = $modelRecData->socreRecordData($oApp, $oRecord, $schemasById, $dbData, null, $aOptimizedFormulas);
                    if ($modelApp->update('xxt_enroll_record', ['score' => json_encode($oRecordScore)], ['id' => $oRecord->id])) {
                        unset($oRecordScore->sum);
                        foreach ($oRecordScore as $schemaId => $dataScore) {
                            $modelApp->update(
                                'xxt_enroll_record_data',
                                ['score' => $dataScore],
                                ['enroll_key' => $oRecord->enroll_key, 'schema_id' => $schemaId]
                            );
                        }
                        $renewCount++;
                    }
                }
                /**
                 * 处理用户按轮次汇总数据，积分数据
                 */
                $oMockUser = new \stdClass;
                $oMockUser->uid = $oRecord->userid;
                $oMockUser->group_id = $oRecord->group_id;
                $modelRec->setSummaryRec($oMockUser, $oApp, $oRecord->rid);
            }
            /**
             * 更新得分题目排名
             */
            $modelRec->setScoreRank($oApp, $oRecord->rid);
            /**
             * 更新用户得分排名
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
     * 更新指定活动下所有记录的得分
     */
    public function renewScore_action($app, $ek) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        // 记录活动
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $schemasById = $this->model('matter\enroll\schema')->asAssoc($oApp->dynaDataSchemas);

        $modelRec = $this->model('matter\enroll\record');
        $modelRecDat = $this->model('matter\enroll\data');
        $q = ['id,enroll_key,rid,userid,group_id,data,score', 'xxt_enroll_record', ['aid' => $oApp->id, 'enroll_key' => $ek]];
        $oRecord = $modelApp->query_obj_ss($q);
        if ($oRecord) {
            if (!empty($oRecord->data)) {
                $dbData = json_decode($oRecord->data);
                /* 题目的得分 */
                $aOptimizedFormulas = [];
                $oRecordScore = $modelRecDat->socreRecordData($oApp, $oRecord, $schemasById, $dbData, null, $aOptimizedFormulas);
                if ($modelApp->update('xxt_enroll_record', ['score' => json_encode($oRecordScore)], ['id' => $oRecord->id])) {
                    unset($oRecordScore->sum);
                    foreach ($oRecordScore as $schemaId => $dataScore) {
                        $modelApp->update(
                            'xxt_enroll_record_data',
                            ['score' => $dataScore],
                            ['enroll_key' => $oRecord->enroll_key, 'schema_id' => $schemaId]
                        );
                    }
                }
            }
            /**
             * 处理用户按轮次汇总数据，积分数据
             */
            $oMockUser = new \stdClass;
            $oMockUser->uid = $oRecord->userid;
            $oMockUser->group_id = $oRecord->group_id;
            $modelRec->setSummaryRec($oMockUser, $oApp, $oRecord->rid);
            /**
             * 更新得分题目排名
             */
            $modelRec->setScoreRank($oApp, $oRecord->rid);

            $modelUsr = $this->model('matter\enroll\user');
            $aUpdatedResult = $modelUsr->renew($oApp, '', $oRecord->userid);
        }

        // 记录操作日志
        //$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'renewScore');

        return new \ResponseData('ok');
    }
    /**
     * 已删除的活动登记名单
     */
    public function recycle_action($app, $page = 1, $size = 30, $rid = null) {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        // 填写记录过滤条件
        $aOptions = [
            'page' => $page,
            'size' => $size,
            'rid' => $rid,
        ];

        // 记录活动
        $modelApp = $this->model('matter\enroll');
        $enrollApp = $modelApp->byId($app);

        // 查询结果
        $modelRec = $this->model('matter\enroll\record');
        $result = $modelRec->recycle($enrollApp, $aOptions);

        return new \ResponseData($result);
    }
    /**
     * 返回指定登记项的活动登记名单
     */
    public function list4Schema_action($app, $rid = null, $schema, $page = 1, $size = 10) {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        // 填写记录过滤条件
        $aOptions = [
            'page' => $page,
            'size' => $size,
        ];
        if (!empty($rid)) {
            $aOptions['rid'] = $rid;
        }

        // 记录活动
        $modelApp = $this->model('matter\enroll');
        $enrollApp = $modelApp->byId($app);

        // 查询结果
        $modelRec = $this->model('matter\enroll\record');
        $result = $modelRec->list4Schema($enrollApp, $schema, $aOptions);

        return new \ResponseData($result);
    }
    /**
     * 复制记录
     *
     * @param string $ek 被复制记录的ID
     * @param string $owner 接受记录的用户id
     *
     */
    public function copy_action($ek, $owner) {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $modelRec = $this->model('matter\enroll\record');
        $oCopiedRecord = $modelRec->byId($ek, ['verbose' => 'N']);
        if (false === $oCopiedRecord) {
            return new \ObjectNotFoundError();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $modelEnl->byId($oCopiedRecord->aid, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $modelEnlUsr = $this->model('matter\enroll\user');
        $oOwner = $modelEnlUsr->byId($oApp, $owner, ['fields' => 'userid,group_id,nickname']);
        if (false === $oOwner) {
            return new \ObjectNotFoundError();
        }

        $oMocker = new \stdClass;
        $oMocker->uid = $oOwner->userid;
        $oMocker->nickname = $oOwner->nickname;
        $oMocker->group_id = $oOwner->group_id;

        /* 创建记录 */
        $aOptions = [];
        $aOptions['assignedRid'] = $oCopiedRecord->rid;
        $oNewRec = $modelRec->enroll($oApp, $oMocker, $aOptions);
        $aResult = $modelRec->setData($oMocker, $oApp, $oNewRec->enroll_key, $oCopiedRecord->data, true);
        if (false === $aResult[0]) {
            return new \ResponseError($aResult[1]);
        }

        /* 返回完整的记录 */
        $oNewRecord = $modelRec->byId($oNewRec->enroll_key, ['verbose' => 'Y']);

        /* 处理用户汇总数据，积分数据 */
        $this->model('matter\enroll\event')->submitRecord($oApp, $oNewRecord, $oMocker, true);

        return new \ResponseData($oNewRecord);
    }
    /**
     * 手工添加登记信息
     *
     * @param string $app
     */
    public function add_action($app) {
        if (false === ($oOperator = $this->accountUser())) {
            return new \ResponseTimeout();
        }
        $posted = $this->getPostJson();
        $modelEnl = $this->model('matter\enroll');
        $modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);

        $oApp = $modelEnl->byId($app, ['cascaded' => 'N']);

        /* 创建填写记录 */
        $aOptions = [];
        !empty($posted->rid) && $aOptions['assignedRid'] = $posted->rid;
        $oNewRec = $modelRec->enroll($oApp, null, $aOptions);

        $record = [];
        $record['verified'] = isset($posted->verified) ? $posted->verified : 'N';
        $record['comment'] = isset($posted->comment) ? $posted->comment : '';
        if (isset($posted->tags)) {
            $record['tags'] = $posted->tags;
            $modelEnl->updateTags($oApp->id, $posted->tags);
        }
        $modelRec->update('xxt_enroll_record', $record, ['enroll_key' => $oNewRec->enroll_key]);

        /* 记录登记数据 */
        $addUser = $this->model('site\fe\way')->who($oApp->siteid);
        $result = $modelRec->setData(null, $oApp, $oNewRec->enroll_key, $posted->data, $addUser->uid, true);

        /* 记录操作日志 */
        $oRecord = $modelRec->byId($oNewRec->enroll_key, ['fields' => 'enroll_key,data,rid']);
        $this->model('matter\log')->matterOp($oApp->siteid, $oOperator, $oApp, 'add', $oRecord);

        /* 返回完整的记录 */
        $oNewRec = $modelRec->byId($oNewRec->enroll_key, ['verbose' => 'Y']);

        return new \ResponseData($oNewRec);
    }
    /**
     * 将记录导入到其他活动
     *
     * @param string $app
     * @param string $targetApp
     */
    public function exportToOther_action($app, $targetApp) {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $oPosted = $this->getPostJson();
        if (empty($oPosted->eks)) {
            return new \ParameterError('没有指定要导出的记录');
        }
        if (empty($oPosted->mappings)) {
            return new \ParameterError('没有指定题目映射关系');
        }

        $modelEnl = $this->model('matter\enroll');

        $oApp = $modelEnl->byId($app, ['fields' => 'siteid,state,mission_id,sync_mission_round,data_schemas']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }
        $oTargetApp = $modelEnl->byId($targetApp, ['fields' => '*']);
        if (false === $oTargetApp || $oTargetApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $aResult = $this->model('matter\enroll\record\copy')->toApp($oApp, $oTargetApp, $oPosted->eks, $oPosted->mappings);
        if (false === $aResult[0]) {
            return new \ResponseError($aResult[1]);
        }

        return new \ResponseData(count($aResult[1]));
    }
    /**
     * 投票结果导出到其他活动作为记录
     */
    public function transferVotes_action($app, $targetApp, $round = '') {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $oPosted = $this->getPostJson();

        if (empty($oPosted->targetSchema)) {
            return new \ParameterError('没有指定目标题目');
        }
        if (empty($oPosted->votingSchemas)) {
            return new \ParameterError('没有指定投票题目');
        }

        $modelEnl = $this->model('matter\enroll');
        $modelRec = $this->model('matter\enroll\record');
        $modelUsr = $this->model('matter\enroll\user');

        $oApp = $modelEnl->byId($app, ['fields' => 'siteid,state,mission_id,sync_mission_round,round_cron,data_schemas', 'appRid' => $round]);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }
        /* 指定的投票题目 */
        $aVotingSchemas = [];
        foreach ($oApp->dataSchemas as $oSchema) {
            if (in_array($oSchema->id, $oPosted->votingSchemas)) {
                if (in_array($oSchema->type, ['single', 'multiple'])) {
                    $aVotingSchemas[] = $oSchema;
                }
            }
        }
        if (empty($aVotingSchemas)) {
            return new \ParameterError('没有指定有效的题目');
        }

        $oTargetApp = $modelEnl->byId($targetApp, ['fields' => '*']);
        if (false === $oTargetApp || $oTargetApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        /* 匹配的轮次 */
        $oAssignedRnd = $oApp->appRound;
        $modelRnd = $this->model('matter\enroll\round');
        if ($oAssignedRnd) {
            $oTargetAppRnd = $modelRnd->byMissionRid($oTargetApp, $oAssignedRnd->mission_rid, ['fields' => 'rid,mission_rid']);
        }
        /* 目标活动的投票结果 */
        $aVotingData = $modelRec->getStat($oApp, $oAssignedRnd ? $oAssignedRnd->rid : '', 'N');
        $newRecordNum = 0;
        /* 根据投票结果创建记录 */
        foreach ($aVotingSchemas as $oVotingSchema) {
            if (empty($aVotingData[$oVotingSchema->id]->ops)) {
                continue;
            }
            $allOps = $aVotingData[$oVotingSchema->id]->ops;
            usort($allOps, function ($a, $b) {
                return $a->c < $b->c;
            });
            $qualifiedOps = []; // 满足条件的选项
            if (!empty($oPosted->limit->scope) && !empty($oPosted->limit->num) && (int) $oPosted->limit->num) {
                if ($oPosted->limit->scope === 'top') {
                    $limitNum = (int) $oPosted->limit->num;
                    if ($limitNum > count($allOps)) {
                        $limitNum = count($allOps);
                    }
                    for ($i = 0; $i < $limitNum; $i++) {
                        $qualifiedOps[] = $allOps[$i];
                    }
                } else if ($oPosted->limit->scope === 'checked') {
                    for ($i = 0, $ii = count($allOps); $i < $ii; $i++) {
                        $oOption = $allOps[$i];
                        $checkedNum = (int) $oPosted->limit->num;
                        if ($oOption->c < $checkedNum) {
                            break;
                        }
                        $qualifiedOps[] = $oOption;
                    }
                }
            }
            foreach ($qualifiedOps as $oQualifiedOp) {
                $oNewRecData = new \stdClass;
                $oNewRecData->{$oPosted->targetSchema} = $oQualifiedOp->l;
                /* 模拟用户 */
                $oVotingOpDs = null;
                foreach ($oVotingSchema->ops as $oOption) {
                    if ($oOption->v === $oQualifiedOp->v && !empty($oOption->ds->user)) {
                        $oVotingOpDs = $oOption->ds;
                        break;
                    }
                }
                if (isset($oVotingOpDs)) {
                    $oMockUser = $modelUsr->byId($oTargetApp, $oVotingOpDs->user, ['fields' => 'id,userid,group_id,nickname']);
                    if (false === $oMockUser) {
                        $oMockUser = $modelUsr->detail($oTargetApp, (object) ['uid' => $oVotingOpDs->user], $oNewRecData);
                    } else {
                        $oMockUser->uid = $oMockUser->userid;
                    }
                } else {
                    $oMockUser = null;
                }
                /* 在目标活动中创建新记录 */
                $oNewRec = $modelRec->enroll($oTargetApp, $oMockUser);
                $modelRec->setData($oMockUser, $oTargetApp, $oNewRec->enroll_key, $oNewRecData, '', true);
                $newRecordNum++;
            }
        }

        return new \ResponseData($newRecordNum);
    }
    /**
     * 投票题目和结果导出到其他活动作为记录
     */
    public function transferSchemaAndVotes_action($app, $targetApp, $round = '') {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $oPosted = $this->getPostJson();
        if (empty($oPosted->questionSchema)) {
            return new \ParameterError('目标活动中没有指定作为问题的题目');
        }
        if (empty($oPosted->answerSchema)) {
            return new \ParameterError('目标活动中没有指定作为答案的题目');
        }

        $modelEnl = $this->model('matter\enroll');
        $modelRec = $this->model('matter\enroll\record');
        $modelData = $this->model('matter\enroll\data');
        $modelUsr = $this->model('matter\enroll\user');

        $oApp = $modelEnl->byId($app, ['fields' => 'siteid,state,mission_id,sync_mission_round,round_cron,data_schemas', 'appRid' => $round]);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }
        /* 指定的投票题目 */
        $aVotingSchemas = [];
        foreach ($oApp->dynaDataSchemas as $oSchema) {
            if (in_array($oSchema->id, $oPosted->votingSchemas)) {
                if (in_array($oSchema->type, ['single', 'multiple'])) {
                    $aVotingSchemas[] = $oSchema;
                }
            }
        }
        if (empty($aVotingSchemas)) {
            return new \ParameterError('没有指定有效的题目');
        }

        $oTargetApp = $modelEnl->byId($targetApp, ['fields' => '*']);
        if (false === $oTargetApp || $oTargetApp->state !== '1') {
            return new \ObjectNotFoundError();
        }
        /* 匹配的轮次 */
        $oAssignedRnd = $oApp->appRound;
        if ($oApp->mission_id === $oTargetApp->mission_id) {
            $modelRnd = $this->model('matter\enroll\round');
            if ($oAssignedRnd) {
                $oTargetAppRnd = $modelRnd->byMissionRid($oTargetApp, $oAssignedRnd->mission_rid, ['fields' => 'rid,mission_rid']);
            }
        } else {
            $oTargetAppRnd = $oTargetApp->appRound;
        }
        /* 目标活动的投票结果 */
        $aVotingData = $modelRec->getStat($oApp, $oAssignedRnd ? $oAssignedRnd->rid : '', 'N');
        $newRecordNum = 0;
        /* 根据投票结果创建记录，每道题生成一条记录 */
        foreach ($aVotingSchemas as $oVotingSchema) {
            if (empty($aVotingData[$oVotingSchema->id]->ops)) {
                continue;
            }
            $allOps = $aVotingData[$oVotingSchema->id]->ops;
            usort($allOps, function ($a, $b) {
                return $a->c < $b->c;
            });
            $qualifiedOps = []; // 满足条件的选项
            if (!empty($oPosted->limit->scope) && !empty($oPosted->limit->num) && (int) $oPosted->limit->num) {
                if ($oPosted->limit->scope === 'top') {
                    $limitNum = (int) $oPosted->limit->num;
                    if ($limitNum > count($allOps)) {
                        $limitNum = count($allOps);
                    }
                    for ($i = 0; $i < $limitNum; $i++) {
                        $qualifiedOps[] = $allOps[$i];
                    }
                } else if ($oPosted->limit->scope === 'checked') {
                    for ($i = 0, $ii = count($allOps); $i < $ii; $i++) {
                        $oOption = $allOps[$i];
                        $checkedNum = (int) $oPosted->limit->num;
                        if ($oOption->c < $checkedNum) {
                            break;
                        }
                        $qualifiedOps[] = $oOption;
                    }
                }
            }
            /* 生成记录 */
            if (isset($oVotingSchema->referRecord->ds->user)) {
                $oMockRecUser = $modelUsr->detail($oTargetApp, (object) ['uid' => $oVotingSchema->referRecord->ds->user]);
            } else {
                $oMockRecUser = new \stdClass;
            }
            $oNewRec = $modelRec->enroll($oTargetApp, $oMockRecUser);

            $oNewRecData = new \stdClass; // 问题+答案的记录数据
            /* 写入问题 */
            $oNewRecData->{$oPosted->questionSchema} = $oVotingSchema->title;

            /* 写入答案 */
            $current = time();
            $oRecData = new \stdClass; // 根结点
            $oRecData->aid = $oTargetApp->id;
            $oRecData->rid = empty($oTargetAppRnd->rid) ? '' : $oTargetAppRnd->rid;
            $oRecData->record_id = $oNewRec->id;
            $oRecData->enroll_key = $oNewRec->enroll_key;
            $oRecData->submit_at = $current;
            $oRecData->userid = isset($oMockRecUser->uid) ? $oMockRecUser->uid : '';
            $oRecData->nickname = isset($oMockRecUser->nickname) ? $modelData->escape($oMockRecUser->nickname) : '';
            $oRecData->group_id = isset($oMockRecUser->group_id) ? $oMockRecUser->group_id : '';
            $oRecData->schema_id = $oPosted->answerSchema;
            $oRecData->is_multitext_root = 'Y';
            $oRecData->multitext_seq = 0;
            $oRecData->value = [];

            foreach ($qualifiedOps as $oQualifiedOp) {
                /* 模拟用户 */
                $oVotingOpDs = null;
                foreach ($oVotingSchema->ops as $oOption) {
                    if ($oOption->v === $oQualifiedOp->v && !empty($oOption->referRecord->ds->user)) {
                        $oVotingOpDs = $oOption->referRecord->ds;
                        break;
                    }
                }
                if (isset($oVotingOpDs)) {
                    $oMockAnswerUser = $modelUsr->byId($oTargetApp, $oVotingOpDs->user, ['fields' => 'id,userid,group_id,nickname']);
                    if (false === $oMockAnswerUser) {
                        $oMockAnswerUser = $modelUsr->detail($oTargetApp, (object) ['uid' => $oVotingOpDs->user], $oNewRecData);
                    } else {
                        $oMockAnswerUser->uid = $oMockAnswerUser->userid;
                    }
                } else {
                    $oMockAnswerUser = null;
                }

                $oNewItem = new \stdClass;
                $oNewItem->aid = $oRecData->aid;
                $oNewItem->rid = $oRecData->rid;
                $oNewItem->record_id = $oRecData->record_id;
                $oNewItem->enroll_key = $oRecData->enroll_key;
                $oNewItem->submit_at = $current;
                $oNewItem->userid = isset($oMockAnswerUser->uid) ? $oMockAnswerUser->uid : '';
                $oNewItem->nickname = isset($oMockAnswerUser->nickname) ? $modelData->escape($oMockAnswerUser->nickname) : '';
                $oNewItem->group_id = isset($oMockAnswerUser->group_id) ? $oMockAnswerUser->group_id : '';
                $oNewItem->schema_id = $oPosted->answerSchema;
                $oNewItem->value = $this->escape($oQualifiedOp->l);
                $oNewItem->is_multitext_root = 'N';
                $oNewItem->multitext_seq = count($oRecData->value) + 1;
                $oNewItem->id = $modelData->insert('xxt_enroll_record_data', $oNewItem, true);

                $oRecData->value[] = (object) ['id' => $oNewItem->id, 'value' => $oNewItem->value];
            }
            /* 记录的数据 */
            $oNewRecData->{$oPosted->answerSchema} = $oRecData->value;
            $modelRec->setData($oMockRecUser, $oTargetApp, $oNewRec->enroll_key, $oNewRecData, '', true);
            $newRecordNum++;
            /* 答案的根数据 */
            $oRecData->value = $modelData->escape($modelData->toJson($oRecData->value));
            $oRecData->id = $modelData->insert('xxt_enroll_record_data', $oRecData, true);
        }

        return new \ResponseData($newRecordNum);
    }
    /**
     * 投票题目和结果导出到其他活动作为记录
     */
    public function transferGroupAndMarks_action($app, $targetApp, $round = '') {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $oPosted = $this->getPostJson();
        if (empty($oPosted->questionSchema)) {
            return new \ParameterError('目标活动中没有指定作为问题的题目');
        }
        if (empty($oPosted->answerSchema)) {
            return new \ParameterError('目标活动中没有指定作为答案的题目');
        }

        $modelEnl = $this->model('matter\enroll');
        $modelRec = $this->model('matter\enroll\record');
        $modelData = $this->model('matter\enroll\data');
        $modelUsr = $this->model('matter\enroll\user');

        $oApp = $modelEnl->byId($app, ['fields' => 'siteid,state,mission_id,sync_mission_round,round_cron,data_schemas', 'appRid' => $round]);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }
        /* 所有打分题 */
        $aAllScoreSchemas = [];
        $aSchemaMapGroupIds = [];
        foreach ($oApp->dynaDataSchemas as $oSchema) {
            if ($oSchema->type === 'score' && isset($oSchema->parent->id)) {
                $aAllScoreSchemas[$oSchema->parent->id][] = $oSchema;
                $aSchemaMapGroupIds[$oSchema->id] = $oSchema->parent->id;
            }
        }
        if (empty($aAllScoreSchemas)) {
            return new \ParameterError('没有指定有效的打分题');
        }
        /* 所有分组题 */
        $aGroupSchemas = [];
        foreach ($oApp->dynaDataSchemas as $oSchema) {
            if ($oSchema->type === 'html' && isset($aAllScoreSchemas[$oSchema->id])) {
                $aGroupSchemas[$oSchema->id] = $oSchema;
            }
        }
        if (empty($aGroupSchemas)) {
            return new \ParameterError('没有指定有效的分组题');
        }
        /* 根据题目打分结果和规则筛选符合条件的题目 */
        $iLimitNum = 1;
        if (isset($oPosted->limit->num) && is_int($oPosted->limit->num)) {
            $iLimitNum = $oPosted->limit->num;
        }
        $oResult = $modelRec->score4Schema($oApp);
        unset($oResult->sum);
        $aResult = (array) $oResult;
        uasort($aResult, function ($a, $b) {
            return $a < $b ? 1 : -1;
        });
        $aGroupSchemasByScore = []; // 每个分组符合得分排序的题目
        foreach ($aResult as $schemaId => $score) {
            $groupSchemaId = $aSchemaMapGroupIds[$schemaId];
            if (!isset($aGroupSchemasByScore[$groupSchemaId])) {
                $aGroupSchemasByScore[$groupSchemaId] = [$schemaId];
            } else if ($iLimitNum > 1 && count($aGroupSchemasByScore[$groupSchemaId]) < $iLimitNum) {
                $aGroupSchemasByScore[$groupSchemaId][] = $schemaId;
            }
        }

        $oTargetApp = $modelEnl->byId($targetApp, ['fields' => '*']);
        if (false === $oTargetApp || $oTargetApp->state !== '1') {
            return new \ObjectNotFoundError();
        }
        /* 匹配的轮次 */
        $oAssignedRnd = $oApp->appRound;
        if ($oApp->mission_id === $oTargetApp->mission_id) {
            $modelRnd = $this->model('matter\enroll\round');
            if ($oAssignedRnd) {
                $oTargetAppRnd = $modelRnd->byMissionRid($oTargetApp, $oAssignedRnd->mission_rid, ['fields' => 'rid,mission_rid']);
            }
        } else {
            $oTargetAppRnd = $oTargetApp->appRound;
        }

        $newRecordNum = 0;
        foreach ($aAllScoreSchemas as $groupSchemaId => $oGroupScoreSchemas) {
            if (empty($aGroupSchemas[$groupSchemaId])) {
                continue;
            }
            $oGroupSchema = $aGroupSchemas[$groupSchemaId];
            /* 生成记录 */
            /* 模拟用户 */
            if (isset($oGroupSchema->referRecord->ds->user)) {
                $oMockRecUser = $modelUsr->detail($oTargetApp, (object) ['uid' => $oGroupSchema->referRecord->ds->user]);
            } else {
                $oMockRecUser = new \stdClass;
            }
            $oNewRec = $modelRec->enroll($oTargetApp, $oMockRecUser);

            $oNewRecData = new \stdClass; // 问题+答案的记录数据
            /* 写入问题 */
            $oNewRecData->{$oPosted->questionSchema} = $oGroupSchema->title;
            $modelRec->setData($oMockRecUser, $oTargetApp, $oNewRec->enroll_key, $oNewRecData, '', true);

            /* 写入答案 */
            $current = time();
            $oRecData = new \stdClass;
            $oRecData->aid = $oTargetApp->id;
            $oRecData->rid = empty($oTargetAppRnd->rid) ? '' : $oTargetAppRnd->rid;
            $oRecData->record_id = $oNewRec->id;
            $oRecData->enroll_key = $oNewRec->enroll_key;
            $oRecData->submit_at = $current;
            $oRecData->userid = isset($oMockRecUser->uid) ? $oMockRecUser->uid : '';
            $oRecData->nickname = isset($oMockRecUser->nickname) ? $modelData->escape($oMockRecUser->nickname) : '';
            $oRecData->group_id = isset($oMockRecUser->group_id) ? $oMockRecUser->group_id : '';
            $oRecData->schema_id = $oPosted->answerSchema;
            $oRecData->is_multitext_root = 'Y';
            $oRecData->multitext_seq = 0;
            $oRecData->value = [];
            $oRecDataValue = [];

            /* 分组下的题目作为答案的内容 */
            foreach ($oGroupScoreSchemas as $oScoreSchema) {
                if (!in_array($oScoreSchema->id, $aGroupSchemasByScore[$groupSchemaId])) {
                    continue;
                }
                /* 模拟用户 */
                if (isset($oScoreSchema->referRecord->ds->user)) {
                    $oMockAnswerUser = $modelUsr->detail($oTargetApp, (object) ['uid' => $oScoreSchema->referRecord->ds->user]);
                } else {
                    $oMockAnswerUser = null;
                }

                $oRecCowork = new \stdClass;
                $oRecCowork->aid = $oRecData->aid;
                $oRecCowork->rid = $oRecData->rid;
                $oRecCowork->record_id = $oRecData->record_id;
                $oRecCowork->enroll_key = $oRecData->enroll_key;
                $oRecCowork->submit_at = $current;
                $oRecCowork->userid = isset($oMockAnswerUser->uid) ? $oMockAnswerUser->uid : '';
                $oRecCowork->nickname = isset($oMockAnswerUser->nickname) ? $modelData->escape($oMockAnswerUser->nickname) : '';
                $oRecCowork->group_id = isset($oMockAnswerUser->group_id) ? $oMockAnswerUser->group_id : '';
                $oRecCowork->schema_id = $oPosted->answerSchema;
                $oRecCowork->value = $this->escape($oScoreSchema->title);
                $oRecCowork->is_multitext_root = 'Y';
                $oRecCowork->multitext_seq = count($oRecData->value) + 1;
                $oRecCowork->id = $modelData->insert('xxt_enroll_record_data', $oRecCowork, true);

                $oRecDataValue[] = (object) ['id' => $oRecCowork->id, 'value' => $oRecCowork->value];
            }
            /* 答案的根数据 */
            $oRecData->value = $modelData->escape($modelData->toJson($oRecDataValue));
            $oRecData->id = $modelData->insert('xxt_enroll_record_data', $oRecData, true);
            /* 记录的数据 */
            $oNewRecData->{$oPosted->answerSchema} = $oRecDataValue;
            $modelData->update(
                'xxt_enroll_record',
                ['data' => $this->escape($modelData->toJson($oNewRecData))],
                ['enroll_key' => $oNewRec->enroll_key]
            );

            $newRecordNum++;
        }

        return new \ResponseData($newRecordNum);
    }
    /**
     * 用一个活动中的数据填充指定活动的记录
     *
     * 仅支持用单行填写题或单选题进行匹配
     *
     * 除更新题目外，支持更新userid
     *
     */
    public function fillByOther_action($app, $targetApp, $preview = 'Y') {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }
        list($targetType, $targetId) = explode(',', $targetApp);
        if (empty($targetType) || empty($targetId)) {
            return new \ParameterError('目标活动参数不完整');
        }
        if (!in_array($targetType, ['mschema'])) {
            return new \ParameterError('指定了不支持的目标活动类型');
        }
        $oPosted = $this->getPostJson();
        if (empty($oPosted->intersectedSchemas)) {
            return new \ParameterError('没有指定题目匹配规则');
        }
        if (empty($oPosted->filledSchemas)) {
            return new \ParameterError('没有指定填充题目规则');
        }

        $modelEnl = $this->model('matter\enroll');
        $modelRec = $this->model('matter\enroll\record');

        $oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }
        /* 允许填写活动定义的题目和部分字段 */
        $filledAppAttrs = [];
        $filledAppSchemas = [];
        foreach ($oPosted->filledSchemas as $aMapping) {
            if (in_array($aMapping[0], ['userid'])) {
                $filledAppAttrs[] = $aMapping[0];
            } else {
                $bFound = false;
                foreach ($oApp->dataSchemas as $oSchema) {
                    if ($aMapping[0] === $oSchema->id) {
                        $bFound = true;
                        $filledAppSchemas[] = $aMapping[0];
                        break;
                    }
                }
                if (false === $bFound) {
                    return new \ParameterError('指定的填充题目不存在');
                }
            }
        }

        switch ($targetType) {
        case 'mschema':
            $oTargetApp = $this->model('matter\memberschema')->byId($targetId);
            if (false === $oTargetApp) {
                return new \ObjectNotFoundError();
            }
            $modelMember = $this->model('site\user\member');
            $fnMatchHandler = function ($oData) use ($oTargetApp, $modelMember) {
                $members = $modelMember->byMschema($oTargetApp->id, ['filter' => (object) ['attrs' => $oData]]);
                foreach ($members as $oMember) {
                    if (!empty($oMember->extattr) && is_string($oMember->extattr)) {
                        $oMember->extattr = json_decode($oMember->extattr);
                    }
                }
                return $members;
            };
            $filledSchemas = $oPosted->filledSchemas;
            $fnFillHandler = function ($oMember) use ($filledSchemas) {
                $oFilled = new \stdClass;
                foreach ($filledSchemas as $aMapping) {
                    if (!empty($oMember->{$aMapping[1]})) {
                        $oFilled->{$aMapping[0]} = $oMember->{$aMapping[1]};
                    } else if (!empty($oMember->extattr->{$aMapping[1]})) {
                        $oFilled->{$aMapping[0]} = $oMember->extattr->{$aMapping[1]};
                    }
                }
                return $oFilled;
            };
            break;
        }

        /* 设置记录匹配的交集 */
        $intersectedSchemas = $oPosted->intersectedSchemas;
        $fnRecordIntersect = function ($oRecord) use ($intersectedSchemas) {
            $oIntersection = new \stdClass;
            if (isset($oRecord->data)) {
                foreach ($intersectedSchemas as $aMapping) {
                    if (!empty($oRecord->data->{$aMapping[0]})) {
                        $oIntersection->{$aMapping[1]} = $oRecord->data->{$aMapping[0]};
                    }
                }
            }
            return $oIntersection;
        };
        /* 用填充数据更新记录 */
        $fnUpdateRecord = function ($oRecord, $oFilled) use ($modelRec, $oApp, $filledAppAttrs, $filledAppSchemas) {
            /* 更新记录属性 */
            if (count($filledAppAttrs)) {
                $aUpdatedAttrs = [];
                foreach ($filledAppAttrs as $attr) {
                    if (isset($oFilled->{$attr})) {
                        $aUpdatedAttrs[$attr] = $oFilled->{$attr};
                    }
                }
                if (count($aUpdatedAttrs)) {
                    $modelRec->update('xxt_enroll_record', $aUpdatedAttrs, ['enroll_key' => $oRecord->enroll_key]);
                    $modelRec->update('xxt_enroll_record_data', $aUpdatedAttrs, ['enroll_key' => $oRecord->enroll_key]);
                }
            }
            /* 更新题目 */
            if (count($filledAppSchemas)) {
                $bModified = false;
                $oUpdatedData = $oRecord->data;
                foreach ($filledAppSchemas as $schemaId) {
                    if (isset($oFilled->{$schemaId})) {
                        $oUpdatedData->{$schemaId} = $oFilled->{$schemaId};
                        $bModified = true;
                    }
                }
                if ($bModified) {
                    $modelRec->setData(null, $oApp, $oRecord->enroll_key, $oUpdatedData);
                }
            }

            return true;
        };

        $oResult = new \stdClass;
        $oResult->total = 0;
        $oResult->filledCount = 0; // 完成了填充的记录数
        $oResult->matchedCount = 0; // 能够匹配的数据

        /* 指定活动的所有记录 */
        $oSearchResult = $modelRec->byApp($oApp);
        if (!empty($oSearchResult->records)) {
            $oResult->total = count($oSearchResult->records);
            foreach ($oSearchResult->records as $oRecord) {
                $oIntersection = $fnRecordIntersect($oRecord);
                $targetRecords = $fnMatchHandler($oIntersection);
                /* 必须唯一匹配，才能进行填充 */
                if (count($targetRecords) !== 1) {
                    continue;
                }
                $oResult->matchedCount++;
                /* 获得要填充的数据 */
                $oFilled = $fnFillHandler($targetRecords[0]);
                if (!empty($oFilled)) {
                    if ('Y' !== $preview) {
                        /* 更新数据 */
                        if ($fnUpdateRecord($oRecord, $oFilled)) {
                            $oResult->filledCount++;
                        }
                    }
                }
            }
        }

        return new \ResponseData($oResult);
    }
    /**
     * 更新填写记录
     *
     * @param string $app
     * @param $ek record's key
     */
    public function update_action($app, $ek) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);
        $oBeforeRecord = $modelRec->byId($ek, ['verbose' => 'N']);
        if (false === $oBeforeRecord || $oBeforeRecord->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $oPosted = $this->getPostJson();
        /* 更新记录数据 */
        $oUpdated = new \stdClass;
        $oUpdated->enroll_at = time();
        if (isset($oPosted->comment)) {
            $oUpdated->comment = $modelEnl->escape($oPosted->comment);
        }
        if (isset($oPosted->agreed) && $oPosted->agreed !== $oBeforeRecord->agreed) {
            $oUpdated->agreed = in_array($oPosted->agreed, ['Y', 'N', 'A']) ? $oPosted->agreed : '';
            $oAgreedLog = $oBeforeRecord->agreed_log;
            if (isset($oAgreedLog->{$oUser->id})) {
                $oLog = $oAgreedLog->{$oUser->id};
                $oLog->time = time();
                $oLog->value = $oUpdated->agreed;
            } else {
                $oAgreedLog->{$oUser->id} = (object) ['time' => time(), 'value' => $oUpdated->agreed];
            }
            $oUpdated->agreed_log = json_encode($oAgreedLog);
            /* 如果活动属于项目，更新项目内的推荐内容 */
            if (!empty($oApp->mission_id)) {
                $modelMisMat = $this->model('matter\mission\matter');
                $modelMisMat->agreed($oApp, 'R', $oBeforeRecord, $oUpdated->agreed);
            }
            /* 处理了用户汇总数据，积分数据 */
            $this->model('matter\enroll\event')->agreeRecord($oApp, $oBeforeRecord, $oUser, $oUpdated->agreed);
        }
        if (isset($oPosted->tags)) {
            $oUpdated->tags = $modelEnl->escape($oPosted->tags);
            $modelEnl->updateTags($oApp->id, $oUpdated->tags);
        }
        if (isset($oPosted->verified)) {
            $oUpdated->verified = $modelEnl->escape($oPosted->verified);
        }
        if (isset($oPosted->rid)) {
            $userOldRid = $oBeforeRecord->rid;
            $userNewRid = $oPosted->rid;
            /* 同步enroll_user中的轮次 */
            if ($userOldRid !== $userNewRid) {
                $modelUser = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

                /* 获取enroll_user中用户现在的轮次,如果有积分则不能移动 */
                $resOld = $modelUser->byId($oApp, $oBeforeRecord->userid, ['rid' => $userOldRid]);
                if ($resOld->user_total_coin > 0) {
                    return new \ResponseError('用户在当前轮次上以获得积分，不能更换轮次！！');
                }
                /* 查询此用户的记录是否被点赞或者被留言，如果有就不能更改 */
                $qd = [
                    'count(*)',
                    'xxt_enroll_record_data',
                    "enroll_key = '$ek' and state = 1 and (like_num > 0 or remark_num > 0)",
                ];
                $UsrDataSum = $modelRec->query_val_ss($qd);
                if ($UsrDataSum > 0) {
                    return new \ResponseError('此数据在当前轮次上被点赞或被留言，不能更换轮次！！');
                }

                /* 在新的轮次中用户是否以有记录 */
                $resNew = $modelUser->byId($oApp, $oBeforeRecord->userid, ['rid' => $userNewRid]);
                if ($resNew === false) {
                    if ($resOld->enroll_num > 1) {
                        $modelRec->update("update xxt_enroll_user set enroll_num = enroll_num - 1 where id = $resOld->id");
                        //插入新的数据
                        $inData = ['last_enroll_at' => time(), 'enroll_num' => 1];
                        $inData['rid'] = $userNewRid;
                        $oUser = new \stdClass;
                        $oUser->uid = $resOld->userid;
                        $oUser->nickname = $resOld->nickname;
                        $oUser->group_id = empty($resOld->group_id) ? '' : $resOld->group_id;
                        $modelUser->add($oApp, $oUser, $inData);
                    } else {
                        $modelRec->update('xxt_enroll_user',
                            ['rid' => $userNewRid],
                            ['id' => $resOld->id]
                        );
                    }
                } else {
                    if ($resOld->enroll_num > 1) {
                        $modelRec->update("update xxt_enroll_user set enroll_num = enroll_num - 1 where id = $resOld->id");
                    } else {
                        $modelRec->delete('xxt_enroll_user', ['id' => $resOld->id]);
                    }

                    $modelRec->update("update xxt_enroll_user set enroll_num = enroll_num + 1 where id = $resNew->id");
                }

                $oUpdated->rid = $modelEnl->escape($oPosted->rid);
            }
        }
        if (isset($oPosted->supplement)) {
            $oUpdated->supplement = $modelEnl->toJson($oPosted->supplement);
        }
        $modelEnl->update('xxt_enroll_record', $oUpdated, ['enroll_key' => $ek]);

        /* 记录登记数据 */
        if (isset($oPosted->data)) {
            $score = isset($oPosted->quizScore) ? $oPosted->quizScore : null;
            $userSite = $this->model('site\fe\way')->who($oApp->siteid);
            $modelRec->setData($userSite, $oApp, $ek, $oPosted->data, '', false, $score);
        } else if (isset($oPosted->quizScore)) {
            /* 只修改登记项的分值 */
            $oAfterScore = new \stdClass;
            $oAfterScore->sum = 0;
            $oBeforeScore = $modelRec->query_val_ss(['score', 'xxt_enroll_record', ['aid' => $app, 'enroll_key' => $ek, 'state' => 1]]);
            $oBeforeScore = empty($oBeforeScore) ? new \stdClass : json_decode($oBeforeScore);
            foreach ($oApp->dataSchemas as $schema) {
                if (empty($schema->requireScore) || $schema->requireScore !== 'Y') {
                    continue;
                }
                //主观题评分
                if (in_array($schema->type, ['single', 'multiple'])) {
                    $oAfterScore->{$schema->id} = isset($oBeforeScore->{$schema->id}) ? $oBeforeScore->{$schema->id} : 0;
                } else {
                    if (isset($oPosted->quizScore->{$schema->id})) {
                        $modelEnl->update('xxt_enroll_record_data', ['score' => $oPosted->quizScore->{$schema->id}], ['enroll_key' => $ek, 'schema_id' => $schema->id, 'state' => 1]);
                        $oAfterScore->{$schema->id} = $oPosted->quizScore->{$schema->id};
                    } else {
                        $oAfterScore->{$schema->id} = 0;
                    }
                }
                $oAfterScore->sum += (int) $oAfterScore->{$schema->id};
            }
            $newScore = $modelRec->toJson($oAfterScore);
            //更新record表
            $modelRec->update('xxt_enroll_record', ['score' => $newScore], ['aid' => $app, 'enroll_key' => $ek, 'state' => 1]);
        }
        //数值型填空题
        if (isset($oPosted->score)) {
            $dataSchemas = $oApp->dataSchemas;
            $modelUsr = $this->model('matter\enroll\user');
            $modelUsr->setOnlyWriteDbConn(true);
            $d['sum'] = 0;
            foreach ($dataSchemas as &$schema) {
                if (isset($oPosted->score->{$schema->id})) {
                    $d[$schema->id] = $oPosted->score->{$schema->id};
                    $modelUsr->update('xxt_enroll_record_data', ['score' => $oPosted->score->{$schema->id}], ['enroll_key' => $ek, 'schema_id' => $schema->id, 'state' => 1]);
                    $d['sum'] += $d[$schema->id];
                }
            }
            $newScore = $modelRec->toJson($d);
            //更新record表
            $modelRec->update('xxt_enroll_record', ['score' => $newScore], ['aid' => $app, 'enroll_key' => $ek, 'state' => 1]);
            //更新enroll_user表
            $result = $modelRec->byId($ek);
            if (isset($result->score->sum)) {
                $upData['score'] = $result->score->sum;
            }
            $modelUsr->update(
                'xxt_enroll_user',
                $upData,
                ['siteid' => $oApp->siteid, 'aid' => $result->aid, 'rid' => $result->rid, 'userid' => $result->userid]
            );
            /* 更新用户获得的分数 */
            $users = $modelUsr->query_objs_ss([
                'id,score',
                'xxt_enroll_user',
                "siteid='$oApp->siteid' and aid='$result->aid' and userid='$result->userid' and rid !='ALL'",
            ]);
            $total = 0;
            foreach ($users as $v) {
                if (!empty($v->score)) {
                    $total += (float) $v->score;
                }
            }
            $upDataALL['score'] = $total;
            $modelUsr->update(
                'xxt_enroll_user',
                $upDataALL,
                ['siteid' => $oApp->siteid, 'aid' => $result->aid, 'rid' => 'ALL', 'userid' => $result->userid]
            );
        }

        /* 更新登记项数据的轮次 */
        if (isset($oPosted->rid)) {
            $modelEnl->update('xxt_enroll_record_data', ['rid' => $modelEnl->escape($oPosted->rid)], ['enroll_key' => $ek, 'state' => 1]);
        }
        if (isset($oUpdated->verified) && $oUpdated->verified === 'Y') {
            $this->_whenVerifyRecord($oApp, $ek);
        }

        /* 返回完整的记录 */
        $oNewRecord = $modelRec->byId($ek, ['verbose' => 'Y']);

        /* 记录操作日志 */
        $oOperation = new \stdClass;
        $oOperation->enroll_key = $ek;
        isset($oPosted->data) && $oOperation->data = $oPosted->data;
        isset($oPosted->quizScore) && $oOperation->quizScore = $oPosted->quizScore;
        isset($oPosted->score) && $oOperation->score = $oPosted->score;
        isset($oPosted->tags) && $oOperation->tags = $oPosted->tags;
        isset($oPosted->comment) && $oOperation->comment = $oPosted->comment;
        $oOperation->rid = $oNewRecord->rid;
        isset($oNewRecord->round) && $oOperation->round = $oNewRecord->round;
        $this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'updateData', $oOperation);

        return new \ResponseData($oNewRecord);
    }
    /**
     * 根据reocrd_data中的数据，修复record中的data字段
     */
    public function repair_action($ek) {
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
     * 删除一条记录
     */
    public function remove_action($app, $ek) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }
        $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }
        $modelEnlRec = $this->model('matter\enroll\record');
        $oRecord = $modelEnlRec->byId($ek, ['fields' => 'userid,state,enroll_key,data,rid']);
        if (false === $oRecord || $oRecord->state !== '1') {
            return new \ObjectNotFoundError();
        }
        // 如果已经获得积分不允许删除
        if (!empty($oRecord->userid)) {
            $modelEnlUsr = $this->model('matter\enroll\user');
            $oEnlUsrRnd = $modelEnlUsr->byId($oApp, $oRecord->userid, ['fields' => 'user_total_coin', 'rid' => $oRecord->rid]);
            if ($oEnlUsrRnd && $oEnlUsrRnd->user_total_coin > 0) {
                return new \ResponseError('提交的记录已经获得活动积分，不能删除');
            }
        }
        // 删除数据
        $rst = $modelEnlRec->remove($oApp, $oRecord);

        // 记录操作日志
        unset($oRecord->userid);
        unset($oRecord->state);
        $this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'removeData', $oRecord);

        return new \ResponseData($rst);
    }
    /**
     * 恢复一条记录
     */
    public function recover_action($app, $ek) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }
        $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }
        $modelEnlRec = $this->model('matter\enroll\record');
        $oRecord = $modelEnlRec->byId($ek, ['fields' => 'userid,enroll_key,data,rid']);
        if (false === $oRecord) {
            return new ObjectNotFoundError();
        }

        $rst = $modelEnlRec->restore($oApp, $oRecord);

        // 记录操作日志
        $this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'restoreData', $oRecord);

        return new \ResponseData($rst);
    }
    /**
     * 清空活动中的所有记录
     */
    public function empty_action($app) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }
        $app = $this->escape($app);
        $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $modelRec = $this->model('matter\enroll\record');
        /* 清除填写记录 */
        $rst = $modelRec->clean($oApp);

        // 记录操作日志
        $this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'empty');

        return new \ResponseData($rst);
    }
    /**
     * 所有记录通过审核
     */
    public function verifyAll_action($app) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);

        $rst = $this->model()->update(
            'xxt_enroll_record',
            ['verified' => 'Y'],
            ['aid' => $oApp->id]
        );

        // 记录操作日志
        $this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'verify.all');

        return new \ResponseData($rst);
    }
    /**
     * 指定记录通过审核
     */
    public function batchVerify_action($app, $all = 'N') {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }
        $modelRun = $this->model('matter\enroll\round');
        if ($activeRound = $modelRun->getActive($oApp)) {
            $rid = $activeRound->rid;
        }

        if ($all === 'Y') {
            $modelApp->update(
                'xxt_enroll_record',
                ['verified' => 'Y'],
                ['aid' => $oApp->id]
            );
            // 记录操作日志
            $operationData = new \stdClass;
            if (isset($rid)) {
                $operationData->rid = $rid;
            }
            $this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'verify.all', $operationData);
        } else {

            $posted = $this->getPostJson();
            $eks = $posted->eks;

            $model = $this->model();
            foreach ($eks as $ek) {
                $modelApp->update(
                    'xxt_enroll_record',
                    ['verified' => 'Y'],
                    ['enroll_key' => $ek]
                );
                // 进行后续处理
                $this->_whenVerifyRecord($oApp, $ek);
            }
            // 记录操作日志
            $operationData = new \stdClass;
            $operationData->data = $eks;
            if (isset($rid)) {
                $operationData->rid = $rid;
            }
            $this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'verify.batch', $operationData);
        }

        return new \ResponseData('ok');
    }
    /**
     * 验证通过时，如果填写记录有对应的签到记录，且签到记录没有验证通过，那么验证通过
     */
    private function _whenVerifyRecord($oApp, $enrollKey) {
        if ($oApp->mission_id) {
            $modelSigninRec = $this->model('matter\signin\record');
            $q = [
                'id',
                'xxt_signin',
                ['entry_rule' => (object) ['op' => 'like', 'pat' => '%' . $oApp->id . '%']],
            ];
            $signinApps = $modelSigninRec->query_objs_ss($q);
            if (count($signinApps)) {
                $enrollRecord = $this->model('matter\enroll\record')->byId(
                    $enrollKey, ['fields' => 'userid,data', 'cascaded' => 'N']
                );
                if (!empty($enrollRecord->data)) {
                    foreach ($signinApps as $signinApp) {
                        // 更新对应的签到记录，如果签到记录已经审核通过就不更新
                        $q = [
                            '*',
                            'xxt_signin_record',
                            ['state' => 1, 'verified' => 'N', 'aid' => $signinApp->id, 'verified_enroll_key' => $enrollKey],
                        ];
                        $signinRecords = $modelSigninRec->query_objs_ss($q);
                        if (count($signinRecords)) {
                            foreach ($signinRecords as $signinRecord) {
                                if (empty($signinRecord->data)) {
                                    continue;
                                }
                                $signinData = json_decode($signinRecord->data);
                                if ($signinData === null) {
                                    $signinData = new \stdClass;
                                }
                                foreach ($enrollData as $k => $v) {
                                    $signinData->{$k} = $v;
                                }
                                // 更新数据
                                $modelSigninRec->delete('xxt_signin_record_data', ['enroll_key' => $signinRecord->enroll_key]);
                                foreach ($signinData as $k => $v) {
                                    $ic = [
                                        'aid' => $oApp->id,
                                        'enroll_key' => $signinRecord->enroll_key,
                                        'name' => $k,
                                        'value' => $model->toJson($v),
                                    ];
                                    $modelSigninRec->insert('xxt_signin_record_data', $ic, false);
                                }
                                // 验证通过
                                $modelSigninRec->update(
                                    'xxt_signin_record',
                                    [
                                        'data' => $modelSigninRec->toJson($signinData),
                                    ],
                                    ['enroll_key' => $signinRecord->enroll_key]
                                );
                            }
                        }
                    }
                }
            }
        }

        return false;
    }
    /**
     * 给记录批量添加标签
     */
    public function batchTag_action($site, $app) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $posted = $this->getPostJson();
        $eks = $posted->eks;
        $tags = $posted->tags;

        /**
         * 给记录打标签
         */
        $modelRec = $this->model('matter\enroll\record');
        if (!empty($eks) && !empty($tags)) {
            foreach ($eks as $ek) {
                $record = $modelRec->byId($ek);
                $existent = $record->tags;
                if (empty($existent)) {
                    $aNew = $tags;
                } else {
                    $aExistent = explode(',', $existent);
                    $aNew = array_unique(array_merge($aExistent, $tags));
                }
                $newTags = implode(',', $aNew);
                $modelRec->update('xxt_enroll_record', ['tags' => $newTags], "enroll_key='$ek'");
            }
        }
        /**
         * 给应用打标签
         */
        $this->model('matter\enroll')->updateTags($app, $posted->appTags);

        return new \ResponseData('ok');
    }
    /**
     * 从关联的记录活动中查找匹配的记录
     */
    public function matchEnroll_action($site, $app) {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $modelApp = $this->model('matter\enroll');
        $oEnlApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $oEnlApp || empty($oEnlApp->dynaDataSchemas)) {
            return new \ObjectNotFoundError();
        }

        if (empty($oEnlApp->entryRule->enroll->id)) {
            return new \ParameterError();
        }

        $oEnlRecord = $this->getPostJson();
        // 匹配规则
        $bEmpty = true;
        $oMatchCriteria = new \stdClass;
        foreach ($oEnlApp->dynaDataSchemas as $oSchema) {
            if (isset($oSchema->requireCheck) && $oSchema->requireCheck === 'Y') {
                if (isset($oSchema->fromApp) && $oSchema->fromApp === $oEnlApp->entryRule->enroll->id) {
                    if (!empty($oEnlRecord->{$oSchema->id})) {
                        $oMatchCriteria->{$oSchema->id} = $oEnlRecord->{$oSchema->id};
                        $bEmpty = false;
                    }
                }
            }
        }

        $aResult = [];
        if (!$bEmpty) {
            // 查找匹配的数据
            $matchApp = $modelApp->byId($oEnlApp->entryRule->enroll->id, ['cascaded' => 'N']);
            $modelEnlRec = $this->model('matter\enroll\record');
            $matchRecords = $modelEnlRec->byData($matchApp, $oMatchCriteria);
            foreach ($matchRecords as $matchRec) {
                $aResult[] = $matchRec->data;
            }
        }

        return new \ResponseData($aResult);
    }
    /**
     * 从关联的分组活动中查找匹配的记录
     */
    public function matchGroup_action($app) {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $modelApp = $this->model('matter\enroll');
        $oEnlApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $oEnlApp || empty($oEnlApp->dataSchemas)) {
            return new \ObjectNotFoundError();
        }
        if (empty($oEnlApp->entryRule->group->id)) {
            return new \ParameterError('没有关联的分组活动');
        }
        $matchedGroupId = $oEnlApp->entryRule->group->id;

        $oEnlRecord = $this->getPostJson();

        $modelGrpUser = $this->model('matter\group\record');
        $aMatchResult = $modelGrpUser->matchByData($matchedGroupId, $oEnlApp, $oEnlRecord);
        if (false === $aMatchResult[0]) {
            return new \ParameterError($aMatchResult[1]);
        }
        $oMatchedGrpRec = $aMatchResult[1];

        return new \ResponseData($oMatchedGrpRec);
    }
    /**
     * 根据记录的userid更新关联分组活动题目的数据
     */
    private function _syncGroup($oEnlApp, $rid, $overwrite = 'N') {
        if (empty($oEnlApp->entryRule->group->id)) {
            return [false, '当前活动没有关联分组活动'];
        }

        $aAssocGrpSchemas = $this->model('matter\enroll\schema')->getAssocSchemasByGroup($oEnlApp->dynaDataSchemas, $oEnlApp->entryRule->group->id);
        //if (empty($aAssocGrpSchemas)) {
        //    return [false ,'当前活动没有指定和分组活动关联的题目'];
        //}
        $oAssocGrpTeamSchema = $this->model('matter\enroll\schema')->getAssocGroupTeamSchema($oEnlApp);

        $updatedCount = 0;
        $modelRec = $this->model('matter\enroll\record');
        $oResult = $modelRec->byApp($oEnlApp, null, (object) ['record' => (object) ['rid' => $rid]]);
        if (count($oResult->records)) {
            $oGrpApp = (object) ['id' => $oEnlApp->entryRule->group->id];
            $modelGrpUsr = $this->model('matter\group\record');
            $oMocker = new \stdClass;
            foreach ($oResult->records as $oRec) {
                $oUpdatedData = $oRec->data;
                $oGrpUsr = $modelGrpUsr->byUser($oGrpApp, $oRec->userid, ['onlyOne' => true, 'fields' => 'team_id,data']);
                if (false === $oGrpUsr) {
                    continue;
                }
                $bModified = ($oRec->group_id !== $oGrpUsr->team_id);
                foreach ($aAssocGrpSchemas as $oGrpSchema) {
                    $enlVal = $this->getDeepValue($oUpdatedData, $oGrpSchema->id);
                    if ($overwrite === 'N' && !empty($enlVal)) {
                        continue;
                    }
                    if ($this->getDeepValue($oAssocGrpTeamSchema, 'id') === $oGrpSchema->id) {
                        $grpVal = $oGrpUsr->team_id;
                    } else {
                        $grpVal = $this->getDeepValue($oGrpUsr->data, $oGrpSchema->id);
                    }
                    if ($enlVal !== $grpVal) {
                        $this->setDeepValue($oUpdatedData, $oGrpSchema->id, $grpVal);
                        $bModified = true;
                    }
                }
                if (false === $bModified) {
                    continue;
                }
                $oMocker->uid = $oRec->userid;
                $oMocker->group_id = $oGrpUsr->team_id;
                $modelRec->setData($oMocker, $oEnlApp, $oRec->enroll_key, $oUpdatedData);
                $modelRec->update('xxt_enroll_record', ['group_id' => $oMocker->group_id], ['enroll_key' => $oRec->enroll_key]);

                $updatedCount++;
            }
        }

        return [true, $updatedCount];
    }
    /**
     * 根据记录的userid更新关联分组活动题目的数据
     */
    public function syncGroup_action($app, $rid, $overwrite = 'N') {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $modelApp = $this->model('matter\enroll');
        $oEnlApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $oEnlApp || $oEnlApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $aSyncResult = $this->_syncGroup($oEnlApp, $rid, $overwrite);
        if (false === $aSyncResult[0]) {
            return new \ParameterError($aSyncResult[1]);
        }

        return new \ResponseData($aSyncResult[1]);
    }
    /**
     * 根据记录的userid更新关联通信录题目的数据
     */
    private function _syncMschema($oEnlApp, $rid, $overwrite = 'N') {
        if ($this->getDeepValue($oEnlApp->entryRule, 'scope.member') !== 'Y' || !isset($oEnlApp->entryRule->member)) {
            return [false, '当前活动没有关联通信录'];
        }

        $aMsSchemas = [];
        if (!empty($oEnlApp->dynaDataSchemas)) {
            foreach ($oEnlApp->dynaDataSchemas as $oSchema) {
                if (isset($oSchema->mschema_id) && isset($oEnlApp->entryRule->member->{$oSchema->mschema_id})) {
                    $aMsSchemas[] = $oSchema;
                }
            }
        }
        if (empty($aMsSchemas)) {
            return [false, '当前活动没有指定和通信录关联的题目'];
        }

        $updatedCount = 0;
        $modelRec = $this->model('matter\enroll\record');
        $oResult = $modelRec->byApp($oEnlApp, null, (object) ['record' => (object) ['rid' => $rid]]);
        if (count($oResult->records)) {
            $modelMem = $this->model('site\user\member');
            $oMocker = new \stdClass;
            foreach ($oResult->records as $oRec) {
                $oUpdatedData = $oRec->data;
                $aMembers = $modelMem->byUser($oRec->userid, ['schemas' => array_keys((array) $oEnlApp->entryRule->member)]);
                if (count($aMembers) === 0) {
                    continue;
                }
                $oMember = (object) ['member' => $aMembers[0]];
                $bModified = false;
                foreach ($aMsSchemas as $oMsSchema) {
                    $enlVal = $this->getDeepValue($oUpdatedData, $oMsSchema->id);
                    if ($overwrite === 'N' && !empty($enlVal)) {
                        continue;
                    }
                    $memVal = $this->getDeepValue($oMember, $oMsSchema->id);
                    if ($enlVal !== $memVal) {
                        $this->setDeepValue($oUpdatedData, $oMsSchema->id, $memVal);
                        $bModified = true;
                    }
                }
                if (false === $bModified) {
                    continue;
                }
                $oMocker->uid = $oRec->userid;
                $oMocker->group_id = $oRec->group_id;
                $modelRec->setData($oMocker, $oEnlApp, $oRec->enroll_key, $oUpdatedData);
                $updatedCount++;
            }
        }

        return [true, $updatedCount];
    }
    /**
     * 根据记录的userid更新关联通信录题目的数据
     */
    public function syncMschema_action($app, $rid, $overwrite = 'N') {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $modelApp = $this->model('matter\enroll');
        $oEnlApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $oEnlApp || $oEnlApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $aSyncResult = $this->_syncMschema($oEnlApp, $rid, $overwrite);
        if (false === $aSyncResult[0]) {
            return new \ResponseError($aSyncResult[1]);
        }

        return new \ResponseData($aSyncResult[1]);
    }
    /**
     * 从其他的记录活动导入登记数据
     *
     * 导入的数据项定义必须兼容，兼容规则如下
     * 从目标应用中导入和指定应用的数据定义中名称（title）和类型（type）一致的项
     * 如果是单选题、多选题、打分题选项必须一致
     * 如果是打分题，分值设置范围必须一致
     * 项目阶段不支持导入
     *
     * @param string $app app'id
     * @param string $fromApp 目标应用的id
     * @param string $fromRnd 目标轮次的id
     * @param string $append 追加记录，否则清空现有记录
     *
     */
    public function importByOther_action($app, $fromApp, $toRnd, $fromRnd = '', $append = 'Y') {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelApp = $this->model('matter\enroll');
        $modelSch = $this->model('matter\enroll\schema');
        $modelRec = $this->model('matter\enroll\record');

        if (false === ($oApp = $modelApp->byId($app))) {
            return new \ResponseError('指定的活动不存在（1）');
        }
        if (false === ($oFromApp = $modelApp->byId($fromApp))) {
            return new \ResponseError('指定的活动不存在（2）');
        }

        /* 获得兼容的登记项 */
        $compatibleSchemas = $modelSch->compatibleSchemas($oApp->dynaDataSchemas, $oFromApp->dynaDataSchemas);
        if (empty($compatibleSchemas)) {
            return new \ResponseData('没有匹配的数据项');
        }
        /* 获得数据 */
        $oResult = $modelRec->byApp($oFromApp, null, (object) ['record' => (object) ['rid' => $fromRnd]]);
        $countOfImport = 0;
        if ($oResult->total > 0) {
            foreach ($oResult->records as $oRecord) {
                // 新登记
                $oEnrollee = new \stdClass;
                $oEnrollee->uid = $oRecord->userid;
                $oEnrollee->nickname = $oRecord->nickname;
                $aOptions = [];
                $aOptions['enrollAt'] = $oRecord->enroll_at;
                $aOptions['nickname'] = $oRecord->nickname;
                $aOptions['assignedRid'] = $toRnd;
                $oNewRec = $modelRec->enroll($oApp, $oEnrollee, $aOptions);
                // 登记数据
                $oRecData = new \stdClass;
                foreach ($compatibleSchemas as $cs) {
                    if (empty($oRecord->data->{$cs[0]->id})) {
                        continue;
                    }
                    $val = $oRecord->data->{$cs[0]->id};
                    if ($cs[0]->type === 'single') {
                        foreach ($cs[0]->ops as $index => $op) {
                            if ($op->v === $val) {
                                $val = $cs[1]->ops[$index]->v;
                                break;
                            }
                        }
                    } else if ($cs[0]->type === 'multiple') {
                        $val3 = new \stdClass;
                        $val2 = explode(',', $val);
                        foreach ($val2 as $v) {
                            foreach ($cs[0]->ops as $index => $op) {
                                if ($op->v === $v) {
                                    $val3->{$cs[1]->ops[$index]->v} = true;
                                    break;
                                }
                            }
                        }
                        $val = $val3;
                    } else if ($cs[0]->type === 'score') {
                        $val2 = new \stdClass;
                        foreach ($val as $opv => $score) {
                            foreach ($cs[0]->ops as $index => $op) {
                                if ($op->v === $opv) {
                                    $val2->{$cs[1]->ops[$index]->v} = $score;
                                    break;
                                }
                            }
                        }
                        $val = $val2;
                    }
                    $oRecData->{$cs[1]->id} = $val;
                }
                $modelRec->setData($oEnrollee, $oApp, $oNewRec->enroll_key, $oRecData);
                $countOfImport++;
            }
        }

        return new \ResponseData($countOfImport);
    }
    /**
     * 从指定的数据源同步数据
     */
    public function syncWithDataSource_action($app, $round, $step = 1) {
        if (false === ($oOperator = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelApp = $this->model('matter\enroll');
        if (false === ($oApp = $modelApp->byId($app, ['fields' => 'id,siteid,data_schemas,scenario,mission_id,sync_mission_round,round_cron', 'appRid' => $round, 'cascaded' => 'N']))) {
            return new \ObjectNotFoundError();
        }
        if (empty($oApp->dataSchemas)) {
            return new \ObjectNotFoundError();
        }
        $modelRnd = $this->model('matter\enroll\round');
        $oAssignedRnd = $modelRnd->byId($round);
        if (false === $oAssignedRnd) {
            return new \ObjectNotFoundError();
        }

        $fnGetDsSchemaIds = function ($oSchema, $oDsApp, $fnFilter) {
            $dsSchemaIds = [];
            foreach ($oDsApp->dataSchemas as $oDsSchema) {
                if (in_array($oDsSchema->id, $oSchema->ds->schema)) {
                    if ($fnFilter($oDsSchema)) {
                        $dsSchemaIds[] = $oDsSchema->id;
                    }
                }
                if (count($oSchema->ds->schema) === count($dsSchemaIds)) {
                    break;
                }
            }
            return $dsSchemaIds;
        };

        /* 获得源活动中对应的同步轮次 */
        $fnGetDsRids = function ($oDsApp) use ($oAssignedRnd, $modelRnd) {
            // 源活动中，开始时间，停止时间和用途都相同轮次
            $oRndResult = $modelRnd->byApp($oDsApp, ['start_at' => $oAssignedRnd->start_at, 'end_at' => $oAssignedRnd->end_at, 'purpose' => $oAssignedRnd->purpose, 'fields' => 'rid,start_at', 'withoutActive' => 'Y']);
            if (count($oRndResult->rounds) === 1) {
                $rounds = $oRndResult->rounds;
            }
            // 源活动中，根据开始时间，停止时间获得的被汇总轮次
            if ($oAssignedRnd->purpose === 'S') {
                $rounds = $modelRnd->getSummaryInclude($oDsApp, $oAssignedRnd->start_at, $oAssignedRnd->end_at);
            }
            if (empty($rounds)) {
                return false;
            }

            return array_map(function ($oRnd) {return $oRnd->rid;}, $rounds);
        };

        $modelRecDat = $this->model('matter\enroll\data');

        /*需要同步的题目*/
        $dsSchemas = array_filter($oApp->dataSchemas, function ($oSchema) {return !empty($oSchema->ds->app->id) && !empty($oSchema->ds->type) && !empty($oSchema->ds->schema) && is_array($oSchema->ds->schema);});
        if (empty($dsSchemas)) {
            return new \ResponseError('没有需要同步数据的题目');
        }
        $dsSchemas = array_values($dsSchemas);

        $oSyncResult = new \stdClass;
        $oSyncResult->steps = count($dsSchemas) + 1;

        /*一次执行一个题目*/
        $step <= 0 && $step = 1;
        while ($step <= count($dsSchemas)) {
            $oSyncResult->step = (int) $step;
            $oSyncResult->left = count($dsSchemas) - $step + 1;
            $oSyncResult->total = 0;

            $oSchema = $dsSchemas[$step - 1];
            $oDsApp = $modelApp->byId($oSchema->ds->app->id, ['fields' => 'id,data_schemas', 'cascaded' => 'N']);
            if (false === $oDsApp) {
                $step++;
                continue;
            }
            $oDsAssignedRids = $fnGetDsRids($oDsApp);
            if (empty($oDsAssignedRids)) {
                $step++;
                continue;
            }

            $syncRecordNum = 0;
            switch ($oSchema->ds->type) {
            case 'act':
                $syncRecordNum = $this->_syncNumberWithAct($oApp, $oSchema, $oDsApp, $oSchema->ds->schema, $oAssignedRnd, $oDsAssignedRids);
                break;
            case 'input':
                $dsSchemaIds = $fnGetDsSchemaIds($oSchema, $oDsApp, function ($oDsSchema) {return $oDsSchema->type === 'shorttext' && $this->getDeepValue($oDsSchema, 'format') === 'number';});
                if (count($dsSchemaIds)) {
                    $syncRecordNum = $this->_syncNumberWithInput($oApp, $oSchema, $oDsApp, $dsSchemaIds, $oAssignedRnd, $oDsAssignedRids);
                }
                break;
            case 'score':
                $dsSchemaIds = $fnGetDsSchemaIds($oSchema, $oDsApp, function ($oDsSchema) {return $this->getDeepValue($oDsSchema, 'requireScore') === 'Y';});
                if (count($dsSchemaIds)) {
                    $syncRecordNum = $this->_syncNumberWithScore($oApp, $oSchema, $oDsApp, $dsSchemaIds, $oAssignedRnd, $oDsAssignedRids);
                }
                break;
            case 'score_rank':
                $dsSchemaIds = $fnGetDsSchemaIds($oSchema, $oDsApp, function ($oDsSchema) {return $this->getDeepValue($oDsSchema, 'requireScore') === 'Y';});
                if (count($dsSchemaIds)) {
                    $syncRecordNum = $this->_syncNumberWithScoreRank($oApp, $oSchema, $oDsApp, $dsSchemaIds, $oAssignedRnd, $oDsAssignedRids);
                }
                break;
            case 'option':
                // $dsSchemas = [];
                // foreach ($oDsApp->dataSchemas as $oDsSchema) {
                //     if (in_array($oDsSchema->id, $oSchema->ds->schema)) {
                //         if (in_array($oDsSchema->type, ['single', 'multiple']) && !empty($oDsSchema->dsOps->app->id)) {
                //             /* 设置动态选项 */
                //             if (!isset($modelSch)) {
                //                 $modelSch = $this->model('matter\enroll\schema');
                //             }
                //             $modelSch->setDynaOptions($oDsApp, isset($oDsAssignedRids) ? $oDsAssignedRids : null);
                //             if (!empty($oDsSchema->ops)) {
                //                 $dsSchemas[] = $oDsSchema;
                //             }
                //         }
                //     }
                //     if (count($oSchema->ds->schema) === count($dsSchemas)) {
                //         break;
                //     }
                // }
                // if (count($dsSchemas)) {
                //     $this->_syncNumberWithOption($oApp, $oSchema, $oDsApp, $dsSchemas, $oDsAssignedRids);
                // }
                break;
            }
            /* 更新结果记录 */
            $oSyncResult->{$oSchema->id} = $syncRecordNum;
            $oSyncResult->total += $syncRecordNum;
            /* 计算得分的排名 */
            if ($this->getDeepValue($oSchema, 'requireScore') === 'Y') {
                $modelRecDat->setScoreRank($oApp, $oSchema, $oAssignedRnd->rid);
            }

            return new \ResponseData($oSyncResult);
        }

        /* 更新用户数据 */
        $modelUsr = $this->model('matter\enroll\user');
        $modelUsr->renew($oApp, $oAssignedRnd->rid);
        $oSyncResult->done = 'Y';

        return new \ResponseData($oSyncResult);
    }
    /**
     * 从题目指定的数据源同步数据
     * 从选择题数据源同步选项的选择数量数据
     */
    private function _syncNumberWithOption($oApp, $oSchema, $oDsApp, $dsSchemas, $oDsAssignedRids) {
        $modelRec = $this->model('matter\enroll\record');
        $aDsOpDataByUser = [];
        foreach ($dsSchemas as $oDsSchema) {
            foreach ($oDsSchema->ops as $oDsOp) {
                /* 获得数据源的值 */
                if (!empty($oDsOp->ds->user)) {
                    if ('single' === $oDsSchema->type) {
                        $q = [
                            'count(*)',
                            'xxt_enroll_record_data',
                            "aid='{$oDsApp->id}' and state=1 and schema_id='{$oDsSchema->id}' and value='{$oDsOp->v}'",
                        ];
                    } else {
                        $q = [
                            'count(*)',
                            'xxt_enroll_record_data',
                            "aid='{$oDsApp->id}' and state=1 and schema_id='{$oDsSchema->id}' and FIND_IN_SET('{$oDsOp->v}', value)",
                        ];
                    }
                    $q[2] .= " and rid in('" . implode("'", $oDsAssignedRids) . "')";

                    $count = (int) $modelRec->query_val_ss($q);
                    if (isset($aDsOpDataByUser[$oDsOp->ds->user])) {
                        $aDsOpDataByUser[$oDsOp->ds->user] += $count;
                    } else {
                        $aDsOpDataByUser[$oDsOp->ds->user] = $count;
                    }
                }
            }
        }
        /* 更新获得记录的数值 */
        $q = [
            'id,enroll_key,data,userid,group_id',
            'xxt_enroll_record',
            ['aid' => $oApp->id, 'state' => 1],
        ];
        /* 限制汇总数据的轮次 */
        if (!empty($oAssignedRnd->rid)) {
            $q[2]['rid'] = $oAssignedRnd;
        }
        $oUserRecords = $modelRec->query_objs_ss($q);
        if (!empty($oUserRecords)) {
            $oRecUser = new \stdClass;
            foreach ($oUserRecords as $oUserRec) {
                $oRecUser->uid = $oUserRec->userid;
                $oRecUser->group_id = $oUserRec->group_id;
                $oRecData = empty($oUserRec->data) ? new \stdClass : json_decode($oUserRec->data);
                if (isset($aDsOpDataByUser[$oUserRec->userid])) {
                    $oRecData->{$oSchema->id} = (string) $aDsOpDataByUser[$oUserRec->userid];
                } else {
                    unset($oRecData->{$oSchema->id});
                }
                $modelRec->setData($oRecUser, $oApp, $oUserRec->enroll_key, $oRecData);
            }
        }

        return count($oUserRecords);
    }
    /**
     * 从题目指定的数据源中同步题目
     * 用户输入数据
     */
    private function _syncNumberWithInput($oApp, $oSchema, $oDsApp, $dsSchemaIds, $oAssignedRnd, $oDsAssignedRids) {
        $modelRec = $this->model('matter\enroll\record');
        $q = [
            'id,enroll_key,data,userid,group_id',
            'xxt_enroll_record',
            ['aid' => $oApp->id, 'state' => 1],
        ];
        /* 限制汇总数据的轮次 */
        if (!empty($oAssignedRnd->rid)) {
            $q[2]['rid'] = $oAssignedRnd->rid;
        }

        $oUserRecords = $modelRec->query_objs_ss($q);
        if (!empty($oUserRecords)) {
            $oRecUser = new \stdClass;
            $q = [
                'sum(value)',
                'xxt_enroll_record_data',
                ['aid' => $oDsApp->id, 'state' => 1, 'schema_id' => $dsSchemaIds, 'rid' => $oDsAssignedRids],
            ];
            foreach ($oUserRecords as $oUserRec) {
                $oRecUser->uid = $oUserRec->userid;
                $oRecUser->group_id = $oUserRec->group_id;
                $oRecData = empty($oUserRec->data) ? new \stdClass : json_decode($oUserRec->data);
                $q[2]['userid'] = $oUserRec->userid;
                $sum = $modelRec->query_val_ss($q);
                $oRecData->{$oSchema->id} = $sum;
                $modelRec->setData($oRecUser, $oApp, $oUserRec->enroll_key, $oRecData);
            }
        }

        return count($oUserRecords);
    }
    /**
     * 从题目指定的数据源中同步题目
     * 题目的数据
     */
    private function _syncNumberWithScore($oApp, $oSchema, $oDsApp, $dsSchemaIds, $oAssignedRnd, $oDsAssignedRids) {
        $modelRec = $this->model('matter\enroll\record');
        $q = [
            'id,enroll_key,data,userid,group_id',
            'xxt_enroll_record',
            ['aid' => $oApp->id, 'state' => 1],
        ];
        /* 限制汇总数据的轮次 */
        if (!empty($oAssignedRnd->rid)) {
            $q[2]['rid'] = $oAssignedRnd->rid;
        }

        $userRecords = $modelRec->query_objs_ss($q);
        if (count($userRecords)) {
            $q = [
                'sum(score)',
                'xxt_enroll_record_data',
                ['aid' => $oDsApp->id, 'state' => 1, 'schema_id' => $dsSchemaIds, 'rid' => $oDsAssignedRids],
            ];
            $oRecUser = new \stdClass;
            foreach ($userRecords as $oUserRec) {
                $oRecUser->uid = $oUserRec->userid;
                $oRecUser->group_id = $oUserRec->group_id;
                $oRecData = empty($oUserRec->data) ? new \stdClass : json_decode($oUserRec->data);
                $q[2]['userid'] = $oUserRec->userid;
                $score = $modelRec->query_val_ss($q);
                $oRecData->{$oSchema->id} = $score;
                $modelRec->setData($oRecUser, $oApp, $oUserRec->enroll_key, $oRecData);
            }
        }

        return count($userRecords);
    }
    /**
     * 从题目指定的数据源中同步题目
     * 题目的数据
     */
    private function _syncNumberWithScoreRank($oApp, $oSchema, $oDsApp, $dsSchemaIds, $oAssignedRnd, $oDsAssignedRids) {
        $modelRec = $this->model('matter\enroll\record');
        $q = [
            'id,enroll_key,data,userid,group_id',
            'xxt_enroll_record',
            ['aid' => $oApp->id, 'state' => 1],
        ];
        /* 需要进行同步的记录 */
        if (!empty($oAssignedRnd->rid)) {
            $q[2]['rid'] = $oAssignedRnd->rid;
        }

        $userRecords = $modelRec->query_objs_ss($q);
        if (count($userRecords)) {
            $q = [
                'sum(score_rank)',
                'xxt_enroll_record_data',
                ['aid' => $oDsApp->id, 'state' => 1, 'schema_id' => $dsSchemaIds, 'rid' => $oDsAssignedRids],
            ];
            $oRecUser = new \stdClass;
            foreach ($userRecords as $oUserRec) {
                $oRecUser->uid = $oUserRec->userid;
                $oRecUser->group_id = $oUserRec->group_id;
                $oRecData = empty($oUserRec->data) ? new \stdClass : json_decode($oUserRec->data);
                $q[2]['userid'] = $oUserRec->userid;
                $score = $modelRec->query_val_ss($q);
                $oRecData->{$oSchema->id} = $score;
                $modelRec->setData($oRecUser, $oApp, $oUserRec->enroll_key, $oRecData);
            }
        }

        return count($userRecords);
    }
    /**
     * 从题目指定的数据源中同步题目
     * 用户行为数据
     */
    private function _syncNumberWithAct($oApp, $oSchema, $oDsApp, $actNames, $oAssignedRnd, $oDsAssignedRids) {
        $modelRec = $this->model('matter\enroll\record');
        $q = [
            'id,enroll_key,data,userid,group_id',
            'xxt_enroll_record',
            ['aid' => $oApp->id, 'state' => 1],
        ];
        /* 限制汇总数据的轮次 */
        if (!empty($oAssignedRnd->rid)) {
            $q[2]['rid'] = $oAssignedRnd->rid;
        }
        $oUserRecords = $modelRec->query_objs_ss($q);
        if (count($oUserRecords)) {
            $q = ['', 'xxt_enroll_user', ['aid' => $oDsApp->id, 'state' => 1, 'rid' => $oDsAssignedRids]];

            $oRecUser = new \stdClass;
            foreach ($oUserRecords as $oUserRec) {
                $oRecUser->uid = $oUserRec->userid;
                $oRecUser->group_id = $oUserRec->group_id;
                $oRecData = empty($oUserRec->data) ? new \stdClass : json_decode($oUserRec->data);
                $q[2]['userid'] = $oUserRec->userid;
                $number = 0;
                foreach ($actNames as $actName) {
                    $q[0] = $actName;
                    $number += (int) $modelRec->query_val_ss($q);
                }
                $oRecData->{$oSchema->id} = $number;
                $modelRec->setData($oRecUser, $oApp, $oUserRec->enroll_key, $oRecData);
            }
        }

        return count($oUserRecords);
    }
    /**
     * 从活动所属项目同步用户记录
     */
    public function syncMissionUser_action($app, $round) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['fields' => 'id,siteid,entry_rule,data_schemas,scenario,mission_id,sync_mission_round,round_cron', 'cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }
        if ('mis_user_score' !== $oApp->scenario) {
            return new \ParameterError('活动类型不正确，无法执行次操作');
        }
        $modelMis = $this->model('matter\mission');
        $oMission = $modelMis->byId($oApp->mission_id);
        if (false === $oMission) {
            return new \ObjectNotFoundError();
        }

        $modelRnd = $this->model('matter\enroll\round');
        $oAssignedRnd = $modelRnd->byId($round);
        if (false === $oAssignedRnd) {
            return new \ObjectNotFoundError();
        }

        /* 获得项目用户 */
        if (isset($oMission->user_app_id) && isset($oMission->user_app_type)) {
            $oUserSource = new \stdClass;
            $oUserSource->id = $oMission->user_app_id;
            $oUserSource->type = $oMission->user_app_type;
            switch ($oUserSource->type) {
            case 'group':
                $oGrpApp = $this->model('matter\group')->byId($oUserSource->id, ['fields' => 'assigned_nickname', 'cascaded' => 'N']);
                $oResult = $this->model('matter\group\record')->byApp($oUserSource, (object) ['fields' => 'userid,nickname']);
                $misUsers = isset($oResult->users) ? $oResult->users : [];
                break;
            case 'enroll':
                $misUsers = $this->model('matter\enroll\user')->enrolleeByApp($oUserSource, '', '', ['fields' => 'userid,nickname', 'cascaded' => 'N']);
                break;
            case 'signin':
                $misUsers = $this->model('matter\signin\record')->enrolleeByApp($oUserSource, ['fields' => 'distinct userid,nickname']);
                break;
            case 'mschema':
                $misUsers = $this->model('site\user\member')->byMschema($oUserSource->id, ['fields' => 'userid,name nickname']);
                break;
            }
        }
        if (empty($misUsers)) {
            return new \ParameterError('项目用户数据为空');
        }

        $newRecordCount = 0; // 新生成的记录数
        $modelRec = $this->model('matter\enroll\record');
        foreach ($misUsers as $oMisUser) {
            if (empty($oMisUser->userid)) {
                continue;
            }
            $oMockUser = new \stdClass;
            $oMockUser->uid = $oMisUser->userid;
            $records = $modelRec->byUser($oApp, $oMockUser, ['rid' => $oAssignedRnd->rid]);
            if (empty($records)) {
                $oMockUser->nickname = $oMisUser->nickname;
                $oNewRec = $modelRec->enroll($oApp, $oMockUser, ['nickname' => $oMockUser->nickname, 'assignedRid' => $oAssignedRnd->rid]);
                $this->model('matter\enroll\event')->submitRecord($oApp, $oNewRec, $oMockUser, true);
                $newRecordCount++;
            }
        }

        /* 更新用户分组数据 */
        $this->_syncGroup($oApp, $oAssignedRnd->rid);

        /* 更新通信录数据 */
        $this->_syncGroup($oApp, $oAssignedRnd->rid);

        /* 更新用户分组数据 */
        $this->model('matter\enroll\user')->repairGroup($oApp);

        return new \ResponseData($newRecordCount);
    }
    /**
     * 填写记录导出
     */
    public function export_action($app, $filter = '') {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $oApp = $this->model('matter\enroll')->byId($app, ['fields' => 'siteid,id,state,title,data_schemas,entry_rule,assigned_nickname,scenario,mission_id,sync_mission_round,round_cron', 'cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            die('访问的对象不存在或不可用');
        }
        $schemas = $oApp->dynaDataSchemas;

        $modelSch = $this->model('matter\enroll\schema');
        // 加入关联活动的题目
        $modelSch->getUnionSchemas($oApp, $schemas);
        // 关联的分组题目
        $oAssocGrpTeamSchema = $modelSch->getAssocGroupTeamSchema($oApp);

        /* 获得所有有效的填写记录 */
        $modelRec = $this->model('matter\enroll\record');

        // 筛选条件
        $filter = $modelRec->unescape($filter);
        $oCriteria = empty($filter) ? new \stdClass : json_decode($filter);
        $rid = empty($oCriteria->record->rid) ? '' : $oCriteria->record->rid;
        if (!empty($oCriteria->record->group_id)) {
            $gid = $oCriteria->record->group_id;
        } else if (!empty($oAssocGrpTeamSchema) && !empty($oCriteria->data->{$oAssocGrpTeamSchema->id})) {
            $gid = $oCriteria->data->{$oAssocGrpTeamSchema->id};
        } else {
            $gid = '';
        }

        $oResult = $modelRec->byApp($oApp, null, $oCriteria);
        if ($oResult->total === 0) {
            die('导出数据为空');
        }

        $records = $oResult->records;
        require_once TMS_APP_DIR . '/lib/PHPExcel.php';

        // Create new PHPExcel object
        $objPHPExcel = new \PHPExcel();
        // Set properties
        $objPHPExcel->getProperties()->setCreator(APP_TITLE)
            ->setLastModifiedBy(APP_TITLE)
            ->setTitle($oApp->title)
            ->setSubject($oApp->title)
            ->setDescription($oApp->title);

        $objActiveSheet = $objPHPExcel->getActiveSheet();
        $columnNum1 = 0; //列号
        $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '填写时间');
        $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '审核通过');
        $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '填写轮次');

        // 转换标题
        $aNumberSum = []; // 数值型题目的合计
        $aScoreSum = []; // 题目的分数合计
        $columnNum4 = $columnNum1; //列号
        $bRequireNickname = true;
        if ($this->getDeepValue($oApp, 'assignedNickname.valid') === 'Y' || isset($oApp->assignedNickname->schema->id)) {
            $bRequireNickname = false;
        }
        $bRequireSum = false; // 是否需要计算合计
        $bRequireScore = false; // 是否需要计算总分
        for ($a = 0, $ii = count($schemas); $a < $ii; $a++) {
            $oSchema = $schemas[$a];
            /* 跳过图片,描述说明和文件 */
            if (in_array($oSchema->type, ['html'])) {
                continue;
            }
            if ($oSchema->type === 'shorttext') {
                /* 数值型，需要计算合计 */
                if (isset($oSchema->format) && $oSchema->format === 'number') {
                    $aNumberSum[$columnNum4] = $oSchema->id;
                    $bRequireSum = true;
                }
                $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $oSchema->title);
            } else if ($oSchema->type === 'score') {
                /* 打分题，需要计算合计 */
                $aNumberSum[$columnNum4] = $oSchema->id;
                $bRequireSum = true;
                $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $oSchema->title);
                if (!empty($oSchema->ops)) {
                    foreach ($oSchema->ops as $op) {
                        $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $op->l);
                    }
                }
            } else {
                $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $oSchema->title);
            }
            /* 需要补充说明 */
            if ($this->getDeepValue($oSchema, 'supplement') === 'Y') {
                $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '补充说明');
            }
            /* 需要计算得分 */
            if ($this->getDeepValue($oSchema, 'requireScore') === 'Y') {
                $aScoreSum[$columnNum4] = $oSchema->id;
                $bRequireScore = true;
                $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '得分');
            }
        }
        if ($bRequireNickname) {
            $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '昵称');
        }
        if (null === $oAssocGrpTeamSchema) {
            $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '分组');
        }
        $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '备注');
        // 记录分数
        if ($oApp->scenario === 'voting') {
            $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分数');
            $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '平均分数');
            $titles[] = '总分数';
            $titles[] = '平均分数';
        }
        if ($bRequireScore) {
            $aScoreSum[$columnNum4] = 'sum';
            $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分');
            $titles[] = '总分';
        }
        // 转换数据
        for ($j = 0, $jj = count($records); $j < $jj; $j++) {
            $oRecord = $records[$j];
            $rowIndex = $j + 2;
            $recColNum = 0; // 记录列号
            $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, date('y-m-j H:i', $oRecord->enroll_at));
            $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->verified);
            // 轮次名
            if (isset($oRecord->round)) {
                $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->round->title);
            }
            // 处理登记项
            $oRecData = $oRecord->data;
            $oRecScore = empty($oRecord->score) ? new \stdClass : $oRecord->score;
            $oRecSupplement = $oRecord->supplement;
            $oVerbose = isset($oRecord->verbose) ? $oRecord->verbose->data : false;
            for ($i2 = 0, $ii = count($schemas); $i2 < $ii; $i2++) {
                $oSchema = $schemas[$i2];
                if (in_array($oSchema->type, ['html'])) {
                    continue;
                }
                $v = $modelRec->getDeepValue($oRecData, $oSchema->id, '');
                switch ($oSchema->type) {
                case 'single':
                    $cellValue = '';
                    if (!empty($oSchema->ops)) {
                        foreach ($oSchema->ops as $op) {
                            if ($op->v === $v) {
                                $cellValue = $op->l;
                            }
                        }
                    }
                    $cellValue = $this->replaceHTMLTags($cellValue, "\n");
                    $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $cellValue, \PHPExcel_Cell_DataType::TYPE_STRING);
                    $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
                    break;
                case 'multiple':
                    $labels = [];
                    if (!empty($oSchema->ops)) {
                        $v = explode(',', $v);
                        foreach ($v as $oneV) {
                            foreach ($oSchema->ops as $op) {
                                if ($op->v === $oneV) {
                                    $labels[] = $op->l;
                                    break;
                                }
                            }
                        }
                    }
                    $cellValue = implode(',', $labels);
                    $cellValue = $this->replaceHTMLTags($cellValue, "\n");
                    $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $cellValue);
                    $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
                    break;
                case 'score':
                    $recColNum2 = $recColNum;
                    $labelsSum = 0;
                    if (!empty($oSchema->ops)) {
                        for ($opi = 0; $opi < count($oSchema->ops); $opi++) {
                            $op = $oSchema->ops[$opi];
                            if (isset($v->{$op->v})) {
                                $labelsSum += $v->{$op->v};
                                $objActiveSheet->setCellValueByColumnAndRow($recColNum2 + $opi + 1, $rowIndex, $v->{$op->v});
                            } else {
                                $objActiveSheet->setCellValueByColumnAndRow($recColNum2 + $opi + 1, $rowIndex, '');
                            }
                            $recColNum++;
                        }
                    }
                    $objActiveSheet->setCellValueByColumnAndRow($recColNum2, $rowIndex, $labelsSum);
                    $recColNum++;
                    break;
                case 'image':
                    $v0 = '';
                    $v0 = $this->replaceHTMLTags($v0, "\n");
                    $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
                    $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
                    break;
                case 'file':
                    $v0 = '';
                    $v0 = $this->replaceHTMLTags($v0, "\n");
                    $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
                    $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
                    break;
                case 'date':
                    $v = (!empty($v) && is_numeric($v)) ? date('y-m-j H:i', $v) : '';
                    $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
                    break;
                case 'shorttext':
                    if (isset($oSchema->format) && $oSchema->format === 'number') {
                        $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    } else {
                        $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    break;
                case 'multitext':
                    if (is_array($v)) {
                        $values = [];
                        foreach ($v as $val) {
                            $values[] = strip_tags($val->value);
                        }
                        $v = implode("\n", $values);
                    }
                    if (is_string($v)) {
                        $v = str_replace(['&nbsp;', '&amp;'], [' ', '&'], $v);
                    } else {
                        $v = '';
                    }
                    $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
                    $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
                    break;
                case 'url':
                    $v0 = '';
                    !empty($v->title) && $v0 .= '【' . $v->title . '】';
                    !empty($v->description) && $v0 .= $v->description;
                    !empty($v->url) && $v0 .= $v->url;
                    $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
                    break;
                default:
                    $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
                    break;
                }
                // 补充说明
                if ($this->getDeepValue($oSchema, 'supplement') === 'Y') {
                    $supplement = $this->getDeepValue($oRecSupplement, $oSchema->id, '');
                    $supplement = preg_replace('/<(style|script|iframe)[^>]*?>[\s\S]+?<\/\1\s*>/i', '', $supplement);
                    $supplement = preg_replace('/<[^>]+?>/', '', $supplement);
                    $supplement = preg_replace('/\s+/', '', $supplement);
                    $supplement = preg_replace('/>/', '', $supplement);
                    $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $supplement, \PHPExcel_Cell_DataType::TYPE_STRING);
                }
                // 分数
                if ($this->getDeepValue($oSchema, 'requireScore') === 'Y') {
                    $cellScore = $this->getDeepValue($oRecScore, $oSchema->id, 0);
                    $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $cellScore, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                }
            }
            // 昵称
            if ($bRequireNickname) {
                $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->nickname);
            }
            // 分组
            if (null === $oAssocGrpTeamSchema) {
                $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, isset($oRecord->group->title) ? $oRecord->group->title : '');
            }
            // 备注
            $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->comment);
            // 记录投票分数
            if ($oApp->scenario === 'voting') {
                $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->_score);
                $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, sprintf('%.2f', $oRecord->_average));
            }
            // 记录测验分数
            if ($bRequireScore) {
                $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, isset($oRecScore->sum) ? $oRecScore->sum : '');
            }
        }
        if (!empty($aNumberSum)) {
            // 数值型合计
            $rowIndex = count($records) + 2;
            $oSum4Schema = $modelRec->sum4Schema($oApp, $rid, $gid);
            $objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
            foreach ($aNumberSum as $key => $val) {
                $objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, $oSum4Schema->$val);
            }
        }
        if (!empty($aScoreSum)) {
            // 分数合计
            $rowIndex = count($records) + 2;
            $oScore4Schema = $modelRec->score4Schema($oApp, $rid, $gid);
            $objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
            foreach ($aScoreSum as $key => $val) {
                $objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, isset($oScore4Schema->$val) ? $oScore4Schema->$val : '');
            }
        }
        // 输出
        header('Content-Type: application/vnd.ms-excel');
        header('Cache-Control: max-age=0');
        $filename = $oApp->title . '.xlsx';
        \TMS_App::setContentDisposition($filename);

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
    /**
     * 导出记录中的图片
     */
    public function exportImage_action($site, $app) {
        if (false === ($oUser = $this->accountUser())) {
            die('请先登录系统');
        }
        if (defined('SAE_TMP_PATH')) {
            die('部署环境不支持该功能');
        }

        $oNameSchema = null;
        $imageSchemas = [];

        // 记录活动
        $oApp = $this->model('matter\enroll')->byId($app, ['fields' => 'id,title,data_schemas,scenario,sync_mission_round', 'cascaded' => 'N']);
        $schemas = $oApp->dynaDataSchemas;
        $modelSch = $this->model('matter\enroll\schema');
        // 加入关联活动的题目
        $modelSch->getUnionSchemas($oApp, $schemas);

        foreach ($schemas as $schema) {
            if ($schema->type === 'image') {
                $imageSchemas[] = $schema;
            } else if ($schema->id === 'name' || (in_array($schema->title, array('姓名', '名称')))) {
                $oNameSchema = $schema;
            }
        }

        if (count($imageSchemas) === 0) {
            die('活动不包含图片数据');
        }

        // 获得所有有效的填写记录
        $records = $this->model('matter\enroll\record')->byApp($oApp);
        if ($records->total === 0) {
            die('record empty');
        }
        $records = $records->records;

        // 转换数据
        $aImages = [];
        for ($j = 0, $jj = count($records); $j < $jj; $j++) {
            $record = $records[$j];
            // 处理登记项
            $oRecData = $record->data;
            for ($i = 0, $ii = count($imageSchemas); $i < $ii; $i++) {
                $schema = $imageSchemas[$i];
                if (!empty($oRecData->{$schema->id})) {
                    $aImages[] = ['url' => $oRecData->{$schema->id}, 'schema' => $schema, 'data' => $oRecData];
                }
            }
        }

        // 输出
        $usedRecordName = [];
        // 输出打包文件
        $zipFilename = tempnam('/tmp', $oApp->id);
        $zip = new \ZipArchive;
        if ($zip->open($zipFilename, \ZIPARCHIVE::CREATE) === false) {
            die('无法打开压缩文件，或者文件创建失败');
        }
        foreach ($aImages as $image) {
            $imageFilename = TMS_APP_DIR . '/' . $image['url'];
            if (file_exists($imageFilename)) {
                $imageName = basename($imageFilename);
                /**
                 * 图片文件名称替换
                 */
                if (isset($oNameSchema)) {
                    $data = $image['data'];
                    if (!empty($data->{$oNameSchema->id})) {
                        $recordName = $data->{$oNameSchema->id};
                        if (isset($usedRecordName[$recordName])) {
                            $usedRecordName[$recordName]++;
                            $recordName = $recordName . '_' . $usedRecordName[$recordName];
                        } else {
                            $usedRecordName[$recordName] = 0;
                        }
                        $imageName = $recordName . '.' . explode('.', $imageName)[1];
                    }
                }
                $zip->addFile($imageFilename, $image['schema']->title . '/' . $imageName);
            }
        }
        $zip->close();

        if (!file_exists($zipFilename)) {
            exit("无法找到压缩文件");
        }
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename=' . $oApp->title . '.zip');
        header("Content-Type: application/zip");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . filesize($zipFilename));
        @readfile($zipFilename);

        exit;
    }
}