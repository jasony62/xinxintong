<?php

namespace matter;

require_once dirname(__FILE__) . '/article_base.php';

class article_model extends article_base
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
    return 'xxt_article';
  }
  /**
   *
   */
  public function getEntryUrl($siteId, $id)
  {
    $url = APP_PROTOCOL . APP_HTTP_HOST;
    $url .= "/rest/site/fe/matter";
    if ($siteId === 'platform') {
      if ($oArticle = $this->byId($id, ['notDecode' => true])) {
        $url .= "?site={$oArticle->siteid}&id={$id}&type=article";
      } else {
        $url = APP_PROTOCOL . APP_HTTP_HOST;
      }
    } else {
      $url .= "?site={$siteId}&id={$id}&type=article";
    }

    return $url;
  }
  /**
   *
   */
  public function byId($id, $options = [])
  {
    $fields = isset($options['fields']) ? $options['fields'] : '*';
    $q = [
      $fields,
      $this->table(),
      ["id" => $id],
    ];
    if (($oMatter = $this->query_obj_ss($q)) && empty($options['notDecode'])) {
      !empty($oMatter->matter_cont_tag) && $oMatter->matter_cont_tag = json_decode($oMatter->matter_cont_tag);
      !empty($oMatter->matter_mg_tag) && $oMatter->matter_mg_tag = json_decode($oMatter->matter_mg_tag);
      $oMatter->type = $this->getTypeName();

      if (property_exists($oMatter, 'config')) {
        $oMatter->config = empty($oMatter->config) ? new \stdClass : json_decode($oMatter->config);
      }
      if ($fields === '*' || false !== strpos($fields, 'siteid')) {
        $oMatter->entryUrl = $this->getEntryUrl($oMatter->siteid, $id);
      }
      if ($fields === '*' || false !== strpos($fields, 'entry_rule')) {
        if (empty($oMatter->entry_rule)) {
          $oMatter->entryRule = $oMatter->entry_rule = new \stdClass;
        } else {
          $oMatter->entryRule = $oMatter->entry_rule = json_decode($oMatter->entry_rule);
        }
      }
      if ($fields === '*' || false !== strpos($fields, 'download_rule')) {
        if (empty($oMatter->download_rule)) {
          $oMatter->downloadRule = $oMatter->download_rule = new \stdClass;
        } else {
          $oMatter->downloadRule = $oMatter->download_rule = json_decode($oMatter->download_rule);
        }
      }
    }

    return $oMatter;
  }
  /**
   *
   */
  public function &byCreater($site, $creater, $fields = '*', $cascade = false)
  {
    $q = array(
      $fields,
      'xxt_article',
      "siteid='$site' and creater='$creater' and state=1"
    );
    $q2 = array('o' => 'modify_at desc');

    $articles = $this->query_objs_ss($q, $q2);

    if (!empty($articles) && $cascade) {
      foreach ($articles as &$a) {
        $a->channels = \TMS_APP::M('matter\channel')->byMatter($a->id, 'article');
      }
    }

    return $articles;
  }
  /**
   * 根据投稿来源
   */
  public function &byEntry($site, $entry, $creater, $fields = '*', $cascade = false)
  {
    $q = array(
      $fields,
      'xxt_article',
      "siteid='$site' and entry='$entry' and creater='$creater' and state=1"
    );
    $q2 = array('o' => 'modify_at desc');

    $articles = $this->query_objs_ss($q, $q2);

    if (!empty($articles) && $cascade) {
      $modelChn = \TMS_APP::M('matter\channel');
      foreach ($articles as &$a) {
        $a->channels = $modelChn->byMatter($a->id, 'article');
      }
    }

    return $articles;
  }
  /**
   * 获得审核通过的文稿
   *
   * $site
   */
  public function &getApproved($site, $entry = null, $page = 1, $size = 30)
  {
    $q = array(
      'a.*',
      'xxt_article a',
      "a.siteid='$site' and a.approved='Y' and state=1",
    );
    !empty($entry) && $q[2] .= " and a.entry='$entry'";

    $q2 = array('o' => 'a.create_at desc');

    $articles = $this->query_objs_ss($q, $q2);

    return $articles;
  }
  /**
   * 这个是基类要求的方法
   * todo 应该用抽象类的机制处理
   */
  public function &getMatters($id)
  {
    $article = $this->byId($id, ['fields' => "id,siteid,title,author,summary,pic,body,url"]);
    $articles = array($article);

    return $articles;
  }
  /**
   * 返回进行推送的消息格式
   */
  public function &getArticles($id)
  {
    $article = $this->byId($id, ['fields' => 'id,siteid,title,author,summary,pic,body,url']);
    $articles = array($article);

    return $articles;
  }
  /**
   * 按条件查找单图文
   */
  public function find($site, $user, $page = 1, $size = 10, $options)
  {
    $s = "a.id,a.title,a.modify_at,a.author,a.summary,a.pic,a.url";
    $w = "a.siteid='$site' and a.state=1 and finished='Y'";
    if (!empty($options->mission)) {
      $w .= " and mission_id=$options->mission";
    }
    if (empty($options->channel) && empty($options->tag)) {
      $q = [
        $s,
        'xxt_article a',
        $w,
      ];
    } else if (!empty($options->channel)) {
      /* 按频道筛选 */
      list($channelIds, $num) = is_array($options->channel) ? [implode(',', $options->channel), count($options->channel)] : [$options->channel, count(explode(',', $options->channel))];
      $w .= " and exists (select 1 from xxt_channel_matter cm where cm.matter_type='article' and cm.matter_id=a.id and cm.channel_id in (" . $channelIds . ")";
      if ($options->logicOR === false) $w .= " group by cm.matter_id having count(cm.matter_id)=" . $num;
      $w .= ")";
      $q = [
        $s,
        'xxt_article a',
        $w,
      ];
    } else if (!empty($options->tag)) {
      /* 按标签筛选 */
      if (is_array($options->tag)) {
        foreach ($options->tag as $tag) {
          $w .= " and a.matter_cont_tag like '%" . $tag . "%'";
        }
      } else {
        $w .= " and a.matter_cont_tag like '%" . $options->tag . "%'";
      }

      $q = [
        $s,
        'xxt_article a',
        $w,
      ];
    }

    $q2['o'] = 'a.modify_at desc';
    $q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];

    if ($articles = $this->query_objs_ss($q)) {
      $q[0] = 'count(*)';
      $total = (int) $this->query_val_ss($q);

      foreach ($articles as $article) {
        $article->entryUrl = $this->getEntryUrl($site, $article->id);
      }

      return ['articles' => $articles, 'total' => $total];
    }

    return ['articles' => array(), 'total' => 0];
  }
  /**
   * 全文检索单图文，将符合条件的结果组成多图文
   */
  public function fullsearch_its($site, $keyword, $page = 1, $limit = 5)
  {
    $s = "id,siteid,title,author,summary,pic,body,url,'article' type";
    $f = 'xxt_article';
    $w = "siteid='$site' and state=1 and approved='Y' and can_fullsearch='Y'";
    $w .= " and (title like '%$keyword%'";
    $w .= "or summary like '%$keyword%'";
    $w .= "or body like '%$keyword%')";
    $q = array($s, $f, $w);

    $q2['o'] = 'create_at desc';
    $q2['r']['o'] = ($page - 1) * $limit;
    $q2['r']['l'] = $limit;

    $articles = $this->query_objs_ss($q, $q2);

    return $articles;
  }
  /** 
   * 返回全部检索内容
   */
  public function search_all($site, $keyword, $page = 1, $size = 12, $mission = 0, $channel = 0)
  {
    $s = "id,title,author,summary,pic,body,url,read_num,create_at,modify_at,has_attachment,download_num,'article' type";
    $f = 'xxt_article';
    $w = "siteid='$site' and state=1 and approved='Y' and can_fullsearch='Y'";
    if ($mission) {
      $w .= " and mission_id=$mission";
    }
    $w .= " and (title like '%$keyword%'";
    $w .= "or summary like '%$keyword%'";
    $w .= "or body like '%$keyword%')";

    $q = [$s, $f, $w];

    $q2['o'] = 'modify_at desc';
    $q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
    $articles = $this->query_objs_ss($q, $q2);

    if (count($articles) > 0) {
      foreach ($articles as $a) {
        if (empty($a->url)) {
          $a->url = $this->getEntryUrl($site, $a->id);
        }
        if (!empty($a->pic)) {
          $a->pic = APP_PROTOCOL . APP_HTTP_HOST . $a->pic;
        }
      }
      $q[0] = 'count(*)';
      $total = (int) $this->query_val_ss($q);
      return ['articles' => $articles, 'total' => $total];
    }

    return ['articles' => [], 'total' => 0];
  }
  /*
     * 返回全文检索（统计）数目
     */
  public function fullsearch_num($site, $keyword)
  {
    $s = "count(*) as c";
    $f = 'xxt_article';
    $w = "siteid='$site' and state=1 and approved='Y' and can_fullsearch='Y'";
    $w .= " and (title like '%$keyword%'";
    $w .= "or summary like '%$keyword%'";
    $w .= "or body like '%$keyword%')";

    $q = array($s, $f, $w);

    $q2['o'] = 'create_at desc';

    $r = $this->query_objs_ss($q, $q2);
    $one = (array) $r[0];
    $num = $one['c'];

    return $num;
  }
}
