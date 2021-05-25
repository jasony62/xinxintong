<?php

namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
include_once TMS_APP_DIR . '/controllers/_trait/matter/enroll/record.php';
/*
 * 记录活动主控制器
 */
abstract class record_base extends main_base
{
  use \matter\enroll\RecordTrait;
  /**
   * 处理数据
   */
  protected function _processDatas($oApp, &$rawDatas, $processType = 'recordList')
  {
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
    foreach ($rawDatas as $rawData) {
      /* 获取记录的投票信息 */
      if (!empty($oApp->voteConfig)) {
        if (empty($voteRules)) {
          $aVoteRules = $modelTask->getVoteRule(null, $rawData->round);
        }
      }
      $aCoworkState = [];
      if (isset($rawData->data)) {
        $processedData = new \stdClass;
        foreach ($oApp->dynaDataSchemas as $oSchema) {
          $schemaId = $oSchema->id;
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
          } else {
            // 分类目录
            $this->setRecordDir($rawData, $oSchema);
            // id转换成文字
            $newData = $this->translate($rawData, $oSchema);
            if (isset($newData)) {
              // 通讯录的题目（member.name）会变成'member:{}'的形式
              $this->setDeepValue($processedData, $schemaId, $newData);
            }
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
        /* 获取记录的投票信息 */
        if (!empty($aVoteRules)) {
          $oVoteResult = new \stdClass;
          foreach ($aVoteRules as $schemaId2 => $oVoteRule) {
            if ($processType === 'recordList') {
              $oVoteResult = new \stdClass;
              if ($this->getDeepValue($oVoteRule->schema, 'cowork') === 'Y') {
                continue;
              }
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
