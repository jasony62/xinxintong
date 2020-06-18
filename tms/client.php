<?php

/**
 * 记录客户端状况
 */
class TMS_CLIENT
{
  /**
   * 获得当前用户的ID
   *
   */
  public static function get_client_uid()
  {
    $modelWay = TMS_APP::M('site\fe\way');
    if ($cookieRegUser = $modelWay->getCookieRegUser()) {
      return $cookieRegUser->unionid;
    }
    return false;
  }
  /**
   * get当前用户信息
   *
   * @param object $account
   *
   */
  public static function account()
  {
    $modelWay = TMS_APP::M('site\fe\way');
    if ($cookieRegUser = $modelWay->getCookieRegUser()) {
      $oAccount = new \stdClass;
      $oAccount->nickname = $cookieRegUser->nickname;
      $oAccount->uid = $cookieRegUser->unionid;
      return $oAccount;
    }
    return false;
  }
  /**
   * 退出登录状态
   */
  public static function logout()
  {
    /* 清除登录状态 */
    $modelWay = TMS_APP::M('site\fe\way');
    $modelWay->quitRegUser();
  }
}
