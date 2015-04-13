<?php
/**
 * 执行OAuth操作
 */
if (!isset($_GET['code'])) {
    $url[] = strtolower(strtok($_SERVER['SERVER_PROTOCOL'], '/'));
    $url[] = '://';
    $url[] = $_SERVER['HTTP_HOST'];
    $url[] = $_SERVER['REQUEST_URI'];

    $state = 'passed';

    $oauth = "https://open.weixin.qq.com/connect/oauth2/authorize";
    $oauth .= "?appid=$wx_appid";
    $oauth .= "&redirect_uri=".urlencode(implode('', $url));
    $oauth .= "&response_type=code";
    $oauth .= "&scope=snsapi_base";
    $oauth .= "&state=$state#wechat_redirect";

    header("Location: $oauth");
    exit;
} else if (isset($_GET['code'])){
    /**
     * 获得openid
     */
    $code = $_GET['code'];

    $tokenUrl = "https://api.weixin.qq.com/sns/oauth2/access_token";
    $tokenUrl .= "?appid=$wx_appid";
    $tokenUrl .= "&secret=$wx_appsecret";
    $tokenUrl .= "&code=$code";
    $tokenUrl .= "&grant_type=authorization_code";

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
    if (false === ($response = curl_exec($ch))) {
        $err = curl_error($ch);
        curl_close($ch);
        die($err);
    }
    curl_close($ch);

    $token = json_decode($response);
    if (isset($token->errcode))
        die($token->errmsg);

    $openid = $token->openid;
    /**
     * 将openid保存在cookie，可用于进行用户身份绑定
     */
    $who = array($openid,'wx');
    require_once dirname(dirname(__FILE__)).'/tms/tms_model.php';
    $model = new TMS_MODEL;
    $encoded = $model->encrypt(json_encode($who), 'ENCODE', $mpid);
    setcookie("xxt_{$mpid}_oauth", $encoded, null, '/', null, false);
}
