<?php

namespace matter\tmplmsg;

/**
 * 模版消息的发送批次
 */
class batch_model extends \TMS_MODEL
{
  /**
   *
   */
  public function byId($id, $options = [])
  {
    $fields = isset($options['fields']) ? $options['fields'] : '*';

    $q = [$fields, 'xxt_log_tmplmsg_batch', ['id' => $id]];

    $batch = $this->query_obj_ss($q);

    return $batch;
  }
  /**
   * 批量发送模板消息
   *
   * @param string $siteId
   * @param int $tmplmsgId 应用内模板消息id
   * @param object $oCreator
   * @param array $receivers
   * @param object $params 
   *
   */
  public function send($siteId, $tmplmsgId, $oCreator, $receivers, $params, $aOptions = [])
  {
    if (count($receivers) === 0) {
      return [true, 'ok'];
    }

    /* 接收消息的用户 */
    $modelAcnt = $this->model('site\user\account');
    $mapOfUsers = []; // 接收消息的用户，进行了去重
    $wxOpenids = []; // 具备微信openid的用户列表，准备发送微信模板消息
    foreach ($receivers as $oReceiver) {
      if (isset($oReceiver->unionid)) {
        $oUser = $modelAcnt->byPrimaryUnionid($siteId, $oReceiver->unionid);
      } else if (isset($oReceiver->userid)) {
        $oUser = $modelAcnt->byId($oReceiver->userid);
      }
      if ($oUser) {
        isset($oReceiver->assoc_with) && $oUser->assoc_with = $oReceiver->assoc_with;
        $mapOfUsers[$oUser->uid] = $oUser;
        if (!empty($oUser->wx_openid)) $wxOpenids[] = $oUser->wx_openid;
      }
    }

    $modelTmpl = $this->model('matter\tmplmsg');
    $tmpl = $modelTmpl->byId($tmplmsgId, ['cascaded' => 'Y']);

    /* 拼装通知消息 */
    $url = isset($params->url) ? $params->url : '';
    // 微信模板消息
    $wxTmplMsg = [
      'template_id' => $tmpl->templateid,
      'url' => $url,
    ];
    $coverData = []; // 消息推送服务封面消息参数
    $txtTmplMsg = []; // 文本格式模板消息
    $txtTmplMsg[] = $tmpl->title;
    /* 组装模板内容 */
    if ($tmpl->params) {
      foreach ($tmpl->params as $tp) {
        $value = isset($params->{$tp->pname}) ? $params->{$tp->pname} : (isset($params->{$tp->id}) ? $params->{$tp->id} : '');
        $wxTmplMsg['data'][$tp->pname] = ['value' => $value, 'color' => '#173177'];
        $txtTmplMsg[] = $tp->plabel . '：' . $value;
        $coverData[$tp->pname] = ['value' => $value, 'color' => empty($tp->color) ? '#173177' : $tp->color];
      }
    }

    /* 指定了消息推送服务 */
    if (defined('TMS_MESSENGER_BACK_ADDRESS')) {
      /* 通过消息服务发送微信模板消息 */
      if (!empty($tmpl->templateid) && count($wxOpenids)) {
        /* 在消息服务中创建发送消息任务 */
        list($success, $oMsgTask) = $this->_createMsgTask($siteId, $tmpl, $coverData, $url);
        if ($success !== true) return [false, $oMsgTask];

        /* 在消息服务中创建发送消息 */
        list($success, $result) = $this->_createMsg($siteId, $oMsgTask->code, $wxOpenids);
        if ($success !== true) return [false, $result];

        /* 进行消息推送 */
        list($success, $result) = $this->_pushMsgTask($siteId, $oMsgTask->code);
        if ($success !== true) return [false, $result];
      }
    }

    // 创建发送批次
    empty($aOptions['remark']) && $aOptions['remark'] = implode("\n", $txtTmplMsg);
    $oBatch = $this->_create($siteId, $tmpl, $oCreator, $params, count($mapOfUsers), $aOptions);

    // 消息发送日志
    $log = [
      'batch_id' => $oBatch->id,
      'siteid' => $siteId,
      'tmplmsg_id' => $tmplmsgId,
    ];
    foreach ($mapOfUsers as $userid => $oUser) {
      $log['userid'] = $userid;
      isset($oUser->assoc_with) && $log['assoc_with'] = $oUser->assoc_with;

      /* 平台应用内消息 */
      $log['data'] = $modelTmpl->escape($modelTmpl->toJson($txtTmplMsg));
      $log['openid'] = '';
      $log['status'] = '';
      $modelTmpl->insert('xxt_log_tmplmsg_detail', $log, false);

      if (!empty($oUser->wx_openid)) {
        if (!empty($tmpl->templateid)) {
          /* 发送微信模板消息 */
          $wxTmplMsg['touser'] = $oUser->wx_openid;
          $log['openid'] = $oUser->wx_openid;
          $log['data'] = $modelTmpl->escape($modelTmpl->toJson($wxTmplMsg));
          /* 没有指定消息推送服务，使用本地推送 */
          if (!defined('TMS_MESSENGER_BACK_ADDRESS')) {
            if (!isset($snsConfig)) {
              $snsConfig = $this->model('sns\wx')->bySite($tmpl->siteid);
              $wxProxy = $this->model('sns\wx\proxy', $snsConfig);
            }
            $rst = $wxProxy->messageTemplateSend($wxTmplMsg);
            if ($rst[0] === false) {
              $log['status'] = 'failed:' . $rst[1];
            } else {
              $log['msgid'] = $rst[1]->msgid;
            }
          }
          $modelTmpl->insert('xxt_log_tmplmsg_detail', $log, false);
        } else {
          $log['openid'] = $oUser->wx_openid;
          $wxTxtTmplMsg = $txtTmplMsg;
          if (!empty($url)) {
            $wxTxtTmplMsg[] = " <a href='" . $url . "'>查看详情</a>";
          }
          $log['data'] = $modelTmpl->escape($modelTmpl->toJson($wxTxtTmplMsg));

          $this->_sendTxtByOpenid($siteId, $oUser->wx_openid, 'wx', $wxTxtTmplMsg, $log);
        }
      }
      /* 易信用户，将模板消息转换文本消息 */
      if (!empty($oUser->qy_openid)) {
        $log['openid'] = $oUser->qy_openid;
        $qyTxtTmplMsg = $txtTmplMsg;
        if (!empty($url)) {
          $qyTxtTmplMsg[] = " <a href='" . $url . "'>查看详情</a>";
        }
        $log['data'] = $modelTmpl->escape($modelTmpl->toJson($qyTxtTmplMsg));

        $this->_sendTxtByOpenid($siteId, $oUser->qy_openid, 'qy', $qyTxtTmplMsg, $log);
      }
    }

    return [true, 'ok'];
  }
  /**
   * 创建批次
   */
  private function _create($siteId, $tmpl, $oCreator, $params, $userCount, $aOptions = [])
  {
    $oBatch = new \stdClass;
    $oBatch->siteid = $siteId;
    $oBatch->tmplmsg_id = $tmpl->id;
    $oBatch->template_id = $tmpl->templateid;
    $oBatch->user_num = $userCount;
    $oBatch->creater = isset($oCreator->uid) ? $oCreator->uid : '';
    $oBatch->creater_name = isset($oCreator->name) ? $this->escape($oCreator->name) : '';
    $oBatch->create_at = time();
    $oBatch->params = $this->escape($this->toJson($params));
    !empty($aOptions['event_name']) && $oBatch->event_name = $aOptions['event_name'];
    !empty($aOptions['send_from']) && $oBatch->send_from = $aOptions['send_from'];
    !empty($aOptions['remark']) && $oBatch->remark = $this->escape($aOptions['remark']);

    $oBatch->id = $this->insert('xxt_log_tmplmsg_batch', $oBatch, true);

    return $oBatch;
  }
  /**
   * 通过消息服务创建发送任务
   * 
   * @param object $tmpl 微信消息模板定义，包含模板参数，来源于表xxt_tmplmsg
   * @param array $coverData 消息封面参数默认值
   * @param string $bodyUrl 消息内容url
   */
  private function _createMsgTask($siteId, $tmpl, $coverData, $bodyUrl)
  {
    if (!defined('TMS_MESSENGER_BACK_ADDRESS')) {
      return [false, '没有配置[TMS_MESSENGER_BACK_ADDRESS]参数'];
    }
    if (empty($tmpl->tms_msg_wx_template_code)) {
      return [false, '指定的消息模板没有对应的消息服务微信模版编码'];
    }

    $cover = ["templateCode" => $tmpl->tms_msg_wx_template_code];
    if (!empty($coverData)) $cover['data'] = $coverData;

    $posted = ['title' => 'test', 'remark' => 'test', 'cover' => $cover];

    if (!empty($bodyUrl)) $posted['body'] = ['url' => $bodyUrl];

    $url = TMS_MESSENGER_BACK_ADDRESS . "/send/task/add";
    $url .= "?bucket={$siteId}";

    list($success, $rsp) = tmsHttpPost($url, $posted);
    if ($success !== true) return [false, $rsp];
    if ($rsp->code !== 0) return [false, $rsp->msg];

    return [true, $rsp->result];
  }
  /**
   * 创建消息任务 
   */
  private function _createMsg($siteId, $taskCode, $receivers)
  {
    if (!defined('TMS_MESSENGER_BACK_ADDRESS')) {
      return [false, '没有配置[TMS_MESSENGER_BACK_ADDRESS]参数'];
    }
    if (empty($taskCode)) {
      return [false, '指定的消息任务编码为空'];
    }
    if (empty($receivers)) {
      return [false, '没有指定接收消息的用户'];
    }
    $url = TMS_MESSENGER_BACK_ADDRESS . "/send/message/add";
    $url .= "?bucket={$siteId}&task={$taskCode}";

    $posted = ["receivers" => $receivers];

    list($success, $rsp) = tmsHttpPost($url, $posted);
    if ($success !== true) return [false, $rsp];
    if ($rsp->code !== 0) return [false, $rsp->msg];

    return [true, $rsp->result];
  }
  /**
   * 通过消息服务推送消息
   */
  private function _pushMsgTask($siteId, $taskCode)
  {
    if (!defined('TMS_MESSENGER_BACK_ADDRESS')) {
      return [false, '没有配置[TMS_MESSENGER_BACK_ADDRESS]参数'];
    }
    if (empty($taskCode)) {
      return [false, '指定的消息任务编码为空'];
    }

    $url = TMS_MESSENGER_BACK_ADDRESS . "/send/task/push";
    $url .= "?bucket={$siteId}&code={$taskCode}";

    list($success, $rsp) = tmsHttpGet($url);
    if ($success !== true) return [false, $rsp];
    if ($rsp->code !== 0) return [false, $rsp->msg];

    return [true, $rsp->result];
  }
  /**
   *
   */
  private function _sendTxtByOpenid($siteId, $openid, $openidSrc, $aTxt, $log)
  {
    $txt = implode("\n", $aTxt);
    $message = [
      "msgtype" => "text",
      "text" => [
        "content" => $txt,
      ],
    ];
    switch ($openidSrc) {
      case 'qy':
        $snsConfig = $this->model('sns\qy')->bySite($siteId);
        $snsProxy = $this->model('sns\qy\proxy', $snsConfig);
        $message['touser'] = $openid;
        $message['agentid'] = $snsConfig->agentid;
        $rst = $snsProxy->messageSend($message, $openid);
        break;
      case 'wx':
        $modelWx = $this->model('sns\wx');
        if (($wxConfig = $modelWx->bySite($siteId)) && $wxConfig->joined === 'Y') {
          $snsConfig = $this->model('sns\wx')->bySite($siteId);
        } else if (($wxConfig = $modelWx->bySite('platform')) && $wxConfig->joined === 'Y') {
          $snsConfig = $this->model('sns\wx')->bySite('platform');
        }
        if (isset($snsConfig)) {
          $snsProxy = $this->model('sns\wx\proxy', $snsConfig);
          $rst = $snsProxy->messageCustomSend($message, $openid);
        } else {
          $rst = [false, '无法获得有效的微信公众号配置信息'];
        }
        break;
    }
    /* 记录日志 */
    if (false === $rst[0]) {
      $log['status'] = 'failed:' . $this->escape(is_string($rst[1]) ? $rst[1] : $this->toJson($rst[1]));
    } else {
      $log['status'] = 'success';
    }
    $this->insert('xxt_log_tmplmsg_detail', $log, false);

    return $rst;
  }
}
