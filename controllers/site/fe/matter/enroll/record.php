<?php

namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 记录活动记录
 */
class record extends base
{
  /**
   * 在调用每个控制器的方法前调用
   */
  public function tmsBeforeEach($app = null, $task = null)
  {
    // 活动任务
    if (!empty($task)) {
      $modelTsk = $this->model('matter\enroll\task', null);
      $oTask = $modelTsk->byId($task);
      if (false === $oTask) {
        return [false, new \ObjectNotFoundError('指定的活动任务不存在')];
      }
      $this->task = $oTask;
    }
    // 记录活动基本信息
    if (!empty($app)) {
      // 记录活动
      $aOptions = ['cascaded' => 'N'];
      if (isset($oTask)) {
        $aOptions['task'] = $oTask;
      }
      $modelApp = $this->model('matter\enroll');
      $oApp = $modelApp->byId($app, $aOptions);
      if (false === $oApp || $oApp->state !== '1') {
        return [false, new \ObjectNotFoundError('指定的记录活动不存在')];
      }
      $this->app = $oApp;
    }

    return [true];
  }
  /**
   * 指定需要作为事物管理的方法
   */
  public function tmsRequireTransaction()
  {
    return [
      'submit',
    ];
  }
  /**
   * 提交记录
   *
   * @param string $app
   * @param string $rid 指定在哪一个轮次上提交（仅限新建的情况）
   * @param string $ek enrollKey 如果要更新之前已经提交的数据，需要指定
   * @param string $submitkey 支持文件分段上传
   *
   */
  public function submit_action($rid = '', $ek = null, $submitkey = '')
  {
    $modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);

    $bSubmitNewRecord = empty($ek); // 是否为新记录
    $bSubmitSavedRecord = false; // 提交保存过的记录

    if (!$bSubmitNewRecord) {
      $oBeforeRecord = $modelRec->byId($ek, ['state' => ['1', '99']]);
      if (false === $oBeforeRecord) {
        return new \ObjectNotFoundError('（3）指定的填写记录不存在');
      }
      if ($oBeforeRecord->state === '99') {
        /* 将之前保存的记录作为提交记录 */
        $modelRec->update('xxt_enroll_record', ['state' => '1'], ['enroll_key' => $ek]);
        $oBeforeRecord->state = '1';
        $bSubmitSavedRecord = true;
      }
      $rid = $oBeforeRecord->rid;
    }

    $oEnlApp = $this->app;
    // 检查或获得提交轮次
    $aResultSubmitRid = $this->_getSubmitRecordRid($oEnlApp, $rid);
    if (false === $aResultSubmitRid[0]) {
      return new \ResponseError($aResultSubmitRid[1]);
    }
    $rid = $aResultSubmitRid[1];

    // 提交的数据
    $oPosted = $this->getPostJson(false);
    if (empty($oPosted->data) || count(get_object_vars($oPosted->data)) === 0) {
      return new \ResponseError('（4）没有提交有效数据');
    }
    $oEnlData = $oPosted->data;

    // 提交数据的用户
    $oUser = $this->getUser($oEnlApp, $oEnlData);

    // 将数据保存在日志中
    $modelLog = $this->model('log');
    $logid = $modelLog->log($oUser->uid, 'enroll:' . $oEnlApp->id . '.record.submit', $this->escape($modelLog->toJson($oEnlData)));

    /* 检查是否允许提交记录 */
    $aResultCanSubmit = $this->_canSubmit($oEnlApp, $oUser, $oEnlData, $ek, $rid);
    if ($aResultCanSubmit[0] === false) {
      $modelLog->setResult($logid, $aResultCanSubmit[1]);
      return new \ResponseError($aResultCanSubmit[1]);
    }
    /* 检查是否存在匹配的记录活动记录 */
    if (!empty($oEnlApp->entryRule->enroll->id)) {
      $aMatchResult = $this->_matchEnlRec($oUser, $oEnlApp, $oEnlApp->entryRule->enroll->id, $oEnlData);
      if (false === $aMatchResult[0]) {

        $modelLog->setResult($logid, $aMatchResult[1]);
        return new \ParameterError($aMatchResult[1]);
      }
      $oMatchedEnlRec = $aMatchResult[1];
    }
    /* 检查是否存在匹配的分组活动记录 */
    if (isset($oEnlApp->entryRule->group->id)) {
      $modelGrpUsr = $this->model('matter\group\record');
      $aMatchResult = $modelGrpUsr->matchByData($oEnlApp->entryRule->group->id, $oEnlApp, $oEnlData, $oUser);
      if (false === $aMatchResult[0]) {
        if (isset($aMatchResult[2])) {
          /*返回了需要进行匹配的数据*/
          if ($this->getDeepValue($oEnlApp->assignedNickname, 'valid') === 'Y') {
            $nicknameSchemaId = $this->getDeepValue($oEnlApp->assignedNickname, 'schema.id');
            $checkData = $aMatchResult[2];
            if (!empty($nicknameSchemaId) && isset($oEnlData->{$nicknameSchemaId}) && isset($checkData->{$nicknameSchemaId}) && $oEnlData->{$nicknameSchemaId} === $checkData->{$nicknameSchemaId}) {
              $sameNameUsrs = $modelGrpUsr->byData($oEnlApp->entryRule->group, (object)[$nicknameSchemaId => $oEnlData->{$nicknameSchemaId}]);
              if (count($sameNameUsrs) === 1) {
                /* 存在唯一的同名用户，检查不匹配数据并提示 */
                $aSchemasById = [];
                foreach ($oEnlApp->dynaDataSchemas as $schema) {
                  $aSchemasById[$schema->id] = $schema;
                }
                $sameNameUsr = $sameNameUsrs[0];
                $aDiffData = [];
                foreach ($checkData as $schemaId => $val) {
                  if ($schemaId === '_round_id') {
                    $sameNameVal = $this->getDeepValue($sameNameUsr, 'team_id');
                  } else {
                    $sameNameVal = $this->getDeepValue($sameNameUsr, 'data.' . $schemaId);
                  }
                  if ($sameNameVal !== $val) {
                    if (isset($aSchemasById[$schemaId])) {
                      $aDiffData[$aSchemasById[$schemaId]->title] = $sameNameVal;
                    }
                  }
                }
                $modelLog->setResult($logid, [$aMatchResult[1], $aDiffData]);
                return new \ParameterError($aMatchResult[1] . '。请检查题目【' . implode(',', array_keys($aDiffData)) . '】是否填写正确', $aDiffData);
              }
            }
          }
        }
        $modelLog->setResult($logid, $aMatchResult);
        return new \ParameterError($aMatchResult[1]);
      }
      $oMatchedGrpRec = $aMatchResult[1];
    }
    /**
     * 提交记录数据
     */
    $aUpdatedEnlRec = [];
    if ($bSubmitNewRecord) {
      /* 插入记录数据 */
      $oNewRec = $modelRec->enroll($oEnlApp, $oUser, ['nickname' => $oUser->nickname, 'assignedRid' => $rid, 'state' => '1']);
      $ek = $oNewRec->enroll_key;
      /* 处理自定义信息 */
      $aResultSetData = $modelRec->setData($oUser, $oEnlApp, $ek, $oEnlData, $submitkey, true);
    } else {
      /* 重新插入新提交的数据 */
      $aResultSetData = $modelRec->setData($oUser, $oEnlApp, $ek, $oEnlData, $submitkey);
      if ($aResultSetData[0] === true) {
        /* 已经记录，更新原先提交的数据，只要进行更新操作就设置为未审核通过的状态 */
        $aUpdatedEnlRec['enroll_at'] = time();
        if ($oBeforeRecord->userid === $oUser->uid) {
          $aUpdatedEnlRec['group_id'] = empty($oUser->group_id) ? '' : $oUser->group_id;
          $aUpdatedEnlRec['nickname'] = $modelRec->escape($oUser->nickname);
        }
        $aUpdatedEnlRec['verified'] = 'N';
      }
    }
    if (false === $aResultSetData[0]) {
      $modelLog->setResult($logid, $aResultSetData[1]);
      return new \ResponseError($aResultSetData[1]);
    }
    /**
     * 提交补充说明
     */
    if (isset($oPosted->supplement) && count(get_object_vars($oPosted->supplement))) {
      $modelRec->setSupplement($oUser, $oEnlApp, $ek, $oPosted->supplement);
    }
    /**
     * 关联记录
     */
    if (isset($oMatchedEnlRec)) {
      $aUpdatedEnlRec['matched_enroll_key'] = $oMatchedEnlRec->enroll_key;
    }
    if (isset($oMatchedGrpRec)) {
      $aUpdatedEnlRec['group_enroll_key'] = $oMatchedGrpRec->enroll_key;
    }
    if (count($aUpdatedEnlRec)) {
      $modelRec->update(
        'xxt_enroll_record',
        $aUpdatedEnlRec,
        ['enroll_key' => $ek]
      );
    }
    $oRecord = $modelRec->byId($ek);
    /**
     * 如果存在提问任务，将记录放到任务专题中
     */
    if (isset($this->task)) {
      $oTask = $this->task;
      switch ($oTask->config_type) {
        case 'question': // 提问任务
          $modelTop = $this->model('matter\enroll\topic', $oEnlApp);
          if ($oTopic = $modelTop->byTask($oTask)) {
            $modelTop->assign($oTopic, $oRecord);
          }
          break;
      }
    }
    // 完成提交，清除临时日志
    $modelLog->remove($logid);
    /**
     * 创建后台任务
     */
    $modelDaemon = $this->model('matter\enroll\daemon\record');
    $params = new \stdClass;
    $params->isNewRecord = $bSubmitSavedRecord || $bSubmitNewRecord; // 是否提交新记录
    $modelDaemon->create($oEnlApp->id, $oRecord->rid, $oRecord->id, $params, $oUser->uid);
    if (!ASYNC_DAEMON_TASKS) {
      $modelDaemon->exec();
    }

    return new \ResponseData($oRecord);
  }
  /**
   * 检查是否存在匹配的记录
   *
   * 只读项不检查
   */
  private function _matchEnlRec($oUser, $oSrcApp, $targetAppId, &$oEnlData)
  {
    $oMatchApp = $this->model('matter\enroll')->byId($targetAppId, ['cascaded' => 'N']);
    if (empty($oMatchApp)) {
      return [false, '指定的记录匹配记录活动不存在'];
    }
    $modelRec = $this->model('matter\enroll\record');

    /* 获得要检查的记录项 */
    $countRequireCheckedData = 0;
    $requireCheckedData = new \stdClass;
    $dataSchemas = $oSrcApp->dynaDataSchemas;
    foreach ($dataSchemas as $oSchema) {
      if ($this->getDeepValue($oSchema, 'readonly') !== 'Y') {
        if ($this->getDeepValue($oSchema, 'requireCheck') === 'Y') {
          if ($this->getDeepValue($oSchema, 'fromApp') === $oMatchApp->id) {
            $countRequireCheckedData++;
            $requireCheckedData->{$oSchema->id} = $modelRec->getValueBySchema($oSchema, $oEnlData);
          }
        }
      }
    }
    if ($countRequireCheckedData === 0) {
      return [true, null];
    }
    /* 在指定的记录活动中检查数据 */
    $matchedRecords = $modelRec->byData($oMatchApp, $requireCheckedData);
    if (empty($matchedRecords)) {
      return [false, '未在指定的记录活动［' . $oMatchApp->title . '］中找到与提交数据相匹配的记录'];
    }
    /* 如果匹配的分组数据不唯一，怎么办？ */
    if (count($matchedRecords) > 1) {
      return [false, '在指定的记录活动［' . $oMatchApp->title . '］中找到多条与提交数据相匹配的记录，匹配关系不唯一'];
    }
    $oMatchedEnlRec = $matchedRecords[0];
    if ($oMatchedEnlRec->verified !== 'Y') {
      return [false, '在指定的记录活动［' . $oMatchApp->title . '］中与提交数据匹配的记录未通过验证'];
    }
    /* 如果记录数据中未包含用户信息，更新用户信息 */
    if (empty($oMatchedEnlRec->userid)) {
      $oUserAcnt = new \stdClass;
      $oUserAcnt->userid = $oUser->uid;
      $oUserAcnt->nickname = $modelRec->escape($oUser->nickname);
      $modelRec->update('xxt_enroll_record', $oUserAcnt, ['id' => $oMatchedEnlRec->id]);
    }
    /* 将匹配的记录记录数据作为提交的记录数据的一部分 */
    $oMatchedData = $oMatchedEnlRec->data;
    foreach ($oMatchApp->dynaDataSchemas as $oSchema) {
      if (!isset($oEnlData->{$oSchema->id}) && isset($oMatchedData->{$oSchema->id})) {
        $oEnlData->{$oSchema->id} = $oMatchedData->{$oSchema->id};
      }
    }

    return [true, $oMatchedEnlRec];
  }
  /**
   * 记录记录信息
   *
   * @param string $app
   * @param string $rid 指定在哪一个轮次上提交（仅限新建的情况）
   * @param string $ek enrollKey 如果要更新之前已经提交的数据，需要指定
   * @param string $submitkey 支持文件分段上传
   */
  public function save_action($app, $rid = '', $ek = null, $submitkey = '')
  {
    $modelEnl = $this->model('matter\enroll');
    $oEnlApp = $modelEnl->byId($app, ['cascaded' => 'N']);
    if (false === $oEnlApp || $oEnlApp->state !== '1') {
      return new \ObjectNotFoundError('指定的活动不存在');
    }

    $modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);

    $bSaveNewRecord = empty($ek); // 是否为提交新记录

    if (!$bSaveNewRecord) {
      $oBeforeRecord = $modelRec->byId($ek, ['state' => ['1', '99']]);
      if (false === $oBeforeRecord) {
        return new \ObjectNotFoundError('指定的填写记录不存在');
      }
      if ($oBeforeRecord->state === '1') {
        return new \ResponseError('记录已经提交，不能再进行保存操作');
      }
      $rid = $oBeforeRecord->rid;
    }

    // 保存轮次
    $aResultSaveRid = $this->_getSubmitRecordRid($oEnlApp, $rid);
    if (false === $aResultSaveRid[0]) {
      return new \ResponseError($aResultSaveRid[1]);
    }
    $rid = $aResultSaveRid[1];

    // 保存的数据
    $oPosted = $this->getPostJson();
    if (empty($oPosted->data) || count(get_object_vars($oPosted->data)) === 0) {
      return new \ResponseError('没有保存有效数据');
    }
    $oEnlData = $oPosted->data;

    // 保存数据的用户
    $oUser = $this->getUser($oEnlApp, $oEnlData);

    // 检查是否允许记录
    $aResultCanSubmit = $this->_canSubmit($oEnlApp, $oUser, $oEnlData, $ek, $rid, false);
    if ($aResultCanSubmit[0] === false) {
      return new \ResponseError($aResultCanSubmit[1]);
    }
    /**
     * 保存记录数据
     */
    if ($bSaveNewRecord) {
      /* 插入记录数据 */
      $oNewRec = $modelRec->enroll($oEnlApp, $oUser, ['nickname' => $oUser->nickname, 'assignedRid' => $rid, 'state' => '99']);
      $ek = $oNewRec->enroll_key;
    } else {
      $modelRec->update('xxt_enroll_record', ['enroll_at' => time()], ['enroll_key' => $ek]);
    }
    /* 保存数据 */
    $aResultSetData = $modelRec->setData($oUser, $oEnlApp, $ek, $oEnlData, $submitkey, $bSaveNewRecord);
    if (false === $aResultSetData[0]) {
      return new \ResponseError($aResultSetData[1]);
    }
    /**
     * 保存补充说明
     */
    if (isset($oPosted->supplement) && count(get_object_vars($oPosted->supplement))) {
      $modelRec->setSupplement($oUser, $oEnlApp, $ek, $oPosted->supplement);
    }

    $oRecord = $modelRec->byId($ek);

    $this->model('matter\enroll\event')->saveRecord($oEnlApp, $oRecord, $oUser);

    return new \ResponseData($oRecord);
  }
  /**
   * 返回当前轮次或者检查指定轮次是否有效
   */
  private function _getSubmitRecordRid($oApp, $rid = '')
  {
    $modelRnd = $this->model('matter\enroll\round');
    if (isset($this->task)) {
      $oRecordRnd = $modelRnd->byTask($oApp, $this->task);
    } else if (empty($rid)) {
      $oRecordRnd = $modelRnd->getActive($oApp);
    } else {
      $oRecordRnd = $modelRnd->byId($rid);
    }
    if (empty($oRecordRnd)) {
      return [false, '没有获得有效的活动轮次，请检查是否已经设置轮次，或者轮次是否已经启用'];
    }
    $now = time();
    if ($oRecordRnd->start_at > 0 && $oRecordRnd->start_at > $now) {
      return [false, '活动轮次【' . $oRecordRnd->title . '】还未开始，不能提交、修改、保存或删除填写记录！'];
    }
    if ($oRecordRnd->end_at > 0 && $oRecordRnd->end_at < $now) {
      return [false, '活动轮次【' . $oRecordRnd->title . '】已结束，不能提交、修改、保存或删除填写记录！'];
    }

    return [true, $oRecordRnd->rid];
  }
  /**
   * 活动是否处于开放时间
   */
  private function _appOpened($oApp)
  {
    /**
     * 检查活动是否在进行过程中
     */
    $current = time();
    if (!empty($oApp->start_at) && $oApp->start_at > $current) {
      return [false, '活动没有开始，不允许修改数据'];
    }
    if (!empty($oApp->end_at) && $oApp->end_at < $current) {
      return [false, '活动已经结束，不允许修改数据'];
    }

    return [true];
  }
  /**
   * 检查是否允许用户进行记录
   *
   * 检查内容：
   * 1、应用允许记录的条数（count_limit）
   * 2、记录项是否和已有记录记录重复（schema.unique）
   * 3、多选题选项的数量（schema.limitChoice, schema.range）
   *
   */
  private function _canSubmit($oApp, $oUser, $oRecData, $ek, $rid = '', $bCheckSchema = true)
  {
    /**
     * 检查活动是否在进行过程中
     */
    $openedResult = $this->_appOpened($oApp);
    if ($openedResult[0] === false) {
      return $openedResult;
    }

    if (empty($oApp->entryRule->exclude_action->submit_record) || $oApp->entryRule->exclude_action->submit_record != "Y") {
      $checkEntryRule = $this->checkEntryRule($oApp, false, $oUser);
      if ($checkEntryRule[0] === false) {
        return $checkEntryRule;
      }
    }
    /**
     * 检查提交人是否在白名单中
     */
    $wlGroupId = $this->getDeepValue($oApp->entryRule, 'wl.submit.group');
    if (!empty($wlGroupId)) {
      if (empty($oUser->group_id)) {
        return [false, '不在可修改数据用户白名单中，请与活动管理员联系'];
      } else if ($oUser->group_id != $wlGroupId) {
        return [false, '所在分组【' . $oUser->group_title . '】不在可修改数据用户分组白名单中，请与活动管理员联系'];
      }
    }

    $modelRec = $this->model('matter\enroll\record');
    if (empty($ek)) {
      /**
       * 检查记录数量
       */
      if (isset($oApp->count_limit) && $oApp->count_limit > 0) {
        $records = $modelRec->byUser($oApp, $oUser, ['rid' => $rid]);
        if (count($records) >= $oApp->count_limit) {
          return [false, '已经进行过' . count($records) . '次记录，不允再次记录'];
        }
      }
    } else {
      /**
       * 检查提交人是否记录创建人
       */
      $oRecord = $modelRec->byId($ek, ['fields' => 'userid']);
      if ($this->getDeepValue($oApp->scenarioConfig, 'can_cowork') !== 'Y') {
        if ($oRecord) {
          if ($oRecord->userid !== $oUser->uid) {
            return [false, '不允许修改其他用户提交的数据'];
          }
        } else {
          return [false, '不允许修改其他用户提交的数据'];
        }
      } else {
        //if ($oRecord) {
        //if ($oRecord->userid !== $oUser->uid && $this->getDeepValue($oUser, 'is_editor') !== 'Y') {
        //    return [false, '不允许修改其他用户提交的数据'];
        //}
        //}
      }
    }
    /**
     * 检查提交数据的合法性
     */
    if ($bCheckSchema === true) {
      $modelData = $this->model('matter\enroll\data');
      foreach ($oApp->dynaDataSchemas as $oSchema) {
        if (isset($oSchema->unique) && $oSchema->unique === 'Y') {
          if (empty($oRecData->{$oSchema->id})) {
            return [false, '唯一项【' . $oSchema->title . '】不允许为空'];
          }
          $checked = new \stdClass;
          $checked->{$oSchema->id} = $oRecData->{$oSchema->id};
          $existings = $modelRec->byData($oApp, $checked, ['fields' => 'enroll_key']);
          if (count($existings)) {
            foreach ($existings as $existing) {
              if ($existing->enroll_key !== $ek) {
                return [false, '唯一项【' . $oSchema->title . '】不允许重复，请检查填写的数据'];
              }
            }
          }
        }
        if (isset($oSchema->type)) {
          switch ($oSchema->type) {
            case 'multiple':
              if (isset($oSchema->limitChoice) && $oSchema->limitChoice === 'Y' && isset($oSchema->range) && is_array($oSchema->range)) {
                if (isset($oRecData->{$oSchema->id})) {
                  $submitVal = $oRecData->{$oSchema->id};
                  if (is_object($submitVal)) {
                    // 多选题，将选项合并为逗号分隔的字符串
                    $opCount = count(array_filter((array) $submitVal, function ($i) {
                      return $i;
                    }));
                  } else {
                    $opCount = 0;
                  }
                } else {
                  $opCount = 0;
                }
                if ($opCount > 0 || $oSchema->required === 'Y') {
                  if ($opCount < $oSchema->range[0] || $opCount > $oSchema->range[1]) {
                    return [false, '【' . $oSchema->title . '】中最多只能选择(' . $oSchema->range[1] . ')项，最少需要选择(' . $oSchema->range[0] . ')项'];
                  }
                }
              }
              break;
            case 'voice':
              if (!defined('WX_VOICE_AMR_2_MP3') || WX_VOICE_AMR_2_MP3 !== 'Y') {
                return [false, '运行环境不支持处理微信录音文件，题目【' . $oSchema->title . '】无效'];
              }
              break;
            case 'single':
              if (isset($oSchema->opRecordCnt) && isset($oSchema->opRecordCnt->data)) {
                if (isset($oRecData->{$oSchema->id})) {
                  $submitVal = $oRecData->{$oSchema->id};
                  if (isset($oSchema->opRecordCnt->data->{$submitVal})) {
                    $cntLimit = $oSchema->opRecordCnt->data->{$submitVal};
                    if ($cntLimit > 0) {
                      // 检查已经提交的记录数量
                      $this->logger->info('限制选项填写数量 ' . $submitVal . '/' . $cntLimit);
                      $recordCnt = $modelData->countBySchema($oApp, $oSchema, ['value' => $submitVal, 'excludeUserid' => $oUser->uid]);
                      if ($recordCnt->total >= $cntLimit) {
                        $tip = !empty($oSchema->opRecordCnt->tip) ? $oSchema->opRecordCnt->tip : '题目【' . $oSchema->title . '】已经达到填写数量限制';
                        return [false, $tip];
                      }
                      $this->logger->info('限制选项填写数量2 ' . $recordCnt->total);
                    }
                  }
                }
              }
          }
        }
      }
    }

    return [true];
  }
  /**
   * 分段上传文件
   *
   * @param string $app
   * @param string $submitKey
   *
   */
  public function uploadFile_action($app, $submitkey = '')
  {
    $modelApp = $this->model('matter\enroll');
    $oApp = $modelApp->byId($app, ['cascaded' => 'N', 'fields' => 'id,siteid,state']);
    if (false === $oApp || $oApp->state !== '1') {
      header("HTTP/1.0 500 Internal Error");
      die('指定的记录活动不存在');
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header("HTTP/1.0 500 Internal Error");
      die('请用POST上传文件，不支持其他方法');
    }

    if (empty($submitkey))
      $submitkey = $this->who->uid;

    $fileData = $_POST;
    /**
     * 分块上传文件
     */
    $dest = '/enroll/' . $oApp->id . '/' . $submitkey . '_' . $fileData['resumableFilename'];
    $oResumable = $this->model('fs/resumable', $oApp->siteid, $dest);
    $aResult = $oResumable->handleRequest($fileData);
    if (true === $aResult[0]) {
      header("HTTP/1.0 200 OK");
      die('ok');
    } else {
      header("HTTP/1.0 500 Internal Error");
      die($aResult[1]);
    }
  }
  /**
   * 返回指定记录或最后一条记录
   *
   * @param string $app
   * @param string $ek
   * @param string $loadLast 如果没有指定ek，是否获取最近一条数据
   * @param string $withSaved 是否获取保存数据
   *
   */
  public function get_action($app, $ek = '', $rid = '', $loadLast = 'Y', $loadAssoc = 'Y', $withSaved = 'N', $task = null)
  {
    $modelApp = $this->model('matter\enroll');
    $modelRec = $this->model('matter\enroll\record');

    $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
    if (false === $oApp || $oApp->state !== '1') {
      return new \ObjectNotFoundError();
    }

    if (!empty($task)) {
      $modelTsk = $this->model('matter\enroll\task');
      $oTask = $modelTsk->byId($task);
      if (false === $oTask) {
        return new \ObjectNotFoundError('指定的活动任务不存在');
      }
    }

    $fields = 'id,aid,state,rid,enroll_key,userid,group_id,nickname,verified,enroll_at,first_enroll_at,data,supplement,score,like_num,like_log,remark_num';
    $ValidRecStates = ['1', '99'];

    if (empty($ek)) {
      $oRecUser = $this->getUser($oApp);
      if ($loadLast === 'Y') {
        if (isset($oTask)) {
          $oTaskRnd = $this->model('matter\enroll\round')->byTask($oApp, $oTask);
          if (empty($oTaskRnd)) {
            return new \ObjectNotFoundError('指定的活动任务轮次不存在');
          }
          $rid = $oTaskRnd->rid;
        }
        $oRecord = $modelRec->lastByUser($oApp, $oRecUser, ['state' => $ValidRecStates, 'rid' => $rid, 'verbose' => 'Y', 'fields' => $fields]);
        if (false === $oRecord) {
          $oRecord = new \stdClass;
          $oRecord->rid = empty($rid) ? $oApp->appRound->rid : $rid;
        }
      } else {
        $oRecord = new \stdClass;
        $oRecord->rid = empty($rid) ? $oApp->appRound->rid : $rid;
      }
    } else {
      $oRecord = $modelRec->byId($ek, ['verbose' => 'Y', 'fields' => $fields]);
      $oRecUser = new \stdClass;
      if (false === $oRecord || !in_array($oRecord->state, $ValidRecStates)) {
        $oRecord = new \stdClass;
      } else {
        // 是否允许其他用户查看
        if ($this->getDeepValue($oApp, 'scenarioConfig.can_result_all') !== 'Y') {
          $oViewer = $this->getUser($oApp);
          if ($oRecord->userid !== $oViewer->uid) {
            return new \ResponseError('不允许查看其他用户的填写记录');
          }
        }
        if (!empty($oRecord->userid)) {
          $oRecUser->uid = $oRecord->userid;
        }
      }
    }

    /* 当前用户在关联活动中填写的数据 */
    if (!empty($oRecUser->uid)) {
      if (!empty($oApp->entryRule->enroll->id)) {
        $oAssocApp = $this->model('matter\enroll')->byId($oApp->entryRule->enroll->id, ['cascaded' => 'N']);
        if ($oAssocApp) {
          $oAssocRec = $modelRec->byUser($oAssocApp, $oRecUser);
          if (count($oAssocRec) === 1) {
            if (!empty($oAssocRec[0]->data)) {
              $oAssocRecData = $oAssocRec[0]->data;
              if (!isset($oRecord->data)) {
                $oRecord->data = new \stdClass;
              }
              foreach ($oAssocRecData as $key => $value) {
                if (!isset($oRecord->data->{$key})) {
                  $oRecord->data->{$key} = $value;
                }
              }
            }
          }
        }
      }
      if (!empty($oApp->entryRule->group->id)) {
        $oGrpApp = $this->model('matter\group')->byId($oApp->entryRule->group->id, ['cascaded' => 'N']);
        if ($oGrpApp) {
          $oGrpUsr = $this->model('matter\group\record')->byUser($oGrpApp, $oRecUser->uid, ['onlyOne' => true, 'fields' => 'team_id,data']);
          if ($oGrpUsr) {
            if (!isset($oRecord->data)) {
              $oRecord->data = new \stdClass;
            }
            $oAssocRecData = $oGrpUsr->data;
            foreach ($oAssocRecData as $k => $v) {
              if (!isset($oRecord->data->{$k})) {
                $oRecord->data->{$k} = $v;
              }
            }
            $oAssocGrpTeamSchema = $this->model('matter\enroll\schema')->getAssocGroupTeamSchema($oApp);
            if ($oAssocGrpTeamSchema) {
              if (!isset($oRecord->data->{$oAssocGrpTeamSchema->id})) {
                $oRecord->data->{$oAssocGrpTeamSchema->id} = $oGrpUsr->team_id;
              }
            }
          }
        }
      }
    }

    if (!empty($oRecord->rid)) {
      $oRecRound = $this->model('matter\enroll\round')->byId($oRecord->rid, ['fields' => 'rid,title,purpose,state,start_at,end_at']);
      $oRecord->round = $oRecRound;
    }

    /* 如果是个空对象，代表没有获得任何有效信息 */
    if (count((array) $oRecord) === 0) {
      return new \ObjectNotFoundError('不存在符合指定条件的填写记录');
    }

    return new \ResponseData($oRecord);
  }
  /**
   * 获得目标值的记录
   * 目标轮次中的记录
   *
   * @param string $app app'id
   * @param string $rid 指定的轮次
   */
  public function baseline_action($app, $rid = '')
  {
    $modelApp = $this->model('matter\enroll');
    $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
    if (false === $oApp || $oApp->state !== '1') {
      return new \ObjectNotFoundError();
    }

    $modelRnd = $this->model('matter\enroll\round');
    $aRndOptions = ['fields' => 'rid,title'];
    if (!empty($rid)) {
      $aRndOptions['assignedRid'] = $rid;
    }
    $oBaselineRnd = $modelRnd->getBaseline($oApp, $aRndOptions);
    if (false === $oBaselineRnd) {
      return new \ResponseData(false);
    }

    $modelRec = $this->model('matter\enroll\record');
    $oBaselineRec = $modelRec->baselineByRound($this->who->uid, $oBaselineRnd);
    if (false === $oBaselineRec) {
      return new \ResponseData(false);
    }
    /* 只有数值题可以有目标值 */
    $oNumberRecData = new \stdClass;
    foreach ($oApp->dynaDataSchemas as $oSchema) {
      if ($oSchema->type === 'shorttext' && $modelRec->getDeepValue($oSchema, 'format') === 'number') {
        $oNumberRecData->{$oSchema->id} = empty($oBaselineRec->data->{$oSchema->id}) ? 0 : $oBaselineRec->data->{$oSchema->id};
      }
    }
    $oBaselineRec->data = $oNumberRecData;

    return new \ResponseData($oBaselineRec);
  }
  /**
   * 记录的概要信息
   */
  public function sketch_action($record)
  {
    $modelRec = $this->model('matter\enroll\record');

    $oSketch = new \stdClass;
    $oRecord = $modelRec->byPlainId($record, ['fields' => 'id,aid,state,enroll_key,agreed,remark_num,like_num,favor_num']);
    if ($oRecord) {
      $modelApp = $this->model('matter\enroll');
      $oApp = $modelApp->byId($oRecord->aid, ['fields' => 'title', 'cascaded' => 'N']);
      $oSketch->raw = $oRecord;
      $oSketch->title = '记录' . $oRecord->id . '|' . $oApp->title;
    }

    return new \ResponseData($oSketch);
  }
  /**
   * 列出所有的记录记录
   *
   * $app
   * $orderby time|remark|score|follower
   * $page
   * $size
   *
   * return
   * [0] 数据列表
   * [1] 数据总条数
   * [2] 数据项的定义
   *
   */
  public function list_action($app, $owner = 'U', $orderby = 'time', $page = 1, $size = 30, $sketch = 'N')
  {
    $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
    if (false === $oApp || $oApp->state !== '1') {
      return new \ObjectNotFoundError();
    }

    $oUser = $this->getUser($oApp);

    // 填写记录过滤条件
    $oCriteria = $this->getPostJson();
    if (empty($oCriteria)) {
      $oCriteria = new \stdClass;
    }

    switch ($owner) {
      case 'A':
        break;
      case 'G':
        $modelUsr = $this->model('matter\enroll\user');
        $options = ['fields' => 'group_id'];
        $oEnrollee = $modelUsr->byIdInApp($oApp, $oUser->uid, $options);
        if ($oEnrollee) {
          !isset($oCriteria->record) && $oCriteria->record = new \stdClass;
          $oCriteria->record->group_id = isset($oEnrollee->group_id) ? $oEnrollee->group_id : '';
        }
        break;
      default:
        !isset($oCriteria->record) && $oCriteria->record = new \stdClass;
        $oCriteria->record->userid = $oUser->uid;
        break;
    }

    $aOptions = [];
    $aOptions['page'] = $page;
    $aOptions['size'] = $size;
    $aOptions['orderby'] = $orderby;
    if ($sketch === 'Y') {
      $aOptions['fields'] = 'id,enroll_key,enroll_at';
    }

    $modelRec = $this->model('matter\enroll\record');

    $oResult = $modelRec->byApp($oApp, $aOptions, $oCriteria);

    return new \ResponseData($oResult);
  }
  /**
   * 点赞记录记录
   *
   * @param string $ek
   *
   */
  public function like_action($ek)
  {
    $modelRec = $this->model('matter\enroll\record');
    $oRecord = $modelRec->byId($ek, ['fields' => 'id,enroll_key,state,aid,rid,userid,group_id,like_log,like_num']);
    if (false === $oRecord || $oRecord->state !== '1') {
      return new \ObjectNotFoundError();
    }

    $oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N']);
    if (false === $oApp || $oApp->state !== '1') {
      return new \ObjectNotFoundError();
    }

    $oUser = $this->getUser($oApp);

    /* 检查是否满足了点赞的前置条件 */
    if (empty($oApp->entryRule->exclude_action->like) || $oApp->entryRule->exclude_action->like != "Y") {
      $checkEntryRule = $this->checkEntryRule($oApp, false, $oUser);
      if ($checkEntryRule[0] === false) {
        return new \ResponseError($checkEntryRule[1]);
      }
    }

    $oLikeLog = $oRecord->like_log;
    if (isset($oLikeLog->{$oUser->uid})) {
      unset($oLikeLog->{$oUser->uid});
      $incLikeNum = -1;
    } else {
      $oLikeLog->{$oUser->uid} = time();
      $incLikeNum = 1;
    }

    $likeNum = $oRecord->like_num + $incLikeNum;
    $modelRec->update(
      'xxt_enroll_record',
      ['like_log' => json_encode($oLikeLog), 'like_num' => $likeNum],
      ['enroll_key' => $oRecord->enroll_key]
    );

    $modelEnlEvt = $this->model('matter\enroll\event');
    if ($incLikeNum > 0) {
      /* 发起点赞 */
      $modelEnlEvt->likeRecord($oApp, $oRecord, $oUser);
    } else {
      /* 撤销发起点赞 */
      $modelEnlEvt->undoLikeRecord($oApp, $oRecord, $oUser);
    }

    $oResult = new \stdClass;
    $oResult->like_log = $oLikeLog;
    $oResult->like_num = $likeNum;

    return new \ResponseData($oResult);
  }
  /**
   * 点踩记录记录
   *
   *
   */
  public function dislike_action($ek)
  {
    $modelRec = $this->model('matter\enroll\record');
    $oRecord = $modelRec->byId($ek, ['fields' => 'id,enroll_key,state,aid,rid,userid,group_id,dislike_log,dislike_num']);
    if (false === $oRecord || $oRecord->state !== '1') {
      return new \ObjectNotFoundError();
    }

    $oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N']);
    if (false === $oApp || $oApp->state !== '1') {
      return new \ObjectNotFoundError();
    }

    $oUser = $this->getUser($oApp);

    /* 检查是否满足了点赞/点踩的前置条件 */
    if (empty($oApp->entryRule->exclude_action->like) || $oApp->entryRule->exclude_action->like != "Y") {
      $checkEntryRule = $this->checkEntryRule($oApp, false, $oUser);
      if ($checkEntryRule[0] === false) {
        return new \ResponseError($checkEntryRule[1]);
      }
    }

    $oDislikeLog = $oRecord->dislike_log;
    if (isset($oDislikeLog->{$oUser->uid})) {
      unset($oDislikeLog->{$oUser->uid});
      $incDislikeNum = -1;
    } else {
      $oDislikeLog->{$oUser->uid} = time();
      $incDislikeNum = 1;
    }

    $dislikeNum = $oRecord->dislike_num + $incDislikeNum;
    $modelRec->update(
      'xxt_enroll_record',
      ['dislike_log' => json_encode($oDislikeLog), 'dislike_num' => $dislikeNum],
      ['enroll_key' => $oRecord->enroll_key]
    );

    $modelEnlEvt = $this->model('matter\enroll\event');
    if ($incDislikeNum > 0) {
      /* 发起反对 */
      $modelEnlEvt->dislikeRecord($oApp, $oRecord, $oUser);
    } else {
      /* 撤销发起反对 */
      $modelEnlEvt->undoDislikeRecord($oApp, $oRecord, $oUser);
    }

    $oResult = new \stdClass;
    $oResult->dislike_log = $oDislikeLog;
    $oResult->dislike_num = $dislikeNum;

    return new \ResponseData($oResult);
  }
  /**
   * 推荐记录记录中
   * 只有组长和超级用户才有权限
   *
   * @param string $ek
   * @param string $value
   *
   */
  public function agree_action($ek, $value = '')
  {
    $modelRec = $this->model('matter\enroll\record');
    $oRecord = $modelRec->byId($ek, ['fields' => 'id,state,aid,rid,enroll_key,userid,group_id,agreed,agreed_log']);
    if (false === $oRecord || $oRecord->state !== '1') {
      return new \ObjectNotFoundError();
    }

    $oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N']);
    if (false === $oApp || $oApp->state !== '1') {
      return new \ObjectNotFoundError();
    }

    if (empty($oApp->entryRule->group->id)) {
      return new \ParameterError('只有进入条件为分组活动的记录活动才允许组长表态');
    }
    $oUser = $this->getUser($oApp);

    $modelGrpUsr = $this->model('matter\group\record');
    /* 当前用户所属分组及角色 */
    $oGrpLeader = $modelGrpUsr->byUser($oApp->entryRule->group, $oUser->uid, ['fields' => 'is_leader,team_id', 'onlyOne' => true]);
    if (false === $oGrpLeader || !in_array($oGrpLeader->is_leader, ['Y', 'S'])) {
      return new \ParameterError('只允许组长进行表态');
    }
    /* 组长只能表态本组用户的数据，或者不属于任何分组的数据 */
    if ($oGrpLeader->is_leader === 'Y') {
      $oGrpMemb = $modelGrpUsr->byUser($oApp->entryRule->group, $oRecord->userid, ['fields' => 'team_id', 'onlyOne' => true]);
      if ($oGrpMemb && !empty($oGrpMemb->team_id)) {
        /* 填写记录的用户属于一个分组 */
        if ($oGrpMemb->team_id !== $oGrpLeader->team_id) {
          return new \ParameterError('只允许组长对本组成员的数据表态');
        }
      } else {
        if (empty($oUser->is_editor) || $oUser->is_editor !== 'Y') {
          return new \ParameterError('只允许编辑组的组长对不属于任何分组的成员的数据表态');
        }
      }
    }

    if (!in_array($value, ['Y', 'N', 'A', 'D'])) {
      $value = '';
    }
    $beforeValue = $oRecord->agreed;
    if ($beforeValue === $value) {
      return new \ParameterError('不能重复设置表态');
    }

    /* 检查推荐数量限制 */
    if ($value === 'Y') {
      if (!empty($oApp->actionRule->leader->record->agree->end)) {
        /* 当前轮次，当前组已经提交的记录数 */
        $oRule = $oApp->actionRule->leader->record->agree->end;
        if (!empty($oRule->max)) {
          $oCriteria = new \stdClass;
          $oCriteria->record = new \stdClass;
          $oCriteria->record->group_id = $oRecord->group_id;
          $oCriteria->record->agreed = 'Y';
          $oResult = $modelRec->byApp($oApp, ['fields' => 'id'], $oCriteria);
          if ((int) $oResult->total >= (int) $oRule->max) {
            $desc = empty($oRule->desc) ? ('每轮次每组最多允许推荐【' . $oRule->max . '条】记录（问题）') : $oRule->desc;
            if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
              $desc .= '，';
            }
            $desc .= '已经推荐【' . $oResult->total . '条】。';
            return new \ResponseError($desc);
          }
        }
      }
    }
    /**
     * 更新记录数据
     */
    $oAgreedLog = $oRecord->agreed_log;
    if (isset($oAgreedLog->{$oUser->uid})) {
      $oLog = $oAgreedLog->{$oUser->uid};
      $oLog->time = time();
      $oLog->value = $value;
    } else {
      $oAgreedLog->{$oUser->uid} = (object) ['time' => time(), 'value' => $value];
    }
    $modelRec->update(
      'xxt_enroll_record',
      ['agreed' => $value, 'agreed_log' => json_encode($oAgreedLog)],
      ['enroll_key' => $ek]
    );
    /* 如果活动属于项目，更新项目内的推荐内容 */
    if (!empty($oApp->mission_id)) {
      $modelMisMat = $this->model('matter\mission\matter');
      $modelMisMat->agreed($oApp, 'R', $oRecord, $value);
    }

    /* 处理用户汇总数据，行为分数据 */
    $this->model('matter\enroll\event')->agreeRecord($oApp, $oRecord, $oUser, $value);

    return new \ResponseData('ok');
  }
  /**
   * 删除当前记录
   *
   * @param string $app
   * @param string $ek
   *
   */
  public function remove_action($app, $ek)
  {
    $modelApp = $this->model('matter\enroll');
    $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
    if ($oApp === false || $oApp->state !== '1') {
      return new \ObjectNotFoundError();
    }
    /* 只有在活动开放时间范围内才能删除 */
    $aOpenedResult = $this->_appOpened($oApp);
    if ($aOpenedResult[0] === false) {
      return new \ResponseError($aOpenedResult[1]);
    }

    $modelRec = $this->model('matter\enroll\record');
    $oRecord = $modelRec->byId($ek, ['fields' => 'id,userid,nickname,state,enroll_key,data,rid']);
    if (false === $oRecord || $oRecord->state !== '1') {
      return new \ResponseError('记录已经被删除，不能再次删除');
    }
    $oUser = $this->getUser($oApp);

    // 判断删除人是否为提交人
    if ($oRecord->userid !== $oUser->uid) {
      return new \ResponseError('仅允许记录的提交者删除记录');
    }
    // 判断活动是否添加了轮次
    $modelRnd = $this->model('matter\enroll\round');
    $oRecordRnd = $modelRnd->byId($oRecord->rid);
    $now = time();
    if (empty($oRecordRnd) || (!empty($oRecordRnd) && ($oRecordRnd->end_at != 0) && $oRecordRnd->end_at < $now) || ($oRecordRnd->rid !== $oRecord->rid)) {
      return new \ResponseError('记录所在活动轮次已结束，不能提交、修改、保存或删除！');
    }
    // 如果已经获得行为分不允许删除
    $modelEnlUsr = $this->model('matter\enroll\user');
    $oEnlUsrRnd = $modelEnlUsr->byIdInApp($oApp, $oUser->uid, ['fields' => 'id,enroll_num,user_total_coin', 'rid' => $oRecord->rid]);
    if ($oEnlUsrRnd && $oEnlUsrRnd->user_total_coin > 0) {
      return new \ResponseError('提交的记录已经获得活动行为分，不能删除');
    }

    // 删除数据
    $rst = $modelRec->removeByUser($oApp, $oRecord);

    /* 记录操作日志 */
    $oTarget = new \stdClass;
    $oTarget->id = $oRecord->id;
    $oTarget->type = 'record';
    $oEvent = new \stdClass;
    $oEvent->name = 'site.matter.enroll.remove';
    $oEvent->op = 'Del';
    $oEvent->at = time();
    $oEvent->user = $oUser;
    $log = $this->model('matter\enroll\event')->_logEvent($oApp, $oRecord->rid, $ek, $oTarget, $oEvent);

    return new \ResponseData($rst);
  }
  /**
   * 返回指定记录项的活动记录
   */
  public function list4Schema_action($app, $rid = null, $schema, $page = 1, $size = 10)
  {
    // 记录数据过滤条件
    $oCriteria = $this->getPostJson();

    // 记录记录过滤条件
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
    $mdoelRec = $this->model('matter\enroll\record');
    $result = $mdoelRec->list4Schema($enrollApp, $schema, $aOptions);

    return new \ResponseData($result);
  }
  /**
   * 将填写记录转发到其他活动
   */
  public function transmit_action($ek, $transmit)
  {
    $modelApp = $this->model('matter\enroll');
    $modelRec = $this->model('matter\enroll\record');

    $fields = 'id,aid,state';
    $oRecord = $modelRec->byId($ek, ['fields' => $fields]);
    if (false === $oRecord || $oRecord->state !== '1') {
      return new \ObjectNotFoundError();
    }

    $oApp = $modelApp->byId($oRecord->aid, ['cascaded' => 'N', 'fields' => 'siteid,state,mission_id,sync_mission_round,data_schemas,transmit_config']);
    if (false === $oApp || $oApp->state !== '1') {
      return new \ObjectNotFoundError();
    }

    $oConfig = tms_array_search($oApp->transmitConfig, function ($oConfig) use ($transmit) {
      return $oConfig->id === $transmit;
    });
    if (empty($oConfig->app->id) || !isset($oConfig->mappings)) {
      return new \ResponseError('没有设置记录转发规则');
    }

    $oTargetApp = $modelApp->byId($oConfig->app->id, ['cascaded' => 'N', 'fields' => '*']);
    if (false === $oTargetApp || $oTargetApp->state !== '1') {
      return new \ObjectNotFoundError();
    }

    $aResult = $this->model('matter\enroll\record\copy')->toApp($oApp, $oTargetApp, [$ek], $oConfig->mappings);
    if (false === $aResult[0]) {
      return new \ResponseError($aResult[1]);
    }
    if (count($aResult[1]) !== 1) {
      return new \ResponseError('记录转发错误');
    }

    $oNewRec = $aResult[1][0];

    return new \ResponseData($oNewRec);
  }
}
