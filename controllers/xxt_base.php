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
            $csrc = 'yx';
        elseif (preg_match('/MicroMessenger/i', $user_agent))
            $csrc = 'wx';
        else
            $csrc = false;

        return $csrc;
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
     * 获得一个指定粉丝的信息
     */
    protected function getFanInfo($mpid, $openid, $getGroup=false)
    {
        $mpa = $this->model('mp\mpaccount')->byId($mpid);
        $mpproxy = $this->model('mpproxy/'.$mpa->mpsrc, $mpid);

        if ($mpa->mpsrc === 'qy')
            $result = $mpproxy->userGet($openid);
        else
            $result = $mpproxy->userInfo($openid, $getGroup);

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
        $fid = $this->model('user/fans')->calcId($mpid, $user->userid);

        $aMember = array();
        $aMember['mid'] = $mid;
        $aMember['fid'] = $fid;
        $aMember['mpid'] = $mpid;
        $aMember['openid'] = $user->userid;
        $aMember['nickname'] = $user->name;
        $aMember['verified'] = 'Y';
        $aMember['create_at'] = $create_at;
        $aMember['sync_at'] = $timestamp;
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
            "mpid='$mpid' and openid='$user->userid'"
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
            $fan['openid'] = $user->userid; 
            $fan['nickname'] = $user->name; 
            isset($user->avatar) && $fan['headimgurl'] = $user->avatar; 
            $user->status == 1 && $fan['subscribe_at'] = $timestamp;
            $this->model()->insert('xxt_fans', $fan, false);
        }

        return true;
    }
    /**
     * 尽最大可能向用户发送消息
     *
     * $mpid
     * $openid
     * $message
     */
    public function send_to_user($mpid, $openid, $message)
    {
        $mpa = $this->model('mp\mpaccount')->getApis($mpid);
        $mpproxy = $this->model('mpproxy/'.$mpa->mpsrc, $mpid);

        switch ($mpa->mpsrc) {
        case 'yx':
            if ($mpa->mpsrc === 'yx' && $mpa->yx_p2p === 'Y') {
                $rst = $mpproxy->messageSend($message, array($openid));
            } else {
                $rst = $mpproxy->messageCustomSend($message, $openid);
            }
            break;
        case 'wx':
            $rst = $mpproxy->messageCustomSend($message, $openid);
            break;
        case 'qy':
            $message['touser'] = $openid; 
            $message['agentid'] = $mpa->qy_agentid;
            $rst = $mpproxy->messageSend($message, $openid);
            break;
        }

        return $rst;
    }
    /**
     * 向企业号用户发送消息
     *
     * $mpid
     * $message
     */
    public function send_to_qyuser($mpid, $message, $encoded=false)
    {
        $mpproxy = $this->model('mpproxy/qy', $mpid);

        $rst = $mpproxy->messageSend($message, $encoded);

        return $rst;
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
        $mpproxy = $this->model('mpproxy/yx', $mpid);

        $rst = $mpproxy->messageSend($message, $openids);

        return $rst;
    }
    /**
     * 通过微信
     */
    public function send_to_wxuser_by_preview($mpid, $message, $openid)
    {
        $mpproxy = $this->model('mpproxy/wx', $mpid);
        
        $rst = $mpproxy->messageMassPreview($message, $openid);

        return $rst;
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
        $model = $this->model('matter\\'.$matter->type); 
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
            $this->send_to_qyuser($mpaccount->mpid, $message);
        } else if ($mpaccount->mpsrc === 'yx') {
            /**
             * 发送给开通了点对点接口的易信用户
             */
            $rst = $this->getOpenid($userSet);
            if ($rst[0] === false)
                return $rst;

            $openids = $rst[1];
            $rst = $this->send_to_yxuser_byp2p($this->mpid, $message, $openids);
            if (false === $rst[0])
                return array(false, $rst[1]);
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
            $mail = new \SaeMail();
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

            $smtp = new \SmtpMail($smtp, $port, $email, $pwd);
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
    public function getMpSetting($runningMpid)
    {

        $q = array(
            'body_ele,body_css,can_article_remark,header_page_id,footer_page_id', 
            'xxt_mpsetting', 
            "mpid='$runningMpid'"
        );
        $setting = $this->model()->query_obj_ss($q);

        $mp = $this->model('mp\mpaccount')->byId($runningMpid, 'parent_mpid');
        if (!empty($mp->parent_mpid)) {
            $q = array(
                'body_ele,body_css,can_article_remark,header_page_id,footer_page_id', 
                'xxt_mpsetting', 
                "mpid='$mp->parent_mpid'"
            );
            $psetting = $this->model()->query_obj_ss($q);

            $setting->header_page_id === '0' && $psetting->header_page_id !== '0' && $setting->header_page_id = $psetting->header_page_id;
            $setting->footer_page_id === '0' && $psetting->footer_page_id !== '0' && $setting->footer_page_id = $psetting->footer_page_id;
            empty($setting->body_ele) && !empty($psetting->body_ele) && $setting->body_ele = $psetting->body_ele;
            empty($setting->body_css) && !empty($psetting->body_css) && $setting->body_css = $psetting->body_css;
            $setting->can_article_remark === 'N' && $psetting->can_article_remark === 'Y' && $setting->can_article_remark = 'Y';
        }
        
        if ($setting->header_page_id !== '0') {
            $setting->header_page = $this->model('code/page')->byId($setting->header_page_id);
        }
        if ($setting->footer_page_id) {
            $setting->footer_page = $this->model('code/page')->byId($setting->footer_page_id);
        }

        return $setting;
    }
}
