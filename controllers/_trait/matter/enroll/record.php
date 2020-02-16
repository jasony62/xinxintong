<?php
namespace matter\enroll;

trait RecordTrait {
    /**
     * 是否在共享页、收藏页可见
     */
    protected function isVisibleInRepos($rawData, $oSchema) {
        /* 清除非共享数据 */
        if (!isset($oSchema->shareable) || $oSchema->shareable !== 'Y') {
            return false;
        }
        $schemaId = $oSchema->id;
        // 过滤空数据
        $rawDataVal = $this->getDeepValue($rawData->data, $schemaId, null);
        if (null === $rawDataVal) {
            return false;
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
                return false;
            }
        }

        return true;
    }
    /**
     * 设置记录的目录数据
     */
    protected function setRecordDir($rawData, $oSchema) {
        //$dirs = [];
        $oRecData = $rawData->data;
        $schemaId = $oSchema->id;
        if ($this->getDeepValue($oSchema, 'asdir') === 'Y' && !empty($oSchema->ops) && !empty($oRecData->{$schemaId})) {
            $val = $oRecData->{$schemaId};
            if (is_string($val)) {
                $val = explode(',', $oRecData->{$schemaId});
            }
            if (!is_array($val)) {
                return false;
            }
            $dirs = array_map(function ($op) {return $op->l;}, array_filter($oSchema->ops, function ($op) use ($val) {return in_array($op->v, $val);}));
            if (empty($dirs)) {
                return false;
            }
            $rawData->recordDir = empty($rawData->recordDir) ? $dirs : array_merge($rawData->recordDir, $dirs);
        }

        return false;
    }
    /**
     * 将引用值翻译为文字
     */
    protected function translate($rawData, $oSchema) {
        $schemaId = $oSchema->id;
        if (empty($rawData->data->{$schemaId})) {
            return null;
        }
        $rawDataVal = $rawData->data->{$schemaId};
        if ($this->getDeepValue($oSchema, 'type') === 'multitext') {
            $newData = [];
            foreach ($rawDataVal as $val) {
                $val2 = new \stdClass;
                $val2->id = $val->id;
                $val2->value = $this->replaceHTMLTags($val->value);
                $newData[] = $val2;
            }
            return $newData;
        } else if ($this->getDeepValue($oSchema, 'type') === 'single') {
            foreach ($oSchema->ops as $val) {
                if ($val->v === $rawDataVal) {
                    return $val->l;
                }
            }
        } else if ($this->getDeepValue($oSchema, 'type') === 'score') {
            $ops = new \stdClass;
            foreach ($oSchema->ops as $val) {
                $ops->{$val->v} = $val->l;
            }
            $newData = [];
            foreach ($rawDataVal as $key => $val) {
                if (!empty($ops->{$key})) {
                    $data2 = new \stdClass;
                    $data2->title = $ops->{$key};
                    $data2->score = $val;
                    $newData[] = $data2;
                }
            }
            return $newData;
        } else if ($this->getDeepValue($oSchema, 'type') === 'multiple') {
            $rawDataVal2 = explode(',', $rawDataVal);
            $ops = new \stdClass;
            foreach ($oSchema->ops as $val) {
                $ops->{$val->v} = $val->l;
            }
            $newData = [];
            foreach ($rawDataVal2 as $val) {
                if (!empty($ops->{$val})) {
                    $newData[] = $ops->{$val};
                }
            }
            return $newData;
        }

        return $rawDataVal;
    }
}