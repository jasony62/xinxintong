<?php
include_once dirname(__FILE__).'/xxt_base.php';
/**
 * member
 */
class member_base extends xxt_base {
    /**
     * 设置代表用户认真身份的cookie
     */
    protected function setCookie4Member($mpid, $authid, $mid) 
    {
        $authapi = $this->model('user/authapi')->byId($authid, 'validity');
        $key = $this->getCookieKey($mpid);
        $encoded = $this->model()->encrypt($mid, 'ENCODE', $key);
        $expireAt = $authapi->validity == 0 ? null : time()+(86400*(int)$authapi->validity);
        $this->mySetCookie("_{$mpid}_{$authid}_member", $encoded, $expireAt);
    }
    /**
     * 
     */
    protected function getCookieKey($mpid) 
    {
        $q = array('creater','xxt_mpaccount',"mpid='$mpid'");
        if (!($mpCreater = $this->model()->query_val_ss($q)))
            die('invalid parameters.');

        return md5($mpid.$mpCreater);
    }
    /**
     * 判断是否为注册用户的条件是
     *
     * 1、cookie中记录了mid
     * 2、mid在注册用户表中存在，且处于可用状态
     *
     * return $mid member's id
     */
    protected function getCookieMember($mpid, $aAuthapis=array()) 
    {
        empty($aAuthapis) && die('没有指定认证接口');

        $members = array();
        $cookiekey = $this->getCookieKey($mpid);
        foreach ($aAuthapis as $authid) {
            if ($encoded = $this->myGetCookie("_{$mpid}_{$authid}_member")) {
                if ($mid = $this->model()->encrypt($encoded,'DECODE',$cookiekey)){
                    /**
                     * 检查数据库中是否有匹配的记录
                     */
                    $q = array(
                        'mid,fid,email_verified,authapi_id,authed_identity,depts,tags', 
                        'xxt_member', 
                        "mpid='$mpid' and mid='$mid' and forbidden='N'"
                    );
                    if ($member = $this->model()->query_obj_ss($q))
                        $members[] =  $member;
                }
            }
        }
        return $members;
    } 
    /**
     * 用户身份认证和绑定
     * 
     * $mpid
     * $aAuthapis
     * $targetUrl
     * 成功后跳转回指定$targetUrl
     * 若url===false，说明不跳转，而是前端通知
     * $fan
     *
     * 如果没有提供用户的公众号身份信息，且公众号开通OAuth认证接口，那么就通过认证接口获取openid
     * 如何知道当前用户是哪来的呢？因为OAuth必须在微信或易信的客户端中打开，所以可以通过当前的浏览器判断是从哪里来的
     * if (preg_match('/yixin/i', $user_agent)) {} elseif (preg_match('/MicroMessenger/i', $user_agent)) {}
     *
     * 用户的实际身份是不变的，无论何时进行绑定都不应该变
     * 所以可能，用户提供了身份信息，但是并没有和公众号的身份绑定起来
     * 如果有些业务逻辑必须要求这两种身份之间进行绑定，再做绑定
     * 也就是说，身份的认证必须做，绑定不一定能做，但是允许以后再绑定
     *
     * 假如用户通过公众号发起了一个请求，但是openid并没有和真实身份进行绑定，那么就可以要求再次绑定
     *
     * 因为公众账号本身的业务逻辑并不需要认证过的真实身份。
     *
     * 假如我要通过公众号查找我对图文发表过的评论
     * 那么就要检查我的openid是否能够对应到mid
     * 结果发现找不到，就进行身份绑定
     * 结果发现已经有身份信息的cookie，就直接绑定
     * 没有cookie就重新认真
     *
     */
    protected function authenticate($runningMpid, $aAuthapis, $targetUrl=null, $fan=null) 
    {
        empty($aAuthapis) && die('aAuthapis is emtpy.');

        if (!empty($fan) && !empty($fan[0])) {
            /**
             * 优先根据通过OAuth获得的openid判断用户的身份
             */
            list($ooid) = $fan;
            $authids = implode(',', $aAuthapis);
            $q = array(
                'm.mid,m.fid,m.email_verified,m.authapi_id,m.authed_identity,m.depts,m.tags',
                'xxt_member m,xxt_fans f',
                "m.mpid='$runningMpid' and m.fid=f.fid and f.openid='$ooid' and m.forbidden='N' and m.authapi_id in($authids)"
            );
            $members = $this->model()->query_objs_ss($q);
        } else
            $members = $this->getCookieMember($runningMpid, $aAuthapis);
        /**
         * 获得用户身份信息
         */
        if (!empty($members)) return $members;
        /**
         * 如果不是注册用户，要求先进行认证
         */
        if (count($aAuthapis) === 1) {
            $authapi = $this->model('user/authapi')->byId($aAuthapis[0], 'authid,url');
            $authUrl = $authapi->url;
            $authUrl .= "?mpid=$runningMpid";
            !empty($ooid) && $authUrl .= "&openid=$ooid";
            $authUrl .= "&authid=".$aAuthapis[0];
        } else {
            /**
             * 让用户选择通过那个认证接口进行认证
             */
            $authUrl = '/rest/member/authoptions';
            $authUrl .= "?mpid=$runningMpid";
            !empty($ooid) && $authUrl .= "&openid=$ooid";
            $authUrl .= "&authids=".implode(',',$aAuthapis);
        }
        /**
         * 返回身份认证页
         */
        if ($targetUrl === false) {
            /**
             * 直接返回认证地址
             * todo angular无法自动执行初始化，所以只能返回URL，由前端加载页面
             */
            $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
            header($protocol.' 401 Unauthorized');
            die("$authUrl");       
        } else {
            /**
             * 跳转到认证接口
             */
            if (empty($targetUrl)) $targetUrl = $this->getRequestUrl();
            /**
             * 将跳转信息保存在cookie中
             */
            $targetUrl = $this->model()->encrypt($targetUrl, 'ENCODE', $runningMpid);
            $this->mySetCookie("_{$runningMpid}_mauth_t", $targetUrl, time()+300);
            $this->redirect($authUrl);
        }
    }
    /**
     * 访问控制设置
     * 
     * 检查当前用户是否为认证用户
     * 检查当前用户是否在白名单中
     *
     * 如果用户没有认证，跳转到认证页
     *
     */
    protected function accessControl($runningMpid, $objId, $authapis, $fan, &$obj, $targetUrl=null)
    {
        $aAuthapis = explode(',', $authapis);
        $members = $this->authenticate($runningMpid, $aAuthapis, $targetUrl, $fan);

        $passed = false;
        foreach ($members as $member) {
            if ($this->canAccessObj($runningMpid, $objId, $member, $authapis, $obj)) {
                /**
                 * 检查用户是否通过了邮箱验证
                 */
                $q = array(
                    'email_verified',
                    'xxt_member', 
                    "mpid='$runningMpid' and mid='$member->mid'"
                );
                if ('Y' !== $this->model()->query_val_ss($q)) {
                    $r = $this->model('user/authapi')->getNotpassStatement($member->authapi_id, $runningMpid); 
                    TPL::assign('title', '访问控制未通过');
                    TPL::assign('body', $r);
                    TPL::output('error');
                    exit;
                }
                $passed = true;
                break;
            }

        }
        if (!$passed) {
            $r = $this->model('user/authapi')->getAclStatement($member->authapi_id, $runningMpid); 
            TPL::assign('title', '访问控制未通过');
            TPL::assign('body', $r);
            TPL::output('error');
            exit;
        }

        return $member;
    }
    /**
     * 执行OAuth操作
     *
     * 会在cookie保留结果5分钟
     *
     * $mpid
     * $controller OAuth的回调地址
     * $state OAuth回调时携带的参数
     */
    protected function oauth($mpid, $state=null)
    {
        empty($mpid) && die('mpid is emtpy, cannot execute oauth.');
        /**
         * 只有通过易信，微信客户端发起才有效
         */
        $csrc = $this->getClientSrc();
        if ($csrc !== 'yx' && $csrc !== 'wx')
            return false;
        /**
         * 如果公众号开放了OAuth接口，通过OAuth获得openid
         */
        $ruri = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $app = $this->model('mp\mpaccount')->byId($mpid, 'mpsrc');

        switch ($app->mpsrc) {
        case 'qy':
            $mpproxy = $this->model('mpproxy/qy', $mpid);
            break;
        case 'wx':
            $fea = $this->model('mp\mpaccount')->getApis($mpid);
            if ($fea->wx_oauth === 'Y')
                $mpproxy = $this->model('mpproxy/wx', $mpid);
            break;
        case 'yx':
            $fea = $this->model('mp\mpaccount')->getApis($mpid);
            if ($fea->yx_oauth === 'Y')
                $mpproxy = $this->model('mpproxy/yx', $mpid);
            break;
        }
        if (isset($mpproxy)) {
            $oauthUrl = $mpproxy->oauthUrl($mpid, $ruri, $state);
            $this->redirect($oauthUrl);
        }

        return false;
    }
    /**
     * 通过OAuth接口获得用户信息
     *
     * $mpid
     * $code
     */
    protected function getOAuthUserByCode($mpid, $code)
    {
        $app = $this->model('mp\mpaccount')->byId($mpid, 'mpsrc');
        switch ($app->mpsrc) {
        case 'yx':
            $who = $this->getYxOAuthUser($mpid, $code);
            break;
        case 'wx':
            $who = $this->getWxOAuthUser($mpid, $code);
            break;
        case 'qy':
            $who = $this->getQyOAuthUser($mpid, $code);
            break;
        default:
            $who = null;
        }

        return $who;
    }
    /**
     * 在cookie中保存OAuth用户信息
     * $mpid
     * $openid
     * $src
     */
    protected function setCookieOAuthUser($mpid, $openid, $src='') 
    {
        $who = array($openid, $src);
        $encoded = $this->model()->encrypt(json_encode($who), 'ENCODE', $mpid);
        $this->mySetcookie("_{$mpid}_oauth", $encoded);

        return true;
    }
    /**
     * 返回当前的用户
     *
     * $mpid
     * $who
     */
    protected function getCookieOAuthUser($mpid)
    {
        if ($user = $this->myGetcookie("_{$mpid}_oauth"))
            $user = json_decode($this->model()->encrypt($user, 'DECODE', $mpid));
        else
            $user = array('', '');

        return $user;
    }
    /**
     * 通过OAuth获得当前用户的openid
     */
    protected function getWxOAuthUser($mpid, $code)
    {
        if ($this->myGetcookie("_{$mpid}_oauth"))
            return $this->getCookieOAuthUser($mpid);
        /**
         * 获得openid
         */
        $app = $this->model('mp\mpaccount')->byId($mpid, "wx_appid,wx_appsecret");

        $tokenUrl = "https://api.weixin.qq.com/sns/oauth2/access_token";
        $tokenUrl .= "?appid=$app->wx_appid";
        $tokenUrl .= "&secret=$app->wx_appsecret";
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
        $encoded = $this->model()->encrypt(json_encode($who), 'ENCODE', $mpid);
        $this->mySetcookie("_{$mpid}_oauth", $encoded);

        return $who;
    }
    /**
     *
     */
    protected function getYxOAuthUser($mpid, $code)
    {
        if ($this->myGetcookie("_{$mpid}_oauth"))
            return $this->getCookieOAuthUser($mpid);
        /**
         * 获得openid
         */
        $app = $this->model('mp\mpaccount')->byId($mpid, "yx_appid,yx_appsecret");

        $tokenUrl = " https://api.yixin.im/sns/oauth2/access_token";
        $tokenUrl .= "?appid=$app->yx_appid";
        $tokenUrl .= "&secret=$app->yx_appsecret";
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
        $who = array($openid,'yx');
        $encoded = $this->model()->encrypt(json_encode($who), 'ENCODE', $mpid);
        $this->mySetcookie("_{$mpid}_oauth", $encoded);

        return $who;
    }
    /**
     *
     */
    protected function getQyOAuthUser($mpid, $code)
    {
        /**
         * 换取userid
         */
        $token = $this->access_token($mpid, 'qy');
        if ($token[0] === false)
            $this->outputError($token[1], '网页授权失败');

        $app = $this->model('mp\mpaccount')->byId($mpid, "qy_agentid");
        $tokenUrl = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo";
        $tokenUrl .= "?access_token={$token[1]}";
        $tokenUrl .= "&code=$code";
        $tokenUrl .= "&agentid=$app->qy_agentid";

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        if (false === ($response = curl_exec($ch))) {
            $err = curl_error($ch);
            curl_close($ch);
            $this->outputError($err, '网页授权失败');
        }
        curl_close($ch);

        $token = json_decode($response);
        if (isset($token->errcode))
            $this->outputError($token->errmsg, '网页授权失败');

        $openid = $token->UserId;
        /**
         * 将openid保存在cookie，可用于进行用户身份绑定
         */
        $who = array($openid, 'qy');
        $encoded = $this->model()->encrypt(json_encode($who), 'ENCODE', $mpid);
        $this->mySetcookie("_{$mpid}_oauth", $encoded);

        return $who;
    }
    /**
     * 判断发起呼叫的用户是否为认证用户，如果是则返回用户的身份信息
     *
     * 一个粉丝用户可能有多个认证用户身份
     * 所以要知道是哪个通过认证接口认证的用户身份
     * 如果是企业号的用户可能就不再需要进行认证，因此有可能不指定authapis
     *
     * $mpid
     * $src
     * $openid
     * $authapis
     *
     */ 
    protected function getUserMembers($mpid, $src, $openid, $authapis) 
    {
        $q = array(
            'm.mid,m.email_verified,m.authapi_id,m.authed_identity,m.depts,m.tags',
            'xxt_member m,xxt_fans f',
            "f.mpid='$mpid' and m.forbidden='N' and f.src='$src' and f.openid='$openid' and f.fid=m.fid"
        );
        !empty($authapis) && $q[2] .= " and authapi_id in ($authapis)";

        $mids = $this->model()->query_objs_ss($q);

        return $mids;
    }
    /**
     * 提示用户进行身份认证
     *
     * $call 客户端发起的请求
     * 由于请求是由客户端直接发起的，因此其中的openid和用户直接关联，是可以信赖的信息
     *
     */
    protected function auth_reply($call, $authapis) 
    {
        $aAuthapis = explode(',', $authapis);
        $tip = array();
        foreach ($aAuthapis as $authid) {
            $tip[] = $this->model('user/authapi')->getEntryStatement(
                $authid,
                $call['mpid'],
                $call['src'],
                $call['from_user']
            );
        }
        $tip = implode("\n", $tip);
        $tr = new TextReply($call, $tip, false);
        $tr->exec();
    }
    /**
     *
     * 要求关注
     *
     * $runningMpid
     * $ooid
     * $osrc
     *
     */
    protected function askFollow($runningMpid, $ooid, $osrc)
    {
        $isfollow = $this->model('user/fans')->isFollow($runningMpid, $ooid, $osrc);

        if (!$isfollow) {
            $fea = $this->model('mp\mpaccount')->getFeatures($runningMpid);

            $mp = $this->model('mp\mpaccount')->byId($runningMpid, 'parent_mpid');
            if (!empty($mp->parent_mpid)) {
                $pfea = $this->model('mp\mpaccount')->getFeatures($mp->parent_mpid);
                empty($fea->follow_ele) && !empty($pfea->follow_ele) && $fea->follow_ele = $pfea->follow_ele;
                empty($fea->follow_css) && !empty($pfea->follow_css) && $fea->follow_css = $pfea->follow_css;
            }
            TPL::assign('follow_ele', $fea->follow_ele);
            TPL::assign('follow_css', $fea->follow_css);
            TPL::output('follow');
            exit;
        }

        return true;
    }
    /**
     * 微信jssdk包
     *
     * $mpid
     * $url
     */
    public function wxjssdksignpackage_action($mpid, $url)
    {
        $rst = $this->getWxjssdkSignPackage($mpid, urldecode($url));
        if ($rst[0] === false) {
            header('Content-Type: text/javascript');
            die("alert('{$rst[1]}');");
        }

        $signPackage = $rst[1];

        $js = "signPackage={appId:'{$signPackage['appId']}'";
        $js .= ",nonceStr:'{$signPackage['nonceStr']}'";
        $js .= ",timestamp:'{$signPackage['timestamp']}'";
        $js .= ",url:'{$signPackage['url']}'";
        $js .= ",signature:'{$signPackage['signature']}'}";
        //$js .= ",rawString:'{$signPackage['rawString']}'};";

        header('Content-Type: text/javascript');
        die($js);
    }
}
