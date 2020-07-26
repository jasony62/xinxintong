<?php

namespace pl\fe\matter\channel;

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
  public function index_action($id)
  {
    $access = $this->accessControlUser('channel', $id);
    if ($access[0] === false) {
      die($access[1]);
    }

    \TPL::output('/pl/fe/matter/channel/frame');
    exit;
  }
  /**
   *
   */
  public function get_action($id)
  {
    $modelChn = $this->model('matter\channel');
    if ($oChannel = $modelChn->byId($id)) {
      $oChannel->entryUrl = $modelChn->getEntryUrl($oChannel->siteid, $id);
      /* 所属项目 */
      if ($oChannel->mission_id) {
        $oChannel->mission = $this->model('matter\mission')->byId($oChannel->mission_id);
      }
      !empty($oChannel->matter_mg_tag) && $oChannel->matter_mg_tag = json_decode($oChannel->matter_mg_tag);

      $oChannel->matters = $modelChn->getMatters($id, $oChannel, $oChannel->siteid);
    }

    return new \ResponseData($oChannel);
  }
  /**
   * 团队下的频道
   *
   * @param string $site site's id
   * @param string $cascade 是否获得频道内的素材和访问控制列表
   */
  public function list_action($site, $page = 1, $size = 12, $cascade = 'Y')
  {
    $modelChn = $this->model('matter\channel');
    $oOptions = $this->getPostJson();
    /**
     * 素材的来源
     */
    $q = [
      '*',
      'xxt_channel c',
      "siteid = '" . $site . "' and state = 1",
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
      $q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='channel' and t.matter_id=c.id and userid='{$this->user->id}')";
    }

    $q2['o'] = 'create_at desc';
    $q2['r']['o'] = ($page - 1) * $size;
    $q2['r']['l'] = $size;

    $channels = $modelChn->query_objs_ss($q, $q2);
    /* 获得子资源 */
    if ($channels) {
      foreach ($channels as $c) {
        $c->type = 'channel';
      }
      if ($cascade == 'Y') {
        foreach ($channels as $c) {
          $c->url = $modelChn->getEntryUrl($site, $c->id);
          $c->matters = $modelChn->getMatters($c->id, $c, $site);
        }
      }
    }
    $aResult = ['docs' => $channels];

    $q[0] = 'count(*)';
    $total = (int) $modelChn->query_val_ss($q);
    $aResult['total'] = $total;

    return new \ResponseData($aResult);
  }
  /**
   * 频道素材
   */
  public function mattersList_action($id, $weight = '', $page = '', $size = '')
  {
    $modelChn = $this->model('matter\channel');
    $oChannel = $modelChn->byId($id, ['fields' => 'id,matter_type,orderby,volume,state']);
    if ($oChannel === false || $oChannel->state != 1) {
      return new \ParameterError();
    }

    $params = new \stdClass;
    if (!empty($page) && !empty($size)) {
      $params->page = $page;
      $params->size = $size;
    }
    if (!empty($weight)) {
      $params->weight = $weight;
    }
    $matters = $modelChn->getMattersNoLimit($oChannel->id, $this->user->id, $params, $oChannel);

    return new \ResponseData($matters);
  }
  /**
   * 在指定团队下创建频道素材
   */
  public function create_action($site = null, $mission = null)
  {
    if (empty($site) && empty($mission)) {
      return new \ParameterError();
    }

    $oPosted = $this->getPostJson();
    $oChannel = new \stdClass;
    if (!empty($mission)) {
      $oMission = $this->model('matter\mission')->byId($mission);
      if (false === $oMission) {
        return new \ObjectNotFoundError();
      }
      $oChannel->siteid = $oMission->siteid;
      $oChannel->mission_id = $oMission->id;
    } else if (!empty($site)) {
      $oSite = $this->model('site')->byId($site);
      if (false === $oSite) {
        return new \ObjectNotFoundError();
      }
      $oChannel->siteid = $oSite->id;
    }
    $oChannel->summary = '';
    $oChannel->fixed_title = '';

    $modelCh = $this->model('matter\channel')->setOnlyWriteDbConn(true);

    $q = ['count(*)', 'xxt_channel', ['siteid' => $site, 'state' => 1]];
    $countOfChn = (int) $modelCh->query_val_ss($q);

    $oChannel->title = isset($oPosted->title) ? $oPosted->title : ('新频道-' . ++$countOfChn);
    $oChannel->matter_type = '';

    $oChannel = $modelCh->create($this->user, $oChannel);

    /* 记录操作日志 */
    $this->model('matter\log')->matterOp($site, $this->user, $oChannel, 'C');

    return new \ResponseData($oChannel);
  }
  /**
   * 更新频道的属性信息
   *
   * @param string $site site's id
   * @param int $id channel's id
   *
   */
  public function update_action($id)
  {
    $modelCh = $this->model('matter\channel');
    $oChannel = $modelCh->byId($id, ['fields' => 'siteid,id,state,title,mission_id']);
    if (false === $oChannel) {
      return new \ObjectNotFoundError();
    }

    $aUpdatedHomeCh = []; // 更新站点频道
    $oUpdated = new \stdClass;
    $oPosted = $this->getPostJson(false);
    foreach ($oPosted as $k => $v) {
      if ($k === 'config') {
        $oUpdated->config = $modelCh->escape($modelCh->toJson($v));
      } else if (in_array($k, ['title', 'summary', 'fixed_title'])) {
        $aUpdatedHomeCh[$k] = $oUpdated->{$k} = $modelCh->escape($v);
      } else if ($k === 'pic') {
        $aUpdatedHomeCh[$k] = $oUpdated->{$k} = $modelCh->escape($v);
      } else {
        $oUpdated->{$k} = $modelCh->escape($v);
      }
    }

    if ($oChannel = $modelCh->modify($this->user, $oChannel, $oUpdated)) {
      /* 更新站点频道中的信息 */
      if (isset($aUpdatedHomeCh['fixed_title'])) {
        unset($aUpdatedHomeCh['fixed_title']);
      }
      if (count($aUpdatedHomeCh)) {
        $modelCh->update('xxt_site_home_channel', $aUpdatedHomeCh, ['channel_id' => $id]);
      }
      $this->model('matter\log')->matterOp($oChannel->siteid, $this->user, $oChannel, 'U');
    }

    return new \ResponseData($oChannel);
  }
  /**
   *
   * $id channel's id.
   * $pos top|bottom
   *
   * post
   * $t matter's type.
   * $id matter's id.
   *
   */
  public function setfixed_action($id, $pos)
  {
    $oUser = $this->user;
    $current = $this->getRequestTime();
    $matters = $this->getPostJson();
    $modelChn = $this->model('matter\channel')->setOnlyWriteDbConn(true);

    if ($pos === 'top') {
      // 获取置顶最大排序
      $q = [
        "max(seq)",
        'xxt_channel_matter',
        ["channel_id" => $id, "seq" => (object) ['op' => '<', 'pat' => 10000]],
      ];
      $maxSeq = (int) $modelChn->query_val_ss($q);
      foreach ($matters as $matter) {
        /* 是否已经加入到频道中 */
        $q2 = [
          'channel_id',
          'xxt_channel_matter',
          ["matter_id" => $matter->id, "matter_type" => $matter->type, "channel_id" => $id],
        ];
        $oMatter = $modelChn->query_obj_ss($q2);
        if ($oMatter) {
          $modelChn->update('xxt_channel_matter', ['seq' => ++$maxSeq], ["matter_id" => $matter->id, "matter_type" => $matter->type, "channel_id" => $id]);
        } else {
          // 如果没有数据就加入到频道中
          $newc = [];
          $newc['matter_id'] = $matter->id;
          $newc['matter_type'] = $matter->type;
          $newc['create_at'] = $current;
          $newc['creater'] = $oUser->id;
          $newc['creater_name'] = $this->escape($oUser->name);
          $newc['channel_id'] = $id;
          $newc['seq'] = ++$maxSeq;
          $modelChn->insert('xxt_channel_matter', $newc, false);
        }
      }
    } else if ($pos === 'bottom') {
      // 获取置底最大排序
      $q = [
        "max(seq)",
        'xxt_channel_matter',
        ["channel_id" => $id, "seq" => (object) ['op' => '>', 'pat' => 20000]],
      ];
      $maxSeq = (int) $modelChn->query_val_ss($q);
      if ($maxSeq == 0) {
        $maxSeq = 20000;
      }
      foreach ($matters as $matter) {
        /* 是否已经加入到频道中 */
        $q2 = [
          'channel_id',
          'xxt_channel_matter',
          ["matter_id" => $matter->id, "matter_type" => $matter->type, "channel_id" => $id],
        ];
        $oMatter = $modelChn->query_obj_ss($q2);
        if ($oMatter) {
          $modelChn->update('xxt_channel_matter', ['seq' => ++$maxSeq], ["matter_id" => $matter->id, "matter_type" => $matter->type, "channel_id" => $id]);
        } else {
          // 如果没有数据就加入到频道中
          $newc = [];
          $newc['matter_id'] = $matter->id;
          $newc['matter_type'] = $matter->type;
          $newc['create_at'] = $current;
          $newc['creater'] = $oUser->id;
          $newc['creater_name'] = $oUser->name;
          $newc['channel_id'] = $id;
          $newc['seq'] = ++$maxSeq;
          $modelChn->insert('xxt_channel_matter', $newc, false);
        }
      }
    }

    $params = new \stdClass;
    $params->weight = $pos;
    $matters = $modelChn->getMattersNoLimit($id, $oUser->id, $params);

    return new \ResponseData($matters);
  }
  /**
   *
   * $id channel's id.
   * $pos top|bottom
   *
   * post
   * $t matter's type.
   * $id matter's id.
   *
   */
  public function unfixed_action($id, $pos)
  {
    $matter = $this->getPostJson();
    if (empty($matter->id) || empty($matter->type)) {
      return new \ResponseError('参数错误');
    }

    $modelChn = $this->model('matter\channel')->setOnlyWriteDbConn(true);
    $modelChn->update('xxt_channel_matter', ['seq' => 10000], ["matter_id" => $matter->id, "matter_type" => $matter->type, "channel_id" => $id]);

    $params = new \stdClass;
    $params->weight = $pos;
    $matters = $modelChn->getMattersNoLimit($id, $oUser->id, $params);

    return new \ResponseData($matters);
  }
  /**
   * 建立频道和素材的关联
   *
   * @param string $site site's id
   * @param int $channel channel's id
   *
   */
  public function addMatter_action($channel = null)
  {
    $oUser = $this->user;
    $relations = $this->getPostJson();
    $modelCh = $this->model('matter\channel')->setOnlyWriteDbConn(true);

    $matters = is_array($relations->matter) ? $relations->matter : [$relations->matter];
    if (empty($channel)) {
      $channels = $relations->channels;
      foreach ($channels as $channel) {
        foreach ($matters as $matter) {
          $modelCh->addMatter($channel->id, $matter, $oUser->id, $oUser->name);
        }
      }
    } else {
      foreach ($matters as $matter) {
        $modelCh->addMatter($channel, $matter, $oUser->id, $oUser->name);
      }
    }
    return new \ResponseData('ok');
  }
  /**
   *
   */
  public function removeMatter_action($id, $reload = 'N')
  {
    $matter = $this->getPostJson();

    $modelCh = $this->model('matter\channel');
    $modelCh->setOnlyWriteDbConn(true);

    $rst = $modelCh->removeMatter($id, $matter);

    if ($reload === 'Y') {
      $matters = $modelCh->getMatters($id);
      return new \ResponseData($matters);
    } else {
      return new \ResponseData($rst);
    }
  }
  /**
   * 删除频道
   */
  public function remove_action($id)
  {
    $modelCh = $this->model('matter\channel');
    $oChannel = $modelCh->byId($id, ['fields' => $modelCh::LOG_FIELDS]);
    if (false === $oChannel) {
      return new \ObjectNotFoundError();
    }
    $rst = $modelCh->remove($this->user, $oChannel);

    return new \ResponseData($rst);
  }
  /**
   * 创建频道定制页面
   */
  public function pageCreate_action($site, $id, $page)
  {
    $code = $this->model('code\page')->create($site, $this->user->id);

    $rst = $this->model()->update(
      'xxt_channel',
      [
        $page . '_page_id' => $code->id,
        $page . '_page_name' => $code->name,
      ],
      ['siteid' => $site, 'id' => $id]
    );

    return new \ResponseData($code);
  }
  /**
   * 重置定制页面
   *
   * @param int $codeId
   */
  public function pageReset_action($id, $page)
  {
    $modelChn = $this->model('matter\channel');
    $channel = $modelChn->byId($id);
    $data = [
      'html' => '',
      'css' => '',
      'js' => '',
    ];
    $modelCode = $this->model('code\page');
    $code = $modelCode->lastByName($channel->siteid, $channel->{$page . '_page_name'});
    $rst = $modelCode->modify($code->id, $data);

    return new \ResponseData($rst);
  }
  /**
   * 置顶素材排序
   */
  public function sortMatters_action($id, $weight = '')
  {
    if (!in_array($weight, ['top', 'bottom'])) {
      return new \ResponseError('只支持置顶或置底素材排序');
    }
    $modelChn = $this->model('matter\channel');
    $oChannel = $modelChn->byId($id, ['fields' => 'id,matter_type,orderby,volume,state']);
    if ($oChannel === false || $oChannel->state != 1) {
      return new \ParameterError();
    }

    $matters = $this->getPostJson();
    if (empty($matters) || count($matters) === 0) {
      return new \ResponseError('指定素材数量错误');
    }

    //
    $where = "channel_id = {$oChannel->id}";
    if ($weight === 'top') {
      $where .= " and seq < 10000";
    } else {
      $where .= " and seq > 20000";
    }
    /**
     * delete relation.
     */
    $modelChn->delete('xxt_channel_matter', $where);
    /**
     * insert new relation.
     */
    $current = $this->getRequestTime();
    $seq = $weight === 'top' ? 0 : 20000;

    foreach ($matters as $matter) {
      // 如果没有数据就加入到频道中
      $newc = [];
      $newc['matter_id'] = $matter->id;
      $newc['matter_type'] = $matter->type;
      $newc['create_at'] = $current;
      $newc['creater'] = $this->user->id;
      $newc['creater_name'] = $this->escape($this->user->name);
      $newc['channel_id'] = $id;
      $newc['seq'] = ++$seq;
      $modelChn->insert('xxt_channel_matter', $newc, false);
    }

    $params = new \stdClass;
    $params->weight = $weight;
    $matters = $modelChn->getMattersNoLimit($id, $this->user->id, $params);

    return new \ResponseData($matters);
  }
}
