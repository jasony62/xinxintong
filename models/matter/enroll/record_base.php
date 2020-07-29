<?php

namespace matter\enroll;

require_once dirname(__FILE__) . '/entity.php';

/**
 * 记录活动记录
 */
abstract class record_base extends entity_model
{
  /**
   * 生成活动记录的key
   */
  public function genKey($siteId, $aid)
  {
    return md5(uniqid() . $siteId . $aid);
  }
  /**
   * 根据题目获得在记录中的值
   */
  public function getValueBySchema($oSchema, $oData)
  {
    $schemaId = $oSchema->id;
    if (strpos($schemaId, 'member.') === 0) {
      $schemaId = explode('.', $schemaId);
      if (count($schemaId) === 2) {
        $schemaId = $schemaId[1];
        if (isset($oData->member->{$schemaId})) {
          $value = $oData->member->{$schemaId};
        }
      }
    } else {
      $value = empty($oData->{$schemaId}) ? '' : $oData->{$schemaId};
    }

    return isset($value) ? $value : '';
  }
  /**
   * 记录提交用户
   */
  public function getRecordUser($oRecord)
  {
    $oUser = new \stdClass;
    $oUser->userid = isset($oRecord->userid) ? $oRecord->userid : '';
    $oUser->nickname = isset($oRecord->nickname) ? $oRecord->nickname : '';
    $oUser->group_id = isset($oRecord->group_id) ? $oRecord->group_id : '';

    return $oUser;
  }
  /**
   * 将单选题和多选题的选择结果统一用数组方式返回
   * 单选题是和选项id对应的字符串，多选题是选项id为key布尔型为值的对象
   * 
   * @return Array 包含选项id的数组或false
   */
  public function getDataOpsArray($dataValue)
  {
    if (empty($dataValue)) return false;

    if (is_object($dataValue)) {
      $dataops = array_filter(get_object_vars($dataValue), function ($v) {
        return $v === true;
      });
      $dataops = array_keys($dataops);
    } else if (is_string($dataValue)) {
      $dataops = [$dataValue];
    } else {
      $dataops = false;
    }

    return $dataops;
  }
}
