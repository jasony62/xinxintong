<?php

namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 *
 */
class group_model extends app_base
{
  /**
   * 记录日志时需要的列
   */
  const LOG_FIELDS = 'siteid,id,title,summary,pic,mission_id';
  /**
   *
   */
  protected function table()
  {
    return 'xxt_group';
  }
  /**
   * 处理从数据库中获得数据
   */
  private function _db2obj(&$oApp, $fields = '*', $cascaded = 'N')
  {
    $oApp->type = 'group';
    if (isset($oApp->siteid) && isset($oApp->id)) {
      $oApp->entryUrl = $this->getEntryUrl($oApp->siteid, $oApp->id);
    }
    if ($cascaded === 'Y') {
      $aTeamOptions = isset($aOptions['team']) ? $aOptions['team'] : [];
      $oApp->teams = $this->model('matter\group\team')->byApp($oApp->id, $aTeamOptions);
    }
    if ($fields === '*' || false !== strpos($fields, 'data_schemas')) {
      if (!empty($oApp->data_schemas)) {
        $oApp->dataSchemas = json_decode($oApp->data_schemas);
      } else {
        $oApp->dataSchemas = [];
      }
      unset($oApp->data_schemas);
    }
    if ($fields === '*' || false !== strpos($fields, 'assigned_nickname')) {
      if (!empty($oApp->assigned_nickname)) {
        $oApp->assignedNickname = json_decode($oApp->assigned_nickname);
      } else {
        $oApp->assignedNickname = new \stdClass;
      }
    }
    if ($fields === '*' || false !== strpos($fields, 'group_rule')) {
      if (!empty($oApp->group_rule)) {
        $oApp->groupRule = json_decode($oApp->group_rule);
      } else {
        $oApp->groupRule = new \stdClass;
      }
    }
    if ($fields === '*' || false !== strpos($fields, 'sync_rule')) {
      if (!empty($oApp->sync_rule)) {
        $oApp->syncRule = json_decode($oApp->sync_rule);
      } else {
        $oApp->syncRule = new \stdClass;
      }
      unset($oApp->sync_rule);
    }
    if (!empty($oApp->matter_mg_tag)) {
      $oApp->matter_mg_tag = json_decode($oApp->matter_mg_tag);
    }

    return $oApp;
  }
  /**
   *
   */
  public function getEntryUrl($siteId, $id)
  {
    if ($siteId === 'platform') {
      $oApp = $this->byId($id, ['cascaded' => 'N', 'fields' => 'id,siteid', 'cascaded' => 'N']);
      if (false === $oApp) {
        return APP_PROTOCOL . APP_HTTP_HOST . '/404.html';
      }
      $siteId = $oApp->siteid;
    }

    $url = APP_PROTOCOL . APP_HTTP_HOST;
    $url .= '/rest/site/fe/matter/group';
    $url .= "?site={$siteId}&app=" . $id;

    return $url;
  }
  /**
   *
   * @param $aid string
   * @param $aOptions array
   */
  public function byId($aid, $aOptions = [])
  {
    $fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
    $cascaded = isset($aOptions['cascaded']) ? $aOptions['cascaded'] : 'Y';
    $q = [
      $fields,
      $this->table(),
      ['id' => $aid],
    ];

    if ($oApp = $this->query_obj_ss($q)) {
      if (!isset($oApp->id)) $oApp->id = $aid;
      $this->_db2obj($oApp, $fields, $cascaded);
    }

    return $oApp;
  }
  /**
   * 返回项目下的分组活动
   */
  public function &byMission($mission, $scenario = null, $page = null, $size = null)
  {
    $result = new \stdClass;

    $q = [
      '*',
      'xxt_group',
      "state=1 and mission_id='$mission'",
    ];
    if (!empty($scenario)) {
      $q[2] .= " and scenario='$scenario'";
    }
    $q2['o'] = 'modify_at desc';
    if ($page && $size) {
      $q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
    }

    $result->apps = $this->query_objs_ss($q, $q2);
    if ($page && $size) {
      $q[0] = 'count(*)';
      $total = (int) $this->query_val_ss($q);
      $result->total = $total;
    } else {
      $result->total = count($result->apps);
    }

    return $result;
  }
  /**
   * 和通讯录关联的分组活动
   */
  public function bySchemaApp($schemaId, $aOptions = [])
  {
    $fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
    $q = [
      $fields,
      'xxt_group',
      ['source_app' => '{"id":"' . $schemaId . '","type":"mschema"}'],
    ];
    if (isset($aOptions['autoSync'])) {
      $q[2]['auto_sync'] = $aOptions['autoSync'];
    }
    $apps = $this->query_objs_ss($q);
    foreach ($apps as $app) {
      $this->_db2obj($app, $fields);
    }

    return $apps;
  }
  /**
   * 更新记录活动标签
   */
  public function updateTags($aid, $tags)
  {
    if (empty($tags)) {
      return false;
    }

    $aOptions = array('fields' => 'tags', 'cascaded' => 'N');
    $app = $this->byId($aid, $aOptions);
    if (empty($app->tags)) {
      $this->update('xxt_group', array('tags' => $tags), "id='$aid'");
    } else {
      $existent = explode(',', $app->tags);
      $checked = explode(',', $tags);
      $updated = array();
      foreach ($checked as $c) {
        if (!in_array($c, $existent)) {
          $updated[] = $c;
        }
      }
      if (count($updated)) {
        $updated = array_merge($existent, $updated);
        $updated = implode(',', $updated);
        $this->update('xxt_group', array('tags' => $updated), "id='$aid'");
      }
    }
    return true;
  }
  /**
   * 进行分组
   */
  public function execute($appId)
  {
    $oApp = $this->model('matter\group')->byId($appId);

    $modelTeam = $this->model('matter\group\team');
    $modelTeam->clean($appId);
    $teams = $modelTeam->byApp($appId);
    if (empty($teams)) {
      return [false, '没有指定分组'];
    }

    $modelGrpRec = \TMS_APP::M('matter\group\record');
    $oResult = $modelGrpRec->byApp($oApp);
    $records = $oResult->records;

    $lenOfRounds = count($teams);
    $lenOfPlayers = count($records);
    $spaceOfRound = ceil($lenOfPlayers / $lenOfRounds);
    $hasSpace = true;
    $current = time();
    $submittedWinners = [];

    while (count($records) && $hasSpace) {
      $hasSpace = false;
      foreach ($teams as &$team) {
        !isset($team->winners) && $team->winners = [];
        is_string($team->targets) && $team->targets = json_decode($team->targets);
        $team->times == 0 && ($team->times = $spaceOfRound);
        if ($team->times > count($team->winners)) {
          $winner4Round = $this->_getWinner4Team($team, $records);
          $winner4Round->team_id = $team->team_id;
          $submittedWinners[] = $winner4Round;
          /*保存结果*/
          $winner = array(
            'team_id' => $team->team_id,
            'team_title' => $this->escape($team->title),
            'draw_at' => $current,
          );
          $modelGrpRec->update('xxt_group_record', $winner, "aid='$appId' and enroll_key='{$winner4Round->enroll_key}'");
          /*轮次是否还可以继续放用户*/
          if ($team->times > count($team->winners)) {
            $hasSpace = true;
          }
        }
        if (count($records) === 0) {
          break;
        }
      }
    }

    return [true, $submittedWinners];
  }
  /**
   *
   */
  private function _getWinner4Team(&$team, &$records)
  {
    $steps = rand(0, 10);
    $matchedPos = $startPos = $steps % count($records);
    $winner = $records[$startPos];

    $target = $team->targets ? $team->targets[count($team->winners) % count($team->targets)] : false;
    if ($target) {
      /* 设置了用户抽取规则 */
      if (count(get_object_vars($target)) > 0) {
        /* 检查是否匹配规则 */
        $matched = $this->_matched($winner, $target);
        while (!$matched) {
          $matchedPos++;
          if ($matchedPos === count($records)) {
            $matchedPos = 0;
          }
          $winner = $records[$matchedPos];
          if ($matchedPos === $startPos) {
            /*比较了所有的候选者，没有匹配的*/
            break;
          } else {
            /*下一个候选者*/
            $matched = $this->_matched($winner, $target);
          }
        }
      }
    }
    $team->winners[] = $winner;

    /* 从候选者中去掉 */
    array_splice($records, $matchedPos, 1);

    return $winner;
  }
  /**
   *
   */
  private function _matched($candidate, $target)
  {
    if (!$candidate) {
      return false;
    }

    if (count(get_object_vars($target)) === 0) {
      return true;
    }

    foreach ($target as $k => $v) {
      if (isset($candidate->data->{$k}) && $candidate->data->{$k} === $v) {
        return true;
      }
    }

    return false;
  }
  /**
   *
   */
  public function &opData($app)
  {
    $aOptions = ['cascade' => 'playerCount'];
    $teams = $this->model('matter\group\team')->byApp($app->id, $aOptions);

    return $teams;
  }
  /**
   * 指定用户的行为报告
   */
  public function reportByUser($oApp, $oUser)
  {
    $modelGrpRec = $this->model('matter\group\record');

    $result = $modelGrpRec->byUser($oApp, $oUser->userid, ['fields' => 'id,team_id,team_title,comment']);

    return $result;
  }
  /**
   * 创建分组活动
   *
   * @param string $site
   * @param string $missioon
   * @param string $scenario
   */
  public function createByConfig($oUser, $oSite, $oCustomConfig, $oMission = null, $scenario = 'split')
  {
    $oNewApp = new \stdClass;
    if (empty($oMission)) {
      $oNewApp->summary = '';
      $oNewApp->pic = $oSite->heading_pic;
      $oNewApp->use_mission_header = 'N';
      $oNewApp->use_mission_footer = 'N';
    } else {
      $oNewApp->summary = $this->escape($oMission->summary);
      $oNewApp->pic = $oMission->pic;
      $oNewApp->mission_id = $oMission->id;
      $oNewApp->use_mission_header = 'Y';
      $oNewApp->use_mission_footer = 'Y';
    }
    /*create app*/
    $oNewApp->siteid = $oSite->id;
    $oNewApp->title = empty($oCustomConfig->proto->title) ? '新分组活动' : $this->escape($oCustomConfig->proto->title);
    $oNewApp->scenario = $scenario;
    $oNewApp->start_at = isset($oCustomConfig->proto->start_at) ? $oCustomConfig->proto->start_at : 0;
    $oNewApp->end_at = isset($oCustomConfig->proto->end_at) ? $oCustomConfig->proto->end_at : 0;
    $oNewApp = $this->create($oUser, $oNewApp);

    /*记录操作日志*/
    $this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');

    /* 指定分组用户名单并导入分组用户 */
    if (isset($oCustomConfig->proto->sourceApp)) {
      $oSourceApp = $oCustomConfig->proto->sourceApp;
      if (!empty($oSourceApp->id) && !empty($oSourceApp->type)) {
        $modelGrpUsr = $this->model('matter\group\record');
        switch ($oSourceApp->type) {
          case 'enroll':
          case 'registration':
            $modelGrpUsr->assocWithEnroll($oNewApp, $oSourceApp->id);
            break;
          case 'signin':
            $modelGrpUsr->assocWithSignin($oNewApp, $oSourceApp->id);
            break;
          case 'mschema':
            $modelGrpUsr->assocWithMschema($oNewApp, $oSourceApp->id);
            break;
        }
      }
    }

    return $oNewApp;
  }
}
