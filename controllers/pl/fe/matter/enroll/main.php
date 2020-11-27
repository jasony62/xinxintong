<?php

namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/**
 * 记录活动主控制器
 */
class main extends main_base
{
  /**
   * 返回指定的记录活动
   */
  public function get_action($app)
  {
    $modelEnl = $this->model('matter\enroll');
    if (false === ($oApp = $modelEnl->byId($app))) {
      return new \ObjectNotFoundError();
    }

    /* channels */
    $oApp->channels = $this->model('matter\channel')->byMatter($oApp->id, 'enroll');
    /* 所属项目 */
    if ($oApp->mission_id) {
      $oApp->mission = $this->model('matter\mission')->byId($oApp->mission_id);
    }
    /* 关联记录活动 */
    if (isset($oApp->entryRule) && $oEntryRule = $oApp->entryRule) {
      if (isset($oEntryRule->member) && is_object($oEntryRule->member)) {
        $modelMs = $this->model('site\user\memberschema');
        foreach ($oEntryRule->member as $msid => $oRule) {
          $oMschema = $modelMs->byId($msid, ['fields' => 'title', 'cascaded' => 'N']);
          if ($oMschema) {
            $oRule->title = $oMschema->title;
          }
        }
      }
      if (isset($oEntryRule->enroll->id)) {
        $oApp->enrollApp = $modelEnl->byId($oEntryRule->enroll->id, ['cascaded' => 'N']);
        if ($oApp->enrollApp) {
          $oEntryRule->enroll->title = $oApp->enrollApp->title;
        }
      }
      /* 指定分组活动用户进入 */
      if (isset($oEntryRule->group->id)) {
        $oRuleApp = $oEntryRule->group;
        $modelGrpTeam = $this->model('matter\group\team');
        $oGroupApp = $this->model('matter\group')->byId($oRuleApp->id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'Y']);
        if ($oGroupApp) {
          $oRuleApp->title = $oGroupApp->title;
          if (!empty($oRuleApp->team->id)) {
            $oGrpTeam = $modelGrpTeam->byId($oRuleApp->team->id, ['fields' => 'title']);
            if ($oGrpTeam) {
              $oRuleApp->team->title = $oGrpTeam->title;
            }
          }
          /* 设置分组题 */
          $this->model('matter\group\schema')->setGroupSchema($oGroupApp);

          $oApp->groupApp = $oGroupApp;
        }
      }
    }

    return new \ResponseData($oApp);
  }
  /**
   * 检查活动的可用性
   */
  public function check_action()
  {
    $oApp = $this->app;
    if (empty($oApp->appRound)) {
      return new \ResponseError('【' . $oApp->title . '】没有可用的填写轮次，请检查');
    }

    return new \ResponseData('ok');
  }
  /**
   * 返回记录活动列表
   *
   * @param string $onlySns 是否仅查询进入规则为仅限关注用户访问的活动列表
   */
  public function list_action($site = null, $mission = null, $page = 1, $size = 30, $scenario = null, $onlySns = 'N', $platform = 'N')
  {
    $oOperator = $this->user;

    $oFilter = $this->getPostJson();

    $modelApp = $this->model('matter\enroll');
    $q = [
      "e.*",
      'xxt_enroll e',
      "state<>0",
    ];
    /* 控制访问权限 */
    $q[2] .= " and (exists(select 1 from xxt_site_admin sa where sa.siteid=e.siteid and uid='{$oOperator->id}') or exists(select 1 from xxt_mission_acl a where a.mission_id=e.mission_id and a.coworker='{$oOperator->id}' and a.state=1 and coworker_role='C' and a.last_invite='Y'))";

    if (!empty($mission)) {
      $q[2] .= " and e.mission_id=" . $mission;
    } else if ($platform === 'Y') {
      $q[2] .= " and e.exists(select 1 from xxt_home_matter where as_global='Y' and matter_type='enroll' and matter_id=e.id)";
    } else if (!empty($site)) {
      $q[2] .= " and e.siteid='" . $site . "'";
    }
    if (!empty($scenario)) {
      $q[2] .= " and e.scenario='" . $scenario . "'";
    }
    if ($onlySns === 'Y') {
      $q[2] .= " and e.entry_rule like '%\"scope.sns\":\"Y\"%'";
    }
    if (!empty($oFilter->byTitle)) {
      $q[2] .= " and e.title like '%" . $oFilter->byTitle . "%'";
    }
    if (!empty($oFilter->byCreator)) {
      $q[2] .= " and e.creater_name like '%" . $oFilter->byCreator . "%'";
    }
    if (!empty($oFilter->byTags)) {
      foreach ($oFilter->byTags as $tag) {
        $q[2] .= " and e.matter_mg_tag like '%" . $tag->id . "%'";
      }
    }
    if (isset($oFilter->byStar) && $oFilter->byStar === 'Y') {
      $q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='enroll' and t.matter_id=e.id and userid='{$oOperator->id}')";
    }

    $q2['o'] = 'e.modify_at desc';
    $q2['r']['o'] = ($page - 1) * $size;
    $q2['r']['l'] = $size;

    $aResult = ['apps' => null, 'total' => 0];

    $apps = $modelApp->query_objs_ss($q, $q2);
    if (count($apps)) {
      foreach ($apps as $oApp) {
        $oApp->type = 'enroll';
        $oApp->url = $modelApp->getEntryUrl($oApp->siteid, $oApp->id);
        $oApp->opData = $modelApp->opData($oApp, true);
        /* 是否已经星标 */
        $qStar = [
          'id',
          'xxt_account_topmatter',
          ['matter_id' => $oApp->id, 'matter_type' => 'enroll', 'userid' => $oOperator->id],
        ];
        if ($oStar = $modelApp->query_obj_ss($qStar)) {
          $oApp->star = $oStar->id;
        }
      }
      $aResult['apps'] = $apps;
    }

    $q[0] = 'count(*)';
    $total = (int) $modelApp->query_val_ss($q);
    $aResult['total'] = $total;

    return new \ResponseData($aResult);
  }
  /**
   * 更新活动的属性信息
   */
  public function update_action()
  {
    $modelApp = $this->model('matter\enroll')->setOnlyWriteDbConn(true);
    $oUser = $this->user;
    $oApp = $this->app;

    $oPosted = $this->getPostJson(false);
    /* 处理数据 */
    $oUpdated = new \stdClass;
    foreach ($oPosted as $prop => $val) {
      switch ($prop) {
        case 'title':
        case 'summary':
          $oUpdated->{$prop} = $modelApp->escape($val);
          break;
        case 'dataSchemas':
          $modelSch = $this->model('matter\enroll\schema');
          $dataSchemas = $modelSch->purify($val);
          $oUpdated->data_schemas = $modelApp->escape($modelApp->toJson($dataSchemas));
          $oApp->dataSchemas = $dataSchemas;
          break;
        case 'entryRule':
          $aScanResult = $modelApp->scanEntryRule($val);
          if (false === $aScanResult[0]) {
            return new \ResponseError($aScanResult[1]);
          }
          $oUpdated->entry_rule = $modelApp->escape($modelApp->toJson($aScanResult[1]));
          break;
        case 'recycle_schemas':
          $oUpdated->recycle_schemas = $modelApp->escape($modelApp->toJson($val));
          break;
        case 'roundCron':
          $rst = $this->model('matter\enroll\round')->checkCron($val);
          if ($rst[0] === false) {
            return new \ResponseError($rst[1]);
          }
          $oUpdated->round_cron = $modelApp->escape($modelApp->toJson($val));
          $oApp->roundCron = $val;
          break;
        case 'actionRule':
          $oUpdated->action_rule = $modelApp->escape($modelApp->toJson($val));
          break;
        case 'assignedNickname':
          $oAssignedNickname = $val;
          if (empty($oAssignedNickname->schema)) {
            $oUpdated->assigned_nickname = '';
          } else {
            $oUpdated->assigned_nickname = $modelApp->escape($modelApp->toJson($oAssignedNickname));
          }
          break;
        case 'scenarioConfig':
          $oUpdated->scenario_config = $modelApp->escape($modelApp->toJson($val));
          break;
        case 'notifyConfig':
          $oPurifyResult = $modelApp->purifyNoticeConfig($oApp, $val);
          if (false === $oPurifyResult[0]) {
            return new \ResponseError($oPurifyResult[1]);
          }
          $oUpdated->notify_config = $modelApp->escape($modelApp->toJson($oPurifyResult[1]));
          $oApp->notifyConfig = $oPurifyResult[1];
          break;
        case 'rpConfig':
          $oUpdated->rp_config = $modelApp->escape($modelApp->toJson($val));
          break;
        case 'reposConfig':
          $oUpdated->repos_config = $modelApp->escape($modelApp->toJson($val));
          break;
        case 'rankConfig':
          $oUpdated->rank_config = $modelApp->escape($modelApp->toJson($val));
          break;
        default:
          $oUpdated->{$prop} = $val;
      }
    }

    if ($oApp = $modelApp->modify($oUser, $oApp, $oUpdated)) {
      // 记录操作日志并更新信息
      $this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'U', $oUpdated);
      /* 清除数据 */
      $uselessProps = ['data_schemas', 'round_cron', 'assigned_nickname'];
      array_walk($uselessProps, function ($prop) use ($oApp) {
        unset($oApp->{$prop});
      });
      /* 更新关联的定时任务 */
      if (isset($oUpdated->round_cron)) {
        $this->model('matter\timer')->updateByRoundCron($oApp);
        if (!empty($oApp->roundCron)) {
          $modelRnd = $this->model('matter\enroll\round');
          foreach ($oApp->roundCron as $rc) {
            $rules[0] = $rc;
            $rc->case = $modelRnd->sampleByCron($rules);
          }
        }
      }
    }

    return new \ResponseData($oApp);
  }
  /**
   * 更新记录的投票规则
   */
  public function updateVoteConfig_action()
  {
    $oPosted = $this->getPostJson(false);
    $method = $this->getDeepValue($oPosted, 'method');
    if (empty($method)) {
      return new \ParameterError('（1）参数不完整');
    }
    $oVoteConfig = $this->getDeepValue($oPosted, 'data');
    if (empty($oVoteConfig)) {
      return new \ParameterError('（2）参数不完整');
    }

    $modelApp = $this->model('matter\enroll');
    $oApp = $this->app;
    $aAllVoteConfigs = $oApp->voteConfig;

    switch ($method) {
      case 'save':
        $oVoteConfig = $this->model('matter\enroll\task', $oApp)->purifyVote($oVoteConfig);
        if (empty($oVoteConfig->id)) {
          $oVoteConfig->id = uniqid();
          $aAllVoteConfigs[] = $oVoteConfig;
        } else {
          $bExistent = false;
          foreach ($aAllVoteConfigs as $index => $oBefore) {
            if ($oBefore->id === $oVoteConfig->id) {
              $aAllVoteConfigs[$index] = $oVoteConfig;
              $bExistent = true;
              break;
            }
          }
          if (false === $bExistent) {
            return new \ObjectNotFoundError('（4）更新的规则不存在');
          }
        }
        break;
      case 'delete':
        $bExistent = false;
        foreach ($aAllVoteConfigs as $index => $oBefore) {
          if ($oBefore->id === $oVoteConfig->id) {
            array_splice($aAllVoteConfigs, $index, 1);
            $bExistent = true;
            break;
          }
        }
        if (false === $bExistent) {
          return new \ObjectNotFoundError('（5）删除的规则不存在');
        }
        break;
    }

    $modelApp->modify($this->user, $oApp, (object) ['vote_config' => $modelApp->escape($modelApp->toJson($aAllVoteConfigs))], ['id' => $oApp->id]);
    if ($method === 'save') {
      return new \ResponseData($oVoteConfig);
    } else {
      return new \ResponseData('ok');
    }
  }
  /**
   * 更新记录的打分规则
   */
  public function updateScoreConfig_action()
  {
    $oPosted = $this->getPostJson(false);
    $method = $this->getDeepValue($oPosted, 'method');
    if (empty($method)) {
      return new \ParameterError('（1）参数不完整');
    }
    $oScoreConfig = $this->getDeepValue($oPosted, 'data');
    if (empty($oScoreConfig)) {
      return new \ParameterError('（2）参数不完整');
    }
    if (empty($oScoreConfig->scoreApp->id)) {
      return new \ParameterError('（3）参数不完整');
    }
    $modelApp = $this->model('matter\enroll');
    $oScoreApp = $modelApp->byId($oScoreConfig->scoreApp->id, ['fields' => 'id,state,data_schemas']);
    if (false === $oScoreApp || $oScoreApp->state !== '1') {
      return new \ObjectNotFoundError('（4）打分活动不存在或不可用');
    }
    $aScoreSchemas = $this->model('matter\enroll\schema')->asAssoc($oScoreApp->dataSchemas);

    $oSourceApp = $this->app;
    $aAllScoreConfigs = $oSourceApp->scoreConfig;
    $aSourceSchemas = $this->model('matter\enroll\schema')->asAssoc($oSourceApp->dataSchemas);

    /* 记录修改的题目 */
    $aUpdatedSourceSchemas = [];
    $aUpdatedScoreSchemas = [];

    /* 删除题目间的关联 */
    $fnUnlinkSchema = function ($schemaIds) use ($oSourceApp, $aSourceSchemas, $aScoreSchemas, &$aUpdatedSourceSchemas, &$aUpdatedScoreSchemas) {
      foreach ($schemaIds as $schemaId) {
        if (isset($aSourceSchemas[$schemaId]->scoreApp)) {
          $oSourceSchemaScoreApp = $aSourceSchemas[$schemaId]->scoreApp;
          if (isset($oSourceSchemaScoreApp->schema->id)) {
            $scoreSchemaId = $oSourceSchemaScoreApp->schema->id;
            if (isset($aScoreSchemas[$scoreSchemaId]->dsSchema)) {
              $oScoreSchemaDsSchema = $aScoreSchemas[$scoreSchemaId]->dsSchema;
              if ($this->getDeepValue($oScoreSchemaDsSchema, 'app.id') === $oSourceApp->id && $this->getDeepValue($oScoreSchemaDsSchema, 'schema.id') === $schemaId) {
                unset($aScoreSchemas[$scoreSchemaId]->dsSchema);
                $aUpdatedScoreSchemas[$scoreSchemaId] = $aScoreSchemas[$scoreSchemaId];
              }
            }
          }
          unset($aSourceSchemas[$schemaId]->scoreApp);
          $aUpdatedSourceSchemas[$schemaId] = $aSourceSchemas[$schemaId];
        }
      }
    };

    switch ($method) {
      case 'save':
        if (empty($oScoreConfig->id)) {
          $oScoreConfig->id = uniqid();
          $aAllScoreConfigs[] = $oScoreConfig;
        } else {
          $bExistent = false;
          foreach ($aAllScoreConfigs as $index => $oBefore) {
            if ($oBefore->id === $oScoreConfig->id) {
              $aAllScoreConfigs[$index] = $oScoreConfig;
              $bExistent = true;
              break;
            }
          }
          if (false === $bExistent) {
            return new \ObjectNotFoundError('（6）更新的规则不存在');
          }
          $removedSchemaIds = array_diff($oBefore->schemas, $oScoreConfig->schemas);
          if (!empty($removedSchemaIds)) {
            $fnUnlinkSchema($removedSchemaIds);
          }
        }
        break;
      case 'delete':
        $bExistent = false;
        foreach ($aAllScoreConfigs as $index => $oBefore) {
          if ($oBefore->id === $oScoreConfig->id) {
            array_splice($aAllScoreConfigs, $index, 1);
            $bExistent = true;
            break;
          }
        }
        if (false === $bExistent) {
          return new \ObjectNotFoundError('（7）删除的规则不存在');
        }
        if (count($oBefore->schemas)) {
          $fnUnlinkSchema($oBefore->schemas);
        }
        break;
    }

    $oUpdated = new \stdClass;
    $oUpdated->score_config = $modelApp->escape($modelApp->toJson($aAllScoreConfigs));
    if (count($aUpdatedSourceSchemas)) {
      $oUpdated->data_schemas = $modelApp->escape($modelApp->toJson($oSourceApp->dataSchemas));
    }
    $modelApp->modify($this->user, $oSourceApp, $oUpdated, ['id' => $oSourceApp->id]);

    if (count($aUpdatedScoreSchemas)) {
      $modelApp->modify($oUser, $oScoreApp, (object) ['data_schemas' => $modelApp->escape($modelApp->toJson($oScoreApp->dataSchemas))], ['id' => $oScoreApp->id]);
    }

    return new \ResponseData(['config' => $oScoreConfig, 'updatedSchemas' => $aUpdatedSourceSchemas]);
  }
  /**
   * 更新提问规则
   */
  public function updateQuestionConfig_action()
  {
    $oPosted = $this->getPostJson(false);
    $method = $this->getDeepValue($oPosted, 'method');
    if (empty($method)) {
      return new \ParameterError('（1）参数不完整');
    }
    $oQuestionConfig = $this->getDeepValue($oPosted, 'data');
    if (empty($oQuestionConfig)) {
      return new \ParameterError('（2）参数不完整');
    }

    $oApp = $this->app;
    $aAllQuestionConfigs = $oApp->questionConfig;

    switch ($method) {
      case 'save':
        $oQuestionConfig = $this->model('matter\enroll\task', $oApp)->purifyQuestion($oQuestionConfig);
        if (empty($oQuestionConfig->id)) {
          $oQuestionConfig->id = uniqid();
          $aAllQuestionConfigs[] = $oQuestionConfig;
        } else {
          $bExistent = false;
          foreach ($aAllQuestionConfigs as $index => $oBefore) {
            if ($oBefore->id === $oQuestionConfig->id) {
              $aAllQuestionConfigs[$index] = $oQuestionConfig;
              $bExistent = true;
              break;
            }
          }
          if (false === $bExistent) {
            return new \ObjectNotFoundError('（4）更新的规则不存在');
          }
        }
        break;
      case 'delete':
        $bExistent = false;
        foreach ($aAllQuestionConfigs as $index => $oBefore) {
          if ($oBefore->id === $oQuestionConfig->id) {
            array_splice($aAllQuestionConfigs, $index, 1);
            $bExistent = true;
            break;
          }
        }
        if (false === $bExistent) {
          return new \ObjectNotFoundError('（5）删除的规则不存在');
        }
        break;
    }

    $modelApp = $this->model('matter\enroll');
    $modelApp->modify($this->user, $oApp, (object) ['question_config' => $modelApp->escape($modelApp->toJson($aAllQuestionConfigs))], ['id' => $oApp->id]);
    if ($method === 'save') {
      return new \ResponseData($oQuestionConfig);
    } else {
      return new \ResponseData('ok');
    }
  }
  /**
   * 更新记录的投票规则
   */
  public function updateAnswerConfig_action()
  {
    $oPosted = $this->getPostJson(false);
    $method = $this->getDeepValue($oPosted, 'method');
    if (empty($method)) {
      return new \ParameterError('（1）参数不完整');
    }
    $oAnswerConfig = $this->getDeepValue($oPosted, 'data');
    if (empty($oAnswerConfig)) {
      return new \ParameterError('（2）参数不完整');
    }

    $oApp = $this->app;
    $aAllAnswerConfigs = $oApp->answerConfig;

    switch ($method) {
      case 'save':
        $oAnswerConfig = $this->model('matter\enroll\task', $oApp)->purifyAnswer($oAnswerConfig);
        if (empty($oAnswerConfig->id)) {
          $oAnswerConfig->id = uniqid();
          $aAllAnswerConfigs[] = $oAnswerConfig;
        } else {
          $bExistent = false;
          foreach ($aAllAnswerConfigs as $index => $oBefore) {
            if ($oBefore->id === $oAnswerConfig->id) {
              $aAllAnswerConfigs[$index] = $oAnswerConfig;
              $bExistent = true;
              break;
            }
          }
          if (false === $bExistent) {
            return new \ObjectNotFoundError('（4）更新的规则不存在');
          }
        }
        break;
      case 'delete':
        $bExistent = false;
        foreach ($aAllAnswerConfigs as $index => $oBefore) {
          if ($oBefore->id === $oAnswerConfig->id) {
            array_splice($aAllAnswerConfigs, $index, 1);
            $bExistent = true;
            break;
          }
        }
        if (false === $bExistent) {
          return new \ObjectNotFoundError('（5）删除的规则不存在');
        }
        break;
    }

    $modelApp = $this->model('matter\enroll');
    $modelApp->modify($this->user, $oApp, (object) ['answer_config' => $modelApp->escape($modelApp->toJson($aAllAnswerConfigs))], ['id' => $oApp->id]);
    if ($method === 'save') {
      return new \ResponseData($oAnswerConfig);
    } else {
      return new \ResponseData('ok');
    }
  }
  /**
   * 更新设定目标规则
   */
  public function updateBaselineConfig_action()
  {
    $oPosted = $this->getPostJson(false);
    $method = $this->getDeepValue($oPosted, 'method');
    if (empty($method)) {
      return new \ParameterError('（1）参数不完整');
    }
    $oBaselineConfig = $this->getDeepValue($oPosted, 'data');
    if (empty($oBaselineConfig)) {
      return new \ParameterError('（2）参数不完整');
    }

    $oApp = $this->app;
    $aAllBaselineConfigs = $oApp->baselineConfig;

    switch ($method) {
      case 'save':
        $oBaselineConfig = $this->model('matter\enroll\task', $oApp)->purifyBaseline($oBaselineConfig);
        if (empty($oBaselineConfig->id)) {
          $oBaselineConfig->id = uniqid();
          $aAllBaselineConfigs[] = $oBaselineConfig;
        } else {
          $bExistent = false;
          foreach ($aAllBaselineConfigs as $index => $oBefore) {
            if ($oBefore->id === $oBaselineConfig->id) {
              $aAllBaselineConfigs[$index] = $oBaselineConfig;
              $bExistent = true;
              break;
            }
          }
          if (false === $bExistent) {
            return new \ObjectNotFoundError('（4）更新的规则不存在');
          }
        }
        break;
      case 'delete':
        $bExistent = false;
        foreach ($aAllBaselineConfigs as $index => $oBefore) {
          if ($oBefore->id === $oBaselineConfig->id) {
            array_splice($aAllBaselineConfigs, $index, 1);
            $bExistent = true;
            break;
          }
        }
        if (false === $bExistent) {
          return new \ObjectNotFoundError('（5）删除的规则不存在');
        }
        break;
    }

    $modelApp = $this->model('matter\enroll');
    $modelApp->modify($this->user, $oApp, (object) ['baseline_config' => $modelApp->escape($modelApp->toJson($aAllBaselineConfigs))], ['id' => $oApp->id]);
    if ($method === 'save') {
      return new \ResponseData($oBaselineConfig);
    } else {
      return new \ResponseData('ok');
    }
  }
  /**
   * 更新记录转发规则
   */
  public function updateTransmitConfig_action()
  {
    $oPosted = $this->getPostJson(false);
    $method = $this->getDeepValue($oPosted, 'method');
    if (empty($method)) {
      return new \ParameterError('（1）参数不完整');
    }
    $oTransmitConfig = $this->getDeepValue($oPosted, 'data');
    if (empty($oTransmitConfig)) {
      return new \ParameterError('（2）参数不完整');
    }

    $oApp = $this->app;
    $aAllTransmitConfigs = $oApp->transmitConfig;

    switch ($method) {
      case 'save':
        if (empty($oTransmitConfig->id)) {
          $oTransmitConfig->id = uniqid();
          $aAllTransmitConfigs[] = $oTransmitConfig;
        } else {
          $bExistent = false;
          foreach ($aAllTransmitConfigs as $index => $oBefore) {
            if ($oBefore->id === $oTransmitConfig->id) {
              $aAllTransmitConfigs[$index] = $oTransmitConfig;
              $bExistent = true;
              break;
            }
          }
          if (false === $bExistent) {
            return new \ObjectNotFoundError('（4）更新的规则不存在');
          }
        }
        break;
      case 'delete':
        $bExistent = false;
        foreach ($aAllTransmitConfigs as $index => $oBefore) {
          if ($oBefore->id === $oTransmitConfig->id) {
            array_splice($aAllTransmitConfigs, $index, 1);
            $bExistent = true;
            break;
          }
        }
        if (false === $bExistent) {
          return new \ObjectNotFoundError('（5）删除的规则不存在');
        }
        break;
    }

    $modelApp = $this->model('matter\enroll');
    $modelApp->modify($this->user, $oApp, (object) ['transmit_config' => $modelApp->escape($modelApp->toJson($aAllTransmitConfigs))], ['id' => $oApp->id]);
    if ($method === 'save') {
      return new \ResponseData($oTransmitConfig);
    } else {
      return new \ResponseData('ok');
    }
  }
  /**
   * 创建题目的id
   *
   */
  protected function getTopicId()
  {
    list($usec, $sec) = explode(" ", microtime());
    $microtime = ((float) $usec) * 1000000;
    $id = 's' . floor($microtime);

    return $id;
  }
  /**
   * 应用的微信二维码
   */
  public function wxQrcode_action()
  {
    $modelQrcode = $this->model('sns\wx\call\qrcode');

    $qrcodes = $modelQrcode->byMatter('enroll', $this->app->id);

    return new \ResponseData($qrcodes);
  }
  /**
   * 删除一个活动
   *
   * 只允许活动的创建者删除数据，其他用户不允许删除
   * 如果没有报名数据，就将活动彻底删除，否则只是打标记
   */
  public function remove_action()
  {
    $modelApp = $this->model('matter\enroll');
    $oUser = $this->user;
    $oApp = $this->app;
    if ($oApp->creater !== $oUser->id) {
      if (!$this->model('site')->isAdmin($oApp->siteid, $oUser->id)) {
        return new \ResponseError('没有删除数据的权限');
      }
      $rst = $modelApp->remove($oUser, $oApp, 'Recycle');
    } else {
      $q = [
        'count(*)',
        'xxt_enroll_record',
        ['aid' => $oApp->id],
      ];
      if ((int) $modelApp->query_val_ss($q) > 0) {
        $rst = $modelApp->remove($oUser, $oApp, 'Recycle');
      } else {
        $modelApp->delete(
          'xxt_enroll_round',
          ["aid" => $oApp->id]
        );
        $modelApp->delete(
          'xxt_code_page',
          "id in (select code_id from xxt_enroll_page where aid='" . $modelApp->escape($oApp->id) . "')"
        );
        $modelApp->delete(
          'xxt_enroll_page',
          ["aid" => $oApp->id]
        );
        $rst = $modelApp->remove($oUser, $oApp, 'D');
      }
    }

    return new \ResponseData($rst);
  }
  /**
   * 登记情况汇总信息
   */
  public function opData_action()
  {
    $modelApp = $this->model('matter\enroll');
    $opData = $modelApp->opData($this->app);

    return new \ResponseData($opData);
  }
}
