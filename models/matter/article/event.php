<?php

namespace matter\article;

/**
 * 记录时间
 */
class event_model extends \TMS_MODEL
{
  /**
   * 访问单图文时
   */
  public function logAccess($articleId, $oUser)
  {
    // 增加单图文的阅读数
    $this->update("update xxt_article set read_num=read_num+1 where id='$articleId'");
  }
}
