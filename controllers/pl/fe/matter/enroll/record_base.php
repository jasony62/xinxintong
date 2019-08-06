<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 记录活动主控制器
 */
abstract class record_base extends main_base {
    /**
     * 处理数据
     */
    protected function _processDatas($oApp, &$rawDatas, $processType = 'recordList') {
        // 处理多项填写题
        $processMultitext = function ($oldVal) {
            $newVal = [];
            foreach ($oldVal as &$val) {
                $val2 = new \stdClass;
                $val2->id = $val->id;
                $val2->value = $this->replaceHTMLTags($val->value);
                $newVal[] = $val2;
            }

            return $newVal;
        };
        //
        $modelData = $this->model('matter\enroll\data');
        if (!empty($oApp->voteConfig)) {
            $modelTask = $this->model('matter\enroll\task', $oApp);
        }
        //
        foreach ($rawDatas as &$rawData) {
            /* 获取记录的投票信息 */
            if (!empty($oApp->voteConfig)) {
                if (empty($voteRules)) {
                    $aVoteRules = $modelTask->getVoteRule(null, $rawData->round);
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
                    // 过滤空数据
                    $rawDataVal = $this->getDeepValue($rawData->data, $schemaId, null);
                    if (empty($rawDataVal)) {
                        continue;
                    }

                    /* 协作填写题 */
                    if ($this->getDeepValue($oSchema, 'cowork') === 'Y') {
                        if ($processType === 'coworkDataList') {
                            if ($rawData->schema_id === $oSchema->id) {
                                $item = new \stdClass;
                                $item->id = $rawData->data_id;
                                $item->value = $this->replaceHTMLTags($rawData->value);
                                $this->setDeepValue($processedData, $schemaId, [$item]);
                                unset($rawData->value);
                            } else {
                                $newVal = $processMultitext($rawDataVal);
                                $this->setDeepValue($processedData, $schemaId, $newVal);
                            }
                        } else {
                            $newVal = [];
                            foreach ($rawDataVal as &$val) {
                                $val2 = new \stdClass;
                                $val2->id = $val->id;
                                $val2->value = $this->replaceHTMLTags($val->value);
                                $newVal[] = $val2;
                            }
                            $this->setDeepValue($processedData, $schemaId, $newVal);
                            $aCoworkState[$schemaId] = (object) ['length' => count($newVal)];
                        }
                    } else if ($this->getDeepValue($oSchema, 'type') === 'multitext') {
                        $newVal = $processMultitext($rawDataVal);
                        $this->setDeepValue($processedData, $schemaId, $newVal);
                    } else if ($this->getDeepValue($oSchema, 'type') === 'single') {
                        foreach ($oSchema->ops as $val) {
                            if ($val->v === $rawDataVal) {
                                $this->setDeepValue($processedData, $schemaId, $val->l);
                            }
                        }
                    } else if ($this->getDeepValue($oSchema, 'type') === 'score') {
                        $ops = new \stdClass;
                        foreach ($oSchema->ops as $val) {
                            $ops->{$val->v} = $val;
                        }
                        $newVal = [];
                        foreach ($rawDataVal as $key => $val) {
                            if (empty($ops->{$key}) || !is_object($ops->{$key})) {
                                continue;
                            }
                            $data2 = new \stdClass;
                            $data2->title = $ops->{$key}->l;
                            $data2->score = $val;
                            $data2->v = $ops->{$key}->v;
                            $newVal[] = $data2;
                        }
                        $this->setDeepValue($processedData, $schemaId, $newVal);
                    } else if ($this->getDeepValue($oSchema, 'type') === 'multiple') {
                        $newVal = [];
                        if (!empty($rawDataVal)) {
                            $ops = new \stdClass;
                            foreach ($oSchema->ops as $val) {
                                $ops->{$val->v} = $val->l;
                            }
                            $rawDataVal2 = explode(',', $rawDataVal);
                            foreach ($rawDataVal2 as $val) {
                                $newVal[] = $ops->{$val};
                            }
                        }
                        $this->setDeepValue($processedData, $schemaId, $newVal);
                    } else {
                        $this->setDeepValue($processedData, $schemaId, $rawDataVal);
                    }
                }
                $rawData->data = $processedData;
                if (!empty($recordDirs)) {
                    $rawData->recordDir = $recordDirs;
                }
                if (!empty($aCoworkState)) {
                    $rawData->coworkState = (object) $aCoworkState;
                    // 协作填写题数据总数量
                    $sum = 0;
                    foreach ($aCoworkState as $k => $v) {
                        $sum += (int) $v->length;
                    }
                    $rawData->coworkDataTotal = $sum;
                }
                /* 获取记录的投票信息 */
                if (!empty($aVoteRules)) {
                    $oVoteResult = new \stdClass;
                    foreach ($aVoteRules as $schemaId2 => $oVoteRule) {
                        if ($processType === 'recordList') {
                            $oVoteResult = new \stdClass;
                            if ($this->getDeepValue($oVoteRule->schema, 'cowork') === 'Y') {continue;}
                            $oRecData = $modelData->byRecord($rawData->enroll_key, ['schema' => $schemaId2, 'fields' => 'id,vote_num']);
                            if ($oRecData) {
                                $oVoteResult->{$schemaId2} = $oRecData;
                            }
                        }
                    }
                    $rawData->voteResult = $oVoteResult;
                }
            }
        }

        return $rawDatas;
    }
}