<?php
require_once dirname(__FILE__).'/base.php';
/**
 * 微信公众号代理类
 */
class wx_model extends mpproxy_base {
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
        $whichToken = "wx_appid,wx_appsecret,wx_token,wx_token_expire_at";
        if ($newAccessToken === false) {
            if (isset($this->wx_token) && time() < $this->wx_token['expire_at'] - 60) {
                /**
                 * 在同一次请求中可以重用
                 */
                return array(true, $this->wx_token['value']);
            }
            /**
             * 从数据库中获取之前保留的token
             */
            $app = TMS_APP::model('mp\mpaccount')->byId($this->mpid, $whichToken);
            if (!empty($app->wx_token) && time() < (int)$app->wx_token_expire_at - 60) {
                /**
                 * 数据库中保存的token可用
                 */
                $this->wx_token = array(
                    'value'=>$app->wx_token,
                    'expire_at'=>$app->wx_token_expire_at
                );
                return array(true, $app->wx_token);
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
        $url_token .= "&appid=$app->wx_appid&secret=$app->wx_appsecret";
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
        $u["wx_token"] = $token->access_token;
        $u["wx_token_expire_at"] = (int)$token->expires_in + time();

        TMS_APP::model()->update('xxt_mpaccount', $u, "mpid='$this->mpid'");

        $this->wx_token = array(
            'value'=>$u["wx_token"],
            'expire_at'=>$u["wx_token_expire_at"]
        );

        return array(true, $token->access_token);
    }
    /**
     *
     */
    public function oauthUrl($mpid, $redirect, $state=null)
    {
        $mpa = TMS_APP::model('mp\mpaccount')->byId($mpid, 'wx_appid');

        $oauth = "https://open.weixin.qq.com/connect/oauth2/authorize";
        $oauth .= "?appid=$mpa->wx_appid";
        $oauth .= "&redirect_uri=".urlencode($redirect);
        $oauth .= "&response_type=code";
        $oauth .= "&scope=snsapi_base";
        !empty($state) && $oauth .= "&state=$state";
        $oauth .= "#wechat_redirect";

        return $oauth;
    }
    /**
     * 获得所有的微信粉丝
     */
    public function userGet($nextOpenid='')
    {
        $cmd = 'https://api.weixin.qq.com/cgi-bin/user/get';

        if (!empty($nextOpenid)) {
            $params = array('next_openid'=>$nextOpenid);
            $result = $this->httpGet($cmd, $params);
        } else {
            $result = $this->httpGet($cmd);
        }
        return $result;
    }
    /**
     * 获得一个指定粉丝的信息
     */
    public function userInfo($openid, $getGroup=false)
    {
        $cmd = 'https://api.weixin.qq.com/cgi-bin/user/info';

        $params = array('openid'=>$openid);

        $userRst = $this->httpGet($cmd, $params);

        if ($getGroup && $userRst[0]) {
            /**
             * 获得粉丝的分组信息
             */
            $cmd = 'https://api.weixin.qq.com/cgi-bin/groups/getid';
            $posted = json_encode(array("openid"=>$openid));
            $groupRst = $this->httpPost($cmd, $posted);
            if ($groupRst[0])
                $userRst[1]->groupid = $groupRst[1]->groupid;
        }

        return $userRst;
    }
    /**
     * 获得所有的微信粉丝分组
     */
    public function groupsGet()
    {
        $cmd = 'https://api.weixin.qq.com/cgi-bin/groups/get';

        $rst = $this->httpGet($cmd);

        return $rst;
    }
    /**
     * upload menu.
     */
    public function menuCreate($menu)
    {
        $cmd = 'https://api.weixin.qq.com/cgi-bin/menu/create';

        $rst = $this->httpPost($cmd, $menu);

        return $rst;
    }
    /**
     * 获得下载媒体文件的链接
     *
     * $mediaid
     */
    public function mediaGetUrl($mediaId)
    {
        $rst = $this->accessToken();
        if ($rst[0] === false) 
            return $rst[1];

        $url = 'http://file.api.weixin.qq.com/cgi-bin/media/get';
        $url .= "?access_token={$rst[1]}";
        $url .= "&media_id=$mediaId";

        return array(true, $url);
    }
}
