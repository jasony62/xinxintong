<?php

namespace sns;

/**
 * 公众号代理类的基类
 */
abstract class proxybase
{
  /**
   * 社交平台配置信息
   */
  protected $config;

  public function __construct($config)
  {
    $this->config = $config;
  }
  /**
   * 
   */
  abstract public function accessToken($newAccessToken = false);
  /**
   *
   */
  public function reset($config)
  {
    $this->config = $config;
  }
  /**
   * 从易信公众号获取信息
   *
   * 需要提供token的请求
   */
  protected function httpGet($cmd, $params = null, $newAccessToken = false, $appendAccessToken = true)
  {
    $url = $cmd;
    if ($appendAccessToken) {
      $token = $this->accessToken($newAccessToken);
      if ($token[0] === false) {
        return $token;
      }
      $url .= false == strpos($url, '?') ? '?' : '&';
      $url .= "access_token={$token[1]}";
    }

    if (!empty($params)) {
      if (false == strpos($url, '?')) {
        $url .= '?';
      } else {
        $url .= '&';
      }
      $url .= http_build_query($params);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
    if (false === ($response = curl_exec($ch))) {
      $err = curl_error($ch);
      curl_close($ch);
      return [false, $err];
    }
    curl_close($ch);

    $response = preg_replace("/\\\\\w|\x{000f}/", '', $response);
    $result = json_decode($response);
    if (isset($result->errcode)) {
      /* access_token有问题，重新获取access_token，并重发请求 */
      if ($newAccessToken !== true && in_array($result->errcode, [40001, 40014])) {
        return $this->httpGet($cmd, $params, true);
      }
      if ($result->errcode !== 0) {
        return [false, $result->errmsg . "(errcode:$result->errcode)"];
      }
    } else if (empty($result)) {
      if (strpos($response, '{') === 0) {
        return [false, 'json failed:' . $response];
      } else {
        return [false, $response];
      }
    }

    return [true, $result];
  }
  /**
   * 提交信息到公众号平台
   */
  protected function httpPost($cmd, $posted, $newAccessToken = false, $rawResponse = false)
  {
    $token = $this->accessToken($newAccessToken);
    if ($token[0] === false) {
      return $token;
    }

    $url = $cmd;
    $url .= false == strpos($url, '?') ? '?' : '&';
    $url .= "access_token=" . $token[1];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $posted);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
    if (false === ($response = curl_exec($ch))) {
      $err = curl_error($ch);
      curl_close($ch);
      return [false, $err];
    }
    curl_close($ch);

    if ($rawResponse) {
      return [true, $response];
    }

    $response = preg_replace("/\\\\\w|\x{000f}/", '', $response);
    $rst = json_decode($response);
    if (isset($rst->errcode)) {
      /* access_token有问题，重新获取access_token，并重发请求 */
      if ($newAccessToken !== true && in_array($rst->errcode, [40001, 40014])) {
        return $this->httpPost($cmd, $posted, true);
      }
      if ($rst->errcode !== 0) {
        return [false, $rst->errmsg . "($rst->errcode)"];
      }
    } else if (empty($rst)) {
      if (strpos($response, '{') === 0) {
        return [false, 'json failed:' . $response];
      } else {
        return [false, $response];
      }
    }

    return [true, $rst];
  }
  /**
   * 需要支持ssl
   */
  public function file_get_contents($url)
  {
    if (strpos($url, 'https') === 0) {
      // $ch = curl_init($url);
      // curl_setopt($ch, CURLOPT_HEADER, 0);
      // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      // curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
      // if (false === ($response = curl_exec($ch))) {
      //   $err = curl_error($ch);
      //   curl_close($ch);
      //   return [false, $err];
      // }
      // curl_close($ch);

      // return $response;
      $opts = [
        "ssl" => [
          "verify_peer" => false,
          "verify_peer_name" => false,
        ]
      ];

      return file_get_contents($url, false, $opts);
    }

    return file_get_contents($url);
  }
  /**
   * 将url的数据抓取到本地并保存在临时文件中返回
   *
   * $url
   */
  public function fetchUrl($url)
  {
    /**
     * 下载文件
     */
    $ext = 'jpg';
    $urlContent = file_get_contents($url);
    $responseInfo = $http_response_header;
    foreach ($responseInfo as $loop) {
      if (strpos($loop, "Content-disposition") !== false) {
        $disposition = trim(substr($loop, 21));
        $filename = explode(';', $disposition);
        $filename = array_pop($filename);
        $filename = explode('=', $filename);
        $filename = array_pop($filename);
        $filename = str_replace('"', '', $filename);
        $filename = explode('.', $filename);
        $ext = array_pop($filename);
        break;
      }
    }
    /**
     * 临时文件
     */
    $tmpfname = tempnam('', '');
    $tmpfname2 = $tmpfname . '.' . $ext;
    rename($tmpfname, $tmpfname2);
    $handle = fopen($tmpfname2, "w");
    fwrite($handle, $urlContent);
    fclose($handle);

    return $tmpfname2;
  }
}
