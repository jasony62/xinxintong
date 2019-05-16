<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 填写记录数据汇总
 */
class repos extends base {
    /**
     * 获得活动中作为内容分类目录使用的题目
     */
    public function dirSchemasGet_action($app) {
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N', 'id,state,data_schemas']);
        if ($oApp === false || $oApp->state !== '1') {
            $this->outputError('指定的记录活动不存在，请检查参数是否正确');
        }

        $dirSchemas = []; // 作为分类的题目
        $oSchemasById = new \stdClass;
        foreach ($oApp->dataSchemas as $oSchema) {
            if (isset($oSchema->asdir) && $oSchema->asdir === 'Y') {
                $oSchemasById->{$oSchema->id} = $oSchema;
                switch ($oSchema->type) {
                case 'single':
                    if (empty($oSchema->optGroups)) {
                        /* 根分类 */
                        foreach ($oSchema->ops as $oOp) {
                            $oRootDir = new \stdClass;
                            $oRootDir->schema_id = $oSchema->id;
                            $oRootDir->schema_type = 'single';
                            $oRootDir->op = $oOp;
                            $dirSchemas[] = $oRootDir;
                        }
                    } else {
                        foreach ($oSchema->optGroups as $oOptGroup) {
                            if (isset($oOptGroup->assocOp) && isset($oOptGroup->assocOp->v) && isset($oSchemasById->{$oOptGroup->assocOp->schemaId})) {
                                $oParentSchema = $oSchemasById->{$oOptGroup->assocOp->schemaId};
                                foreach ($oParentSchema->ops as $oAssocOp) {
                                    if ($oAssocOp->v === $oOptGroup->assocOp->v) {
                                        if (!isset($oAssocOp->childrenDir)) {
                                            $oAssocOp->childrenDir = [];
                                        }
                                        foreach ($oSchema->ops as $oOp) {
                                            if (isset($oOp->g) && $oOp->g === $oOptGroup->i) {
                                                $oAssocOp->childrenDir[] = (object) ['schema_id' => $oSchema->id, 'op' => $oOp];
                                            }
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    break;
                case 'shorttext':
                    $modelData = $this->model('matter\enroll\data');
                    if (empty($oSchema->historyAssoc)) {
                        $oOptions = new \stdClass;
                        $oOptions->rid = empty($oApp->appRound) ? '' : $oApp->appRound->rid;
                        $oOptions->page = 1;
                        $oOptions->size = 99;
                        $oResult = $modelData->bySchema($oApp, $oSchema, $oOptions);
                        foreach ($oResult->records as $oRecData) {
                            $oRootDir = new \stdClass;
                            $oRootDir->schema_id = $oSchema->id;
                            $oRootDir->schema_type = 'shorttext';
                            $oRootDir->op = (object) ['v' => $oRecData->value, 'l' => $oRecData->value];
                            $dirSchemas[] = $oRootDir;
                        }
                    } else {
                        foreach ($dirSchemas as $oParentDirSchema) {
                            if (in_array($oParentDirSchema->schema_id, $oSchema->historyAssoc)) {
                                $aChildrenDir = [];
                                $oOptions = new \stdClass;
                                $oOptions->rid = empty($oApp->appRound) ? '' : $oApp->appRound->rid;
                                $oOptions->page = 1;
                                $oOptions->size = 99;
                                $oOptions->assocData = (object) [$oParentDirSchema->schema_id => $oParentDirSchema->op->v];
                                $oResult = $modelData->bySchema($oApp, $oSchema, $oOptions);
                                foreach ($oResult->records as $oRecData) {
                                    $oChildOption = new \stdClass;
                                    $oChildOption = (object) ['schema_id' => $oSchema->id, 'op' => (object) ['v' => $oRecData->value, 'l' => $oRecData->value]];
                                    $aChildrenDir[] = $oChildOption;
                                }
                                $oParentDirSchema->op->childrenDir = $aChildrenDir;
                            }
                        }

                    }
                    break;
                }
            }
        }

        return new \ResponseData($dirSchemas);
    }
    /**
     * 返回指定题目的活动登记名单
     */
    public function list4Schema_action($app, $page = 1, $size = 12) {
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }
        $oUser = $this->getUser($oApp);

        // 登记数据过滤条件
        $oCriteria = $this->getPostJson();

        // 填写记录过滤条件
        $oOptions = new \stdClass;
        $oOptions->page = $page;
        $oOptions->size = $size;
        $oOptions->fields = 'id,value,schema_id,multitext_seq,rid,userid,group_id,score,submit_at,remark_num,like_num,like_log,agreed,agreed_log,vote_num';

        !empty($oCriteria->keyword) && $oOptions->keyword = $oCriteria->keyword;
        !empty($oCriteria->rid) && $oOptions->rid = $oCriteria->rid;
        !empty($oCriteria->agreed) && $oOptions->agreed = $oCriteria->agreed;
        !empty($oCriteria->owner) && $oOptions->owner = $oCriteria->owner;
        !empty($oCriteria->userGroup) && $oOptions->userGroup = $oCriteria->userGroup;
        !empty($oCriteria->tag) && $oOptions->tag = $oCriteria->tag;
        if (empty($oCriteria->schema)) {
            $oOptions->schemas = [];
            foreach ($oApp->dataSchemas as $dataSchema) {
                if (isset($dataSchema->shareable) && $dataSchema->shareable === 'Y') {
                    $oOptions->schemas[] = $dataSchema->id;
                }
            }
            if (empty($oOptions->schemas)) {
                return new \ResponseData(['total' => 0]);
            }
        } else {
            $oOptions->schemas = [$oCriteria->schema];
        }

        // 查询结果
        $modelEnlDat = $this->model('matter\enroll\data');
        $oResult = $modelEnlDat->byApp($oApp, $oUser, $oOptions);
        if (count($oResult->records)) {
            /* 处理获得的数据 */
            $modelRem = $this->model('matter\enroll\remark');
            foreach ($oResult->records as $oRecData) {
                if ($oRecData->remark_num) {
                    $agreedRemarks = $modelRem->listByRecord($oUser, $oRecData->enroll_key, $oRecData->schema_id, $page = 1, $size = 10, ['agreed' => 'Y', 'fields' => 'id,content,create_at,nickname,like_num,like_log']);
                    if ($agreedRemarks->total) {
                        $oRecData->agreedRemarks = $agreedRemarks;
                    }
                }
                $oRecData->tag = empty($oRecData->tag) ? [] : json_decode($oRecData->tag);
            }
        }

        return new \ResponseData($oResult);
    }
    /**
     * 返回指定记录活动，指定题目的填写内容（只返回去重后的内容）
     *
     * @param string $app
     * @param string $schema schema'id
     * @param string $rid 轮次id，如果不指定为当前轮次，如果为ALL，所有轮次
     * @param string $onlyMine 只返回当前用户自己的
     *
     */
    public function dataBySchema_action($app, $schema, $rid = '', $onlyMine = 'N', $page = 1, $size = 10) {
        $schemaIds = explode(',', $schema);
        if (empty($schemaIds)) {
            return new \ParameterError('没有指定有效参数');
        }

        $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }
        if (empty($oApp->dynaDataSchemas)) {
            return new \ResponseError('活动【' . $oApp->title . '】没有定义题目');
        }
        $aSchemas = array_filter($oApp->dynaDataSchemas, function ($oSchema) use ($schemaIds) {
            return in_array($oSchema->id, $schemaIds);
        });
        if (empty($aSchemas)) {
            return new \ObjectNotFoundError();
        }

        $oRecData = $this->getPostJson();
        $modelEnlDat = $this->model('matter\enroll\data');
        $oResult = new \stdClass;

        $oOptions = new \stdClass;
        $oOptions->rid = $rid;
        $oOptions->page = $page;
        $oOptions->size = $size;
        if (count((array) $oRecData)) {
            $oOptions->assocData = $oRecData;
        }

        foreach ($aSchemas as $oSchema) {
            $oResult->{$oSchema->id} = $modelEnlDat->bySchema($oApp, $oSchema, $oOptions);
        }

        return new \ResponseData($oResult);
    }
    /**
     * 处理数据
     */
    private function _processDatas($oApp, $oUser, &$rawDatas, $processType = 'recordList', $voteRules = null) {
        $modelData = $this->model('matter\enroll\data');
        if (!empty($oApp->voteConfig)) {
            $modelTask = $this->model('matter\enroll\task', $oApp);
        }
        /* 是否设置了编辑组 */
        $oEditorGrp = $this->getEditorGroup($oApp);

        foreach ($rawDatas as &$rawData) {
            /* 获取记录的投票信息 */
            if (!empty($oApp->voteConfig)) {
                if (empty($voteRules)) {
                    $aVoteRules = $modelTask->getVoteRule($oUser, $rawData->round);
                } else {
                    $aVoteRules = $voteRules;
                }
            }
            $aCoworkState = [];
            $recordDirs = [];
            if (isset($rawData->data)) {
                $processedData = new \stdClass;
                foreach ($oApp->dynaDataSchemas as $oSchema) {
                    $schemaId = $oSchema->id;
                    // 分类目录
                    if ($this->getDeepValue($oSchema, 'asdir') === 'Y' && !empty($oSchema->ops) && !empty($rawData->data->{$schemaId})) {
                        foreach ($oSchema->ops as $op) {
                            if ($op->v === $rawData->data->{$schemaId}) {
                                $recordDirs[] = $op->l;
                            }
                        }
                    }
                    /* 清除非共享数据 */
                    if (!isset($oSchema->shareable) || $oSchema->shareable !== 'Y') {
                        continue;
                    }
                    // 过滤空数据
                    $rawDataVal = $this->getDeepValue($rawData->data, $schemaId, null);
                    if (null === $rawDataVal) {
                        continue;
                    }
                    // 选择题题目可见性规则
                    if (!empty($oSchema->visibility->rules)) {
                        $checkSchemaVisibility = true;
                        foreach ($oSchema->visibility->rules as $oRule) {
                            if (strpos($schemaId, 'member.extattr') === 0) {
                                $memberSchemaId = str_replace('member.extattr.', '', $schemaId);
                                if (!isset($rawData->data->member->extattr->{$memberSchemaId}) || ($rawData->data->member->extattr->{$memberSchemaId} !== $oRule->op && empty($rawData->data->member->extattr->{$memberSchemaId}))) {
                                    $checkSchemaVisibility = false;
                                }
                            } else if (!isset($rawData->data->{$oRule->schema}) || ($rawData->data->{$oRule->schema} !== $oRule->op && empty($rawData->data->{$oRule->schema}->{$oRule->op}))) {
                                $checkSchemaVisibility = false;
                            }
                        }
                        if ($checkSchemaVisibility === false) {
                            continue;
                        }
                    }

                    /* 协作填写题 */
                    if ($this->getDeepValue($oSchema, 'cowork') === 'Y') {
                        if ($processType === 'recordByTopic') {
                            $items = $modelData->getCowork($rawData->enroll_key, $schemaId, ['excludeRoot' => true, 'agreed' => ['Y', 'A'], 'fields' => 'id,agreed,like_num,nickname,value']);
                            $aCoworkState[$schemaId] = (object) ['length' => count($items)];
                            $processedData->{$schemaId} = $items;
                        } else if ($processType === 'coworkDataList') {
                            $item = new \stdClass;
                            $item->id = $rawData->data_id;
                            $item->value = $this->replaceHTMLTags($rawData->value);
                            $this->setDeepValue($processedData, $schemaId, [$item]);
                            unset($rawData->value);
                        } else {
                            $aOptions = ['fields' => 'id', 'agreed' => ['Y', 'A']];
                            $countItems = $modelData->getCowork($rawData->enroll_key, $schemaId, $aOptions);
                            $aCoworkState[$schemaId] = (object) ['length' => count($countItems)];
                        }
                    } else if ($this->getDeepValue($oSchema, 'type') === 'multitext') {
                        $newData = [];
                        foreach ($rawDataVal as &$val) {
                            $val2 = new \stdClass;
                            $val2->id = $val->id;
                            $val2->value = $this->replaceHTMLTags($val->value);
                            $newData[] = $val2;
                        }
                        $this->setDeepValue($processedData, $schemaId, $newData);
                    } else if ($this->getDeepValue($oSchema, 'type') === 'single') {
                        foreach ($oSchema->ops as $val) {
                            if ($val->v === $rawDataVal) {
                                $this->setDeepValue($processedData, $schemaId, $val->l);
                            }
                        }
                    } else if ($this->getDeepValue($oSchema, 'type') === 'score') {
                        $ops = new \stdClass;
                        foreach ($oSchema->ops as $val) {
                            $ops->{$val->v} = $val->l;
                        }
                        $newData = [];
                        foreach ($rawDataVal as $key => $val) {
                            $data2 = new \stdClass;
                            $data2->title = $ops->{$key};
                            $data2->score = $val;
                            $newData[] = $data2;
                        }
                        $this->setDeepValue($processedData, $schemaId, $newData);
                    } else if ($this->getDeepValue($oSchema, 'type') === 'multiple') {
                        $rawDataVal2 = explode(',', $rawDataVal);
                        $ops = new \stdClass;
                        foreach ($oSchema->ops as $val) {
                            $ops->{$val->v} = $val->l;
                        }
                        $newData = [];
                        foreach ($rawDataVal2 as $val) {
                            $newData[] = $ops->{$val};
                        }
                        $this->setDeepValue($processedData, $schemaId, $newData);
                    } else {
                        $this->setDeepValue($processedData, $schemaId, $rawDataVal);
                    }
                }
                $rawData->data = $processedData;
                if (!empty($aCoworkState)) {
                    $rawData->coworkState = (object) $aCoworkState;
                    // 协作填写题数据总数量
                    $sum = 0;
                    foreach ($aCoworkState as $k => $v) {
                        $sum += (int) $v->length;
                    }
                    $rawData->coworkDataTotal = $sum;
                }
                if (!empty($recordDirs)) {
                    $rawData->recordDir = $recordDirs;
                }

                /* 获取记录的投票信息 */
                if (!empty($aVoteRules)) {
                    $oVoteResult = new \stdClass;
                    foreach ($aVoteRules as $schemaId => $oVoteRule) {
                        if ($processType === 'coworkDataList') {
                            continue;
                        } else if ($processType === 'recordByTopic') {
                            if ($this->getDeepValue($oVoteRule->schema, 'cowork') === 'Y') {continue;}
                            $oRecData = $modelData->byRecord($rawData->enroll_key, ['schema' => $schemaId, 'fields' => 'id,vote_num']);
                            if ($oRecData) {
                                $vote_at = (int) $modelData->query_val_ss(['vote_at', 'xxt_enroll_vote', ['rid' => $oApp->appRound->rid, 'data_id' => $oRecData->id, 'state' => 1, 'userid' => $oUser->uid]]);
                                $oRecData->vote_at = $vote_at;
                                $oRecData->state = $oVoteRule->state;
                                $oVoteResult->{$schemaId} = $oRecData;
                            }
                        } else {
                            $oVoteResult = new \stdClass;
                            if ($this->getDeepValue($oVoteRule->schema, 'cowork') === 'Y') {continue;}
                            $oRecData = $modelData->byRecord($rawData->enroll_key, ['schema' => $schemaId, 'fields' => 'id,vote_num']);
                            if ($oRecData) {
                                $oVoteResult->{$schemaId} = $oRecData;
                            }
                        }
                    }
                    $rawData->voteResult = $oVoteResult;
                }
            }
            /* 设置昵称 */
            $this->setNickname($rawData, $oUser, isset($oEditorGrp) ? $oEditorGrp : null);
            /* 清除不必要的内容 */
            unset($rawData->comment);
            unset($rawData->verified);

            /* 是否已经被当前用户收藏 */
            if ($processType === 'recordList' || $processType === 'recordByTopic') {
                if (!empty($oUser->unionid) && $rawData->favor_num > 0) {
                    $q = ['id', 'xxt_enroll_record_favor', ['record_id' => $rawData->id, 'favor_unionid' => $oUser->unionid, 'state' => 1]];
                    if ($modelData->query_obj_ss($q)) {
                        $rawData->favored = true;
                    }
                }
            }
            /* 记录的标签 */
            if ($processType === 'recordList') {
                if (!isset($modelTag)) {
                    $modelTag = $this->model('matter\enroll\tag2');
                }
                $oRecordTags = $modelTag->byRecord($rawData, $oUser, ['UserAndPublic' => empty($oPosted->favored)]);
                if (!empty($oRecordTags->user)) {
                    $rawData->userTags = $oRecordTags->user;
                }
                if (!empty($oRecordTags->public)) {
                    $rawData->tags = $oRecordTags->public;
                }
            }
            /* 答案关联素材 */
            if ($processType === 'coworkDataList') {
                if (!isset($modelAss)) {
                    $modelAss = $this->model('matter\enroll\assoc');
                    $oAssocsOptions = [
                        'fields' => 'id,assoc_mode,assoc_num,first_assoc_at,last_assoc_at,entity_a_id,entity_a_type,entity_b_id,entity_b_type,public,assoc_text,assoc_reason',
                    ];
                }
                $entityA = new \stdClass;
                $entityA->id = $rawData->data_id;
                $entityA->type = 'data';
                $oAssocsOptions['entityA'] = $entityA;
                $record = new \stdClass;
                $record->id = $rawData->record_id;
                $oAssocs = $modelAss->byRecord($record, $oUser, $oAssocsOptions);
                if (count($oAssocs)) {
                    foreach ($oAssocs as $oAssoc) {
                        $modelAss->adapt($oAssoc);
                    }
                }
                $rawData->oAssocs = $oAssocs;
                //
                $rawData->id = $rawData->record_id;
            }
        }

        return $rawDatas;
    }
    /**
     * 返回指定活动的填写记录的共享内容
     */
    public function recordList_action($app, $page = 1, $size = 12) {
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $oUser = $this->getUser($oApp);

        // 填写记录过滤条件
        $oPosted = $this->getPostJson();
        // 填写记录过滤条件
        $oOptions = new \stdClass;
        $oOptions->page = $page;
        $oOptions->size = $size;

        !empty($oPosted->keyword) && $oOptions->keyword = $this->escape($oPosted->keyword);

        if (!empty($oPosted->orderby)) {
            switch ($oPosted->orderby) {
            case 'earliest':
                $oOptions->orderby = ['enroll_at asc'];
                break;
            case 'lastest':
                $oOptions->orderby = ['enroll_at'];
                break;
            case 'earliest_first':
                $oOptions->orderby = ['first_enroll_at asc'];
                break;
            case 'lastest_first':
                $oOptions->orderby = ['first_enroll_at desc'];
                break;
            case 'mostvoted':
                $oOptions->orderby = ['vote_schema_num', 'enroll_at'];
                break;
            case 'mostliked':
                $oOptions->orderby = ['like_num', 'enroll_at'];
                break;
            case 'agreed':
                $oOptions->orderby = ['agreed', 'enroll_at'];
                break;
            }
        }

        // 查询结果
        $modelRec = $this->model('matter\enroll\record');
        $oCriteria = new \stdClass;
        $oCriteria->record = new \stdClass;
        $oCriteria->record->rid = !empty($oPosted->rid) ? $this->escape($oPosted->rid) : 'all';

        /* 指定了分组过滤条件 */
        if (!empty($oPosted->userGroup)) {
            $oCriteria->record->group_id = $this->escape($oPosted->userGroup);
        }
        /* 记录的创建人 */
        if (!empty($oPosted->mine) && $oPosted->mine === 'creator') {
            $oCriteria->record->userid = $oUser->uid;
        } else if (!empty($oPosted->mine) && $oPosted->mine === 'favored') {
            // 当前用户收藏
            $oCriteria->record->favored = true;
        }
        /* 记录的表态 */
        if (!empty($oPosted->agreed) && stripos($oPosted->agreed, 'all') === false) {
            $oCriteria->record->agreed = $this->escape($oPosted->agreed);
        }
        /* 记录的标签 */
        if (!empty($oPosted->tags)) {
            if (is_array($oPosted->tags)) {
                foreach ($oPosted->tags as &$tagid) {
                    $tagid = $this->escape($tagid);
                }
            } else {
                $oPosted->tags = $this->escape($oPosted->tags);
            }
            $oCriteria->record->tags = $oPosted->tags;
        }
        !empty($oPosted->data) && $oCriteria->data = $oPosted->data;

        /* 答案的筛选 */
        if (!empty($oPosted->coworkAgreed) && stripos($oPosted->coworkAgreed, 'all') === false) {
            foreach ($oApp->dynaDataSchemas as $oSchema) {
                if (isset($oSchema->cowork) && $oSchema->cowork === 'Y') {
                    $oCriteria->cowork = new \stdClass;
                    $oCriteria->cowork->agreed = $this->escape($oPosted->coworkAgreed);
                    break;
                }
            }
        }

        $oResult = $modelRec->byApp($oApp, $oOptions, $oCriteria, $oUser);
        if (!empty($oResult->records)) {
            $this->_processDatas($oApp, $oUser, $oResult->records, 'recordList');
        }

        // 记录搜索事件
        if (!empty($oPosted->keyword)) {
            $oPosted->keyword = $this->escape($oPosted->keyword);
            $rest = $this->model('matter\enroll\search')->addUserSearch($oApp, $oUser, $oPosted->keyword);
            // 记录日志
            $this->model('matter\enroll\event')->searchRecord($oApp, $rest['search'], $oUser);
        }

        return new \ResponseData($oResult);
    }
    /**
     * 返回指定活动的填写记录的共享内容
     * 答案视图
     */
    public function coworkDataList_action($app, $page = 1, $size = 12) {
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $coworkSchemaIds = [];
        foreach ($oApp->dynaDataSchemas as $oSchema) {
            if (isset($oSchema->cowork) && $oSchema->cowork === 'Y') {
                $coworkSchemaIds[] = $oSchema->id;
            }
        }
        if (empty($coworkSchemaIds)) {
            return new \ObjectNotFoundError('活动中没有协作题');
        }

        $oUser = $this->getUser($oApp);
        // 填写记录过滤条件
        $oOptions = new \stdClass;
        $oOptions->page = $page;
        $oOptions->size = $size;
        $oOptions->regardRemarkRoundAsRecordRound = true; // 将留言的轮次作为记录的轮次

        $oPosted = $this->getPostJson();
        if (!empty($oPosted->orderby)) {
            switch ($oPosted->orderby) {
            case 'earliest':
                $oOptions->orderby = ['submit_at asc'];
                break;
            case 'lastest':
                $oOptions->orderby = ['submit_at'];
                break;
            case 'mostvoted':
                $oOptions->orderby = ['vote_num', 'submit_at'];
                break;
            case 'mostliked':
                $oOptions->orderby = ['like_num', 'submit_at'];
                break;
            case 'agreed':
                $oOptions->orderby = ['agreed', 'submit_at'];
                break;
            }
        }

        // 查询结果
        $modelRecDat = $this->model('matter\enroll\data');
        $oCriteria = new \stdClass;
        !empty($oPosted->keyword) && $oCriteria->keyword = $this->escape($oPosted->keyword);
        // 按指定题的值筛选
        !empty($oPosted->data) && $oCriteria->data = $oPosted->data;

        $oCriteria->recordData = new \stdClass;
        $oCriteria->recordData->rid = !empty($oPosted->rid) ? $this->escape($oPosted->rid) : 'all';

        /* 指定了分组过滤条件 */
        if (!empty($oPosted->userGroup)) {
            $oCriteria->recordData->group_id = $this->escape($oPosted->userGroup);
        }
        /* 答案的创建人 */
        if (!empty($oPosted->mine) && $oPosted->mine === 'creator') {
            $oCriteria->recordData->userid = $oUser->uid;
        }
        /* 答案的表态 */
        if (!empty($oPosted->agreed) && stripos($oPosted->agreed, 'all') === false) {
            $oCriteria->recordData->agreed = $this->escape($oPosted->agreed);
        }

        $oResult = $modelRecDat->coworkDataByApp($oApp, $oOptions, $oCriteria, $oUser, 'cowork');
        if (!empty($oResult->recordDatas)) {
            // 处理数据
            $this->_processDatas($oApp, $oUser, $oResult->recordDatas, 'coworkDataList');
        }

        // 记录搜索事件
        if (!empty($oPosted->keyword)) {
            $oPosted->keyword = $this->escape($oPosted->keyword);
            $rest = $this->model('matter\enroll\search')->addUserSearch($oApp, $oUser, $oPosted->keyword);
            // 记录日志
            $this->model('matter\enroll\event')->searchRecord($oApp, $rest['search'], $oUser);
        }

        return new \ResponseData($oResult);
    }
    /**
     * 返回指定活动的填写记录的共享内容
     */
    public function recordByTopic_action($app, $topic, $page = 1, $size = 12) {
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $oUser = $this->getUser($oApp);

        // 填写记录过滤条件
        $oOptions = new \stdClass;
        $oOptions->page = $page;
        $oOptions->size = $size;

        // 查询结果
        $modelRec = $this->model('matter\enroll\record');
        $modelTop = $this->model('matter\enroll\topic', $oApp);
        $oTopic = $modelTop->byId($topic);

        $oResult = $modelTop->records($oTopic, ['fields' => $modelRec::REPOS_FIELDS]);
        if (!empty($oResult->records)) {
            /* 获取记录的投票信息 */
            if (!empty($oApp->voteConfig)) {
                $aVoteRules = $this->model('matter\enroll\task', $oApp)->getVoteRule($oUser);
            } else {
                $aVoteRules = null;
            }
            // 处理数据
            $this->_processDatas($oApp, $oUser, $oResult->records, 'recordByTopic', $aVoteRules);
            /**
             * 根据任务进行排序
             * 1、投票任务结束后，根据投票数排序
             * 2、投票进行中，指定了排序规则，按规则排序
             */
            if (!empty($oTopic->task_id)) {
                $oTask = $this->model('matter\enroll\task', $oApp)->byId($oTopic->task_id);
                if ($oTask) {
                    if ($oTask->config_type === 'vote') {
                        if ($oTask->state === 'AE') {
                            if (!empty($oTask->schemas)) {
                                $p = 'voteResult.' . $oTask->schemas[0] . '.vote_num';
                                usort($oResult->records, function ($a, $b) use ($p) {
                                    $anum = $this->getDeepValue($a, $p, 0);
                                    $bnum = $this->getDeepValue($b, $p, 0);
                                    return $bnum - $anum;
                                });
                            }
                        } else if (!empty($oResult->records)) {
                            if (isset($oTask->source->orderby)) {
                                if ('random' === $oTask->source->orderby) {
                                    shuffle($oResult->records);
                                }
                            }
                        }
                    }
                }
            }
        }

        return new \ResponseData($oResult);
    }
    /**
     * 获得一条记录可共享的内容
     */
    public function recordGet_action($app, $ek) {
        $modelApp = $this->model('matter\enroll');
        $modelRec = $this->model('matter\enroll\record');

        $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }
        $fields = 'id,state,aid,rid,enroll_key,userid,group_id,nickname,verified,enroll_at,first_enroll_at,supplement,score,like_num,like_log,remark_num,rec_remark_num,favor_num,agreed,data,dislike_num,dislike_log';
        $oRecord = $modelRec->byId($ek, ['fields' => $fields]);
        if (false === $oRecord || $oRecord->state !== '1') {
            return new \ObjectNotFoundError();
        }
        $oUser = $this->getUser($oApp);

        $oRecords = [ & $oRecord];
        $this->_processDatas($oApp, $oUser, $oRecords, 'recordList');

        return new \ResponseData($oRecord);
    }
    /**
     * 获取活动共享页筛选条件
     */
    public function criteriaGet_action($app, $viewType = 'record') {
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['fields' => 'id,siteid,state,repos_config,data_schemas,entry_rule,action_rule,mission_id,sync_mission_round,assigned_nickname,round_cron', 'cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $oUser = $this->getUser($oApp);

        $oCriterias = $this->_originCriteriaGet();
        $result = $this->_packCriteria($oApp, $oUser, $oCriterias, $viewType);
        if ($result[0] === false) {
            return new \ParameterError($result[1]);
        }

        $criterias = $result[1];
        return new \ResponseData($criterias);
    }
    /**
     * 按当前用户角色过滤筛选条件
     */
    private function _packCriteria($oApp, $oUser, $criterias, $viewType = 'record') {
        $varType = gettype($criterias);
        if ($varType === 'object') {
            $criterias = (array) $criterias;
        } else if ($varType !== 'array') {
            return [false, '参数格式错误！'];
        }

        foreach ($criterias as $key => $oCriteria) {
            // 默认排序
            if ($oCriteria->type === 'orderby') {
                if ($viewType === 'topic') {
                    $oCriteria->menus = [];
                    $oCriteria->menus[] = (object) ['id' => 'lastest', 'title' => '最近创建'];
                    $oCriteria->menus[] = (object) ['id' => 'earliest', 'title' => '最早创建'];
                    $oCriteria->default = $oCriteria->menus[0];
                } else {
                    if (!empty($oApp->reposConfig->defaultOrder)) {
                        foreach ($oCriteria->menus as $i => $v) {
                            if ($v->id === $oApp->reposConfig->defaultOrder) {
                                $oCriteria->default = $oCriteria->menus[$i];
                                break;
                            }
                        }
                    }
                }
            }
            //获取轮次
            if ($oCriteria->type === 'rid') {
                if ($viewType === 'topic') {
                    unset($criterias[$key]);
                } else {
                    $modelRun = $this->model('matter\enroll\round');
                    $options = [
                        'fields' => 'rid,title',
                        'state' => ['1', '2'],
                    ];
                    $result = $modelRun->byApp($oApp, $options);
                    if (count($result->rounds) == 1) {
                        unset($criterias[$key]);
                    } else {
                        foreach ($result->rounds as $round) {
                            if ($round->rid === $result->active->rid) {
                                $oCriteria->menus[] = (object) ['id' => $round->rid, 'title' => '(当前填写轮次) ' . $round->title];
                            } else {
                                $oCriteria->menus[] = (object) ['id' => $round->rid, 'title' => $round->title];
                            }
                        }
                    }
                }
            }
            // 如果有答案的题型才显示筛选答案的按钮
            if ($oCriteria->type === 'coworkAgreed') {
                $coworkState = false;
                if ($viewType === 'record') {
                    foreach ($oApp->dynaDataSchemas as $oSchema) {
                        if (isset($oSchema->cowork) && $oSchema->cowork === 'Y') {
                            $coworkState = true;
                            break;
                        }
                    }
                }
                if (!$coworkState) {
                    unset($criterias[$key]);
                }
            }
            // 获取分组
            if ($oCriteria->type === 'userGroup') {
                if ($viewType === 'topic') {
                    unset($criterias[$key]);
                } else if (empty($oApp->entryRule->group->id)) {
                    unset($criterias[$key]);
                } else {
                    $assocGroupAppId = $oApp->entryRule->group->id;
                    $modelGrpTeam = $this->model('matter\group\team');
                    $groups = $modelGrpTeam->byApp($assocGroupAppId, ['fields' => "team_id,title"]);
                    if (empty($groups)) {
                        unset($criterias[$key]);
                    } else {
                        foreach ($groups as $group) {
                            $oCriteria->menus[] = (object) ['id' => $group->team_id, 'title' => $group->title];
                        }
                    }
                }
            }
            /*
             *表态 当用户为编辑或者超级管理员或者有组时才会出现“接受”，“关闭” ，“讨论”，“未表态” ，否则只有推荐和不限两种
             */
            if ($oCriteria->type === 'agreed') {
                if ($viewType === 'topic') {
                    unset($criterias[$key]);
                } else if (!empty($oUser->group_id) || (isset($oUser->is_leader) && $oUser->is_leader === 'S') || (isset($oUser->is_editor) && $oUser->is_editor === 'Y')) {
                    $oCriteria->menus[] = (object) ['id' => 'A', 'title' => '接受'];
                    $oCriteria->menus[] = (object) ['id' => 'D', 'title' => '讨论'];
                    $oCriteria->menus[] = (object) ['id' => 'N', 'title' => '关闭'];
                }
            }
            // 只有登录用户才会显示我的记录和我的收藏
            if ($oCriteria->type === 'mine') {
                if (empty($oUser->unionid)) {
                    unset($criterias[$key]);
                } else if ($viewType === 'record') {
                    $oCriteria->menus[] = (object) ['id' => 'creator', 'title' => '我的记录'];
                    $oCriteria->menus[] = (object) ['id' => 'favored', 'title' => '我的收藏'];
                } else if ($viewType === 'coworkData') {
                    $oCriteria->menus[] = (object) ['id' => 'creator', 'title' => '我的回答'];
                } else if ($viewType === 'topic') {
                    $oCriteria->menus[] = (object) ['id' => 'creator', 'title' => '我的专题'];
                    $oCriteria->menus[] = (object) ['id' => 'public', 'title' => '公共专题'];
                } else {
                    unset($criterias[$key]);
                }
            }
            // 搜索历史
            if ($oCriteria->type === 'keyword') {
                $search = $this->model('matter\enroll\search')->listUserSearch($oApp, $oUser);
                $userSearchs = $search->userSearch;
                foreach ($userSearchs as $userSearch) {
                    $oCriteria->menus[] = (object) ['id' => $userSearch->keyword, 'title' => $userSearch->keyword];
                }
            }
        }

        $criterias = array_values($criterias);

        return [true, $criterias];
    }
    /**
     * 获得所有条件
     */
    private function _originCriteriaGet() {
        $criterias = [];
        // 排序
        $orderby = new \stdClass;
        $orderby->type = 'orderby';
        $orderby->title = '排序';
        $orderby->menus = [
            (object) ['id' => 'lastest', 'title' => '最近提交'],
            (object) ['id' => 'earliest', 'title' => '最早提交'],
            (object) ['id' => 'mostliked', 'title' => '最多赞同'],
            (object) ['id' => 'mostvoted', 'title' => '最多投票'],
        ];
        $orderby->default = $orderby->menus[0];
        $criterias[] = $orderby;
        // 搜索历史
        $keyword = new \stdClass;
        $keyword->type = 'keyword';
        $keyword->title = '历史';
        $keyword->menus = [
            (object) ['id' => null, 'title' => '不限'],
        ];
        $keyword->default = $keyword->menus[0];
        $criterias[] = $keyword;
        // 协作
        $coworkAgreed = new \stdClass;
        $coworkAgreed->type = 'coworkAgreed';
        $coworkAgreed->title = '协作';
        $coworkAgreed->menus = [
            (object) ['id' => null, 'title' => '所有问题'],
            (object) ['id' => 'answer', 'title' => '已回答'],
            (object) ['id' => 'unanswer', 'title' => '等待回答'],
        ];
        $coworkAgreed->default = $coworkAgreed->menus[0];
        $criterias[] = $coworkAgreed;
        // 轮次
        $round = new \stdClass;
        $round->type = 'rid';
        $round->title = '轮次';
        $round->menus = [
            (object) ['id' => null, 'title' => '不限'],
        ];
        $round->default = $round->menus[0];
        $criterias[] = $round;
        // 分组
        $group = new \stdClass;
        $group->type = 'userGroup';
        $group->title = '分组';
        $group->menus = [
            (object) ['id' => null, 'title' => '不限'],
        ];
        $group->default = $group->menus[0];
        $criterias[] = $group;
        // 表态
        $agreed = new \stdClass;
        $agreed->type = 'agreed';
        $agreed->title = '表态';
        $agreed->menus = [
            (object) ['id' => null, 'title' => '不限'],
            (object) ['id' => 'Y', 'title' => '推荐'],
        ];
        $agreed->default = $agreed->menus[0];
        $criterias[] = $agreed;
        // 我的
        $mine = new \stdClass;
        $mine->type = 'mine';
        $mine->title = '我的';
        $mine->menus = [
            (object) ['id' => null, 'title' => '不限'],
        ];
        $mine->default = $mine->menus[0];
        $criterias[] = $mine;

        return $criterias;
    }
}