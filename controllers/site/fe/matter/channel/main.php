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
   * 获得指定频道下的素材
   * 
   * $site
   * $id channel's id
   * $keyword 包含的关键字
   * $afterAddAt 最晚加入频道的时间
   * 
   */
  public function mattersGet_action($site, $id, $missionId = null, $matterType = null, $page = null, $size = null, $keyword = null, $afterAddAt = 0)
  {
    $modelChannel = $this->model('matter\channel');
    $channel = $modelChannel->byId($id);
    if (false === $channel) {
      return new \ObjectNotFoundError();
    }

    /**处理查询参数*/
    $params = new \stdClass;
    if (!empty($matterType))  $params->matterType = $matterType;

    if ($page !== null && $size !== null) {
      $params->page = $page;
      $params->size = $size;
    }

    if (!empty($keyword)) $params->keyword = $keyword;

    if ($afterAddAt > 0) $params->afterAddAt = $afterAddAt;

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

    $availableMatters = [];
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

      if (!empty($missionId)) {
        if (empty($matter->mission_id) || $matter->mission_id != $missionId) {
          continue;
        }
      }

      $availableMatters[] = $matter;
    }

    return new \ResponseData($availableMatters);
  }
}
