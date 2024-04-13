<?php

namespace site\fe\user;

/**
 * 验证码 
 */
trait CaptchaTrait
{
  /**
   * 验证码服务地址 
   */
  protected function captchaAddress()
  {
    if (defined('TMS_CHECK_CAPTCHA_ADDRESS')) {
      return TMS_CHECK_CAPTCHA_ADDRESS;
    }
    return '';
  }
  /**
   * 生成验证码
   */
  protected function createCaptcha($appId, $captchaId)
  {
    if (defined('TMS_CREATE_CAPTCHA_ADDRESS')) {
      $url = TMS_CREATE_CAPTCHA_ADDRESS;
      $url .= "?appid={$appId}&captchaid={$captchaId}&codeSize=6&alphabetType=number&returnType=text";

      list($success, $rsp, $rawRsp) = tmsHttpGet($url);
      if ($success !== true) {
        $this->logger->error($rsp);
        return false;
      }

      if (!is_object($rsp) && !isset($rsp->code)) {
        $this->logger->error("$url - $rawRsp");
        return false;
      }

      if ($rsp->code !== 0) {
        $this->logger->error("$url - $rawRsp");
        return false;
      }

      return $rsp->result;
    }

    return false;
  }
  /**
   * 检查验证码是否合法
   */
  protected function checkCaptcha($appId, $captchaId, $captcha)
  {
    if (defined('TMS_CHECK_CAPTCHA_ADDRESS')) {
      $url = TMS_CHECK_CAPTCHA_ADDRESS;
      $url .= "?appid={$appId}&captchaid={$captchaId}&code={$captcha}";

      list($success, $rsp, $rawRsp) = tmsHttpGet($url);
      if ($success !== true) {
        $this->logger->error($rsp);
        return false;
      }

      if (!is_object($rsp) && !isset($rsp->code)) {
        $this->logger->error("$url - $rawRsp");
        return false;
      }

      if ($rsp->code !== 0) {
        $this->logger->error("$url - $rawRsp");
        return false;
      }

      return true;
    }

    return false;
  }
}
