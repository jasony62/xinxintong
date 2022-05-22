<?php

namespace site\fe\user;

/**
 * 验证码 
 */
trait CaptchaTrait
{
  /**
   * 检查验证码是否合法
   */
  protected function  checkCaptcha($appId, $captchaId, $captcha)
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
