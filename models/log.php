<?php

/**
 *
 */
class log_model extends TMS_MODEL
{
  /**
   *
   */
  public function log($siteid, $method, $data, $agent = '', $referer = '')
  {
    if (empty($agent) && isset($_SERVER['HTTP_USER_AGENT'])) {
      $agent = tms_get_server('HTTP_USER_AGENT');
    }
    if (empty($referer) && isset($_SERVER['HTTP_REFERER'])) {
      $referer = tms_get_server('HTTP_REFERER');
    }

    $current = time();
    $log = [];
    $log['siteid'] = $siteid;
    $log['method'] = $method;
    $log['create_at'] = $current;
    $log['data'] = $data;
    $log['user_agent'] = $agent;
    $log['referer'] = $referer;
    $log['request_method'] = tms_get_server('REQUEST_METHOD');
    $log['http_accept'] = tms_get_server('HTTP_ACCEPT');

    $logid = $this->insert('xxt_log', $log, true);

    return $logid;
  }
  /**
   * 
   */
  public function setResult($logid, $result)
  {
    if (!is_string($result)) $result = json_encode($result);

    $rst = $this->update('xxt_log', ['result' => $this->escape($result)], ['id' => $logid]);

    return $rst;
  }
  /**
   *
   */
  public function remove($logid)
  {
    return $this->delete('xxt_log', ['id' => $logid]);
  }
  /**
   * 接收消息日志
   */
  public function receive($msg)
  {
    $openid = $msg['from_user'];
    $siteId = $msg['siteid'];
    $src = $msg['src'];
    $fan = TMS_APP::model("sns\\" . $src . "\\fan")->byOpenid($siteId, $openid, 'nickname');

    $createAt = $msg['create_at'];

    $r = array();
    $r['siteid'] = $siteId;
    !empty($msg['msgid']) && $r['msgid'] = $msg['msgid'];
    $r['to_user'] = $msg['to_user'];
    $r['openid'] = $openid;
    $r['nickname'] = !empty($fan) ? $this->escape($fan->nickname) : '';
    $r['create_at'] = $createAt;
    $r['type'] = $msg['type'];
    if (is_array($msg['data'])) {
      $data = array();
      foreach ($msg['data'] as $d) {
        $data[] = urlencode($d);
      }
      $r['data'] = $this->escape(urldecode(json_encode($data)));
    } else {
      $r['data'] = $this->escape($msg['data']);
    }

    $this->insert('xxt_log_mpreceive', $r, false);

    return true;
  }
  /**
   * 是否已经接收过消息
   *
   * @param array $msg
   * @param int $interval 两条消息的时间间隔
   */
  public function hasReceived($msg, $interval = 60)
  {
    $siteId = isset($msg['siteid']) ? $msg['siteid'] : $msg['siteid'];
    $msgid = $msg['msgid'];
    /**
     * 没有消息ID就认为没收到过
     */
    if (empty($msgid)) {
      $current = time() - $interval;
      $openid = $msg['from_user'];
      if (is_array($msg['data'])) {
        $data = array();
        foreach ($msg['data'] as $d) {
          $data[] = urlencode($d);
        }
        $logData = $this->escape(urldecode(json_encode($data)));
      } else {
        $logData = $this->escape($msg['data']);
      }
      $q = [
        'count(*)',
        'xxt_log_mpreceive',
        "siteid='$siteId' and openid='$openid' and data='$logData' and create_at>$current",
      ];
      $cnt = (int) $this->query_val_ss($q);
    } else {
      $q = [
        'count(*)',
        'xxt_log_mpreceive',
        "siteid='$siteId' and msgid='$msgid'",
      ];
      $cnt = (int) $this->query_val_ss($q);
    }

    return $cnt !== 0;
  }
  /**
   * 记录所有发送给用户的消息
   */
  public function send($siteid, $openid, $groupid, $content, $matter)
  {
    $i['siteid'] = $siteid;
    $i['creater'] = TMS_CLIENT::get_client_uid();
    $i['create_at'] = time();
    !empty($openid) && $i['openid'] = $openid;
    !empty($groupid) && $i['groupid'] = $groupid;
    !empty($content) && $i['content'] = $this->escape($content);
    if (!empty($matter)) {
      $i['matter_id'] = $matter->id;
      $i['matter_type'] = $matter->type;
    }
    $this->insert('xxt_log_mpsend', $i, false);

    return true;
  }
  /**
   *
   */
  public function read()
  {
  }
  /**
   * 用户是否可以接收t推送消息
   */
  public function canReceivePush($siteid, $openid)
  {
    return true;
  }
  /**
   * 汇总各类日志，形成用户完整的踪迹
   */
  public function track($siteid, $openid, $page = 1, $size = 30)
  {
    $q = array(
      'creater,create_at,content,matter_id,matter_type',
      'xxt_log_mpsend',
      "siteid='$siteid' and openid='$openid'",
    );
    $q2 = array(
      'r' => array('o' => ($page - 1) * $size, 'l' => $size),
      'o' => 'create_at desc',
    );

    $sendlogs = $this->query_objs_ss($q, $q2);

    $q = array(
      'create_at,data content',
      'xxt_log_mpreceive',
      "siteid='$siteid' and openid='$openid' and type='text'",
    );
    $q2 = array(
      'r' => array('o' => ($page - 1) * $size, 'l' => $size),
      'o' => 'create_at desc',
    );

    $recelogs = $this->query_objs_ss($q, $q2);

    $logs = array_merge($sendlogs, $recelogs);

    /**
     * order by create_at
     */
    usort($logs, function ($a, $b) {
      return $b->create_at - $a->create_at;
    });

    return $logs;
  }
  /**
   * 文章打开的次数
   * todo 应该用哪个openid，根据oauth是否开放来决定？
   */
  public function getMatterRead($type, $id, $page, $size)
  {
    $q = array(
      'l.openid,l.nickname,l.read_at',
      'xxt_log_matter_read l',
      "l.matter_type='$type' and l.matter_id='$id'",
    );
    /**
     * 分页数据
     */
    $q2 = array(
      'o' => 'l.read_at desc',
      'r' => array(
        'o' => (($page - 1) * $size),
        'l' => $size,
      ),
    );

    $log = $this->query_objs_ss($q, $q2);

    return $log;
  }
  /**
   * 群发消息发送日志
   */
  public function mass($sender, $siteid, $matterId, $matterType, $message, $msgid, $result)
  {
    $log = array(
      'siteid' => $siteid,
      'matter_type' => $matterType,
      'matter_id' => $matterId,
      'sender' => $sender,
      'send_at' => time(),
      'message' => $this->escape(json_encode($message)),
      'result' => $result,
      'msgid' => $msgid,
    );

    $this->insert('xxt_log_massmsg', $log, false);

    return true;
  }
}
