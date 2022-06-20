<?php

namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
require_once dirname(__FILE__) . '/captcha.php';

/**
 * 用户手机+验证码登录 
 */
class mobile extends \site\fe\base
{
  use CaptchaTrait;

  public function get_access_rule()
  {
    $rule_action['rule_type'] = 'white';
    $rule_action['actions'] = array();
    $rule_action['actions'][] = 'index';
    $rule_action['actions'][] = 'sendCode';
    $rule_action['actions'][] = 'login';

    return $rule_action;
  }
  /**
   * 发送验证码
   */
  public function sendCode_action($appId, $captchaId)
  {
    header('Access-Control-Allow-Headers: Origin, Content-Type, Accept');
    header("Access-Control-Allow-Origin: *");

    // 通过短信通道发送验证码
    if (!defined('TMS_SEND_SMSCODE_ADDRESS')) {
      return new \ResponseError("系统未配置短信通道，无法发送验证码");
    }

    // 检查输入的数据是否完整
    $data = $this->getPostJson(false);
    if (empty($appId) || empty($captchaId) || empty($data->mobile)) {
      return new \ResponseError("参数不完整");
    }

    $mobile = $data->mobile;
    if (0 === preg_match('/^1(3[0-9]|4[57]|5[0-35-9]|66|7[0135678]|8[0-9]|9[0-9])\\d{8}$/', $mobile)) {
      return new \ResponseError("请提供正确的手机号");
    }

    // 通过账号服务生成验证码
    $captcha = $this->createCaptcha($appId, $captchaId);
    if (false === $captcha) {
      return new \ResponseError("获取验证码失败");
    }

    // 通过短信通道发送验证码
    $url = TMS_SEND_SMSCODE_ADDRESS;
    $posted = new \stdClass;
    $posted->phoneNumber = $mobile;
    $posted->code = $captcha;
    $posted->outId = $captchaId;

    list($success, $rsp, $rawRsp) = tmsHttpPost($url, $posted);
    if ($success !== true) {
      $this->logger->error($rsp);
      return new \ResponseError("短信发送失败");
    }

    if (!is_object($rsp) && !isset($rsp->code)) {
      $this->logger->error($rawRsp);
      return new \ResponseError("短信发送失败");
    }

    if ($rsp->code !== 0) {
      $this->logger->error($rawRsp);
      return new \ResponseError("短信发送失败");
    }

    return new \ResponseData('ok');
  }
  /**
   * 执行用户检查
   */
  public function login_action($appId, $captchaId)
  {
    header('Access-Control-Allow-Headers: Origin, Content-Type, Accept');
    header("Access-Control-Allow-Origin: *");

    // 检查输入的数据是否完整
    $data = $this->getPostJson(false);
    if (empty($appId) || empty($captchaId) || empty($data->mobile) ||  empty($data->captcha)) {
      return new \ResponseError("登录信息不完整");
    }

    $modelWay = $this->model('site\fe\way');
    $modelReg = $this->model('site\user\registration');
    $cookieRegUser = $modelWay->getCookieRegUser();
    if ($cookieRegUser) {
      if (isset($cookieRegUser->loginExpire)) {
        return new \ResponseError("请退出当前账号再登录");
      }
      $modelWay->quitRegUser();
    }

    // 检查登录条件
    $rst = tms_login_check();
    if ($rst[0] === false) {
      return new \ResponseError($rst[1]);
    }

    // 清理数据
    $uname = $modelReg->escape($data->mobile);

    if (0 === preg_match('/^1(3[0-9]|4[57]|5[0-35-9]|7[0135678]|8[0-9]|9[0-9])\\d{8}$/', $uname)) {
      return new \ResponseError("请使用手机号作为登录账号");
    }

    // 通过账号服务检查验证码是否有效
    if (false === $this->checkCaptcha($appId, $captchaId, $data->captcha)) {
      return new \ResponseError("验证码未通过验证，请重试");
    }

    // 用户已经存在
    $userExist = $modelReg->checkUname($uname);

    if ($userExist === false) {
      // 新用户注册
      // 应该标注是手机+验证码注册，不允许用密码登录
      $aOptions = [];
      $aOptions['from_ip'] = $this->client_ip();
      $aOptions['is_smscode_register'] = 1;
      $oRegistration = $modelReg->create($this->siteId, $uname, $uname, $aOptions);
    }

    // 获取用户完整信息
    $oRegistration = $modelReg->byUname($uname, ['forbidden' => 0, 'fields' => 'uid unionid,email uname,nickname,password,salt,from_siteid,login_limit_expire,pwd_error_num']);

    /* cookie中保留注册信息 */
    $aResult = $modelWay->shiftRegUser($oRegistration);
    if (false === $aResult[0]) {
      return new \ResponseError($aResult[1]);
    }

    /* 记录登录状态 */
    $fromip = $this->client_ip();
    $modelReg->updateLastLogin($oRegistration->unionid, $fromip);

    $oCookieUser = $modelWay->who($this->siteId);
    if ($referer = $this->myGetCookie('_user_access_referer')) {
      $oCookieUser->_loginReferer = $referer;
      $this->mySetCookie('_user_access_referer', null);
    }
    /**
     * 支持自动登录
     */
    if (isset($data->autologin) && $data->autologin === 'Y') {
      $expire = time() + (86400 * 365 * 10);
      $ua = $_SERVER['HTTP_USER_AGENT'];
      $token = [
        'uid' => $oRegistration->unionid,
        'email' => $oRegistration->uname,
        'password' => $oRegistration->password,
      ];
      $cookiekey = md5($ua);
      $cookieToken = json_encode($token);
      $encoded = $modelWay->encrypt($cookieToken, 'ENCODE', $cookiekey);

      $this->mySetCookie('_login_auto', 'Y', $expire);
      $this->mySetCookie('_login_token', $encoded, $expire);
    }

    return new \ResponseData($oCookieUser);
  }
}
