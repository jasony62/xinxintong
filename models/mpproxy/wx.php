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
     * 加密/校验流程：
     * 1. 将token、timestamp、nonce三个参数进行字典序排序 
     * 2. 将三个参数字符串拼接成一个字符串进行sha1加密 
     * 3. 开发者获得加密后的字符串可与signature对比，标识该请求来源于易信 
     *
     * 若确认此次GET请求来自易信服务器，请原样返回echostr参数内容，则接入生效，否则接入失败。
     */
    public function join($data) 
    {    
        $signature = $data['signature'];
        $timestamp = $data['timestamp'];
        $nonce = $data['nonce'];
        $echostr = $data['echostr'];

        $mpa = TMS_APP::G('mp\mpaccount');

        $tmpArr = array($mpa->token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr );
        $tmpStr = sha1($tmpStr);
        if ($tmpStr === $signature) {
            /**
             * 如果存在，断开公众号原有连接
             */
            TMS_APP::model()->update(
                'xxt_mpaccount', 
                array('wx_joined'=>'N'), 
                "wx_appid='$mpa->wx_appid' and wx_appsecret='$mpa->wx_appsecret'");
            /**
             * 确认建立连接
             */
            TMS_APP::model()->update(
                'xxt_mpaccount', 
                array('wx_joined'=>'Y'), 
                "mpid='$this->mpid'");

            return array(true, $echostr);
        } else {
            return array(false, 'failed.');
        }
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
        $url_token = "https://api.weixin.qq.com/cgi-bin/token";
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
     * 获得微信JSSDK签名包
     *
     * $mpid
     */
    public function getJssdkSignPackage($url)
    {
        $mpa = TMS_APP::M('mp\mpaccount')->byId($this->mpid, 'wx_appid');

        $rst = $this->getJsApiTicket();
        if ($rst[0] === false)
            return $rst;

        $jsapiTicket = $rst[1];

        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);

        $signPackage = array(
            "appId" => $mpa->wx_appid,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );

        $js = "signPackage={appId:'{$signPackage['appId']}'";
        $js .= ",nonceStr:'{$signPackage['nonceStr']}'";
        $js .= ",timestamp:'{$signPackage['timestamp']}'";
        $js .= ",url:'{$signPackage['url']}'";
        $js .= ",signature:'{$signPackage['signature']}'}";

        return array(true, $js); 
    }
    /**
     *
     */
    private function createNonceStr($length = 16) 
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++)
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);

        return $str;
    }
    /**
     *
     */
    private function getJsapiTicket()
    {
        $mpa = TMS_APP::M('mp\mpaccount')->byId($this->mpid, 'wx_jsapi_ticket,wx_jsapi_ticket_expire_at');

        if (!empty($mpa->wx_jsapi_ticket) && time() < $mpa->wx_jsapi_ticket_expire_at-60)
            return array(true, $mpa->wx_jsapi_ticket);

        $cmd = "https://api.weixin.qq.com/cgi-bin/ticket/getticket";
        $params = array('type'=>'jsapi');

        $rst = $this->httpGet($cmd, $params);
        if ($rst[0] === false)
            return $rst[1];

        $ticket = $rst[1];

        TMS_APP::model()->update(
            'xxt_mpaccount', 
            array(
                'wx_jsapi_ticket'=>$ticket->ticket,
                'wx_jsapi_ticket_expire_at'=>time()+$ticket->expires_in
            ),
            "mpid='$this->mpid'"
        );

        return array(true, $ticket->ticket);
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
     *
     */
    public function getOAuthUser($code)
    {
        $mpa = TMS_APP::M('mp\mpaccount')->byId($this->mpid, "wx_appid,wx_appsecret");

        $cmd = "https://api.weixin.qq.com/sns/oauth2/access_token";
        $params["appid"] = $mpa->wx_appid;
        $params["secret"] = $mpa->wx_appsecret;
        $params["code"] = $code;
        $params["grant_type"] = "authorization_code";

        $rst = $this->httpGet($cmd, $params, false, false);

        if ($rst[0] === false)
            return $rst;

        $openid = $rst[1]->openid;

        return array(true, $openid);
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
     * 添加粉丝分组
     *
     * 同时在公众平台和本地添加
     */
    public function groupsCreate($group)
    {
        /**
         * 在公众平台上添加
         */
        $cmd = 'https://api.weixin.qq.com/cgi-bin/groups/create';
        $posted = json_encode(array('group'=>$group));
        $rst = $this->httpPost($cmd, $posted);

        return $rst;
    }
    /**
     * 更新粉丝分组的名称
     *
     * 同时修改公众平台的数据和本地数据
     */
    public function groupsUpdate($group)
    {
        $cmd = "https://api.weixin.qq.com/cgi-bin/groups/update";
        $posted = json_encode(array('group'=>$group));
        $rst = $this->httpPost($posted);

        return $rst;
    }
    /**
     * 删除粉丝分组
     */
    public function groupsDelete($group)
    {
        $group->name = urlencode($group->name);
        $posted = urldecode(json_encode(array('group'=>$group)));
        $cmd = "https://api.weixin.qq.com/cgi-bin/groups/delete";
        $rst = $this->httpPost($cmd, $posted);

        return $rst;
    }
    /**
     * 设置关注用户的分组
     */
    public function groupsMembersUpdate($openid, $groupid)
    {
        $cmd = "https://api.weixin.qq.com/cgi-bin/groups/members/update";
        $posted = json_encode(array("openid"=>$openid,"to_groupid"=>$groupid));
        $rst = $this->httpPost($cmd, $posted);

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
     * upload menu.
     */
    public function menuDelete()
    {
        $cmd = 'https://api.weixin.qq.com/cgi-bin/menu/delete';

        $rst = $this->httpGet($cmd);

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
    /**
     * 将消息上传到微信公众号平台
     */
    public function mediaUploadNews($message)
    {
        /**
         * 拼装消息
         */
        $uploaded = array();
        $articles = $message['news']['articles'];
        foreach ($articles as $a) {
            $body = str_replace(array("\r\n", "\n", "\r"), '', $a['body']);
            $uploaded[] = array(
                "thumb_media_id"=>$a['thumb_media_id'],
                "title"=>urlencode($a['title']),
                "content"=>urlencode(addslashes($body)),
                "digest"=>urlencode($a['description']),
                "show_cover_pic"=>"1"
            );
        }
        $uploaded = array('articles'=>$uploaded);
        $uploaded = urldecode(json_encode($uploaded)); 
        /**
         * 发送消息
         */
        $cmd = "https://api.weixin.qq.com/cgi-bin/media/uploadnews";

        $rst = $this->httpPost($cmd, $uploaded);
        if ($rst[0] === false)
            return $rst;

        $media_id = $rst[1]->media_id;

        return array(true, $media_id);
    }
    /**
     * 将图片上传到公众号平台
     *
     * $imageUrl
     * $imageType
     */
    public function mediaUpload($mediaUrl, $mediaType='image') 
    {
        $tmpfname = $this->fetchUrl($mediaUrl);
        $uploaded['media'] = "@$tmpfname";
        /**
         * upload image
         */
        $cmd = 'http://file.api.weixin.qq.com/cgi-bin/media/upload';
        $cmd .= "?type=$mediaType";

        $rst = $this->httpPost($cmd, $uploaded);
        if ($rst[0] === false)
            return $rst;

        $media_id = $rst[1]->media_id;

        return array(true, $media_id);
    }
    /**
     * 向用户群发消息
     */
    public function messageMassSendall($message) 
    {
        $cmd = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall';

        $rst = $this->httpPost($cmd, $message);
        if ($rst[0] === false)
            return $rst;

        $msgid = $rst[1]->msg_id;

        return array(true, $msgid);
    }
    /**
     * 发送客服消息
     *
     * $openid
     * $message
     */
    public function messageCustomSend($message, $openid)
    {
        $message['touser'] = $openid; 
        $cmd = 'https://api.weixin.qq.com/cgi-bin/message/custom/send';
        $posted = urldecode(json_encode($message)); 

        $rst = $this->httpPost($cmd, $posted); 

        return $rst;
    }
    /**
     * 发送模板消息
     */
    public function messageTemplateSend($message) 
    {
        $cmd = 'https://api.weixin.qq.com/cgi-bin/message/template/send';

        $posted = json_encode($message);

        $rst = $this->httpPost($cmd, $posted);

        return $rst;
    }
    /**
     * 创建一个二维码响应
     * 微信的永久二维码最大值100000
     */
    public function qrcodeCreate($scene_id, $oneOff=true, $expire=1800)
    {
        $cmd = 'https://api.weixin.qq.com/cgi-bin/qrcode/create';

        if ($oneOff)
            $posted = array(
                "action_name"=>"QR_SCENE",
                "action_info"=>array(
                    "expire_seconds"=>$expire,
                    "scene"=>array("scene_id"=>$scene_id)
                )
            );
        else
            $posted = array(
                "action_name"=>"QR_LIMIT_SCENE", 
                "action_info"=>array(
                    "scene"=>array("scene_id"=>$scene_id)
                )
            );

        $posted = json_encode($posted);
        $rst = $this->httpPost($cmd, $posted);
        if (false === $rst[0])
            return $rst;
            
        $ticket = $rst[1]->ticket;
        $pic = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=$ticket";
        
        $d = array(
            'scene_id' => $scene_id,
            'pic' => $pic
        );
        $oneOff && $d['expire_seconds'] = $expire;

        return array(true, (object)$d);
    }
}
