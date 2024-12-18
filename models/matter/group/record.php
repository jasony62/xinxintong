<?php

namespace matter\group;

require_once dirname(dirname(__FILE__)) . '/enroll/record_base.php';
/**
 * 处理从数据库中获得的数据
 */
class DbRecordHandler
{
  public $host;

  public $appId;

  private $_modelGrpLev;

  public function __construct($host, $appId = null)
  {
    $this->host = $host;
    $this->appId = $appId;
  }
  /**
   * 处理从数据库中获得的记录
   */
  public function handle($oRecord)
  {
    if (property_exists($oRecord, 'data')) {
      if (empty($oRecord->data)) {
        $oRecord->data = new \stdClass;
      } else if (is_string($oRecord->data)) {
        $oRecord->data = json_decode($oRecord->data);
      }
    }
    if (property_exists($oRecord, 'role_teams')) {
      if (empty($oRecord->role_teams)) {
        $oRecord->role_teams = [];
      } else if (is_string($oRecord->role_teams)) {
        $oRecord->role_teams = json_decode($oRecord->role_teams);
      }
    }

    return $oRecord;
  }
  /**
   * 给记录添加请假信息
   */
  public function handleWithLeave($oRecord)
  {
    $this->handle($oRecord);
    if (property_exists($oRecord, 'aid')) {
      $appId = $oRecord->aid;
    } else if (!empty($this->appId)) {
      $appId = $this->appId;
    }
    if (!empty($appId) && property_exists($oRecord, 'userid')) {
      if (!isset($this->_modelGrpLev)) {
        $this->_modelGrpLev = $this->host->model('matter\group\leave');
      }
      $leaves = $this->_modelGrpLev->byUser($appId, $oRecord->userid, ['fields' => 'id,begin_at,end_at']);
      $oRecord->leaves = $leaves;
    }
    return $oRecord;
  }
}
/**
 * 分组活动记录
 */
class record_model extends \matter\enroll\record_base
{
  /**
   * 用户登记（不包括登记数据）
   *
   * @param object $oApp
   * @param object $oUser
   * @param array $aOptions
   *
   */
  public function enroll($oApp, $oUser, $aOptions = [])
  {
    if (is_object($aOptions)) {
      $aOptions = (array) $aOptions;
    }

    if (isset($aOptions['enroll_key'])) {
      $ek = $aOptions['enroll_key'];
    } else {
      $ek = $this->genKey($oApp->siteid, $oApp->id);
    }
    $current = time();
    $aNewRec = [
      'aid' => $oApp->id,
      'siteid' => $oApp->siteid,
      'enroll_key' => $ek,
      'userid' => $oUser->uid,
      'nickname' => $this->escape($oUser->nickname),
    ];
    $aNewRec['enroll_at'] = isset($aOptions['enroll_at']) ? $aOptions['enroll_at'] : $current;
    $aNewRec['draw_at'] = isset($aOptions['draw_at']) ? $aOptions['draw_at'] : $current;
    $aNewRec['team_id'] = isset($aOptions['team_id']) ? $aOptions['team_id'] : '';
    isset($aOptions['team_title']) && $aNewRec['team_title'] = $this->escape($aOptions['team_title']);
    isset($aOptions['is_leader']) && $aNewRec['is_leader'] = $aOptions['is_leader'];
    isset($aOptions['comment']) && $aNewRec['comment'] = $this->escape($aOptions['comment']);
    isset($aOptions['tags']) && $aNewRec['tags'] = $this->escape($aOptions['tags']);
    isset($aOptions['referrer']) && $aNewRec['referrer'] = $aOptions['referrer'];

    $this->insert('xxt_group_record', $aNewRec, false);

    return $ek;
  }
  /**
   * 保存登记的数据
   */
  public function setData($oApp, $ek, $data)
  {
    if (empty($data)) {
      return [true];
    }
    // 处理后的登记记录
    $dbData = new \stdClass;

    $aSchemasById = $this->model('matter\enroll\schema')->asAssoc($oApp->dataSchemas);

    /* 已有的登记数据 */
    $fields = $this->query_vals_ss(['name', 'xxt_group_record_data', "aid='{$oApp->id}' and enroll_key='$ek'"]);

    foreach ($data as $n => $v) {
      if ($n === 'member' && is_object($v)) {
        $dbData->{$n} = $v;
        /* 自定义用户信息 */
        $treatedValue = new \stdClass;
        isset($v->name) && $treatedValue->name = urlencode($v->name);
        isset($v->email) && $treatedValue->email = urlencode($v->email);
        isset($v->mobile) && $treatedValue->mobile = urlencode($v->mobile);
        if (!empty($v->extattr)) {
          $extattr = new \stdClass;
          foreach ($v->extattr as $mek => $mev) {
            $extattr->{$mek} = urlencode($mev);
          }
          $treatedValue->extattr = $extattr;
        }
        $treatedValue = urldecode(json_encode($treatedValue));
      } else {
        if (!isset($aSchemasById[$n])) {
          continue;
        }
        $schema = $aSchemasById[$n];
        /**
         * 插入自定义属性
         */
        if (is_array($v) && (isset($v[0]->serverId) || isset($v[0]->imgSrc))) {
          /* 上传图片 */
          $treatedValue = array();
          $fsuser = $this->model('fs/user', $oApp->siteid);
          foreach ($v as $img) {
            $rst = $fsuser->storeImg($img);
            if (false === $rst[0]) {
              return $rst;
            }
            $treatedValue[] = $rst[1];
          }
          $treatedValue = implode(',', $treatedValue);
          $dbData->{$n} = $treatedValue;
        } else if ($schema->type === 'score') {
          $dbData->{$n} = $v;
          $treatedValue = json_encode($v);
        } else {
          if (is_string($v)) {
            $treatedValue = $this->escape($v);
          } else if (is_object($v) || is_array($v)) {
            $treatedValue = implode(',', array_keys(array_filter((array) $v, function ($i) {
              return $i;
            })));
          } else {
            $treatedValue = $v;
          }
          $dbData->{$n} = $treatedValue;
        }
        if (!empty($fields) && in_array($n, $fields)) {
          $this->update(
            'xxt_group_record_data',
            ['value' => $treatedValue],
            ['aid' => $oApp->id, 'enroll_key' => $ek, 'name' => $n]
          );
          unset($fields[array_search($n, $fields)]);
        } else {
          $ic = [
            'aid' => $oApp->id,
            'enroll_key' => $ek,
            'name' => $n,
            'value' => $treatedValue,
          ];
          $this->insert('xxt_group_record_data', $ic, false);
        }
      }
    }
    // 记录数据
    $dbData = $this->escape($this->toJson($dbData));
    $this->update(
      'xxt_group_record',
      ['enroll_at' => time(), 'data' => $dbData],
      ['enroll_key' => $ek]
    );

    return [true, $dbData];
  }
  /**
   * 处理从数据库中获得的数据
   */
  private function _callableDbRecordHandler($appId = null, $handlerName = 'handle')
  {
    return [new DbRecordHandler($this, $appId), $handlerName];
  }
  /**
   * 根据ID返回登记记录
   */
  public function byIdInApp($aid, $ek, $aOptions = [])
  {
    $fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';

    $q = [
      $fields,
      'xxt_group_record',
      ['aid' => $aid, 'enroll_key' => $ek, 'state' => 1],
    ];
    $oRecord = $this->query_obj_ss($q, $this->_callableDbRecordHandler($aid));

    return $oRecord;
  }
  /**
   * 获得指定项目下的登记记录
   *
   * @param int $missionId
   */
  public function byMission($missionId, $aOptions)
  {
    $fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
    $q = [
      $fields,
      'xxt_group_record r',
    ];
    $where = "state=1 and exists(select 1 from xxt_group g where r.aid=g.id and g.mission_id={$missionId})";

    if (isset($aOptions['userid'])) {
      $where .= " and userid='" . $this->escape($aOptions['userid']) . "'";
    }
    $q[2] = $where;

    $list = $this->query_objs_ss($q, null, $this->_callableDbRecordHandler());

    return $list;
  }
  /**
   * 用户清单
   *
   * return
   * [0] 数据列表
   * [1] 数据总条数
   */
  public function byApp($oApp, $oOptions = null)
  {
    if (is_string($oApp)) {
      $oApp = $this->model('matter\group')->byId($oApp);
    }

    if ($oOptions) {
      is_array($oOptions) && $oOptions = (object) $oOptions;
      $page = isset($oOptions->page) ? $oOptions->page : null;
      $size = isset($oOptions->size) ? $oOptions->size : null;
    }

    $fields = isset($oOptions->fields) ? $oOptions->fields : 'enroll_key,enroll_at,comment,tags,data,userid,nickname,is_leader,team_id,team_title,role_teams';
    if (isset($oApp->dataSchemas))
      $aSchemasById = $this->model('matter\group\schema')->asAssoc($oApp->dataSchemas);

    /* 数据过滤条件 */
    $w = "state=1 and aid='{$oApp->id}'";
    /*tags*/
    if (!empty($oOptions->tags)) {
      $aTags = explode(',', $oOptions->tags);
      foreach ($aTags as $tag) {
        $w .= " and concat(',',tags,',') like '%,$tag,%'";
      }
    }
    if (isset($oOptions->teamId)) {
      if ($oOptions->teamId === 'inTeam') { // 已分组
        $w .= " and team_id <> ''";
      } else if ($oOptions->teamId === '' || $oOptions->teamId === 'pending') { // 未分组
        $w .= " and team_id = ''";
      } else if (strcasecmp($oOptions->teamId, 'all') !== 0) { // 指定分组
        $w .= " and team_id = '" . $oOptions->teamId . "' and userid <> ''";
      }
    }
    if (isset($oOptions->roleTeamId)) {
      if ($oOptions->roleTeamId === 'pending') { // 未指定角色
        $w .= " and (role_teams = '' or role_teams = '[]')";
      } else if (!empty($oOptions->roleTeamId)) { // 角色组
        $w .= " and role_teams like '%\"" . $oOptions->roleTeamId . "\"%' and userid <> ''";
      }
    }
    // 根据用户昵称过滤
    if (!empty($oOptions->nickname)) {
      $w .= " and nickname like '%{$oOptions->nickname}%'";
    }
    // 用户组内角色过滤
    if (!empty($oOptions->leader) && is_array($oOptions->leader)) {
      $leader = "('" . implode("','", $oOptions->leader) . "')";
      $w .= " and is_leader in {$leader}";
    }
    // 按扩展字段进行筛选
    // 指定了记录数据过滤条件
    if (isset($aSchemasById) && isset($oOptions->data)) {
      $whereByData = '';
      foreach ($oOptions->data as $k => $v) {
        if (!empty($v) && isset($aSchemasById[$k])) {
          $oSchema = $aSchemasById[$k];
          $whereByData .= ' and (';
          if ($oSchema->type === 'multiple') {
            // 选项ID是否互斥，不存在，例如：v1和v11
            $bOpExclusive = true;
            $strOpVals = '';
            foreach ($oSchema->ops as $op) {
              $strOpVals .= ',' . $op->v;
            }
            foreach ($oSchema->ops as $op) {
              if (false !== strpos($strOpVals, $op->v)) {
                $bOpExclusive = false;
                break;
              }
            }
            // 拼写sql
            $v2 = explode(',', $v);
            foreach ($v2 as $index => $v2v) {
              if ($index > 0) {
                $whereByData .= ' and ';
              }
              // 获得和题目匹配的子字符串
              $dataBySchema = 'substr(substr(data,locate(\'"' . $k . '":"\',data)),1,locate(\'"\',substr(data,locate(\'"' . $k . '":"\',data)),' . (strlen($k) + 5) . '))';
              $whereByData .= '(';
              if ($bOpExclusive) {
                $whereByData .= $dataBySchema . ' like \'%' . $v2v . '%\'';
              } else {
                $whereByData .= $dataBySchema . ' like \'%"' . $v2v . '"%\'';
                $whereByData .= ' or ' . $dataBySchema . ' like \'%"' . $v2v . ',%\'';
                $whereByData .= ' or ' . $dataBySchema . ' like \'%,' . $v2v . ',%\'';
                $whereByData .= ' or ' . $dataBySchema . ' like \'%,' . $v2v . '"%\'';
              }
              $whereByData .= ')';
            }
          } else if ($oSchema->type === 'single') {
            $whereByData .= 'data like \'%"' . $k . '":"' . $v . '"%\'';
          } else {
            $whereByData .= 'data like \'%"' . $k . '":"%' . $v . '%"%\'';
          }
          $whereByData .= ')';
        }
      }
      $w .= $whereByData;
    }
    $q = [
      $fields,
      'xxt_group_record',
      $w,
    ];
    /* 分页参数 */
    if (isset($page) && isset($size)) {
      $q2 = [
        'r' => ['o' => ($page - 1) * $size, 'l' => $size],
      ];
    }
    /* 排序 */
    $q2['o'] = 'team_id asc,enroll_at desc';

    $oResult = new \stdClass; // 返回的结果
    $oResult->records = $this->query_objs_ss($q, $q2, $this->_callableDbRecordHandler($oApp->id, 'handleWithLeave'));

    /* total */
    $q[0] = 'count(*)';
    $total = (int) $this->query_val_ss($q);
    $oResult->total = $total;

    return $oResult;
  }
  /**
   * 根据用户id获得在指定分组活动中的分组信息
   */
  public function byUser($oApp, $userid, $aOptions = [])
  {
    if (empty($userid)) {
      return false;
    }

    $fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
    $q = [
      $fields,
      'xxt_group_record',
      ['state' => 1, 'aid' => $oApp->id, 'userid' => $userid],
    ];
    $q2 = ['o' => 'enroll_at desc'];
    if (isset($aOptions['onlyOne']) && $aOptions['onlyOne'] === true) {
      $q2['r'] = ['o' => 0, 'l' => 1];
    }

    $list = $this->query_objs_ss($q, $q2, $this->_callableDbRecordHandler($oApp->id));

    if (isset($aOptions['onlyOne']) && $aOptions['onlyOne'] === true) {
      return count($list) ? $list[0] : false;
    }

    return $list;
  }
  /**
   * 获得指定分组内的用户
   */
  public function byTeam($tid, $aOptions = [])
  {
    $oTeam = $this->model('matter\group\team')->byId($tid, ['fields' => 'aid,team_id,team_type']);
    if (false === $oTeam) {
      return false;
    }

    $fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
    $q = [
      $fields,
      'xxt_group_record',
      ['aid' => $oTeam->aid, 'state' => 1],
    ];
    if (isset($aOptions['is_leader']) && is_array($aOptions['is_leader'])) {
      $q[2]['is_leader'] = $aOptions['is_leader'];
    }
    switch ($oTeam->team_type) {
      case 'T':
        $q[2]['team_id'] = $oTeam->team_id;
        break;
      case 'R':
        $q[2]['role_teams'] = (object) ['op' => 'like', 'pat' => '%' . $oTeam->team_id . '%'];
        break;
      default:
        return false;
    }

    $q2 = ['o' => 'team_id,draw_at'];

    $records = $this->query_objs_ss($q, $q2, $this->_callableDbRecordHandler($oTeam->aid, 'handleWithLeave'));

    return $records;
  }
  /**
   * 根据指定的数据查找匹配的记录
   */
  public function byData($oApp, $data, $aOptions = [])
  {
    $fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
    $records = false;

    // 查找条件
    $whereByData = '';
    foreach ($data as $k => $v) {
      if ($k === '_round_id') {
        $whereByData .= ' and (';
        $whereByData .= 'team_id="' . $v . '"';
        $whereByData .= ')';
      } else {
        if (!empty($v)) {
          /* 通讯录字段简化处理 */
          if (strpos($k, 'member.') === 0) {
            $k = str_replace('member.', '', $k);
          }
          $whereByData .= ' and (';
          $whereByData .= 'data like \'%"' . $k . '":"' . $v . '"%\'';
          $whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . '"%\'';
          $whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . ',%"%\'';
          $whereByData .= ' or data like \'%"' . $k . '":"' . $v . ',%"%\'';
          $whereByData .= ')';
        }
      }
    }

    // 没有指定条件时就认为没有符合条件的记录
    if (empty($whereByData)) {
      return $records;
    }

    // 查找匹配条件的数据
    $q = [
      $fields,
      'xxt_group_record',
      "state=1 and aid='{$oApp->id}' $whereByData",
    ];
    $records = $this->query_objs_ss($q, null, $this->_callableDbRecordHandler($oApp->id));

    return $records;
  }
  /**
   * 检查是否存在匹配的分组记录
   *
   * 只读的题目不做检查（做检查有副作用吗？）
   */
  public function matchByData($targetAppId, $oSrcApp, &$oEnlData, $oUser = null)
  {
    /* 获得要检查的记录项 */
    $countRequireCheckedData = 0;
    $oRequireCheckedData = new \stdClass;
    $dataSchemas = isset($oSrcApp->dynaDataSchemas) ? $oSrcApp->dynaDataSchemas : $oSrcApp->dataSchemas;
    foreach ($dataSchemas as $oSchema) {
      if ($this->getDeepValue($oSchema, 'readonly') !== 'Y') {
        if ($this->getDeepValue($oSchema, 'requireCheck') === 'Y' && $this->getDeepValue($oSchema, 'fromApp') === $targetAppId) {
          $countRequireCheckedData++;
          $val = $this->getValueBySchema($oSchema, $oEnlData);
          if (!empty($val)) {
            $oRequireCheckedData->{$oSchema->id} = $val;
          }
        }
      }
    }
    if ($countRequireCheckedData === 0) {
      return [true, null];
    }
    $oGroupApp = $this->model('matter\group')->byId($targetAppId);
    if (empty($oGroupApp)) {
      return [false, '指定的记录匹配分组活动不存在'];
    }
    /* 在指定的分组活动中检查数据 */
    $groupUsers = $this->byData($oGroupApp, $oRequireCheckedData);
    if (empty($groupUsers)) {
      return [false, '未在指定的分组活动［' . $oGroupApp->title . '］中找到与提交数据相匹配的记录', $oRequireCheckedData];
    }
    /* 如果匹配的分组数据不唯一，怎么办？ */
    if (count($groupUsers) > 1) {
      return [false, '在指定的分组活动［' . $oGroupApp->title . '］中找到多条与提交数据相匹配的记录，匹配关系不唯一', $oRequireCheckedData];
    }
    $oMatchedGrpUsr = $groupUsers[0];
    /* 如果分组数据中未包含用户信息，更新用户信息 */
    if (isset($oUser->uid) && empty($oMatchedGrpUsr->userid)) {
      $oUserAcnt = new \stdClass;
      $oUserAcnt->userid = $oUser->uid;
      $oUserAcnt->nickname = $this->escape($oUser->nickname);
      $this->update('xxt_group_record', $oUserAcnt, ['id' => $oMatchedGrpUsr->id]);
    }
    /* 将匹配的分组记录数据作为提交的记录数据的一部分 */
    $oMatchedData = $oMatchedGrpUsr->data;
    foreach ($oGroupApp->dataSchemas as $oSchema) {
      if (!isset($oEnlData->{$oSchema->id}) && isset($oMatchedData->{$oSchema->id})) {
        $oEnlData->{$oSchema->id} = $oMatchedData->{$oSchema->id};
      }
    }
    /* 所属分组id */
    if (isset($oMatchedGrpUsr->team_id)) {
      $oAssocGrpTeamSchema = $this->model('matter\enroll\schema')->getAssocGroupTeamSchema($oSrcApp);
      if ($oAssocGrpTeamSchema) {
        $oEnlData->{$oAssocGrpTeamSchema->id} = $oMatchedGrpUsr->data->{$oAssocGrpTeamSchema->id} = $oMatchedGrpUsr->team_id;
      }
    }

    return [true, $oMatchedGrpUsr];
  }
  /**
   * 获得分组内用户的数量（团队分组）
   */
  public function countByTeam($tid, $aOptions = [])
  {
    $oTeam = $this->model('matter\group\team')->byId($tid, ['fields' => 'aid,team_id,team_type']);
    if (false === $oTeam) {
      return false;
    }

    $q = [
      'count(*)',
      'xxt_group_record',
      ['aid' => $oTeam->aid, 'state' => 1],
    ];

    switch ($oTeam->team_type) {
      case 'T':
        $q[2]['team_id'] = $oTeam->team_id;
        break;
      case 'R':
        $q[2]['role_teams'] = (object) ['op' => 'like', 'pat' => '%' . $oTeam->team_id . '%'];
        break;
      default:
        return false;
    }

    if (isset($aOptions['is_leader']) && in_array($aOptions['is_leader'], ['N', 'Y', 'S', 'O'])) {
      $q[2]['is_leader'] = $aOptions['is_leader'];
    }

    $cnt = (int) $this->query_val_ss($q);

    return $cnt;
  }
  /**
   * 指定用户是否属于指定用户组
   */
  public function isInTeam($tid, $userid)
  {
    /* 主分组 */
    $q = [
      '1',
      'xxt_group_record',
      ['userid' => $userid, 'state' => 1, 'team_id' => $tid],
    ];
    $oUsers = $this->query_objs_ss($q);
    if (count($oUsers)) {
      return true;
    }
    /* 辅助分组 */
    unset($q[2]['team_id']);
    $q[2]['role_teams'] = (object) ['op' => 'like', 'pat' => '%' . $tid . '%'];
    $oUsers = $this->query_obj_ss($q);
    if ($oUsers) {
      return true;
    }

    return false;
  }
  /**
   * 根据ID返回登记记录
   *
   * @param string $ek 因为分组活动的用户有可能是从其他活动导入的，使用的是导入记录的ek，因为有可能一个ek导入到多个分组活动中
   * @param string $aid 分组活动的id。如果指定只返回单条记录，如果不指定返回数据
   *
   */
  private function _byEnrollKey($ek, $aid = null, $aOptions = [])
  {
    $fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';

    $q = [
      $fields,
      'xxt_group_record',
      ["enroll_key" => $ek, "state" => 1],
    ];
    if (empty($aid)) {
      $records = $this->query_objs_ss($q, null, $this->_callableDbRecordHandler());
      return $records;
    } else {
      $q[2]['aid'] = $aid;
      $oRecord = $this->query_obj_ss($q, $this->_callableDbRecordHandler($aid));
      return $oRecord;
    }
  }
  /**
   * 移入分组 （团队分组）
   */
  public function joinGroup($appId, $oTeam, $ek)
  {
    $rst = 0;
    switch ($oTeam->team_type) {
      case 'T':
        $rst = $this->update(
          'xxt_group_record',
          [
            'team_id' => $oTeam->team_id,
            'team_title' => $this->escape($oTeam->title),
          ],
          ["aid" => $appId, "enroll_key" => $ek]
        );
        break;
      case 'R':
        $oUser = $this->_byEnrollKey($ek, $appId, ['fields' => 'role_teams']);
        if ($oUser && !in_array($oTeam->team_id, $oUser->role_teams)) {
          $oUser->role_teams[] = $oTeam->team_id;
          $rst = $this->update(
            'xxt_group_record',
            [
              'role_teams' => json_encode($oUser->role_teams),
            ],
            ["aid" => $appId, "enroll_key" => $ek]
          );
        }
        break;
    }

    return $rst;
  }
  /**
   * 移出分组 （团队分组）
   */
  public function quitTeam($appId, $ek)
  {
    $rst = $this->update(
      'xxt_group_record',
      [
        'team_id' => 0,
        'team_title' => '',
      ],
      ["aid" => $appId, "enroll_key" => $ek]
    );

    return $rst;
  }
  /**
   * 退出用户的所有角色分组 
   */
  public function quitRoleTeam($appId, $ek)
  {
    $rst = $this->update(
      'xxt_group_record',
      [
        'role_teams' => '[]',
      ],
      ["aid" => $appId, "enroll_key" => $ek]
    );

    return $rst;
  }
  /**
   * 检查数据是否和同步筛选条件匹配
   */
  public function matchSyncFilter($syncRule, $data)
  {
    if (empty($syncRule) || empty($syncRule->rules)) return false;
    if (!$data || !is_object($data)) return false;

    $rules = $syncRule->rules;
    $logicOR = empty($syncRule->logicOR) ? false : true;

    $matched = false;
    if ($logicOR === true) {
      /* 满足任意一条规则就可以 */
      foreach ($rules as $r) {
        if (empty($r->schema) || empty($r->op)) continue;
        if (!isset($data->{$r->schema})) continue;
        $dataops = $this->getDataOpsArray($data->{$r->schema});
        if (is_array($dataops) && !empty($dataops)) {
          if (in_array($r->op, $dataops)) {
            $matched = true;
            break;
          }
        }
      }
    } else {
      /* 满足所有规则 */
      $allpass = true;
      foreach ($rules as $r) {
        if (empty($r->schema) || empty($r->op)) continue;
        if (empty($data->{$r->schema})) {
          $allpass = false;
          break;
        }
        $dataops = $this->getDataOpsArray($data->{$r->schema});
        if (!is_array($dataops) || empty($dataops) || !in_array($r->op, $dataops)) {
          $allpass = false;
          break;
        }
      }
      $matched = $allpass;
    }
    return $matched;
  }
  /**
   * 同步数据
   */
  public function syncRecord($siteId, &$objGrp, &$records, &$modelRec, $type = '', $assignTeam = null)
  {
    $this->setOnlyWriteDbConn(true);
    $cnt = 0;
    if (!empty($records)) {
      $aOptions = ['cascaded' => 'Y'];
      foreach ($records as $oRecord) {
        if ($oRecord->state === '1' || $oRecord->state === 'N') {
          if ($type === 'mschema') {
            $oRecord = $this->_getMschData($objGrp, $oRecord->enroll_key);
          } else {
            $oRecord = $modelRec->byId($oRecord->enroll_key, $aOptions);
          }
          if (empty($oRecord)) {
            continue;
          }
          $oGrpUser = new \stdClass;
          $oGrpUser->uid = $oRecord->userid;
          $oGrpUser->nickname = $oRecord->nickname;
          if ($oBeforeGrpUser = $this->byIdInApp($objGrp->id, $oRecord->enroll_key, ['cascaded' => 'N'])) {
            $updata = [];
            if (!empty($assignTeam) && is_object($assignTeam)) {
              $updata['team_id'] = $assignTeam->team_id;
              $updata['team_title'] = $this->escape($assignTeam->title);
            }
            if ($oBeforeGrpUser->nickname !== $oGrpUser->nickname) {
              $updata['nickname'] = $this->escape($oGrpUser->nickname);
            }
            if (!empty($updata)) {
              $this->_modify($oRecord->enroll_key, $updata);
            }
            // 已经同步过的用户
            $this->setData($objGrp, $oRecord->enroll_key, $oRecord->data);
          } else {
            // 新用户
            $aOptions2 = ['enroll_key' => $oRecord->enroll_key, 'enroll_at' => $oRecord->enroll_at];
            if (!empty($assignTeam) && is_object($assignTeam)) {
              $aOptions2['team_id'] = $assignTeam->team_id;
              $aOptions2['team_title'] = $this->escape($assignTeam->title);
            }
            $this->enroll($objGrp, $oGrpUser, $aOptions2);
            $this->setData($objGrp, $oRecord->enroll_key, $oRecord->data);
          }
          $cnt++;
        } else {
          // 删除用户
          if ($this->remove($objGrp->id, $oRecord->enroll_key, true)) {
            $cnt++;
          }
        }
      }
    }

    return $cnt;
  }
  /**
   *
   */
  private function _modify($ek, $oData)
  {
    $rst = $this->update(
      'xxt_group_record',
      $oData,
      ['enroll_key' => $ek]
    );
    return $rst;
  }
  /**
   * 获取通讯录用户的data
   *
   */
  private function _getMschData($objGrp, $id)
  {
    /* 获取变化的登记数据 */
    $modelRec = $this->model('site\user\member');
    $q = [
      'm.id enroll_key,m.modify_at enroll_at,m.name nickname,m.name,m.mobile,m.email,m.extattr,m.forbidden,m.userid,a.wx_openid,a.qy_openid,a.headimgurl',
      'xxt_site_member m,xxt_site_account a',
      "m.id = $id and a.uid = m.userid",
    ];
    $oRecord = $modelRec->query_obj_ss($q);
    if ($oRecord === false) {
      return false;
    }
    if (!empty($oRecord->extattr)) {
      $extattr = json_decode($oRecord->extattr);
      foreach ($extattr as $k => $e) {
        $oRecord->{$k} = $e;
      }
    }

    $dataSchemas = $objGrp->dataSchemas;
    $data = new \stdClass;
    foreach ($dataSchemas as $ds) {
      if (!empty($ds->format) && in_array($ds->format, ['name', 'email', 'mobile'])) {
        $data->{$ds->id} = $oRecord->{$ds->format};
      } else {
        $data->{$ds->id} = !empty($oRecord->{$ds->id}) ? $oRecord->{$ds->id} : '';
      }
    }
    $oRecord->data = $data;

    return $oRecord;
  }
  /**
   * 从通讯录中导入数据
   */
  public function assocWithMschema($oGrpApp, $mschemaId, $keepExistent = false)
  {
    $modelMsc = $this->model('site\user\memberschema');

    $oMschema = $modelMsc->byId($mschemaId);
    $dataSchemas = [];
    if ($oMschema->attr_mobile[0] === '0') {
      $dataSchema = new \stdClass;
      $dataSchema->id = 'ms_' . $mschemaId . '_mobile';
      $dataSchema->type = 'shorttext';
      $dataSchema->title = '手机号';
      $dataSchema->format = 'mobile';
      $dataSchemas[] = $dataSchema;
    }
    if ($oMschema->attr_email[0] === '0') {
      $dataSchema = new \stdClass;
      $dataSchema->id = 'ms_' . $mschemaId . '_email';
      $dataSchema->type = 'shorttext';
      $dataSchema->title = '电子邮件';
      $dataSchema->format = 'email';
      $dataSchemas[] = $dataSchema;
    }
    if ($oMschema->attr_name[0] === '0') {
      $dataSchema = new \stdClass;
      $dataSchema->id = 'ms_' . $mschemaId . '_name';
      $dataSchema->type = 'shorttext';
      $dataSchema->title = '姓名';
      $dataSchema->format = 'name';
      $dataSchemas[] = $dataSchema;
    }
    $extDataSchemas = [];
    if (!empty($oMschema->extAttrs)) {
      foreach ($oMschema->extAttrs as $ea) {
        $dataSchema = new \stdClass;
        foreach ($ea as $key => $val) {
          $dataSchema->{$key} = $val;
        }
        $extDataSchemas[] = $dataSchema;
      }
    }

    $oMschema->dataSchemas = array_merge($dataSchemas, $extDataSchemas);

    /* 导入活动定义 */
    $this->update(
      'xxt_group',
      [
        'last_sync_at' => 0,
        'source_app' => '{"id":"' . $mschemaId . '","type":"mschema"}',
        'data_schemas' => $this->toJson($oMschema->dataSchemas),
      ],
      ['id' => $oGrpApp->id]
    );
    /* 清空已有分组数据 */
    if ($keepExistent !== true)
      $this->clean($oGrpApp->id, true);

    return $oMschema;
  }
  /**
   * 关联报名活动数据
   */
  public function assocWithEnroll($oGrpApp, $byApp, $keepExistent = false)
  {
    $modelEnl = $this->model('matter\enroll');

    $oSourceApp = $modelEnl->byId($byApp, ['fields' => 'id,data_schemas,assigned_nickname', 'cascaded' => 'N']);
    $aDataSchemas = $oSourceApp->dataSchemas;

    /* 移除题目中和其他活动、通讯录的关联信息 */
    $modelEnl->replaceAssocSchema($aDataSchemas);
    $modelEnl->replaceMemberSchema($aDataSchemas, null, true);

    /* 导入活动定义 */
    $this->update(
      'xxt_group',
      [
        'last_sync_at' => 0,
        'source_app' => '{"id":"' . $byApp . '","type":"enroll"}',
        'data_schemas' => $this->escape($this->toJson($aDataSchemas)),
        'assigned_nickname' => $oSourceApp->assignedNickname,
      ],
      ['id' => $oGrpApp->id]
    );
    $oGrpApp->dataSchemas = $oSourceApp->dataSchemas;
    /* 清空已有分组数据 */
    if ($keepExistent !== true)
      $this->clean($oGrpApp->id, true);

    return $oSourceApp;
  }
  /**
   * 从签到活动导入数据
   * 如果指定了包括报名数据，只需要从报名活动中导入登记项的定义，签到时已经自动包含了报名数据
   */
  public function assocWithSignin($oGrpApp, $byApp, $includeEnroll = 'Y', $keepExistent = false)
  {
    $modelSignin = $this->model('matter\signin');
    $oSourceApp = $modelSignin->byId($byApp, ['fields' => 'entry_rule,data_schemas,assigned_nickname', 'cascaded' => 'N']);
    $aSourceDataSchemas = $oSourceApp->dataSchemas;
    /**
     * 导入报名数据，需要合并签到和报名的登记项
     */
    if ($includeEnroll === 'Y') {
      if (!empty($oSourceApp->entryRule->enroll->id)) {
        $modelEnl = $this->model('matter\enroll');
        $enrollApp = $modelEnl->byId($oSourceApp->entryRule->enroll->id, ['fields' => 'data_schemas', 'cascaded' => 'N']);
        $diff = array_udiff($enrollApp->dataSchemas, $aSourceDataSchemas, create_function('$a,$b', 'return strcmp($a->id,$b->id);'));
        $aSourceDataSchemas = array_merge($aSourceDataSchemas, $diff);
      }
    }

    /* 移除题目中和其他活动、通讯录的关联信息 */
    $modelSignin->replaceAssocSchema($aSourceDataSchemas);
    $modelSignin->replaceMemberSchema($aSourceDataSchemas, null, true);

    /* 导入活动定义 */
    $this->update(
      'xxt_group',
      [
        'last_sync_at' => 0,
        'source_app' => '{"id":"' . $byApp . '","type":"signin"}',
        'data_schemas' => $this->escape($this->toJson($aSourceDataSchemas)),
        'assigned_nickname' => $oSourceApp->assigned_nickname,
      ],
      ['id' => $oGrpApp->id]
    );
    $oGrpApp->dataSchemas = $aSourceDataSchemas;

    /* 清空已有数据 */
    if ($keepExistent !== true)
      $this->clean($oGrpApp->id, true);

    return $oSourceApp;
  }
  /**
   * 删除一个分组用户
   *
   * @param string $appId
   * @param string $ek
   */
  public function remove($appId, $ek, $byDelete = false)
  {
    if ($byDelete) {
      $rst = $this->delete(
        'xxt_group_record_data',
        ['aid' => $appId, 'enroll_key' => $ek]
      );
      $rst = $this->delete(
        'xxt_group_record',
        ['aid' => $appId, 'enroll_key' => $ek]
      );
    } else {
      $rst = $this->update(
        'xxt_group_record_data',
        ['state' => 100],
        ['aid' => $appId, 'enroll_key' => $ek]
      );
      $rst = $this->update(
        'xxt_group_record',
        ['state' => 100],
        ['aid' => $appId, 'enroll_key' => $ek]
      );
    }

    return $rst;
  }
  /**
   * 清除所有记录
   *
   * @param string $appId
   */
  public function clean($appId, $byDelete = false)
  {
    if ($byDelete) {
      $rst = $this->delete(
        'xxt_group_record_data',
        ['aid' => $appId]
      );
      $rst = $this->delete(
        'xxt_group_record',
        ['aid' => $appId]
      );
    } else {
      $rst = $this->update(
        'xxt_group_record_data',
        ['state' => 0],
        ['aid' => $appId]
      );
      $rst = $this->update(
        'xxt_group_record',
        ['state' => 0],
        ['aid' => $appId]
      );
    }

    return $rst;
  }
}
