<?php

namespace site\fe\user;

use ResponseError;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户历史轨迹
 */
class history extends \site\fe\base
{
  /**
   *
   */
  public function index_action()
  {
    \TPL::output('/site/fe/user/history/main');
    exit;
  }
  /**
   * 获得当前用户在指定站点参与的活动
   *
   * @param string $site site'id
   * @param string $matterType
   */
  public function appList_action($site = '', $matterType = 'enroll,signin', $userid = '')
  {
    $modelAct = $this->model('site\user\account');
    $q = [
      'matter_id,matter_type,matter_title,operate_at,siteid',
      'xxt_log_user_matter',
      "user_last_op='Y' and operation='submit'",
    ];
    // 指定团队下的访问记录
    if (!empty($site) && $site !== 'platform') {
      $q[2] .= " and siteid='{$site}'";
    }
    // 指定用户的访问记录
    if (!empty($userid)) {
      $q[2] .= " and userid='{userid}'";
    } else if (empty($this->who->unionid)) {
      $q[2] .= " and userid='{$this->who->uid}'";
    } else {
      $aSiteAccounts = $modelAct->byUnionid($this->who->unionid, ['fields' => 'uid']);
      $q[2] .= " and userid in('";
      foreach ($aSiteAccounts as $index => $oSiteAccount) {
        if ($index > 0) {
          $q[2] .= "','";
        }
        $q[2] .= $oSiteAccount->uid;
      }
      $q[2] .= "')";
    }
    // 指定素材类型
    if (!empty($matterType)) {
      $matterType = explode(',', $matterType);
      $matterType = "'" . implode("','", $matterType) . "'";
      $q[2] .= " and matter_type in (" . $matterType . ")";
    }

    $q2 = ['o' => 'operate_at desc'];

    $logs = $modelAct->query_objs_ss($q, $q2);

    $oResult = new \stdClass;
    $oResult->apps = $logs;

    return new \ResponseData($oResult);
  }
  /**
   * 获得当前用户在指定站点参与的活动
   *
   * @param string $site site'id
   * @param string $matterType
   */
  public function appCount_action($site = '', $matterType = 'enroll,signin', $userid = '')
  {
    $modelAct = $this->model('site\user\account');
    $q = [
      'count(distinct matter_id)',
      'xxt_log_user_matter',
      "user_last_op='Y' and operation='submit'",
    ];
    // 指定团队下的访问记录
    if (!empty($site) && $site !== 'platform') {
      $q[2] .= " and siteid='{$site}'";
    }
    // 指定用户的访问记录
    if (!empty($userid)) {
      $q[2] .= " and userid='{userid}'";
    } else if (empty($this->who->unionid)) {
      $q[2] .= " and userid='{$this->who->uid}'";
    } else {
      $aSiteAccounts = $modelAct->byUnionid($this->who->unionid, ['fields' => 'uid']);
      $q[2] .= " and userid in('";
      foreach ($aSiteAccounts as $index => $oSiteAccount) {
        if ($index > 0) {
          $q[2] .= "','";
        }
        $q[2] .= $oSiteAccount->uid;
      }
      $q[2] .= "')";
    }
    // 指定素材类型
    if (!empty($matterType)) {
      $matterType = explode(',', $matterType);
      $matterType = "'" . implode("','", $matterType) . "'";
      $q[2] .= " and matter_type in (" . $matterType . ")";
    }

    $count = (int) $modelAct->query_val_ss($q);

    return new \ResponseData($count);
  }
  /**
   * 获得当前用户在指定团队参与的项目
   * 
   * 1、用户访问过项目
   *
   * @param string $site
   */
  public function missionList_action($site, $userid = '')
  {
    $modelAct = $this->model('site\user\account');
    $q = [
      'distinct mission_id,mission_title,siteid',
      'xxt_log_user_matter',
      "mission_id<>0 and user_last_op='Y'",
    ];

    // 指定团队下的访问记录
    if (!empty($site) && $site !== 'platform') {
      $q[2] .= " and siteid='{$site}'";
    }

    // 指定用户的访问记录
    if (!empty($userid)) {
      $q[2] .= " and userid='{userid}'";
    } else if (empty($this->who->unionid)) {
      $q[2] .= " and userid='{$this->who->uid}'";
    } else {
      $aSiteAccounts = $modelAct->byUnionid($this->who->unionid, ['fields' => 'uid']);
      $q[2] .= " and userid in('";
      foreach ($aSiteAccounts as $index => $oSiteAccount) {
        if ($index > 0) {
          $q[2] .= "','";
        }
        $q[2] .= $oSiteAccount->uid;
      }
      $q[2] .= "')";
    }

    $logs = $modelAct->query_objs_ss($q);
    if (count($logs)) {
      $q[0] = 'max(operate_at)';
      $w = $q[2];
      foreach ($logs as &$log) {
        $q[2] = $w . " and mission_id={$log->mission_id}";
        $log->operate_at = (int) $modelAct->query_val_ss($q);
      }
      usort($logs, function ($mis1, $mis2) {
        return $mis2->operate_at - $mis1->operate_at;
      });
    }

    $oResult = new \stdClass;
    $oResult->missions = $logs;

    return new \ResponseData($oResult);
  }

  /**
   * 获得当前用户在指定站点参与的项目的数量
   *
   * @param string $site
   */
  public function missionCount_action($site, $userid = '')
  {
    $modelAct = $this->model('site\user\account');
    $q = [
      'count(distinct mission_id)',
      'xxt_log_user_matter',
      "mission_id<>0 and user_last_op='Y'",
    ];

    // 指定团队下的访问记录
    if (!empty($site) && $site !== 'platform') {
      $q[2] .= " and siteid='{$site}'";
    }

    // 指定用户的访问记录
    if (!empty($userid)) {
      $q[2] .= " and userid='{userid}'";
    } else if (empty($this->who->unionid)) {
      $q[2] .= " and userid='{$this->who->uid}'";
    } else {
      $aSiteAccounts = $modelAct->byUnionid($this->who->unionid, ['fields' => 'uid']);
      $q[2] .= " and userid in('";
      foreach ($aSiteAccounts as $index => $oSiteAccount) {
        if ($index > 0) {
          $q[2] .= "','";
        }
        $q[2] .= $oSiteAccount->uid;
      }
      $q[2] .= "')";
    }

    $count = (int) $modelAct->query_val_ss($q);

    return new \ResponseData($count);
  }
  /**
   * 
   */
  private function _missionList($unionid)
  {
    /* 用户加入的所有通讯录 */
    $model = $this->model();
    $q = ['siteid,schema_id', 'xxt_site_member', ['unionid' => $unionid, 'verified' => 'Y', 'forbidden' => 'N']];
    $mschemas = $model->query_objs_ss($q);

    /* 将用户加入的所有通讯录，按团队id分组 */
    $missions = array_reduce($mschemas, function ($result, $mschema) use ($model) {
      $q = ['siteid,id,title,start_at,end_at', 'xxt_mission', ['siteid' => $mschema->siteid, 'state' => '1', 'entry_rule' => (object) ['op' => 'like', 'pat' => '%"member":{"' . $mschema->schema_id . '"%']]];
      $missions =  $model->query_objs_ss($q);
      foreach ($missions as $mission) $result[] = $mission;
      return $result;
    }, []);

    /* 按照项目的开始时间进行排序 */
    usort($missions, function ($a, $b) {
      return $b->start_at - $a->start_at;
    });

    return $missions;
  }
  /**
   * 获得当前用户在指定团队参与的项目
   * 
   * 1、项目的进入规则为通讯录，用户加入了通讯录
   *
   * @param string $site
   */
  public function missionList2_action()
  {
    if (empty($this->who->unionid)) return new ResponseError('请登录后再访问');

    $unionid = $this->who->unionid;

    $missions = $this->_missionList($unionid);

    $oResult = new \stdClass;
    $oResult->missions = $missions;

    return new \ResponseData($oResult);
  }
  /**
   * 获得当前用户在指定站点参与的项目的数量
   *
   * @param string $site
   */
  public function missionCount2_action($site, $userid = '')
  {
    if (empty($this->who->unionid)) return new ResponseError('请登录后再访问');

    $unionid = $this->who->unionid;

    $missions = $this->_missionList($unionid);

    $count = count($missions);

    return new \ResponseData($count);
  }
}
