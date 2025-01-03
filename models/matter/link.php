<?php

namespace matter;

require_once dirname(__FILE__) . '/base.php';

class link_model extends base_model
{
  /**
   * 记录日志时需要的列
   */
  const LOG_FIELDS = 'siteid,id,title,summary,pic';
  /**
   *
   */
  protected function table()
  {
    return 'xxt_link';
  }
  /**
   * 返回链接和链接的参数
   */
  public function byIdWithParams($id)
  {
    if ($oLink = $this->byId($id)) {
      !empty($oLink->matter_mg_tag) && $oLink->matter_mg_tag = json_decode($oLink->matter_mg_tag);
      if (empty($oLink->entry_rule)) {
        $oLink->entry_rule = new \stdClass;
        $oLink->entry_rule->scope = new \stdClass;
      }
      $oLink->entryRule = $oLink->entry_rule;
      /* params */
      $q2 = [
        'id,pname,pvalue,authapi_id',
        'xxt_link_param',
        "link_id='$id'",
      ];
      $oLink->params = $this->query_objs_ss($q2);
      /* channels */
      $oLink->channels = $this->model('matter\channel')->byMatter($id, 'link');
      /* 所属项目 */
      if ($oLink->mission_id) {
        $oLink->mission = $this->model('matter\mission')->byId($oLink->mission_id);
      }
      $oLink->entryUrl = $this->getEntryUrl($oLink->siteid, $oLink->id);
    }

    return $oLink;
  }
  /**
   * 返回进行推送的消息格式
   *
   * $siteId
   * $id
   */
  public function &forCustomPush($siteId, $id)
  {
    $link = $this->byId($id);
    $link->type = 'link';

    $msg = array(
      'msgtype' => 'news',
      'news' => array(
        'articles' => array(
          array(
            'title' => $link->title,
            'description' => $link->summary,
            'url' => $this->getEntryUrl($siteId, $id),
            'picurl' => $link->pic,
          ),
        ),
      ),
    );

    return $msg;
  }
  /**
   *
   */
  public function getEntryUrl($siteId, $id, $openid = null, $call = null, $matter = null)
  {
    if (isset($matter->urlsrc)) {
      /**
       * link
       */
      switch ($matter->urlsrc) {
        case 0: // external link
          if ($matter->open_directly === 'Y') {
            $url = $matter->url;
            $q = array(
              'pname,pvalue,authapi_id',
              'xxt_link_param',
              "link_id=$matter->id",
            );
            if ($params = $this->query_objs_ss($q)) {
              $url .= (strpos($url, '?') === false) ? '?' : '&';
              $url .= $this->_spliceParams($siteId, $params, $openid);
            }
            if (preg_match('/^(http:|https:)/', $url) === 0) {
              $url = 'http://' . $url;
            }

            return $url;
          } else {
            $url = "?site=$siteId&id=$matter->id&type=link";
          }
          break;
        case 2: // channel
          $url = "?site=$siteId&type=channel&id=" . $matter->url;
          break;
        default:
          die('unknown link urlsrc.');
      }
    } else {
      $matter = $this->byId($id);
      if ($matter->urlsrc == 0 && $matter->embedded === 'Y' && (strpos($matter->url, 'https') === false)) {
        $url = 'http://' . APP_HTTP_HOST . "/rest/site/fe/matter/link";
      } else {
        $url = APP_PROTOCOL . APP_HTTP_HOST . "/rest/site/fe/matter/link";
      }

      $url .= "?site=$siteId&id=$id&type=" . $this->getTypeName();

      return $url;
    }
  }
  /**
   * 拼接URL中的参数
   */
  private function _spliceParams($siteId, &$params, $openid = '')
  {
    $pairs = array();
    foreach ($params as $p) {
      switch ($p->pvalue) {
        case '{{site}}':
          $v = $siteId;
          break;
        case '{{openid}}':
          $v = $openid;
          break;
        default:
          $v = $p->pvalue;
      }
      $pairs[] = "$p->pname=$v";
    }
    $spliced = implode('&', $pairs);

    return $spliced;
  }
}
