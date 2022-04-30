<?php

namespace site\fe\matter\channel;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 频道
 */
class main extends \site\fe\matter\base
{
  /**
   *
   */
  public function get_action($id)
  {
    $data = [];

    $user = $this->who;
    $data['user'] = $user;

    $channel = $this->model('matter\channel')->byId($id);
    $oInvitee = new \stdClass;
    $oInvitee->id = $channel->siteid;
    $oInvitee->type = 'S';
    $oInvite = $this->model('invite')->byMatter($channel, $oInvitee, ['fields' => 'id,code,expire_at,state']);
    if ($oInvite && $oInvite->state === '1') {
      $channel->invite = $oInvite;
    }

    $data['channel'] = $channel;

    return new \ResponseData($data);
  }
  /**
   *
   * $site
   * $id channel's id
   */
  public function mattersGet_action($site, $id, $page = null, $size = null, $keyword = null)
  {
    $modelChannel = $this->model('matter\channel');
    $channel = $this->model('matter\channel')->byId($id);
    if (false === $channel) {
      return new \ObjectNotFoundError();
    }

    /**处理查询参数*/
    $params = new \stdClass;
    if ($page !== null && $size !== null) {
      $params->page = $page;
      $params->size = $size;
    }
    if (!empty($keyword)) {
      $params->keyword = $keyword;
    }

    $user = $this->who;
    $data = $modelChannel->getMattersNoLimit($id, $params, $channel, $user, $this);

    // 频道是否开启了邀请
    $checkInvite = false;
    $oInvitee = new \stdClass;
    $oInvitee->id = $site;
    $oInvitee->type = 'S';
    $channel = new \stdClass;
    $channel->id = $id;
    $channel->type = 'channel';
    $oInvite = $this->model('invite')->byMatter($channel, $oInvitee, ['fields' => 'id,code,expire_at,state']);
    if ($oInvite && $oInvite->state === '1') {
      $checkInvite = true;
    }

    foreach ($data->matters as $matter) {
      /**补充素材访问链接数据*/
      if ($matter->type === 'link' && !$checkInvite) {
        $oLink = $this->model('matter\link')->byIdWithParams($matter->id);
        $oInvite = $this->model('invite')->byMatter($oLink, $oInvitee, ['fields' => 'id,code,expire_at,state']);
        if ($oInvite && $oInvite->state === '1') {
          $oCreator = new \stdClass;
          $oCreator->id = $matter->siteid;
          $oCreator->name = '';
          $oCreator->type = 'S';
          if (!isset($modelInv)) {
            $modelInv = $this->model('invite');
          }
          $oInvite = $modelInv->byMatter($oLink, $oCreator, ['fields' => 'id,code']);
          if ($oInvite) {
            $matter->url = $modelInv->getEntryUrl($oInvite);
          } else {
            $matter->url = $this->model('matter\\' . $matter->type)->getEntryUrl($matter->siteid, $matter->id);
          }
        } else {
          $matter->url = $this->model('matter\\' . $matter->type)->getEntryUrl($matter->siteid, $matter->id);
        }
      } else {
        $matterModel = \TMS_APP::M('matter\\' . $matter->type);
        $matter->url = $matterModel->getEntryUrl($matter->siteid, $matter->id);
      }

      $availableMatters[] = $matter;
    }

    return new \ResponseData($data);
  }
}
