<?php

namespace pl\fe\matter\link;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/**
 *
 */
class wrap extends \pl\fe\matter\main_base
{
  /**
   *
   */
  public function get_access_rule()
  {
    return ['rule_type' => 'white', 'actions' => ['channel']];
  }
  /**
   * 包裹外部频道
   */
  public function channel_action($id)
  {
    $modelLink = $this->model('matter\link');
    $oLink = $modelLink->byId($id);
    if (false === $oLink)
      return new \ObjectNotFoundError();

    $oChannel = $this->getPostJson(false);
    if (!$oChannel || !isset($oChannel->id))
      return new \ObjectNotFoundError('没有指定要引用的频道');

    $modelCh = $this->model('matter\channel');
    $oChannel = $modelCh->byId($oChannel->id);
    if (false === $oChannel)
      return new \ObjectNotFoundError('指定的频道不存在');

    /* 用户信息 */
    $oUser = new \stdClass;
    $oUser->id = 'system';
    $oUser->name = 'system';
    $oUser->src = 'S';

    /* 更新连接包装的对象数据 */
    $oUpdated = new \stdClass;
    $oUpdated->urlsrc = 3;
    $oUpdated->url = $oChannel->id;

    if ($oLink = $modelLink->modify($oUser, $oLink, $oUpdated))
      $this->model('matter\log')->matterOp($oLink->siteid, $oUser, $oLink, 'U');


    return new \ResponseData($oLink);
  }
}
