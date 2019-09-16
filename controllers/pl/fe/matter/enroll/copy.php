<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/**
 * 记录活动主控制器
 */
class copy extends main_base {
    /**
     *
     * 复制指定的记录活动
     *
     * 跨项目进行复制：
     * 1、关联了项目的通讯录，取消关联，修改相关题目的id和type
     * 2、关联了分组活动，取消和分组活动的关联，修改分组题目，修改相关题目的id和type
     * 3、关联了记录活动，取消和记录活动的关联，修改分组题目，修改相关题目的id和type
     *
     * @param string $site 是否要支持跨团队进行活动的复制？
     * @param string $app
     * @param int $mission
     * @param int $cpTimer 是否复制定时通知
     * @param int $cpCoinRule 是否复制积分规则
     * @param int $cpRecord 是否复制数据
     * @param int $cpEnrollee 是否复制用户行为
     *
     */
    public function default_action($site, $app, $mission = null, $cpTimer = 'Y', $cpCoinRule = 'Y', $cpRecord = 'N', $cpEnrollee = 'N') {
        $oUser = $this->user;
        $modelApp = $this->model('matter\enroll')->setOnlyWriteDbConn(true);
        $oCopied = $modelApp->byId($app);
        if (false === $oCopied || $oCopied->state !== '1') {
            return new \ObjectNotFoundError();
        }

        list($oNewEntryRule, $aDataSchemas, $aPages) = $this->_copyEntryRule($oCopied, $mission);
        $newaid = uniqid();
        $oNewApp = new \stdClass;
        $oNewApp->siteid = $site;
        $oNewApp->id = $newaid;
        $oNewApp->title = $modelApp->escape($oCopied->title) . '（副本）';
        $oNewApp->pic = $oCopied->pic;
        $oNewApp->summary = $modelApp->escape($oCopied->summary);
        $oNewApp->scenario = $oCopied->scenario;
        $oNewApp->scenario_config = json_encode($oCopied->scenarioConfig);
        $oNewApp->vote_config = json_encode($oCopied->voteConfig);
        $oNewApp->count_limit = $oCopied->count_limit;
        $oNewApp->enrolled_entry_page = $oCopied->enrolled_entry_page;
        $oNewApp->entry_rule = $modelApp->escape($modelApp->toJson($oNewEntryRule));
        $oNewApp->round_cron = $modelApp->escape($modelApp->toJson($oCopied->roundCron));
        $oNewApp->data_schemas = $modelApp->escape($modelApp->toJson($aDataSchemas));
        $oNewApp->tags = $modelApp->escape($oCopied->tags);
        $oNewApp->count_limit = $modelApp->escape($oCopied->count_limit);

        /* 作为昵称的题目 */
        $oNicknameSchema = $modelApp->findAssignedNicknameSchema($aDataSchemas);
        if (!empty($oNicknameSchema)) {
            $oNewApp->assigned_nickname = json_encode(['valid' => 'Y', 'schema' => ['id' => $oNicknameSchema->id]]);
        }

        /* 所属项目 */
        if (!empty($mission)) {
            $oNewApp->mission_id = $mission;
        }

        $oNewApp = $modelApp->create($oUser, $oNewApp);
        /**
         * 复制页面
         */
        if (count($oCopied->pages)) {
            $this->_copyPages($oCopied, $oNewApp);
        }
        /**
         * 复制定时通知
         */
        if ($cpTimer === 'Y') {
            $this->_copyTimer($oCopied, $oNewApp);
        }
        /**
         * 复制积分规则
         */
        if ($cpCoinRule === 'Y') {
            $this->_copyCoinRule($oCopied, $oNewApp);
        }
        /* 复制记录活动数据 */
        if ($cpRecord === 'Y') {
            $oNewApp = $modelApp->byId($oNewApp->id);
            $this->_copyRecords($oCopied, $oNewApp, $cpEnrollee);
        }

        /* 记录操作日志 */
        $this->model('matter\log')->matterOp($oNewApp->siteid, $oUser, $oNewApp, 'C', (object) ['id' => $oCopied->id, 'title' => $oCopied->title]);

        return new \ResponseData($oNewApp);
    }
    /**
     * 复制进入规则
     */
    private function _copyEntryRule($oCopied, $mission) {
        $modelApp = $this->model('matter\enroll');
        $modelPg = $this->model('matter\enroll\page');
        $oNewEntryRule = clone $oCopied->entryRule;
        $aDataSchemas = $oCopied->dataSchemas;
        $aPages = $oCopied->pages;
        /**
         * 如果通讯录的所属范围和新活动的范围不一致，需要解除关联的通信录
         */
        if (isset($oNewEntryRule->scope->member) && $oNewEntryRule->scope->member === 'Y') {
            $aMatterMschemas = $modelApp->getEntryMemberSchema($oNewEntryRule);
            foreach ($aMatterMschemas as $oMschema) {
                if (!empty($oMschema->matter_type) && ($oMschema->matter_type !== 'mission' || $oMschema->matter_id !== $mission)) {
                    /* 应用的题目 */
                    $modelApp->replaceMemberSchema($aDataSchemas, $oMschema);
                    /* 页面的题目 */
                    foreach ($aPages as $oPage) {
                        $modelPg->replaceMemberSchema($oPage, $oMschema);
                    }
                    unset($oNewEntryRule->member->{$oMschema->id});
                }
            }
            if (count((array) $oNewEntryRule->member) === 0) {
                unset($oNewEntryRule->scope->member);
                unset($oNewEntryRule->member);
            }
        }
        /**
         * 跨项目进行复制
         */
        if ($oCopied->mission_id !== $mission) {
            /**
             * 只有同项目内的分组活动和记录活动可以作为参与规则
             */
            $aAssocApps = [];
            if (isset($oNewEntryRule->scope->group) && $oNewEntryRule->scope->group === 'Y') {
                unset($oNewEntryRule->scope->group);
            }
            if (isset($oNewEntryRule->group)) {
                $aAssocApps[] = $oNewEntryRule->group->id;
                unset($oNewEntryRule->group);
            }
            if (isset($oNewEntryRule->scope->enroll) && $oNewEntryRule->scope->enroll === 'Y') {
                unset($oNewEntryRule->scope->enroll);
            }
            if (isset($oNewEntryRule->enroll)) {
                $aAssocApps[] = $oNewEntryRule->enroll->id;
                unset($oNewEntryRule->enroll);
            }
            /**
             * 如果关联了分组或记录活动，需要去掉题目的关联信息
             */
            if (count($aAssocApps)) {
                /* 页面的题目 */
                foreach ($aPages as $oPage) {
                    $modelPg->replaceAssocSchema($oPage, $aAssocApps);
                }
                /* 应用的题目 */
                $modelApp->replaceAssocSchema($aDataSchemas, $aAssocApps);
            }
        }

        return [$oNewEntryRule, $aDataSchemas, $aPages];
    }
    /**
     * 复制页面
     */
    private function _copyPages($oCopied, $oNewApp) {
        $modelPg = $this->model('matter\enroll\page');
        $modelCode = $this->model('code\page');
        foreach ($oCopied->pages as $ep) {
            $oNewPage = $modelPg->add($this->user, $oNewApp->siteid, $oNewApp->id);
            $rst = $modelPg->update(
                'xxt_enroll_page',
                [
                    'title' => $modelPg->escape($ep->title),
                    'name' => $ep->name,
                    'type' => $ep->type,
                    'data_schemas' => $modelPg->escape($modelPg->toJson($ep->dataSchemas)),
                    'act_schemas' => $modelPg->escape($modelPg->toJson($ep->actSchemas)),
                ],
                ['aid' => $oNewApp->id, 'id' => $oNewPage->id]
            );
            $data = [
                'title' => $ep->title,
                'html' => $ep->html,
                'css' => $ep->css,
                'js' => $ep->js,
            ];
            $modelCode->modify($oNewPage->code_id, $data);
        }

        return true;
    }
    /**
     * 复制定时通知
     */
    private function _copyTimer($oCopied, $oNewApp) {
        $modelTim = $this->model('matter\timer');
        $tasks = $modelTim->byMatter('enroll', $oCopied->id);
        foreach ($tasks as $oTask) {
            $oCopyResult = $modelTim->copy($oNewApp, $oTask);
        }

        return true;
    }
    /**
     * 复制积分规则
     */
    private function _copyCoinRule($oCopied, $oNewApp) {
        $filter = 'ID:' . $oCopied->id;
        $modelRule = $this->model('site\coin\rule');
        $rules = $modelRule->byMatterFilter($filter, ['fields' => 'id,act,actor_delta,actor_overlap,matter_type']);
        foreach ($rules as $oRule) {
            if ($oRule->actor_delta != 0) {
                $oNewRule = clone $oRule;
                unset($oNewRule->id);
                $oNewRule->siteid = $oNewApp->siteid;
                $oNewRule->matter_type = 'enroll';
                $oNewRule->matter_filter = 'ID:' . $oNewApp->id;
                $oNewRule->id = $modelRule->insert('xxt_coin_rule', $oNewRule, true);
            }
        }

        return true;
    }
    /**
     * 复制填写记录
     */
    private function _copyRecords($oCopied, $oNewApp, $cpEnrollee) {
        $modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);
        /* 创建新活动的轮次和原活动匹配 */
        $modelRnd = $this->model('matter\enroll\round');
        $oldRounds = $modelRnd->byApp($oCopied)->rounds;

        foreach ($oldRounds as $oldRound) {
            $props = new \stdClass;
            $props->title = $oldRound->title . '（复制）';
            $props->summary = $oldRound->summary;
            $props->start_at = $oldRound->start_at;
            $props->end_at = $oldRound->end_at;
            $props->state = $oldRound->state;
            $newRound = $modelRnd->create($oNewApp, $props, $this->user);
            if (!$newRound[0]) {
                return new \ResponseError($newRound[1]);
            }
            $newRid = $newRound[1]->rid;
            // 插入数据
            $oldCriteria = new \stdClass;
            $oldCriteria->record = new \stdClass;
            $oldCriteria->record->rid = $oldRound->rid;
            $oldUsers = $modelRec->byApp($oCopied, null, $oldCriteria);

            if (isset($oldUsers->records) && count($oldUsers->records)) {
                foreach ($oldUsers->records as $record) {
                    $cpUser = new \stdClass;
                    $cpUser->uid = ($cpEnrollee !== 'Y') ? '' : $record->userid;
                    $cpUser->nickname = ($cpEnrollee !== 'Y') ? '' : $record->nickname;
                    /* 插入登记数据 */
                    $oNewRec = $modelRec->enroll($oNewApp, $cpUser, ['nickname' => $cpUser->nickname, 'assignedRid' => $newRid]);
                    /* 处理自定义信息 */
                    if (isset($record->data->member) && $this->getDeepValue($oNewApp, 'entryRule.scope.member') !== 'Y') {
                        unset($record->data->member->schema_id);
                        foreach ($record->data->member as $schemaId => $val) {
                            $record->data->{$schemaId} = $val;
                        }
                        unset($record->data->member);
                    }
                    $oEnrolledData = $record->data;
                    $rst = $modelRec->setData($cpUser, $oNewApp, $oNewRec->enroll_key, $oEnrolledData, '', false);
                    if (!empty($record->supplement) && count(get_object_vars($record->supplement))) {
                        $rst = $modelRec->setSupplement($cpUser, $oNewApp, $oNewRec->enroll_key, $record->supplement);
                    }
                    $upDate = [];
                    $upDate['verified'] = $record->verified;
                    $upDate['comment'] = $modelRec->escape($record->comment);
                    if (!empty($record->tags)) {
                        $upDate['tags'] = $modelRec->escape($record->tags);
                    }
                    $rst = $modelRec->update(
                        'xxt_enroll_record',
                        $upDate,
                        ['enroll_key' => $oNewRec->enroll_key, 'state' => 1]
                    );
                }
            }
        }

        return true;
    }
}