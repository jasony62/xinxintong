<?php

namespace matter;

require_once dirname(__FILE__) . '/article_base.php';

class channel_model extends article_base
{
  /**
   * 记录日志时需要的列
   */
  const LOG_FIELDS = 'siteid,mission_id,id,title';
  /**
   *
   */
  protected function table()
  {
    return 'xxt_channel';
  }
  /**
   * 获得一个账号下的频道
   */
  public function &bySite($siteId, $acceptType = null)
  {
    $q = [
      "*",
      'xxt_channel',
      "siteid='$siteId' and state=1",
    ];
    !empty($acceptType) && $q[2] .= " and (matter_type='' or matter_type='$acceptType')";

    $q2['o'] = 'create_at desc';

    $channels = $this->query_objs_ss($q, $q2);

    return $channels;
  }
  /**
   * 按条件查找单图文
   */
  public function find($site, $page = 1, $size = 10, $options)
  {
    $s = "c.id,c.title,c.modify_at,c.summary,c.pic";
    $w = "c.siteid='$site' and c.state=1";
    if (!empty($options->mission)) {
      $w .= " and mission_id=$options->mission";
    }
    if (empty($options->channel) && empty($options->tag)) {
      $q = [
        $s,
        'xxt_channel c',
        $w,
      ];
    } else if (!empty($options->channel)) {
      /* 按频道筛选 */
      list($channelIds, $num) = is_array($options->channel) ? [implode(',', $options->channel), count($options->channel)] : [$options->channel, count(explode(',', $options->channel))];
      $w .= " and exists (select 1 from xxt_channel_matter cm where cm.matter_type='channel' and cm.matter_id=c.id and cm.channel_id in (" . $channelIds . ")";
      if ($options->logicOR === false) $w .= " group by cm.matter_id having count(cm.matter_id)=" . $num;
      $w .= ")";
      $q = [
        $s,
        'xxt_channel c',
        $w,
      ];
    } else if (!empty($options->tag)) {
      /* 按标签筛选 */
      if (is_array($options->tag)) {
        foreach ($options->tag as $tag) {
          $w .= " and c.matter_cont_tag like '%" . $tag . "%'";
        }
      } else {
        $w .= " and c.matter_cont_tag like '%" . $options->tag . "%'";
      }

      $q = [
        $s,
        'xxt_channel c',
        $w,
      ];
    }

    $q2['o'] = 'a.modify_at desc';
    $q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];

    if ($channels = $this->query_objs_ss($q)) {
      $q[0] = 'count(*)';
      $total = (int) $this->query_val_ss($q);

      foreach ($channels as $article) {
        $article->entryUrl = $this->getEntryUrl($site, $article->id);
      }

      return ['channels' => $channels, 'total' => $total];
    }

    return ['channels' => [], 'total' => 0];
  }
  /**
   * 获得素材的所有频道
   */
  public function &byMatter($id, $type, $oOptions = [])
  {
    $q = [
      "c.id,c.title,cm.create_at,c.config,c.style_page_id,c.header_page_id,c.footer_page_id,c.style_page_name,c.header_page_name,c.footer_page_name,'channel' type",
      'xxt_channel_matter cm,xxt_channel c',
      "cm.matter_id='$id' and cm.matter_type='$type' and cm.channel_id=c.id and c.state=1",
    ];
    if (isset($oOptions['public_visible'])) {
      $q[2] .= " and public_visible='{$oOptions['public_visible']}'";
    }
    $q2['o'] = 'cm.create_at desc';

    $channels = $this->query_objs_ss($q, $q2);
    foreach ($channels as $oChannel) {
      $oChannel->config = empty($oChannel->config) ? new \stdClass : json_decode($oChannel->config);
    }

    return $channels;
  }
  /**
   * 获得返回素材的列列表
   */
  private function matterColumns($type, $prefix = 'm')
  {
    $columns = array('id', 'title', 'summary', 'pic', 'create_at');
    switch ($type) {
      case 'article':
        $columns[] = 'read_num';
        $columns[] = 'share_friend_num';
        $columns[] = 'share_timeline_num';
        $columns[] = 'score';
        $columns[] = 'remark_num';
        break;
    }

    if (!empty($prefix)) {
      $columns = $prefix . '.' . implode(",$prefix.", $columns) . ',"' . $type . '" type';
    } else {
      $columns = implode(",", $columns) . ',"' . $type . '" type';
    }

    return $columns;
  }
  /**
   * 获得素材的排序字段
   */
  private function matterOrderby($type, $ordeby, $default = '', $prefix = 'm')
  {
    $schema = '';
    if ($type === 'article') {
      switch ($ordeby) {
        case 'read':
          $schema = $prefix . '.read_num desc';
          break;
        case 'like':
          $schema = $prefix . '.score desc';
          break;
        case 'remark':
          $schema = $prefix . '.remark_num desc';
          break;
        case 'share':
          $schema = "($prefix.share_friend_num+$prefix.share_timeline_num*50) desc";
          break;
      }
    }
    if (!empty($default)) {
      if (!empty($schema)) {
        $schema = $schema . ',' . $default;
      } else {
        $schema = $default;
      }
    }

    return $schema;
  }
  /**
   * 获得指定频道下的素材
   *
   * $channel_id int 频道的id
   * $channel 频道
   *
   * 置顶+动态+置底
   *
   * return 频道包含的文章，小于等于频道的容量
   */
  public function &getMatters($channel_id, $channel = null)
  {
    $matters = []; // 返回结果
    /**
     * load channel.
     */
    if (empty($channel)) {
      $channel = $this->byId($channel_id, ['fields' => 'id,state,siteid,matter_type,orderby,volume,top_type,top_id,bottom_type,bottom_id']);
    }
    if ($channel === false || $channel->state != 1) {
      return $matters;
    }

    if (empty($channel->matter_type)) {
      $matterTypes = [
        'article' => 'xxt_article',
        'enroll' => 'xxt_enroll',
        'signin' => 'xxt_signin',
        //'channel' => 'xxt_channel',
        'link' => 'xxt_link',
        'mission' => 'xxt_mission',
      ];
    } else {
      $matterTypes = [$channel->matter_type => 'xxt_' . $channel->matter_type];
    }

    // 获取素材
    $getTypeMatters = function ($seq, $num, $orderby = '', $sort = true) use ($channel, $matterTypes) {
      $typeMatters = [];
      foreach ($matterTypes as $type => $table) {
        $q1 = [];
        $q1[] = $this->matterColumns($type) . ",cm.create_at add_at,cm.seq";
        $q1[] = "$table m,xxt_channel_matter cm";
        $qaw = "cm.channel_id='{$channel->id}' and m.id=cm.matter_id and cm.matter_type='$type'";
        $qaw .= " and " . $seq;
        switch ($type) {
          case 'article':
            $qaw .= " and m.state<>0 and m.approved='Y'";
            break;
          case 'enroll':
          case 'signin':
            $qaw .= " and m.state<>0";
            break;
          default:
            $qaw .= " and m.state=1";
        }

        $q1[] = $qaw;
        $q2 = [];
        //order by
        if (empty($orderby)) {
          $q2['o'] = $this->matterOrderby($type, $channel->orderby, 'cm.create_at desc');
        } else {
          $q2['o'] = $orderby;
        }
        $q2['r']['o'] = 0;
        $q2['r']['l'] = $num;
        $rst = $this->query_objs_ss($q1, $q2);
        $typeMatters = array_merge($typeMatters, $rst);
      }
      //order by add_at
      if (count($matterTypes) > 1 && $sort) {
        usort($typeMatters, function ($a, $b) {
          return $b->add_at - $a->add_at;
        });
      }
      // 截取指定数量
      $typeMatters = array_slice($typeMatters, 0, $num);

      return $typeMatters;
    };
    /** -------------------------------
     *
     * 获取置顶和置底的素材 top、bottom
     */
    $TBMatters = $getTypeMatters('cm.seq <> 10000', $channel->volume, 'cm.seq,cm.create_at desc,cm.matter_id desc,cm.matter_type desc', false);
    usort($TBMatters, function ($a, $b) {
      return $a->seq - $b->seq;
    });
    //已有素材数量
    $fixed_num = count($TBMatters);

    if ($fixed_num < $channel->volume) {
      // 还差素材数量
      $centre_num = (int) $channel->volume - $fixed_num;
      // 获取部分素材
      $typeMatters = $getTypeMatters('cm.seq = 10000', $centre_num);
      // 组合素材
      $topMatters = [];
      $botmMatters = [];
      foreach ($TBMatters as $TBMatter) {
        // 置顶素材
        if ($TBMatter->seq < 10000) {
          $topMatters[] = $TBMatter;
        } else {
          // 置底素材
          $botmMatters[] = $TBMatter;
        }
      }
      $matters = array_merge($topMatters, $typeMatters);
      $matters = array_merge($matters, $botmMatters);
    } else if ($fixed_num > $channel->volume) {
      $matters = array_slice($TBMatters, 0, $fixed_num);
    } else {
      $matters = $TBMatters;
    }

    return $matters;
  }
  /**
   * 只返回频道内包含的图文不包括连接
   * 因为微信的群发消息只能发送图文
   *
   * $channel_id int 频道的id
   * $channel 置顶频道
   *
   * return 频道包含的文章，小于等于频道的容量
   */
  public function &getArticles($channel_id, $channel = null)
  {
    $articles = [];
    /**
     * load channel.
     */
    if (empty($channel)) {
      $channel = $this->byId($channel_id, ['fields' => 'id,siteid,orderby,volume,top_type,top_id,bottom_type,bottom_id']);
    }

    /**
     * top matter
     */
    if (!empty($channel->top_type) && $channel->top_type === 'article') {
      $qt[] = "a.id,a.siteid,a.title,a.summary,a.pic,a.body,a.create_at,a.modify_at";
      $qt[] = 'xxt_article a';
      $qt[] = "a.id='$channel->top_id' and a.state=1 and a.approved='Y'";
      $top = $this->query_obj_ss($qt);
    }
    /**
     * bottom matter
     */
    if (!empty($channel->bottom_type) && $channel->bottom_type === 'article') {
      $qb[] = 'a.id,a.siteid,a.title,a.summary,a.pic,a.body,a.create_at,a.modify_at';
      $qb[] = 'xxt_article a';
      $qb[] = "a.id='$channel->bottom_id' and a.state=1 and a.approved='Y'";
      $bottom = $this->query_obj_ss($qb);
    }
    /**
     * load articles.
     */
    $qa1[] = 'a.id,a.siteid,a.title,a.summary,a.pic,a.body,a.create_at,a.modify_at,ca.create_at add_at';
    $qa1[] = 'xxt_article a,xxt_channel_matter ca';
    $qaw = "ca.channel_id='{$channel->id}' and a.id=ca.matter_id and ca.matter_type='article' and a.state=1 and a.approved='Y'";
    if (!empty($top)) {
      $qaw .= " and a.id<>'{$top->id}'";
    }
    if (!empty($bottom)) {
      $qaw .= " and a.id<>'{$bottom->id}'";
    }
    $qa1[] = $qaw;
    $qa2['o'] = $this->matterOrderby('article', $channel->orderby, 'ca.create_at desc');
    $qa2['r']['o'] = 0;
    $qa2['r']['l'] = $channel->volume;
    $articles = $this->query_objs_ss($qa1, $qa2);
    /**
     * add top and bottom.
     */
    !empty($top) && $articles = array_merge(array($top), $articles);
    !empty($bottom) && $articles = array_merge($articles, array($bottom));
    /**
     * size
     */
    $articles = array_slice($articles, 0, $channel->volume);

    return $articles;
  }
  /**
   * 获得单图文频道下的单图文
   */
  private function _getArticlesNoLimit($channel, $params)
  {
    $orderby = $channel->orderby;
    $q1 = [];
    $q1[] = "m.id,m.title,m.summary,m.pic,m.create_at,m.modify_at,m.creater_name,cm.create_at add_at,'article' type,m.remark_num,st.name site_name,st.id siteid,st.heading_pic,m.matter_cont_tag,m.matter_mg_tag,cm.seq";
    $q1[] = "xxt_article m,xxt_channel_matter cm,xxt_site st";
    $q1[] = "m.state=1 and m.approved='Y' and cm.channel_id='{$channel->id}' and m.id=cm.matter_id and cm.matter_type='article' and m.siteid=st.id";
    if (!empty($params->weight)) {
      switch ($params->weight) {
        case 'top':
          $q1[2] .= " and cm.seq < 10000";
          break;
        case 'bottom':
          $q1[2] .= " and cm.seq > 20000";
          break;
        default:
          $q1[2] .= " and cm.seq = 10000";
          break;
      }
    }
    // 指定按关键字过滤
    if (!empty($params->keyword)) {
      $q1[2] .= " and (m.title like '%$params->keyword%'";
      $q1[2] .= "or m.summary like '%$params->keyword%'";
      $q1[2] .= "or m.body like '%$params->keyword%')";
    }
    // 指定最晚加入频道的时间
    if (!empty($params->afterAddAt)) {
      $q1[2] .= " and cm.create_at>={$params->afterAddAt}";
    }
    $q2 = [];
    $q2['o'] = 'cm.seq,' . $this->matterOrderby('article', $orderby, 'cm.create_at desc');

    if (isset($params->page) && isset($params->size)) {
      $q2['r'] = array(
        'o' => ($params->page - 1) * $params->size,
        'l' => $params->size,
      );
    } else if (isset($channel->volume)) {
      $q2['r'] = array(
        'o' => 0,
        'l' => $channel->volume,
      );
    }

    if ($matters = $this->query_objs_ss($q1, $q2)) {
      foreach ($matters as $matter) {
        !empty($matter->matter_cont_tag) && $matter->matter_cont_tag = json_decode($matter->matter_cont_tag);
        !empty($matter->matter_mg_tag) && $matter->matter_mg_tag = json_decode($matter->matter_mg_tag);
      }
    }
    $q1[0] = 'count(*)';
    $total = (int) $this->query_val_ss($q1);

    $data = new \stdClass;
    $data->matters = $matters;
    $data->total = $total;

    return $data;
  }
  /**
   * 获得任意素材频道下的素材
   */
  private function _getAnyNoLimit($channel, $params, $bFilterByEntryRule, $user, $ctrl)
  {
    /**
     * 获得频道中的素材
     */
    $q1 = [
      'cm.create_at,cm.matter_type,cm.matter_id,cm.seq',
      'xxt_channel_matter cm',
      ["cm.channel_id" => $channel->id],
    ];
    if (!empty($params->matterType)) {
      $q1[2]['cm.matter_type'] = $params->matterType;
    }
    if (!empty($params->afterAddAt)) {
      $q1[2]['cm.create_at'] = (object)['op' => '>=', 'pat' => $params->afterAddAt];
    }
    if (!empty($params->weight)) {
      switch ($params->weight) {
        case 'top':
          $q1[2]['cm.seq'] = (object) ['op' => '<', 'pat' => 10000];
          break;
        case 'bottom':
          $q1[2]['cm.seq'] = (object) ['op' => '>', 'pat' => 20000];
          break;
        default:
          $q1[2]['cm.seq'] = 10000;
          break;
      }
    }
    $q2['o'] = 'cm.seq, cm.create_at desc , cm.matter_id desc , cm.matter_type desc';

    // 分页获取，如果素材已经删除，或者素材尚未批准的情况下，分页会导致返回的数量不正确
    if (isset($params->page) && isset($params->size)) {
      $q2['r'] = [
        'o' => ($params->page - 1) * $params->size,
        'l' => $params->size,
      ];
    }
    $matters = []; // 可用的素材
    $simpleMatters = $this->query_objs_ss($q1, $q2);
    $q1[0] = 'count(*)';
    $total = (int) $this->query_val_ss($q1);

    /**
     * 获得素材详细数据
     */
    foreach ($simpleMatters as $sm) {
      /* 检查素材是否可用 */
      $valid = true;
      if ($sm->matter_type !== 'article') {
        $fullMatter = \TMS_APP::M('matter\\' . $sm->matter_type)->byId($sm->matter_id);
      } else {
        $q = [
          "a.id,a.mission_id,a.title,a.creater_name,a.create_at,a.modify_at,a.summary,a.pic,a.state,entry_rule,'article' type,a.matter_cont_tag,a.matter_mg_tag,s.name site_name,s.id siteid,s.heading_pic",
          'xxt_article a, xxt_site s',
          "a.id = '{$sm->matter_id}' and a.state = 1 and a.approved = 'Y' and a.siteid=s.id and s.state = 1",
        ];
        $fullMatter = $this->query_obj_ss($q);
      }

      if (false === $fullMatter) {
        continue;
      }
      /**
       * 检查素材状态
       */
      switch ($sm->matter_type) {
        case 'enroll':
        case 'signin':
          if ($fullMatter->state !== '1' && $fullMatter->state !== '2') {
            $valid = false;
          }
          break;
        default:
          if ($fullMatter->state !== '1') {
            $valid = false;
          }
      }
      if (!$valid) {
        continue;
      }
      /**
       * 检查进入规则
       */
      if ($bFilterByEntryRule && $user && $ctrl) {
        /**处理进入规则*/
        if (empty($fullMatter->entry_rule))
          $fullMatter->entryRule = new \stdClass;
        else if (is_string($fullMatter->entry_rule))
          $fullMatter->entryRule = json_decode($fullMatter->entry_rule);
        else
          $fullMatter->entryRule = $fullMatter->entry_rule;
        /**检查进入规则*/
        if ($aResult = $ctrl->checkEntryRule($fullMatter, false, $user)) {
          if ($aResult[0] !== true)
            continue;
        }
      }
      unset($fullMatter->entry_rule);
      unset($fullMatter->entryRule);
      /**
       * 补充数据
       */
      $fullMatter->type = $sm->matter_type;
      $fullMatter->add_at = $sm->create_at;
      $fullMatter->seq = $sm->seq;
      if (!empty($fullMatter->matter_cont_tag) && is_string($fullMatter->matter_cont_tag)) {
        $fullMatter->matter_cont_tag = json_decode($fullMatter->matter_cont_tag);
      }
      if (!empty($fullMatter->matter_mg_tag) && is_string($fullMatter->matter_mg_tag)) {
        $fullMatter->matter_mg_tag = json_decode($fullMatter->matter_mg_tag);
      }
      $matters[] = $fullMatter;
    }

    $data = new \stdClass;
    $data->matters = $matters;
    $data->total = $total;

    return $data;
  }
  /**
   * 直接打开频道的情况下（不是返回信息卡片），忽略置顶和置底，返回频道中的所有条目
   *
   * @param int $channel_id 频道的id
   * @param object $params 查询参数
   * @param int $params->size 分页大小
   * @param int $params->page 分页
   * @param string $params->keyword 如果频道的素材类型是单图文，支持按关键字进行搜索
   * @param int $params->afterAddAt 最晚加入频道时间
   * @param object $channel 频道对象，减少查询次数
   * @param object $user 访问数据的用户。如果频道要求按素材访问规则进行过滤时使用。
   * @param object $ctrl 调用访问的控制器。为了检查访问权限时调用控制器上的方法。
   *
   * @return 频道包含的所有条目
   */
  public function getMattersNoLimit($channel_id, $params, $channel = null, $user = null, $ctrl = null)
  {
    /**
     * load channel.
     */
    if (empty($channel)) {
      $channel = $this->byId($channel_id, ['fields' => 'id,state,matter_type,orderby,volume']);
    }
    if ($channel === false || $channel->state != 1) {
      $data = new \stdClass;
      $data->matters = [];
      $data->total = 0;
      return $data;
    }
    // 根据素材的进入规则进行过滤
    $bFilterByEntryRule = false;
    if ($user && \TMS_MODEL::getDeepValue($channel, 'config.filterByEntryRule') === 'Y') {
      $bFilterByEntryRule = true;
    }
    // 获取素材
    if ($channel->matter_type === 'article') {
      $data = $this->_getArticlesNoLimit($channel, $params);
      return $data;
    } else {
      $data = $this->_getAnyNoLimit($channel, $params, $bFilterByEntryRule, $user, $ctrl);
      return $data;
    }
  }
  /**
   * 频道中增加素材
   *
   * @param int $id channel's id
   * @param object $matter
   */
  public function addMatter($id, $matter, $createrId, $createrName)
  {
    is_array($matter) && $matter = (object) $matter;

    $channel = $this->byId($id);
    $oMatter = $this->model('matter\\' . $matter->type)->byId($matter->id);
    $current = time();

    $newc['matter_id'] = $oMatter->id;
    $newc['matter_type'] = $oMatter->type;
    $newc['create_at'] = $current;
    $newc['creater'] = $createrId;
    $newc['creater_name'] = $this->escape($createrName);
    $newc['seq'] = 10000;

    /* 是否已经加入到频道中 */
    $q = [
      'count(*)',
      'xxt_channel_matter',
      ["channel_id" => $id, "matter_id" => $oMatter->id, "matter_type" => $oMatter->type],
    ];
    if (1 === (int) $this->query_val_ss($q)) {
      return false;
    }

    // new one
    $newc['channel_id'] = $id;
    $this->insert('xxt_channel_matter', $newc, false);

    /* 如果频道已经发布到团队主页上，频道增加素材时，推送给关注者 */
    if ($this->isAtHome($channel->id)) {
      $modelSite = $this->model('site');
      $site = $modelSite->byId($oMatter->siteid);
      /**
       * 推送给关注团队的站点用户
       */
      $modelSite->pushToClient($site, $oMatter);
      /**
       * 推送给关注团队的团队账号
       */
      $modelSite->pushToFriend($site, $oMatter);
    }

    return true;
  }
  /**
   * 从频道中移除素材
   */
  public function removeMatter($id, $matter)
  {
    is_array($matter) && $matter = (object) $matter;

    $rst = $this->delete(
      'xxt_channel_matter',
      ["matter_id" => $matter->id, "matter_type" => $matter->type, "channel_id" => $id]
    );

    return $rst;
  }
  /**
   * 频道是否已发布到团队站点首页
   *
   * @param int @id channel'is
   *
   */
  public function isAtHome($id)
  {
    $q = [
      'count(*)',
      'xxt_site_home_channel',
      ["channel_id" => $id],
    ];
    $cnt = (int) $this->query_val_ss($q);

    return $cnt > 0;
  }
}
