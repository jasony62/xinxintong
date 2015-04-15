<?php
/**
 *
 */
class xxt_base extends TMS_CONTROLLER {
    /**
     * 发起请求的来源
     */
    protected function getClientSrc() 
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/yixin/i', $user_agent))
            $src = 'yx';
        elseif (preg_match('/MicroMessenger/i', $user_agent))
            $src = 'wx';
        else
            $src = false;

        return $src;
    }
    /**
     *
     */
    protected function getMpaccount()
    {
        if (isset($_SESSION['mpaccount']))
            return $_SESSION['mpaccount'];
        else
            return TMS_APP::model('mp\mpaccount')->byId($this->mpid,'name,mpid,mpsrc,asparent,parent_mpid,yx_joined,wx_joined,qy_joined');
    }
    /**
     * 获得当前访客的ID
     *
     * 若cookie中已经有记录则返回cookie中记录的内容
     * 否则生成一条新的访客记录并返回ID
     */
    protected function getVisitorId($mpid) 
    {
        $cname = G_COOKIE_PREFIX."_{$mpid}_visitor";

        if (isset($_COOKIE[$cname])) {
            return $_COOKIE[$cname];
        } else {
            $time = time();
            $vid = md5($mpid.$time.uniqid());
            $i['mpid'] = $mpid; 
            $i['vid'] = $vid; 
            $i['create_at'] = $time; 

            $this->model()->insert('xxt_visitor', $i);

            $this->mySetCookie("_{$mpid}_visitor", $vid, time()+(86400*5));

            return $vid;
        }
    }
    /**
     * 获得与公众平台进行交互的token
     */
    protected function access_token($mpid, $src, $newAccessToken=false) 
    {
        /**
         * 不重用之前保留的access_token
         */
        switch ($src) {
        case 'yx':
        case 'wx':
            $whichToken = "{$src}_appid,{$src}_appsecret,{$src}_token,{$src}_token_expire_at";
            break;
        case 'qy':
            $whichToken = "qy_corpid,qy_secret,qy_token,qy_token_expire_at";
        }
        if ($newAccessToken === false) {
            if (isset($this->{$src.'_token'}) && time()<$this->{$src.'_token'}['expire_at']-60) {
                /**
                 * 在同一次请求中可以重用
                 */
                return array(true, $this->{$src.'_token'}['value']);
            }
            /**
             * 从数据库中获取之前保留的token
             */
            $app = $this->model('mp\mpaccount')->byId(
                $mpid,
                $whichToken
            );
            if (!empty($app->{$src.'_token'}) && time() < (int)$app->{$src.'_token_expire_at'}-60) {
                /**
                 * 数据库中保存的token可用
                 */
                $this->{$src.'_token'} = array(
                    'value'=>$app->{$src.'_token'},
                    'expire_at'=>$app->{$src.'_token_expire_at'}
                );
                return array(true, $app->{$src.'_token'});
            }
        } else {
            /**
             * 从数据库中获取之前保留的token
             */
            $app = $this->model('mp\mpaccount')->byId(
                $mpid,
                $whichToken
            );
        }
        /**
         * 重新获取token
         */
        if ($src === 'yx') {
            $url_token = "https://api.yixin.im/cgi-bin/token";
            $url_token .= "?grant_type=client_credential"; 
            $url_token .= "&appid=$app->yx_appid&secret=$app->yx_appsecret";
        } else if ($src === 'wx') {
            $url_token = "https://api.weixin.qq.com/cgi-bin/token";
            $url_token .= "?grant_type=client_credential"; 
            $url_token .= "&appid=$app->wx_appid&secret=$app->wx_appsecret"; 
        } else if ($src === 'qy') {
            $url_token = "https://qyapi.weixin.qq.com/cgi-bin/gettoken";
            $url_token .= "?corpid=$app->qy_corpid&corpsecret=$app->qy_secret";
        }
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
        $u["{$src}_token"] = $token->access_token;
        if ($src === 'qy')
            $u["{$src}_token_expire_at"] = 7200 + time();
        else
            $u["{$src}_token_expire_at"] = (int)$token->expires_in + time();

        $this->model()->update('xxt_mpaccount', $u, "mpid='$mpid'");

        $this->{$src.'_token'} = array(
            'value'=>$u["{$src}_token"],
            'expire_at'=>$u["{$src}_token_expire_at"]
        );

        return array(true, $token->access_token);
    }
    /**
     * 从易信公众号获取信息
     *
     * 需要提供token的请求
     */
    protected function getFromMp($mpid, $src, $cmd, $params=null, $newAccessToken=false)
    {
        $token = $this->access_token($mpid, $src, $newAccessToken);
        if ($token[0] === false)
            return $token;

        $url = $cmd;
        $url .= "?access_token={$token[1]}";
        !empty($params) && $url .= '&'.http_build_query($params);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
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

        $result = json_decode($response);
        if (isset($result->errcode)) {
            if ($result->errcode == 40014) {
                /**
                 * 不合法的access_token
                 */
                return $this->getFromMp($mpid, $src, $cmd, $params, true);
            }
            if ($result->errcode !== 0)
                return array(false, $result->errmsg."($result->errcode)");
        }

        return array(true, $result);
    }
    /**
     * 提交信息到公众号平台
     */
    protected function postToMp($mpid, $src, $cmd, $posted, $newAccessToken=false)
    {
        $token = $this->access_token($mpid, $src, $newAccessToken);
        if ($token[0] === false)
            return $token;

        $url = $cmd;
        $url .= "?access_token=".$token[1];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $posted);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        if (false === ($response = curl_exec($ch))) {
            $err = curl_error($ch);
            curl_close($ch);
            return array(false, $err);
        }
        curl_close($ch);

        $rst = json_decode($response);
        if (isset($rst->errcode)) {
            if ($rst->errcode == 40014) {
                /**
                 * 不合法的access_token
                 */
                return $this->postToMp($mpid, $src, $cmd, $posted, true);
            }
            if ($rst->errcode !== 0)
                return array(false, $rst->errmsg."($rst->errcode)");
        }

        return array(true, $rst);
    }
    /**
     * 获得一个指定粉丝的信息
     */
    protected function getFanInfo($mpid, $src, $openid, $getGroup=false)
    {
        if ($src === 'qy') {
            $cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/get";
            $params = array(
                'userid'=>$openid
            );
            $result = $this->getFromMp($mpid, $src, $cmd, $params);
        } else {
            if ($src == 'yx')
                $cmd = 'https://api.yixin.im/cgi-bin/user/info';
            else 
                $cmd = 'https://api.weixin.qq.com/cgi-bin/user/info';

            $params = array('openid'=>$openid);

            $result = $this->getFromMp($mpid, $src, $cmd, $params);

            if ($getGroup && $result[0]) {
                /**
                 * 获得粉丝的分组信息
                 */
                if ($src == 'yx')
                    $cmd = 'https://api.yixin.im/cgi-bin/groups/getid';
                else 
                    $cmd = 'https://api.weixin.qq.com/cgi-bin/groups/getid';
                $posted = json_encode(array("openid"=>$openid));
                $group = $this->postToMp($mpid, $src, $cmd, $posted);
                if ($group[0]) {
                    $gid = $group[1]->groupid;
                    $result[1]->groupid = $group[1]->groupid;
                }
            }
        }
        return $result;
    }
    /**
     * 创建一个企业号的粉丝用户
     * 同步的创建会员用户
     *
     * $user 企业号用户的详细信息
     */
    protected function createQyFan($mpid, $user, $authid, $timestamp=null, $mapDeptR2L=null)
    {
        if (empty($authid))
            return '未设置内置认证接口，无法同步通讯录';

        $create_at = time();
        empty($timestamp) && $timestamp = $create_at;
        $mid = md5(uniqid().$create_at); //member's id
        $fid = $this->model('user/fans')->calcId($mpid, 'qy', $user->userid);

        $aMember = array();
        $aMember['mid'] = $mid;
        $aMember['fid'] = $fid;
        $aMember['mpid'] = $mpid;
        $aMember['create_at'] = $create_at;
        $aMember['sync_at'] = $timestamp;
        $aMember['ooid'] = $user->userid;
        $aMember['osrc'] = 'qy';
        $aMember['authapi_id'] = $authid;
        $aMember['authed_identity'] = $user->userid;
        $aMember['name'] = $user->name;
        isset($user->mobile) && $aMember['mobile'] = $user->mobile;
        isset($user->email) && $aMember['email'] = $user->email;
        isset($user->weixinid) && $aMember['weixinid'] = $user->weixinid;
        $extattr = array();
        if (isset($user->extattr) && !empty($user->extattr->attrs)) {
            foreach ($user->extattr->attrs as $ea)
                $extattr[urlencode($ea->name)] = urlencode($ea->value);
        }
        /**
         * 处理岗位信息
         */
        if (!empty($user->position))
            $extattr['position'] = urlencode($user->position);
        $aMember['extattr'] = urldecode(json_encode($extattr));
        /**
         * 建立成员和部门之间的关系
         */
        $udepts = array();
        foreach ($user->department as $ud) {
            if (empty($mapDeptR2L)) {
                $q = array(
                    'fullpath',
                    'xxt_member_department',
                    "mpid='$mpid' and extattr like '%\"id\":$ud,%'"
                );
                $fullpath = $this->model()->query_val_ss($q);
                $udepts[] = explode(',', $fullpath);
            } else    
                isset($mapDeptR2L[$ud]) && $udepts[] = explode(',', $mapDeptR2L[$ud]['path']);
        }

        $aMember['depts'] = json_encode($udepts);

        $this->model()->insert('xxt_member', $aMember, false);
        /**
         * 为了兼容服务号和订阅号的操作，生成和成员用户对应的粉丝用户
         */
        $fan = array();
        $fan['fid'] = $fid; 
        $fan['mpid'] = $mpid; 
        $fan['src'] = 'qy'; 
        $fan['openid'] = $user->userid; 
        $fan['nickname'] = $user->name; 
        isset($user->avatar) && $fan['headimgurl'] = $user->avatar; 
        $user->status == 1 && $fan['subscribe_at'] = $create_at;
        $this->model()->insert('xxt_fans', $fan, false);

        return true;
    }
    /**
     * 更新企业号用户信息
     */
    protected function updateQyFan($mpid, $fid, $user, $authid, $timestamp=null, $mapDeptR2L=null)
    {
        empty($timestamp) && $timestamp = time();

        $aMember = array();
        $aMember['sync_at'] = $timestamp;
        $aMember['name'] = $user->name;
        $aMember['authapi_id'] = $authid;
        $aMember['authed_identity'] = $user->userid;
        isset($user->mobile) && $aMember['mobile'] = $user->mobile;
        isset($user->email) && $aMember['email'] = $user->email;
        $extattr = array();
        if (isset($user->extattr) && !empty($user->extattr->attrs)) {
            foreach ($user->extattr->attrs as $ea)
                $extattr[urlencode($ea->name)] = urlencode($ea->value);
        }
        $aMember['tags'] = ''; // 先将成员的标签清空，标签同步的阶段会重新更新
        /**
         * 处理岗位信息
         */
        if (!empty($user->position))
            $extattr['position'] = urlencode($user->position);
        $aMember['extattr'] = urldecode(json_encode($extattr));
        /**
         * 建立成员和部门之间的关系
         */
        $udepts = array();
        foreach ($user->department as $ud) {
            if (empty($mapDeptR2L)) {
                $q = array(
                    'fullpath',
                    'xxt_member_department',
                    "mpid='$mpid' and extattr like '%\"id\":$ud,%'"
                );
                $fullpath = $this->model()->query_val_ss($q);
                $udepts[] = explode(',', $fullpath);
            } else    
                isset($mapDeptR2L[$ud]) && $udepts[] = explode(',', $mapDeptR2L[$ud]['path']);
        }

        $aMember['depts'] = json_encode($udepts);
        $this->model()->update(
            'xxt_member', 
            $aMember, 
            "mpid='$mpid' and ooid='$user->userid' and osrc='qy'"
        );
        /**
         * 成员用户对应的粉丝用户
         */
        if ($old = $this->model('user/fans')->byId($fid)) {
            $fan = array();
            $fan['nickname'] = $user->name;
            isset($user->avatar) && $fan['headimgurl'] = $user->avatar; 
            if ($user->status == 1 && $old->subscribe_at == 0)
                $fan['subscribe_at'] = $timestamp;
            else if ($user->status == 1 && $old->unsubscribe_at != 0)
                $fan['unsubscribe_at'] = 0;
            else if ($user->status == 4 && $old->unsubscribe_at == 0)
                $fan['unsubscribe_at'] = $timestamp;
            $this->model()->update(
                'xxt_fans', 
                $fan, 
                "mpid='$mpid' and fid='$fid'"
            );
        } else {
            $fan = array();
            $fan['fid'] = $fid; 
            $fan['mpid'] = $mpid; 
            $fan['src'] = 'qy'; 
            $fan['openid'] = $user->userid; 
            $fan['nickname'] = $user->name; 
            isset($user->avatar) && $fan['headimgurl'] = $user->avatar; 
            $user->status == 1 && $fan['subscribe_at'] = $timestamp;
            $this->model()->insert('xxt_fans', $fan, false);
        }

        return true;
    }
    /**
     * 发送客服消息
     *
     * $mpid
     * $src
     * $openid
     * $message
     */
    public function send_to_user($mpid, $src, $openid, $message)
    {
        /**
         * get access token.
         */
        $token = $this->access_token($mpid, $src);
        if ($token[0] === false)
            return $token[1];
        /**
         * send message.
         */
        $message['touser'] = $openid; 

        switch ($src) {
        case 'yx':
            $url_send = 'https://api.yixin.im/cgi-bin/message/custom/send';
            break;
        case 'wx':
            $url_send = 'https://api.weixin.qq.com/cgi-bin/message/custom/send';
            break;
        case 'qy':
            $url_send = 'https://qyapi.weixin.qq.com/cgi-bin/message/send';
            $mpa = $this->model('mp\mpaccount')->byId($mpid, 'qy_agentid');
            $message['agentid'] = $mpa->qy_agentid;
            break;
        default:
            return 'invalid parameter';
        }

        $url_send .= "?access_token={$token[1]}";

        $sMessage = urldecode(json_encode($message)); 
        $ch = curl_init($url_send);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sMessage);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        $response = curl_exec($ch);
        curl_close($ch);

        $ret = json_decode($response);
        if (isset($ret->errcode) && $ret->errcode != 0)
            return $ret->errmsg;

        return true;
    }
    /**
     * 向企业号用户发送消息
     *
     * $mpid
     * $message
     */
    public function send_to_qyuser($mpid, $message)
    {
        /**
         * get access token.
         */
        $token = $this->access_token($mpid, 'qy');
        if ($token[0] === false)
            return $token[1];
        /**
         * send message.
         */
        $url_send = 'https://qyapi.weixin.qq.com/cgi-bin/message/send';
        $mpa = $this->model('mp\mpaccount')->byId($mpid, 'qy_agentid');
        $message['agentid'] = $mpa->qy_agentid;
        $url_send .= "?access_token={$token[1]}";

        $sMessage = urldecode(json_encode($message)); 
        $ch = curl_init($url_send);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sMessage);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        $response = curl_exec($ch);
        curl_close($ch);

        $ret = json_decode($response);
        if (isset($ret->errcode) && $ret->errcode != 0)
            return $ret->errmsg;

        return true;
    }
    /**
     * 通过易信点对点接口向用户发送消息
     * 
     * $mpid
     * $message
     * $openids
     */
    public function send_to_yxuser_byp2p($mpid, $message, $openids) 
    {
        is_string($openids) && $openids = array($openids);
        /**
         * 发送消息
         */
        $token = $this->access_token($mpid, 'yx');
        if ($token[0] === false) return $token[1];

        $url_send = 'https://api.yixin.im/cgi-bin/message/send';
        $url_send .= '?access_token='.$token[1];

        foreach ($openids as $openid) {
            $message['touser'] = $openid;

            $posted = urldecode(json_encode($message)); 
            $ch = curl_init($url_send);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $posted);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            $response = curl_exec($ch);
            curl_close($ch);

            $ret = json_decode($response);
            if ($ret->errcode != 0) $warning[] = $ret->errmsg;
        }

        return isset($warning) ? $warning : true;
    }
    /**
     * 发送给认证用户
     *
     * $mpaccount mpid or mpaccount object
     * $userSet
     * $message
     */
    public function send_to_member($mpaccount, $userSet, $matter) 
    {
        is_string($mpaccount) && $mpaccount = $this->model('mp\mpaccount')->byId($mpaccount,'mpid,mpsrc,qy_agentid');
        /**
         * 消息内容
         */
        $model = $this->model("matter/$matter->type"); 
        $message = $model->forCustomPush($mpaccount->mpid, $matter->id);
        /**
         * 发送给认证用户
         */
        if ($mpaccount->mpsrc === 'qy') {
            /**
             * 发送给企业号用户
             */
            $parties = array();
            $tags = array();
            $users = array();
            foreach ($userSet as $us) {
                switch ($us->idsrc) {
                case 'D':
                    $dept = $this->model('user/department')->byId($us->identity, 'extattr');
                    if (!empty($dept->extattr)) {
                        $dept->extattr = json_decode($dept->extattr);
                        $parties[] = $dept->extattr->id;
                    }
                    break;
                case 'T':
                    $tag = $this->model('user/tag')->byId($us->identity, 'extattr');
                    if (!empty($tag->extattr)) {
                        $tag->extattr = json_decode($tag->extattr);
                        $tags[] = $tag->extattr->tagid;
                    } 
                    break;
                case 'DT':
                    $deptAndTagIds = explode(',', $us->identity);
                    $deptid = $deptAndTagIds[0];
                    $tagids = array_slice($deptAndTagIds, 1);
                    $fans = $this->model('user/department')->getFansByTag($deptid, $tagids, 'openid');
                    foreach ($fans as $fan)
                        $users[] = $fan->openid;
                    break;
                case 'M':
                    $member = $this->model('user/member')->byId($us->identity, 'fid');
                    $fan = $this->model('user/fans')->byId($member->fid, 'openid');
                    $users[] = $fan->openid; 
                    break;
                }
            }
            if (empty($parties) && empty($tags) && empty($users))
                return array(false, '没有获得接收消息的用户');
            if (!empty($parties)) $message['toparty'] = implode('|', $parties);
            if (!empty($tags)) $message['totag'] = implode('|', $tags);
            if (!empty($users)) $message['touser'] = implode('|', $users);
            /**
             * 发送消息
             */
            if (true !== ($errmsg = $this->send_to_qyuser($mpaccount->mpid, $message)))
                return array(false, $errmsg);
        } else if ($mpaccount->mpsrc === 'yx') {
            /**
             * 发送给开通了点对点接口的易信用户
             */
            $rst = $this->getOpenid($userSet);
            if ($rst[0] === false)
                return $rst;

            $openids = $rst[1];
            if (true !== ($errmsg = $this->send_to_yxuser_byp2p($this->mpid, $message, $openids)))
                return array(false, $errmsg);
        }
    }
    /**
     *
     * $userSet
     */
    protected function getOpenid($userSet)
    {
        $openids = array();
        foreach ($userSet as $us) {
            switch ($us->idsrc) {
            case 'D':
                $fans = $this->model('user/department')->getFans($us->identity, 'openid');
                foreach ($fans as $fan)
                    $openids[] = $fan->openid;
                break;
            case 'T':
                $fans = $this->model('user/tag')->getFans($us->identity, 'openid');
                foreach ($fans as $fan)
                    $openids[] = $fan->openid;
                break;
            case 'DT':
                $deptAndTagIds = explode(',', $us->identity);
                $deptid = $deptAndTagIds[0];
                $tagids = array_slice($deptAndTagIds, 1);
                $fans = $this->model('user/department')->getFansByTag($deptid, $tagids, 'openid');
                foreach ($fans as $fan)
                    $openids[] = $fan->openid;
                break;
            case 'M':
                $mid = $us->identity;
                $member = $this->model('user/member')->byId($mid, 'fid');
                if (empty($member->fid)) return array(false, '无法获得当前用户的openid');
                $fan = $this->model('user/fans')->byId($member->fid, 'openid');
                $openids[] = $fan->openid;
                break;
            }
        }

        return array(true, $openids);
    }
    /**
     * 用内置的邮箱发送邮件
     *
     * $mpid
     * $subject string
     * $content string HTML格式
     * $to 收件人的邮箱
     */
    protected function send_email($mpid, $subject, $content, $to) 
    {
        $features = $this->model('mp\mpaccount')->getFeatures($mpid);
        if (!empty($features->admin_email) && !empty($features->admin_email_pwd) && !empty($features->admin_email_smtp)) {
            $smtp = $features->admin_email_smtp;
            $port = $features->admin_email_port;
            $email = $features->admin_email;
            $pwd = $this->model()->encrypt($features->admin_email_pwd, 'DECODE', $mpid);
        } else {
            /**
             * todo 是否考虑去掉？
             */
            $smtp = 'smtp.163.com';
            $port = 25;
            $email = 'xin_xin_tong@163.com';
            $pwd = 'p0o9i8u7';
        }

        if (defined('SAE_MYSQL_DB')) { // sae
            $mail = new SaeMail();
            if ($mail->setOpt(array(
                'from'=>$email,
                'to'=>$to,
                'subject'=>$subject,
                'content'=>$content,
                'content_type'=>'HTML',
                'smtp_host'=>$smtp,
                'smtp_port'=>$port,
                'smtp_username'=>$email,
                'smtp_password'=>$pwd,
                'tls'=>false
            ))){
                if (!$mail->send())
                    return '邮件发送错误（'.$mail->errno().'）：'.$mail->errmsg();
            } else
                return '邮件参数设置错误（'.$mail->errno().'）:'.$mail->errmsg();
        } else {
            require_once(dirname(dirname(__FILE__)).'/lib/mail/SmtpMail.php');

            $smtp = new SmtpMail($smtp, $port, $email, $pwd);
            $smtp->send(
                $email,
                $to,
                $subject,
                $content
            );
        }
        return true;
    }
    /**
     * 获得微信JSSDK签名包
     *
     * $mpid
     */
    protected function getWxjssdkSignPackage($mpid, $url)
    {
        $mpa = $this->model('mp\mpaccount')->byId($mpid, 'mpsrc,wx_appid,qy_corpid');

        if ($mpa->mpsrc !== 'wx' && $mpa->mpsrc !== 'qy')
            return array(false, '当前账号不支持微信JS-SDK');

        $rst = $this->getWxJsApiTicket($mpid);
        if ($rst[0] === false)
            return $rst;

        $jsapiTicket = $rst[1];

        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId" => $mpa->mpsrc==='wx' ? $mpa->wx_appid : $mpa->qy_corpid,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );

        return array(true, $signPackage); 
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
    private function getWxJsApiTicket($mpid) 
    {
        $mpa = $this->model('mp\mpaccount')->byId($mpid, 'mpsrc,wx_jsapi_ticket,wx_jsapi_ticket_expire_at');

        if (!empty($mpa->wx_jsapi_ticket) && time()<$mpa->wx_jsapi_ticket_expire_at-60)
            return array(true, $mpa->wx_jsapi_ticket);

        if ($mpa->mpsrc === 'wx') {
            $cmd = "https://api.weixin.qq.com/cgi-bin/ticket/getticket";
            $params = array('type'=>'jsapi');
        } else if ($mpa->mpsrc === 'qy')
            $cmd = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket";
        $rst = $this->getFromMp($mpid, $mpa->mpsrc, $cmd, $params);

        if ($rst[0] === false)
            return array(false, $rst[1]);

        $rst = $rst[1];
        if ($rst->errcode !== 0)
            return array(false, $rst->errmsg."($rst->errcode)");

        $this->model()->update(
            'xxt_mpaccount', 
            array(
                'wx_jsapi_ticket'=>$rst->ticket,
                'wx_jsapi_ticket_expire_at'=>time()+$rst->expires_in
            ),
            "mpid='$mpid'"
        );

        return array(true, $rst->ticket);
    }
    /**
     *
     */
    protected function outputError($err, $title='程序错误')
    {
        TPL::assign('title', $title);
        TPL::assign('body', $err);
        TPL::output('error');
        exit;
    }
    /**
     * 获得公众号的公共配置信息 
     *
     * $runningMpid 当前正在运行的公众号
     */
    public function getCommonSetting($runningMpid)
    {

        $q = array(
            'body_ele,body_css,can_article_remark', 
            'xxt_mpsetting', 
            "mpid='$runningMpid'"
        );
        $setting = $this->model()->query_obj_ss($q);

        $mp = $this->model('mp\mpaccount')->byId($runningMpid, 'parent_mpid');
        if (!empty($mp->parent_mpid)) {
            $q = array(
                'body_ele,body_css,can_article_remark', 
                'xxt_mpsetting', 
                "mpid='$mp->parent_mpid'"
            );
            $psetting = $this->model()->query_obj_ss($q);

            empty($setting->body_ele) && !empty($psetting->body_ele) && $setting->body_ele = $psetting->body_ele;
            empty($setting->body_css) && !empty($psetting->body_css) && $setting->body_css = $psetting->body_css;
            $setting->can_article_remark === 'N' && $psetting->can_article_remark === 'Y' && $setting->can_article_remark = 'Y';
        }

        return $setting;
    }
    /**
     * 将图片上传到公众号平台
     *
     * $mpsrc wx|yx
     * $token
     * $imageUrl
     */
    public function upload_pic_to_mp($mpsrc, $token, $imageUrl, $mpid=null) 
    {
        if (empty($token)) {
            $token = $this->access_token($mpid, $mpsrc); 
            if ($token[0] === false)
                return $token;
            $token = $token[1];
        }
        /**
         * download image
         */
        $ch = curl_init($imageUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (false === ($imageData = curl_exec($ch))) {
            $err = curl_error($ch);
            curl_close($ch);
            return array(false, $err);
        }
        curl_close($ch);
        /**
         * 临时文件
         */
        //$imageUrl = urldecode($imageUrl);
        //$imageType = explode('.', $imageUrl);
        //$imageType = $imageType[count($imageType)-1];
        //$imageType = preg_replace('/\?.*/', '', $imageType);
        $tmpfname = tempnam('','');
        //rename($tmpfname, "$tmpfname.$imageType");
        //$handle = fopen("$tmpfname.$imageType", "w");
        $handle = fopen($tmpfname, "w");
        fwrite($handle, $imageData);
        fclose($handle);
        /**
         * upload image
         */
        //$post_data['media'] = "@$tmpfname.$imageType";
        $post_data['media'] = "@$tmpfname";
        /**
         * upload image
         */
        switch ($mpsrc) {
        case 'wx':
            $cmd = 'http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=';
            break;
        case 'yx':
            $cmd = "https://api.yixin.im/cgi-bin/media/upload?access_token=";
            break;
        }
        $url_send = $cmd.$token.'&type=image';

        $ch = curl_init($url_send);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
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

        $rsp = json_decode($response);
        if (isset($rsp->errcode) && $rsp->errcode != 0) {
            return array(false, $rsp->errmsg);
        }

        $media_id = $rsp->media_id;

        return array(true, $media_id);
    }
}
