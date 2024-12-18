<?php

namespace site\fe\matter\enroll;

use stdClass;

include_once dirname(__FILE__) . '/base.php';
/**
 * 记录活动排行榜
 */
class rank extends base
{
  /**
   * 根据活动进入规则，获得用户分组信息
   */
  private function _getUserGroups($oApp, $startAt = 0, $endAt = 0)
  {
    if (empty($oApp->entryRule->group->id)) {
      return false;
    }

    $modelGrpTeam = $this->model('matter\group\team');
    $aByAppOptions = [
      'cascade' => 'playerCount,onlookerCount,leaveCount',
      'start_at' => $startAt, 'end_at' => $endAt,
    ];
    $teams = $modelGrpTeam->byApp($oApp->entryRule->group->id, $aByAppOptions);
    if (empty($teams)) {
      return $teams;
    }

    $userGroups = [];
    foreach ($teams as $oTeam) {
      $oNewGroup = new \stdClass;
      $oNewGroup->v = $oTeam->team_id;
      $oNewGroup->l = $oTeam->title;
      $oNewGroup->playerCount = $oTeam->playerCount;
      $oNewGroup->onlookerCount = $oTeam->onlookerCount;
      $oNewGroup->leaveCount = $modelGrpTeam->getDeepValue($oTeam, 'leaveCount', 0);
      $userGroups[$oTeam->team_id] = $oNewGroup;
    }

    return $userGroups;
  }
  /**
   * 获得分组活动中，按照选项题的选项对用户进行分组的情况
   * 如果分组活动中的schema未包含指定的schema，返回false
   * 
   * 返回每个选项的用户数
   */
  private function _getUserBySchemaOpsInGroupApp($oGrpAppId, $oSchema)
  {
    $modelGrpRec = $this->model('matter\group\record');
    $oOptions = new \stdClass;
    $oOptions->leader = ['N', 'Y', 'S']; // 排除旁观者
    $grpRecsRst = $modelGrpRec->byApp($oGrpAppId, $oOptions);

    if (empty($grpRecsRst->total)) return false;

    $schemaOps = new \stdClass;
    foreach ($grpRecsRst->records as $rec) {
      $opVal = $this->getDeepValue($rec->data, $oSchema->id);
      if (!empty($opVal)) {
        if (!isset($schemaOps->$opVal)) {
          $schemaOps->$opVal = new \stdClass;
          $schemaOps->$opVal->num = 1;
        } else {
          $schemaOps->$opVal->num += 1;
        }
      }
    }
    return $schemaOps;
  }
  /**
   * 根据用户的行为数据进行排行，例如：提交记录、点赞、评论等
   * 
   * xxt_enroll_user记录了用户在活动中的行为数据
   * 不包含旁观用户
   */
  private function _userByBehavior($oApp, $oCriteria, $page = 1, $size = 100)
  {
    $modelUsr = $this->model('matter\enroll\user');

    $q = [
      'u.userid,u.nickname,a.headimgurl',
      'xxt_enroll_user u left join xxt_site_account a on u.userid = a.uid and u.siteid = a.siteid',
      "u.aid='{$oApp->id}' and u.state=1",
    ];
    // 指定了按分组过滤
    if (!empty($oCriteria->group) && !empty($oCriteria->group->id)) {
      $q[2] .= " and u.group_id='{$oCriteria->group->id}'";
    }
    // 用户分组信息，必须是分组活动中的用户，排除旁观者
    if (!empty($oApp->entryRule->group->id)) {
      $q[0] .= ',u.group_id,g.team_title';
      $q[1] .= ",xxt_group_record g";
      $q[2] .= " and g.state=1 and g.aid='{$oApp->entryRule->group->id}' and u.userid=g.userid and g.team_id=u.group_id and g.is_leader not in('O')";
    }

    // 轮次
    if (!empty($oCriteria->round) && is_string($oCriteria->round)) {
      $oCriteria->round = explode(',', $oCriteria->round);
    }
    if (empty($oCriteria->round) || in_array('ALL', $oCriteria->round)) {
      $q[2] .= " and u.rid = 'ALL'";
    } else {
      $whereByRound = ' and rid in("';
      $whereByRound .= implode('","', $oCriteria->round);
      $whereByRound .= '")';
      $q[2] .= $whereByRound;
    }

    switch ($oCriteria->orderby) {
      case 'enroll':
        $q[0] .= ',sum(u.enroll_num) enroll_num';
        $q[2] .= ' and u.enroll_num>0';
        $q2 = ['o' => 'enroll_num desc'];
        break;
      case 'cowork':
        $q[0] .= ',sum(u.cowork_num) cowork_num';
        $q[2] .= ' and u.cowork_num>0';
        $q2 = ['o' => 'cowork_num desc'];
        break;
      case 'remark':
        $q[0] .= ',sum(u.remark_num) remark_num';
        $q[2] .= ' and u.remark_num>0';
        $q2 = ['o' => 'remark_num desc'];
        break;
      case 'like':
        $q[0] .= ',sum(u.like_num) like_num';
        $q[2] .= ' and u.like_num>0';
        $q2 = ['o' => 'like_num desc'];
        break;
      case 'do_remark':
        $q[0] .= ',sum(u.do_remark_num) do_remark_num';
        $q[2] .= ' and u.do_remark_num>0';
        $q2 = ['o' => 'do_remark_num desc'];
        break;
      case 'do_like':
        $q[0] .= ',sum(u.do_like_num) do_like_num';
        $q[2] .= ' and u.do_like_num>0';
        $q2 = ['o' => 'do_like_num desc'];
        break;
      case 'total_coin':
        $q[0] .= ',sum(u.user_total_coin) user_total_coin';
        $q[2] .= ' and u.user_total_coin>0';
        $q2 = ['o' => 'user_total_coin desc'];
        break;
      case 'score':
        $q[0] .= ',sum(u.score) score';
        if (empty($oApp->rankConfig->scoreIncludeZero)) {
          $q[2] .= ' and u.score>0';
        }
        $q2 = ['o' => 'score desc'];
        break;
      case 'vote_schema':
        $q[0] .= ',sum(u.vote_schema_num) vote_schema_num';
        $q[2] .= ' and u.vote_schema_num>0';
        $q2 = ['o' => 'vote_schema_num desc'];
        break;
      case 'vote_cowork':
        $q[0] .= ',sum(u.vote_cowork_num) vote_cowork_num';
        $q[2] .= ' and u.vote_cowork_num>0';
        $q2 = ['o' => 'vote_cowork_num desc'];
        break;
    }
    $q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
    $q2['g'] = ['userid'];

    $oResult = new \stdClass;
    $users = $modelUsr->query_objs_ss($q, $q2);
    if (count($users) && !empty($oApp->entryRule->group->id)) {
      foreach ($users as $oUser) {
        $oUser->group = new \stdClass;
        $oUser->group->team_id = $oUser->group_id;
        $oUser->group->team_title = $oUser->team_title;
        unset($oUser->group_id);
        unset($oUser->team_title);
      }
    }
    $oResult->users = $users;

    /**
     * 添加组内缺席用户
     */
    if (!empty($oApp->entryRule->group->id) && !empty($oCriteria->group)) {
      $userIds = [];
      foreach ($users as $u) {
        $userIds[] = $u->userid;
      }
      $qgu = [
        'gu.userid,gu.nickname,a.headimgurl',
        'xxt_group_record gu left join xxt_site_account a on gu.userid = a.uid and gu.siteid = a.siteid',
        [
          'state' => 1,
          'aid' => $oApp->entryRule->group->id,
          'team_id' => $oCriteria->group->id,
          'is_leader' => (object) ['op' => 'not in', 'pat' => ['O']],
          'userid' => (object) ['op' => 'not in', 'pat' => $userIds]
        ]
      ];
      $absentUsers = $modelUsr->query_objs_ss($qgu);
      if (count($absentUsers)) {
        foreach ($absentUsers as $au) {
          $oResult->users[] = $au;
        }
      }
    }

    $q[0] = 'count(*)';
    $oResult->total = (int) $modelUsr->query_val_ss($q);

    if ($oResult->total === 0) {
      $this->logger->debug('userByBehavior=0 ' . json_encode($q));
    }

    return $oResult;
  }
  /**
   * 根据用户的填写数据进行排行
   * 
   * xxt_enroll_record_data记录了用户填写的数据
   * 
   * 条件设置（$oCriteria）：
   * $oCriteria->orderby = "schema_xxxx"，xxx为schemaId
   * 数值题：oSchema.type === 'shorttext' && /number|calculate/.test(oSchema.format)
   * 单选题：oSchema.type === 'single'
   * 
   */
  private function _userByRecord($oApp, $oCriteria, $page = 1, $size = 100)
  {
    $schemaId = substr($oCriteria->orderby, 7);
    $schemaSumCol = 'schema_' . $schemaId . '_sum';

    $modelRecDat = $this->model('matter\enroll\data');

    $q = [
      'r.userid,sum(cast(value as decimal(19,2))) ' . $schemaSumCol,
      'xxt_enroll_record_data r',
      ['r.aid' => $oApp->id, 'r.state' => 1, 'r.schema_id' => $schemaId, 'r.userid' => (object) ['op' => '<>', 'pat' => '']],
    ];
    // 指定了按分组过滤
    if (!empty($oCriteria->group) && !empty($oCriteria->group->id)) {
      $q[2]['r.group_id'] = $oCriteria->group->id;
    }
    // 用户分组信息，必须是分组活动中的用户，排除旁观者和缺席者
    if (!empty($oApp->entryRule->group->id)) {
      $q[0] .= ',r.group_id,g.team_title';
      $q[1] .= ",xxt_group_record g";
      $q[2]['g.state'] = 1; // 可以状态的记录
      $q[2]['g.aid'] = $oApp->entryRule->group->id;
      $q[2]['userid'] = (object) ['op' => 'and', 'pat' => ['g.userid=r.userid']];
      $q[2]['g.is_leader'] = (object) ['op' => 'not in', 'pat' => ['O']];
      $q[2]['group_id'] = (object) ['op' => 'and', 'pat' => ['g.team_id=r.group_id']];
    }
    // if (!empty($oApp->entryRule->group->id)) {
    //   if (!empty($oCriteria->group)) {
    //     $qgu = [
    //       '*',
    //       'xxt_group_record',
    //       [
    //         'state' => 1,
    //         'aid' => $oApp->entryRule->group->id,
    //         'group_id' => $oCriteria->group,
    //         'g.is_leader' => (object) ['op' => 'not in', 'pat' => ['O']]
    //       ]
    //     ];
    //     $groupUsers = $modelRecDat->query_objs_ss($qgu);
    //   }
    // }
    // 轮次条件
    if (!empty($oCriteria->round)) {
      if (is_string($oCriteria->round)) {
        $oCriteria->round = explode(',', $oCriteria->round);
      }
      if (!in_array('ALL', $oCriteria->round)) {
        $q[2]['r.rid'] = $oCriteria->round;
      }
    }

    $q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
    $q2['g'] = ['userid'];
    $q2['o'] = [$schemaSumCol . ' desc'];

    $users = $modelRecDat->query_objs_ss($q, $q2);

    $oResult = new \stdClass;
    $q[0] = 'count(distinct r.userid)';
    $oResult->total = (int) $modelRecDat->query_val_ss($q);

    if (!empty($users)) {
      /**
       * 补充用户信息
       */
      $q = [
        'u.nickname,a.headimgurl',
        'xxt_enroll_user u left join xxt_site_account a on u.userid = a.uid and u.siteid = a.siteid',
        ['u.aid' => $oApp->id, 'u.state' => 1, 'rid' => 'ALL'],
      ];
      foreach ($users as $oUser) {
        if (!empty($oApp->entryRule->group->id)) {
          $oUser->group = new \stdClass;
          $oUser->group->team_id = $oUser->group_id;
          $oUser->group->team_title = $oUser->team_title;
          unset($oUser->group_id);
          unset($oUser->team_title);
        }
        // 用户头像
        $q[2]['userid'] = $oUser->userid;
        $oEnlUsr = $modelRecDat->query_obj_ss($q);
        if ($oEnlUsr) {
          $oUser->nickname = $oEnlUsr->nickname;
          $oUser->headimgurl = $oEnlUsr->headimgurl;
        }
      }
    }
    $oResult->users = $users;

    if (isset($groupUsers)) {
      $oResult->groupUsers = $groupUsers;
    }

    return $oResult;
  }
  /**
   * 用户排行榜
   */
  public function userByApp_action($app, $groupid = '', $samegroup = 'N', $page = 1, $size = 100)
  {
    $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
    if (false === $oApp || $oApp->state !== '1') {
      return new \ObjectNotFoundError();
    }

    $oCriteria = $this->getPostJson();
    if (empty($oCriteria->orderby)) {
      return new \ParameterError();
    }

    /* 和当前用户同组的用户 */
    if ($samegroup === 'Y') {
      /* 当前访问用户的基本信息 */
      $oUser = $this->getUser($oApp);
      if (!empty($oUser->group_id)) {
        $oCriteria->group = (object)['id' => $oUser->group_id];
      }
    } else if (!empty($groupid)) {
      /* 指定分组中的用户 */
      $oCriteria->group = (object)['id' => $groupid];
    }

    if (0 === strpos($oCriteria->orderby, 'schema_')) {
      $oResult = $this->_userByRecord($oApp, $oCriteria, $page, $size);
    } else {
      $oResult = $this->_userByBehavior($oApp, $oCriteria, $page, $size);
    }

    return new \ResponseData($oResult);
  }
  /**
   * 根据行为对用户组排行，例如：提交记录、点赞、评论等
   */
  private function _groupByBehavior($oApp, $oCriteria, $userGroups)
  {
    $sql = 'select ';
    switch ($oCriteria->orderby) {
      case 'enroll':
        $sql .= 'sum(enroll_num)';
        break;
      case 'cowork':
        $sql .= 'sum(cowork_num)';
        break;
      case 'remark':
        $sql .= 'sum(remark_num)';
        break;
      case 'like':
        $sql .= 'sum(like_num)';
        break;
      case 'remark_other':
        $sql .= 'sum(do_remark_num)';
        break;
      case 'like_other':
        $sql .= 'sum(do_like_num)';
        break;
      case 'total_coin':
      case 'average_total_coin':
        $sql .= 'sum(user_total_coin)';
        break;
      case 'group_total_coin':
        $sql .= 'group_total_coin';
        break;
      case 'score':
      case 'average_score':
        $sql .= 'sum(score)';
        break;
      case 'vote_schema':
        $sql .= 'sum(vote_schema_num)';
        break;
      case 'vote_cowork':
        $sql .= 'sum(vote_cowork_num)';
        break;
      default:
        return new \ParameterError('不支持的排行数据类型【' . $oCriteria->orderby . '】');
    }
    if ($oCriteria->orderby === 'group_total_coin') {
      $sql .= ' from xxt_enroll_group where aid=\'' . $oApp->id . "' and state=1";
    } else {
      $sql .= ' from xxt_enroll_user where aid=\'' . $oApp->id . "' and state=1";
    }
    if (!empty($oCriteria->round) && is_string($oCriteria->round)) {
      $oCriteria->round = explode(',', $oCriteria->round);
    }
    if (empty($oCriteria->round) || in_array('ALL', $oCriteria->round)) {
      $sql .= " and rid = 'ALL'";
    } else {
      $whereByRound = ' and rid in("';
      $whereByRound .= implode('","', $oCriteria->round);
      $whereByRound .= '")';
      $sql .= $whereByRound;
    }

    /* 获取分组的数据 */
    $modelUsr = $this->model('matter\enroll\user');
    foreach ($userGroups as $oUserGroup) {
      $sqlByGroup = $sql . ' and group_id=\'' . $oUserGroup->v . '\'';
      $oUserGroup->id = $oUserGroup->v;
      $oUserGroup->title = $oUserGroup->l;
      unset($oUserGroup->v);
      unset($oUserGroup->l);
      if (in_array($oCriteria->orderby, ['score', 'average_score', 'average_total_coin'])) {
        if ($oCriteria->orderby === 'score') {
          $oUserGroup->num = round((float) $modelUsr->query_value($sqlByGroup), 2);
        } else {
          if (!empty($oUserGroup->playerCount)) {
            // 不包含旁观者和缺席者
            $validCount = $oUserGroup->playerCount - $oUserGroup->onlookerCount - $oUserGroup->leaveCount;
            if ($validCount > 0) {
              $oUserGroup->num = round((float) ($modelUsr->query_value($sqlByGroup) / $validCount), 2);
            } else {
              $oUserGroup->num = 0;
            }
          } else {
            $oUserGroup->num = 0;
          }
        }
      } else {
        $oUserGroup->num = (int) $modelUsr->query_value($sqlByGroup);
      }
    }
    /* 对分组数据进行排序 */
    usort($userGroups, function ($a, $b) {
      return $a->num < $b->num ? 1 : -1;
    });

    $oResult = new \stdClass;
    $oResult->groups = $userGroups;

    return $oResult;
  }
  /**
   * 根据记录数据对用户组排行
   */
  private function _groupByRecord($oApp, $oCriteria, $aUserGroups)
  {
    $schemaId = substr($oCriteria->orderby, 7);
    $schemaSumCol = 'schema_' . $schemaId . '_sum';

    $modelRecDat = $this->model('matter\enroll\data');

    $q = [
      'group_id,sum(cast(value as decimal(19,2))) ' . $schemaSumCol,
      'xxt_enroll_record_data',
      ['aid' => $oApp->id, 'state' => 1, 'schema_id' => $schemaId, 'group_id' => (object) ['op' => '<>', 'pat' => '']],
    ];
    if (!empty($oCriteria->round)) {
      if (is_string($oCriteria->round)) {
        $oCriteria->round = explode(',', $oCriteria->round);
      }
      if (!in_array('ALL', $oCriteria->round)) {
        $q[2]['rid'] = $oCriteria->round;
      }
    }

    $q2['g'] = ['group_id'];
    $q2['o'] = [$schemaSumCol . ' desc'];

    $groups = $modelRecDat->query_objs_ss($q, $q2);
    if (!empty($groups)) {
      foreach ($groups as $oGroup) {
        if (!isset($aUserGroups[$oGroup->group_id])) {
          continue;
        }
        $oGroup->id = $oGroup->group_id;
        $oGroup->title = $aUserGroups[$oGroup->group_id]->l;
        unset($oGroup->group_id);
      }
    }
    $oResult = new \stdClass;
    $oResult->groups = $groups;

    return $oResult;
  }
  /**
   * 分组排行榜
   */
  public function groupByApp_action($app)
  {
    $modelApp = $this->model('matter\enroll');
    $oApp = $modelApp->byId($app, ['cascaded' => 'N', 'id,state,entry_rule,data_schemas']);
    if ($oApp === false || $oApp->state !== '1') {
      return new \ObjectNotFoundError();
    }

    $oCriteria = $this->getPostJson();
    if (empty($oCriteria->orderby)) {
      return new \ParameterError();
    }

    $startAt = $endAt = 0;
    if (!empty($oCriteria->round)) {
      $modelEnlRnd = $this->model('matter\enroll\round');
      $rids = is_string($oCriteria->round) ? explode(',', $oCriteria->round) : $oCriteria->round;
      $rounds = $modelEnlRnd->byIds($rids, ['fields' => 'start_at,end_at']);
      if (!empty($rounds)) {
        foreach ($rounds as $oRnd) {
          if ($startAt === 0 || $oRnd->start_at < $startAt) {
            $startAt = $oRnd->start_at;
          }
          if ($oRnd->end_at > $endAt) {
            $endAt = $oRnd->end_at;
          }
        }
      }
    }

    $userGroups = $this->_getUserGroups($oApp, $startAt, $endAt);
    if (empty($userGroups)) {
      return new \ObjectNotFoundError();
    }

    if (0 === strpos($oCriteria->orderby, 'schema_')) {
      $oResult = $this->_groupByRecord($oApp, $oCriteria, $userGroups);
    } else {
      $oResult = $this->_groupByBehavior($oApp, $oCriteria, $userGroups);
    }

    return new \ResponseData($oResult);
  }
  /**
   * 根据行为数据对单选项数据排行
   * 
   * 需要支持根据排序对象在分组活动中对应的用户数量作为平均分的分母
   */
  private function _schemaByBehavior($oApp, $oCriteria, $oRankSchema, $aSchemaOps, $oUserBySchemaOps)
  {
    $modelRecDat = $this->model('matter\enroll\data');

    /* 处理通讯录题目，例如：member.extattr.s1558673860999 */
    $aSchemaIdSegs = explode('.', $oRankSchema->id);

    switch ($oCriteria->orderby) {
      case 'enroll': // 填写次数
        $q = [
          'value,count(*) num',
          'xxt_enroll_record_data',
          ['aid' => $oApp->id, 'state' => 1],
        ];
        if (count($aSchemaIdSegs) === 3) {
          return [false, '系统暂时无法提供通讯录数据进行排行'];
        } else {
          $q[2]['schema_id'] = $oRankSchema->id;
          $q[2]['value'] = (object) ['op' => '<>', 'pat' => ''];
        }
        if (!empty($oCriteria->round) && is_array($oCriteria->round) && !in_array('ALL', $oCriteria->round)) {
          $q[2]['rid'] = $oCriteria->round;
        }
        $q2 = ['g' => 'value', 'o' => 'num desc'];
        $oRankResult = $modelRecDat->query_objs_ss($q, $q2);
        if (count($oRankResult)) {
          array_walk($oRankResult, function (&$oData) use ($aSchemaOps) {
            $oData->l = isset($aSchemaOps[$oData->value]) ? $aSchemaOps[$oData->value] : '!未知';
            unset($oData->value);
          });
        }
        break;
      case 'score': // 总数据分
      case 'average_score':
        $oRankResult = [];
        $aScoreSchemas = $this->model('matter\enroll\schema')->asAssoc($oApp->dynaDataSchemas, ['filter' => function ($oSchema) {
          return $this->getDeepValue($oSchema, 'requireScore') === 'Y';
        }]);
        if (count($aScoreSchemas)) {
          $q = [
            'sum(score) num,count(distinct userid) user_num',
            'xxt_enroll_record_data rd1',
            ['aid' => $oApp->id, 'state' => 1, 'schema_id' => array_keys($aScoreSchemas)],
          ];
          if (!empty($oCriteria->round) && is_array($oCriteria->round) && !in_array('ALL', $oCriteria->round)) {
            $q[2]['rid'] = $oCriteria->round;
          }
          foreach ($aSchemaOps as $opv => $opl) {
            if (count($aSchemaIdSegs) === 3) {
              $opVal = '"' . $aSchemaIdSegs[2] . '":"' . $opv . '"';
              $q[2]['value'] = (object) ['op' => 'exists', 'pat' => 'select 1 from xxt_enroll_record_data rd2 where rd1.aid=rd2.aid and rd1.enroll_key=rd2.enroll_key and rd2.state=1 and rd2.schema_id="member" and rd2.value like \'%' . $opVal . '%\''];
            } else {
              $q[2]['value'] = (object) ['op' => 'exists', 'pat' => 'select 1 from xxt_enroll_record_data rd2 where rd1.aid=rd2.aid and rd1.enroll_key=rd2.enroll_key and rd2.state=1 and rd2.schema_id=\'' . $oRankSchema->id . '\' and rd2.value=\'' . $opv . '\''];
            }
            // $sql = $modelRecDat->query_obj_ss_toSql($q);
            $oNum = $modelRecDat->query_obj_ss($q); // 指定字段填写情况
            $oNum->l = $opl;
            if ($oCriteria->orderby === 'average_score') {
              if (!empty($oUserBySchemaOps) && !empty($oUserBySchemaOps->$opv)) {
                $oNum->user_num = $oUserBySchemaOps->$opv->num;
              }
              if (!empty($oNum->num) && !empty($oNum->user_num)) {
                $oNum->num = round((float) ($oNum->num / $oNum->user_num), 2);
              }
            }
            $oRankResult[] = $oNum;
          }
          /* 数据排序 */
          usort($oRankResult, function ($a, $b) {
            return $a->num < $b->num ? 1 : -1;
          });
        } else {
          return [false, '活动中没有打分题'];
        }
        break;
      default:
        return [false, '不支持的排行指标类型【' . $oCriteria->orderby . '】'];
    }

    return [true, $oRankResult];
  }
  /**
   * 根据记录对用户组排行
   */
  private function _schemaByRecord($oApp, $oCriteria, $oRankSchema, $aSchemaOps)
  {
    $schemaId = substr($oCriteria->orderby, 7);

    /* 处理通讯录题目，例如：member.extattr.s1558673860999 */
    $aSchemaIdSegs = explode('.', $oRankSchema->id);

    $modelRecDat = $this->model('matter\enroll\data');
    $q = [
      'sum(cast(value as decimal(19,2))) num',
      'xxt_enroll_record_data rd1',
      ['aid' => $oApp->id, 'state' => 1, 'schema_id' => $schemaId],
    ];
    if (!empty($oCriteria->round) && is_array($oCriteria->round) && !in_array('ALL', $oCriteria->round)) {
      $q[2]['rid'] = $oCriteria->round;
    }
    foreach ($aSchemaOps as $opv => $opl) {
      if (count($aSchemaIdSegs) === 3) {
        $opVal = '"' . $aSchemaIdSegs[2] . '":"' . $opv . '"';
        $q[2]['value'] = (object) ['op' => 'exists', 'pat' => 'select 1 from xxt_enroll_record_data rd2 where rd1.enroll_key=rd2.enroll_key and rd2.state=1 and rd2.schema_id="member" and rd2.value like \'%' . $opVal . '%\''];
      } else {
        $q[2]['value'] = (object) ['op' => 'exists', 'pat' => 'select 1 from xxt_enroll_record_data rd2 where rd1.enroll_key=rd2.enroll_key and rd2.state=1 and rd2.schema_id=\'' . $oRankSchema->id . '\' and rd2.value=\'' . $opv . '\''];
      }
      $oNum = $modelRecDat->query_obj_ss($q);
      $oNum->l = $opl;
      $oRankResult[] = $oNum;
    }
    /* 数据排序 */
    usort($oRankResult, function ($a, $b) {
      return $a->num < $b->num ? 1 : -1;
    });

    return [true, $oRankResult];
  }
  /**
   * 题目排行榜（仅限单选题）
   * 
   * 查询参数：
   * schema=s1678587829457
   * 
   * body示例：
   * {
   *    "orderby": "schema_c1",
   *    "agreed": "all",
   *    "round": [
   *      "ALL"
   *    ],
   *    "obj": "s1678587829457"
   * }
   * 
   * 排行字段是单选题时，
   * 
   */
  public function schemaByApp_action($app, $schema)
  {
    $modelApp = $this->model('matter\enroll');
    $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
    if ($oApp === false || $oApp->state !== '1') {
      return new \ObjectNotFoundError();
    }

    $oCriteria = $this->getPostJson();
    if (empty($oCriteria->orderby)) {
      return new \ParameterError();
    }

    $oRankSchema = tms_array_search($oApp->dynaDataSchemas, function ($oSchema) use ($schema) {
      return $oSchema->id === $schema;
    });
    if (false === $oRankSchema) {
      return new \ObjectNotFoundError('指定的题目不存在');
    }
    if ($oRankSchema->type !== 'single' || empty($oRankSchema->ops)) {
      return new \ParameterError('指定的题目不支持进行排行');
    }
    $aSchemaOps = []; // 单选题选项作为排行对象
    array_walk($oRankSchema->ops, function ($oOp) use (&$aSchemaOps) {
      $aSchemaOps[$oOp->v] = $oOp->l;
    });
    if (empty($aSchemaOps)) {
      return new \ParameterError('指定的题目选项为空，无法进行排行');
    }
    /**
     * 如果指定字段来源于分组活动，获得题目在分组活动中对应的用户数量
     */
    $oUserBySchemaOps = false;
    if (!empty($oApp->entryRule->group->id) && !empty($oRankSchema->fromApp) && $oApp->entryRule->group->id === $oRankSchema->fromApp) {
      $oUserBySchemaOps = $this->_getUserBySchemaOpsInGroupApp($oApp->entryRule->group->id, $oRankSchema);
    }

    if (0 === strpos($oCriteria->orderby, 'schema_')) {
      $aResult = $this->_schemaByRecord($oApp, $oCriteria, $oRankSchema, $aSchemaOps);
    } else {
      $aResult = $this->_schemaByBehavior($oApp, $oCriteria, $oRankSchema, $aSchemaOps, $oUserBySchemaOps);
    }

    if (false === $aResult[0]) {
      return new \ResponseError($aResult[1]);
    }

    return new \ResponseData($aResult[1]);
  }
}
