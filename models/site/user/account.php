<?php

namespace site\user;

/**
 * 站点访客用户
 */
class account_model extends \TMS_MODEL
{
  /**
   * 缺省的用户组
   */
  const DEFAULT_LEVEL = 1;
  /**
   * 缺省属性列表
   */
  const DEFAULT_FIELDS = 'siteid,uid,nickname,is_wx_primary,wx_openid,is_qy_primary,qy_openid,unionid,is_reg_primary,headimgurl';
  /**
   *
   *
   * @param string $uid
   *
   * @return object
   */
  public function byId($uid, $aOptions = [])
  {
    $fields = isset($aOptions['fields']) ? $aOptions['fields'] : self::DEFAULT_FIELDS;
    $q = [
      $fields,
      'xxt_site_account',
      ["uid" => $uid],
    ];
    $act = $this->query_obj_ss($q);

    return $act;
  }
  /**
   * 根据公众号openid获得指定站点下的访客用户账号
   *
   * 一个站点下，同一个openid，可能对应多个访客用户账号，每个访客账号对应不同注册账号
   *
   * @param string $siteId
   * @param string $snsName
   * @param string $openid
   *
   * @return object
   */
  public function &byOpenid($siteId, $snsName, $openid, $aOptions = [])
  {
    $fields = isset($aOptions['fields']) ? $aOptions['fields'] : self::DEFAULT_FIELDS;

    $q = [
      $fields,
      'xxt_site_account',
      [$snsName . '_openid' => $openid],
    ];
    if (!empty($siteId) && $siteId !== 'ALL') {
      $q[2]["siteid"] = $siteId;
    }
    if (isset($aOptions['is_primary'])) {
      $q[2]['is_' . $snsName . '_primary'] = $aOptions['is_primary'];
    }
    if (isset($aOptions['is_reg_primary'])) {
      $q[2]['is_reg_primary'] = $aOptions['is_reg_primary'];
    }
    if (isset($aOptions['has_unionid']) && true === $aOptions['has_unionid']) {
      $q[2]['unionid'] = (object) ['op' => '<>', 'pat' => ''];
    }
    $acts = $this->query_objs_ss($q);

    return $acts;
  }
  /**
   * 获得指定站点下公众号用户的主访客账号
   */
  public function byPrimaryOpenid($siteId, $snsName, $openid)
  {
    $aOptions['is_primary'] = 'Y';
    $siteUsers = $this->byOpenid($siteId, $snsName, $openid, $aOptions);
    if (count($siteUsers) > 1) {
      $this->logger->error('数据错误，获得了多个公众号用户【' . $siteId . '/' . $openid . '】主访客账号');
      throw new \Exception('数据错误，获得了多个公众号用户主访客账号');
    } else if (count($siteUsers) === 0) {
      return false;
    } else {
      return $siteUsers[0];
    }
  }
  /**
   * 将用户设置为公众号用户的主用户
   */
  public function setAsSnsPrimary($oUser, $snsName)
  {
    $oPrimaryUser = $this->byPrimaryOpenid($oUser->siteid, $snsName, $oUser->{$snsName . '_openid'});
    if ($oPrimaryUser && $oPrimaryUser->uid !== $oUser->uid) {
      $this->update('xxt_site_account', ['is_' . $snsName . '_primary' => 'N'], ['uid' => $oPrimaryUser->uid]);
    }

    $this->update('xxt_site_account', ['is_' . $snsName . '_primary' => 'Y'], ['uid' => $oUser->uid]);

    return true;
  }
  /**
   * get account objects by it's unionid
   *
   * @param string $unionid
   *
   * @return object
   */
  public function &byUnionid($unionid, $aOptions = [])
  {
    $fields = isset($aOptions['fields']) ? $aOptions['fields'] : self::DEFAULT_FIELDS;
    $q = [
      $fields,
      'xxt_site_account',
      ["unionid" => $unionid],
    ];
    if (isset($aOptions['is_reg_primary'])) {
      $q[2]['is_reg_primary'] = $aOptions['is_reg_primary'];
    }
    if (isset($aOptions['siteid'])) {
      $q[2]['siteid'] = $aOptions['siteid'];
    }
    $acts = $this->query_objs_ss($q);

    return $acts;
  }
  /**
   * 获得站定下，关联注册账号的主访客账号
   * 每个站点下，每个注册账号，只有一个主访客账号
   *
   */
  public function byPrimaryUnionid($siteId, $unionid, $aOptions = [])
  {
    $aOptions['siteid'] = $siteId;
    $aOptions['is_reg_primary'] = 'Y';
    $acts = $this->byUnionid($unionid, $aOptions);
    if (count($acts) > 1) {
      // 正常情况下一定会存在，且只有一个
      $this->logger->error('数据错误，注册账号的主访客账号存在多条数据【siteid=' . $siteId . '/unionid=' . $unionid . '】');
      throw new \Exception('数据错误，注册账号的主访客账号存在多条数据');
    } else if (count($acts) === 1) {
      return $acts[0];
    } else {
      $this->logger->error('数据错误，注册账号不存在主访客账号【siteid=' . $siteId . '/unionid=' . $unionid . '】');
      return false;
    }
  }
  /**
   * 绑定公众号账号信息
   */
  public function bindSns($account, $snsName, $snsUser)
  {
    // 公众号用户信息
    $propsAccount = [
      'ufrom' => $snsName,
      $snsName . '_openid' => $snsUser->openid,
      'nickname' => isset($snsUser->nickname) ? $this->escape($snsUser->nickname) : '',
      'headimgurl' => isset($snsUser->headimgurl) ? $snsUser->headimgurl : '',
    ];
    /* 判断是否为首次绑定 */
    $q = [
      'uid',
      'xxt_site_account',
      ['siteid' => $account->siteid, $snsName . '_openid' => $snsUser->openid, 'is_' . $snsName . '_primary' => 'Y'],
    ];
    $uid = $this->query_objs_ss($q);
    if (count($uid) > 1) {
      throw new \Exception('公众号用户对应了多个首次绑定访客账号');
    } else if (count($uid) === 0) {
      $propsAccount['is_' . $snsName . '_primary'] = 'Y';
    }

    $ret = $this->update('xxt_site_account', $propsAccount, ["uid" => $account->uid]);

    return $ret;
  }
  /**
   * 创建空站点访客帐号
   *
   * 数据合格性检查
   * 1，一个站点下，对应一个注册账号，只能有一个主访客账号
   * 2，一个站点下，一个注册账号或非注册账号，一个openid，只能对应一个访客账号
   *
   * @param string $siteId
   * @param bool $persisted 是否在数据库中创建
   * @param array $props
   *
   * @return object
   */
  public function &blank($siteid, $persisted = false, $props = array())
  {
    /* new accouont key */
    $uid = isset($props['uid']) ? $props['uid'] : uniqid();
    /* new account */
    $current = time();
    $oNewAccount = new \stdClass;
    $oNewAccount->siteid = $siteid;
    $oNewAccount->uid = $uid;
    $oNewAccount->unionid = isset($props['unionid']) ? $props['unionid'] : '';
    $oNewAccount->is_reg_primary = isset($props['is_reg_primary']) ? $props['is_reg_primary'] : 'N';
    $oNewAccount->level_id = self::DEFAULT_LEVEL;
    $oNewAccount->reg_time = $current;
    $oNewAccount->user_agent = tms_get_server('HTTP_USER_AGENT') ? tms_get_server('HTTP_USER_AGENT') : '';

    if (empty($props['nickname'])) {
      $oNewAccount->nickname = '用户' . $uid;
    } else {
      $oNewAccount->nickname = $this->escape($props['nickname']);
    }
    if (isset($props['headimgurl'])) {
      $oNewAccount->headimgurl = $props['headimgurl'];
    }
    if (isset($props['ufrom'])) {
      $oNewAccount->ufrom = $props['ufrom'];
    }
    /* 检查：一个公众号用户（openid），在一个站点下，只能有一个主站点用户账号 */
    if (isset($props['wx_openid'])) {
      $oNewAccount->wx_openid = $props['wx_openid'];
      if (isset($props['is_wx_primary'])) {
        if ($this->byPrimaryOpenid($siteid, 'wx', $oNewAccount->wx_openid)) {
          throw new \Exception('数据错误，重复创建主公众号用户站点访客账号');
        }
        $oNewAccount->is_wx_primary = $props['is_wx_primary'];
      }
    }
    if (isset($props['qy_openid'])) {
      $oNewAccount->qy_openid = $props['qy_openid'];
      if (isset($props['is_qy_primary'])) {
        if ($this->byPrimaryOpenid($siteid, 'qy', $oNewAccount->qy_openid)) {
          throw new \Exception('数据错误，重复创建主公众号用户站点访客账号');
        }
        $oNewAccount->is_qy_primary = $props['is_qy_primary'];
      }
    }
    if ($persisted === true) {
      $this->insert('xxt_site_account', $oNewAccount, false);
      //记录站点活跃数
      $this->model('site\active')->add($siteid, $oNewAccount, 0, 'createSiteUser');
    }

    return $oNewAccount;
  }
  /**
   * record last login information.
   */
  public function updateLastLogin($uid, $from_ip)
  {
    $updated['last_login'] = time();
    $updated['last_ip'] = $from_ip;
    $rst = $this->update(
      'xxt_site_account',
      $updated,
      "uid='$uid'"
    );

    return $rst;
  }
  /**
   * 修改昵称
   */
  public function changeNickname($siteId, $uid, $nickname)
  {
    $rst = $this->update(
      'xxt_site_account',
      ['nickname' => $nickname],
      ["siteid" => $siteId, "uid" => $uid]
    );

    return $rst;
  }
  /**
   * 修改头像
   */
  public function changeHeadImgUrl($siteId, $uid, $headimgurl)
  {
    $rst = $this->update(
      'xxt_site_account',
      ['headimgurl' => $headimgurl],
      ["siteid" => $siteId, "uid" => $uid]
    );

    return $rst;
  }
  /**
   * 删除一个注册用户
   *
   * 如果一个用户从来没有登录过，就可以直接删除
   *
   */
  public function remove($uid)
  {
    $q = array(
      'count(*)',
      'xxt_site_account',
      "uid='$uid' and reg_time=last_login",
    );
    /**
     * 从来没有登录过，直接删除数据
     */
    if ('1' === $this->query_val_ss($q)) {
      $this->delete(
        'xxt_site_account',
        ['uid' => $uid]
      );
      return true;
    }

    return false;
  }
}
