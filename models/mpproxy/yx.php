<?php
require_once dirname(__FILE__).'/base.php';
/**
 * 易信公众号号代理类
 */
class yx_model extends mpproxy_base {
    /**
     *
     * $mpid
     */
    public function __construct($mpid)
    {
        parent::__construct($mpid);
    }
    /**
     * 获得与公众平台进行交互的token
     */
    protected function accessToken($newAccessToken=false) 
    {
        $whichToken = "yx_appid,yx_appsecret,yx_token,yx_token_expire_at";
        if ($newAccessToken === false) {
            if (isset($this->yx_token) && time() < $this->yx_token['expire_at'] - 60) {
                /**
                 * 在同一次请求中可以重用
                 */
                return array(true, $this->yx_token['value']);
            }
            /**
             * 从数据库中获取之前保留的token
             */
            $app = TMS_APP::model('mp\mpaccount')->byId($this->mpid, $whichToken);
            if (!empty($app->yx_token) && time() < (int)$app->yx_token_expire_at - 60) {
                /**
                 * 数据库中保存的token可用
                 */
                $this->yx_token = array(
                    'value'=>$app->yx_token,
                    'expire_at'=>$app->yx_token_expire_at
                );
                return array(true, $app->yx_token);
            }
        } else {
            /**
             * 从数据库中获取之前保留的token
             */
            $app = TMS_APP::model('mp\mpaccount')->byId($this->mpid, $whichToken);
        }
        /**
         * 重新获取token
         */
        $url_token = "https://api.yixin.im/cgi-bin/token";
        $url_token .= "?grant_type=client_credential"; 
        $url_token .= "&appid=$app->yx_appid&secret=$app->yx_appsecret";
        $ch = curl_init($url_token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        if (false === ($response = curl_exec($ch))) {
            $err = curl_error($ch);
            curl_close($ch);
            return array(false, $err);
        }
        curl_close($ch);
        $token = json_decode($response);
        if (isset($token->errcode))
            return array(false, $token->errmsg);
        /**
         * 保存获得的token
         */
        $u["yx_token"] = $token->access_token;
        $u["yx_token_expire_at"] = (int)$token->expires_in + time();

        TMS_APP::model()->update('xxt_mpaccount', $u, "mpid='$this->mpid'");

        $this->yx_token = array(
            'value'=>$u["yx_token"],
            'expire_at'=>$u["yx_token_expire_at"]
        );

        return array(true, $token->access_token);
    }
    /**
     *
     */
    public function oauthUrl($mpid, $redirect, $state=null) 
    {
        $mpa = TMS_APP::model('mp\mpaccount')->byId($mpid, 'yx_appid');

        $oauth = "http://open.plus.yixin.im/connect/oauth2/authorize";
        $oauth .= "?appid=$mpa->yx_appid";
        $oauth .= "&redirect_uri=".urlencode($redirect);
        $oauth .= "&response_type=code";
        $oauth .= "&scope=snsapi_base";
        !empty($state) && $oauth .= "&state=$state";
        $oauth .= "#yixin_redirect";

        return $oauth;
    }
    /**
     *
     */
    public function mobile2Openid($mobile)
    {
        $cmd = 'https://api.yixin.im/cgi-bin/user/valid';

        $rst = $this->httpGet($cmd, array('mobile'=>$mobile));

        return $rst;
    }
    /**
     * 获得所有的易信粉丝
     */
    public function userGet($nextOpenid='')
    {
        $cmd = 'https://api.yixin.im/cgi-bin/user/get';

        if (empty($nextOpenid)) {
            $params = array('next_openid'=>$nextOpenid);
            $result = $this->httpGet($cmd, $params);
        } else
            $result = $this->httpGet($cmd);

        return $result;
    }
    /**
     * 获得一个指定粉丝的信息
     */
    public function userInfo($openid, $getGroup=false)
    {
        $cmd = 'https://api.yixin.im/cgi-bin/user/info';

        $params = array('openid'=>$openid);

        $userRst = $this->httpGet($cmd, $params);

        if ($getGroup && $userRst[0]) {
            /**
             * 获得粉丝的分组信息
             */
            $cmd = 'https://api.yixin.im/cgi-bin/groups/getid';
            $posted = json_encode(array("openid"=>$openid));
            $groupRst = $this->httpPost($cmd, $posted);
            if ($groupRst[0])
                $userRst[1]->groupid = $groupRst[1]->groupid;
        }

        return $userRst;
    }
    /**
     * 获得所有的易信粉丝分组
     */
    public function groupsGet()
    {
        $cmd = 'https://api.yixin.im/cgi-bin/groups/get';

        $rst = $this->httpGet($cmd);

        return $rst;
    }
}
