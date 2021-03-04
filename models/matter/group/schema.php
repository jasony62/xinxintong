<?php

namespace matter\group;

require_once dirname(dirname(__FILE__)) . '/enroll/schema.php';
/**
 *
 */
class schema_model extends \matter\enroll\schema_model
{
  /**
   * 设置主分组题
   */
  public function setGroupSchema(&$oApp)
  {
    $oGrpSchema = new \stdClass;
    $oGrpSchema->id = '_round_id';
    $oGrpSchema->type = 'single';
    $oGrpSchema->title = '分组名称';

    $modelGrpTeam = $this->model('matter\group\team');
    $oGrpSchema->ops = $modelGrpTeam->byApp($oApp->id, ['fields' => 'team_id v,title l', 'team_type' => 'T']);

    $oApp->dataSchemas = empty($oApp->dataSchemas) ? [$oGrpSchema] : array_merge([$oGrpSchema], $oApp->dataSchemas);

    return $oGrpSchema;
  }
}
