<?php

namespace pl\fe\matter\link;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/**
 *
 */
class main extends \pl\fe\matter\main_base
{
  /**
   * 在调用每个控制器的方法前调用
   */
  public function tmsBeforeEach()
  {
    // 要求登录用户操作
    if (false === ($oUser = $this->accountUser())) {
      return [false, new \ResponseTimeout()];
    }
    $this->user = $oUser;
    return [true];
  }
  /**
   *
   */
  public function index_action()
  {
    \TPL::output('/pl/fe/matter/link/frame');
    exit;
  }
  /**
   *
   */
  public function setting_action()
  {
    \TPL::output('/pl/fe/matter/link/frame');
    exit;
  }
  /**
   *
   */
  public function get_action($id)
  {
    $model = $this->model();

    $oLink = $this->model('matter\link')->byIdWithParams($id);
    if ($oLink === false) {
      return new \ObjectNotFoundError();
    }
    /* 指定分组活动访问 */
    if (isset($oLink->entry_rule->scope->group) && $oLink->entry_rule->scope->group === 'Y') {
      if (isset($oLink->entry_rule->group)) {
        !is_object($oLink->entry_rule->group) && $oLink->entry_rule->group = (object) $oLink->entry_rule->group;
        $oRuleApp = $oLink->entry_rule->group;
        if (!empty($oRuleApp->id)) {
          $oGroupApp = $this->model('matter\group')->byId($oRuleApp->id, ['fields' => 'title', 'cascaded' => 'N']);
          if ($oGroupApp) {
            $oRuleApp->title = $oGroupApp->title;
            if (!empty($oRuleApp->team->id)) {
              $oGrpTeam = $this->model('matter\group\team')->byId($oRuleApp->team->id, ['fields' => 'title']);
              if ($oGrpTeam) {
                $oRuleApp->team->title = $oGrpTeam->title;
              }
            }
          }
        }
      }
    }
    $oLink->attachments = $model->query_objs_ss(['*', 'xxt_matter_attachment', ['matter_id' => $id, 'matter_type' => 'link']]);

    return new \ResponseData($oLink);
  }
  /**
   *
   */
  public function list_action($site, $cascade = 'Y')
  {
    $model = $this->model();
    $oOptions = $this->getPostJson();
    /**
     * get links
     */
    $q = [
      "*",
      'xxt_link l',
      "siteid='$site' and state=1",
    ];
    if (!empty($oOptions->byTitle)) {
      $q[2] .= " and title like '%" . $oOptions->byTitle . "%'";
    }
    if (!empty($oOptions->byCreator)) {
      $q[2] .= " and creater_name like '%" . $oOptions->byCreator . "%'";
    }
    if (!empty($oOptions->byTags)) {
      foreach ($oOptions->byTags as $tag) {
        $q[2] .= " and matter_mg_tag like '%" . $tag->id . "%'";
      }
    }
    if (isset($oOptions->byStar) && $oOptions->byStar === 'Y') {
      $q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='article' and t.matter_id=l.id and userid='{$this->user->id}')";
    }
    $q2['o'] = 'create_at desc';
    $links = $model->query_objs_ss($q, $q2);
    /**
     * get params and channels
     */
    if ($cascade === 'Y') {
      $modelChn = $this->model('matter\channel');
      foreach ($links as $l) {
        /**
         * params
         */
        $q = array(
          'id,pname,pvalue',
          'xxt_link_param',
          "link_id=$l->id"
        );
        $l->params = $model->query_objs_ss($q);
        /**
         * channels
         */
        $l->channels = $modelChn->byMatter($l->id, 'link');
        /**
         * acl
         */
        $l->type = 'link';
      }
    }

    return new \ResponseData(['docs' => $links, 'total' => count($links)]);
  }
  /**
   *
   */
  public function cascade_action($id)
  {
    /**
     * params
     */
    $q = [
      'id,pname,pvalue',
      'xxt_link_param',
      "link_id='$id'",
    ];
    $l['params'] = $this->model()->query_objs_ss($q);
    /**
     * channels
     */
    $l['channels'] = $this->model('matter\channel')->byMatter($id, 'link');

    return new \ResponseData($l);
  }
  /**
   * 创建外部链接素材
   */
  public function create_action($site = null, $mission = null, $title = '新链接')
  {
    $modelLink = $this->model('matter\link');
    $oLink = new \stdClass;
    /*从站点或项目获取的定义*/
    if (empty($mission)) {
      $oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
      if (false === $oSite) {
        return new \ObjectNotFoundError();
      }
      $oLink->siteid = $oSite->id;
      $oLink->pic = $oSite->heading_pic; //使用站点的缺省头图
      $oLink->summary = '';
    } else {
      $modelMis = $this->model('matter\mission');
      $oMission = $modelMis->byId($mission);
      $oLink->siteid = $oMission->siteid;
      $oLink->summary = $modelLink->escape($oMission->summary);
      $oLink->pic = $oMission->pic;
      $oLink->mission_id = $oMission->id;
    }

    $oLink->title = $modelLink->escape($title);

    $oLink = $modelLink->create($this->user, $oLink);

    /* 记录操作日志 */
    $this->model('matter\log')->matterOp($oLink->siteid, $this->user, $oLink, 'C');

    return new \ResponseData($oLink);
  }
  /**
   * 更新链接属性
   */
  public function update_action($id)
  {
    $modelLink = $this->model('matter\link');
    $oLink = $modelLink->byId($id);
    if (false === $oLink) {
      return new \ObjectNotFoundError();
    }
    /**
     * 处理数据
     */
    $oUpdated = $this->getPostJson(false);
    foreach ($oUpdated as $n => $v) {
      if ($n === 'entry_rule') {
        if ($v->scope === 'group') {
          if (isset($v->group->title)) {
            unset($v->group->title);
          }
          if (isset($v->group->team->title)) {
            unset($v->group->team->title);
          }
        }
        $oUpdated->entry_rule = $modelLink->escape($modelLink->toJson($v));
      } else if ($n === 'config') {
        $oUpdated->config = $modelLink->escape($modelLink->toJson($v));
      } else {
        $oUpdated->{$n} = $modelLink->escape($v);
      }

      $oLink->{$n} = $v;
    }

    if ($oLink = $modelLink->modify($this->user, $oLink, $oUpdated)) {
      $this->model('matter\log')->matterOp($oLink->siteid, $this->user, $oLink, 'U');
    }

    return new \ResponseData($oLink);
  }
  /**
   * 删除链接
   */
  public function remove_action($id)
  {
    $modelLink = $this->model('matter\link');
    $oLink = $modelLink->byId($id);
    if (false === $oLink) {
      return new \ObjectNotFoundError();
    }

    $rst = $modelLink->remove($this->user, $oLink);

    return new \ResponseData($rst);
  }
  /**
   *
   * @param $linkid link's id
   */
  public function paramAdd_action($linkid)
  {
    $p = ['link_id' => $linkid, 'pname' => '', 'pvalue' => ''];

    $id = $this->model()->insert('xxt_link_param', $p);

    return new \ResponseData($id);
  }
  /**
   *
   * 更新参数定义
   *
   * 因为参数的属性之间存在关联，因此要整体更新
   *
   * @param $id parameter's id
   */
  public function paramUpd_action($id)
  {
    $p = $this->getPostJson();

    !empty($p->pvalue) && $p->pvalue = urldecode($p->pvalue);

    $rst = $this->model()->update(
      'xxt_link_param',
      $p,
      "id=$id"
    );

    return new \ResponseData($rst);
  }
  /**
   *
   * @param $id parameter's id
   */
  public function removeParam_action($id)
  {
    $rst = $this->model()->delete('xxt_link_param', "id=$id");

    return new \ResponseData($rst);
  }
}
