<?php
require_once dirname(dirname(dirname(__FILE__))).'/lib/wxqy/WXBizMsgCrypt.php';
require_once dirname(__FILE__).'/usercall_class.php';
require_once dirname(dirname(dirname(__FILE__))).'/models/reply_class.php';
require_once dirname(dirname(__FILE__)).'/member_base.php';

class main extends member_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white'; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
        $rule_action['actions'][] = 'api';
        $rule_action['actions'][] = 'hello';

        return $rule_action;
    }
    /**
     * 接收来源于公众平台的请求
     *
     */
    public function api_action($mpid, $src) 
    {
        $method = $_SERVER['REQUEST_METHOD'];
        //$this->model('log')->log($mpid, $src, $method, '');
        switch ($method) {
        case 'GET':
            $this->model('log')->log($mpid, $src, 'get', 'join');
            if ($src === 'yx')
                $this->yx_join($mpid, $_GET['signature'], $_GET['timestamp'], $_GET['nonce'], $_GET['echostr']);
            else if ($src === 'wx')
                $this->wx_join($mpid, $_GET['signature'], $_GET['timestamp'], $_GET['nonce'], $_GET['echostr']);
            else if ($src === 'qy')
                $this->qy_join($mpid, $_GET['msg_signature'], $_GET['timestamp'], $_GET['nonce'], $_GET['echostr']);
            break;
        case 'POST':
            $data = file_get_contents("php://input");
            //$this->model('log')->log($mpid, $src, 'post', $data);
            if ($src === 'qy') {
                /**
                 * 需要对数据进行解密处理
                 */
                $app = $this->model('mp\mpaccount')->byId($mpid);
                $msg_signature = $_GET['msg_signature'];
                $timestamp = $_GET['timestamp'];
                $nonce = $_GET['nonce'];
                $sMsg = "";
                $wxcpt = new WXBizMsgCrypt($app->token, $app->qy_encodingaeskey, $app->qy_corpid);
                $errCode = $wxcpt->DecryptMsg($msg_signature, $timestamp, $nonce, $data, $sMsg);
                if ($errCode != 0) exit;
                $data = $sMsg;
                //$this->model('log')->log($mpid, 'qy', 'post', $data);
            }
            $call = new UserCall($src, $data);
            $this->handle($mpid, $call);
            break;
        default:
        }
    }
    /**
     * 加密/校验流程：
     * 1. 将token、timestamp、nonce三个参数进行字典序排序 
     * 2. 将三个参数字符串拼接成一个字符串进行sha1加密 
     * 3. 开发者获得加密后的字符串可与signature对比，标识该请求来源于易信 
     *
     * 若确认此次GET请求来自易信服务器，请原样返回echostr参数内容，则接入生效，否则接入失败。
     */
    private function yx_join($mpid, $signature, $timestamp, $nonce, $echostr) 
    {
        $app = $this->model('mp\mpaccount')->byId($mpid);
        $p = array($app->token, $timestamp, $nonce);
        asort($p);
        $s = implode('', $p);
        $ss = sha1($s);
        if ($ss === $signature) {
            /**
             * 断开连接
             */
            $this->model()->update(
                'xxt_mpaccount', 
                array('yx_joined'=>'N'), 
                "yx_appid='$app->yx_appid' and yx_appsecret='$app->yx_appsecret'"
            );
            /**
             * 确认建立连接
             */
            $this->model()->update('xxt_mpaccount', array('yx_joined'=>'Y'), "mpid='$mpid'");
            die($echostr);
        } else {
            die('failed.');
        }
    }
    /**
     * 加密/校验流程：
     * 1. 将token、timestamp、nonce三个参数进行字典序排序 
     * 2. 将三个参数字符串拼接成一个字符串进行sha1加密 
     * 3. 开发者获得加密后的字符串可与signature对比，标识该请求来源于易信 
     *
     * 若确认此次GET请求来自易信服务器，请原样返回echostr参数内容，则接入生效，否则接入失败。
     */
    private function wx_join($mpid, $signature, $timestamp, $nonce, $echostr) 
    {    
        $app = $this->model('mp\mpaccount')->byId($mpid);
        //$p = array($app->token, $timestamp, $nonce);
        //asort($p);
        //$ss = sha1($s);
        $tmpArr = array($app->token, $timestamp, $nonce);
        // use SORT_STRING rule
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr );
		$tmpStr = sha1($tmpStr);
        if ($tmpStr === $signature) {
        	 /**
             * 断开连接
             */
            $this->model()->update(
                'xxt_mpaccount', 
                array('wx_joined'=>'N'), 
                "wx_appid='$app->wx_appid' and wx_appsecret='$app->wx_appsecret'"
            );
            /**
             * 确认建立连接
             */
            $this->model()->update('xxt_mpaccount', array('wx_joined'=>'Y'), "mpid='$mpid'");
            header('Content-Type: text/html; charset=utf-8');
            die($echostr);
        } else {
            die('failed.');
        }
    }
    /**
     * 对接企业号
     */
    private function qy_join($mpid, $msg_signature, $timestamp, $nonce, $echostr) 
    {    
        $logger = $this->model('log');
        $logger->log($mpid, 'qy', 'get', 'qy_join');

        $app = $this->model('mp\mpaccount')->byId($mpid);

        $sEchoStr = '';
        $wxcpt = new WXBizMsgCrypt($app->token, $app->qy_encodingaeskey, $app->qy_corpid);
        $errCode = $wxcpt->VerifyURL($msg_signature, $timestamp, $nonce, $echostr, $sEchoStr, $logger);

        $logger->log($mpid, 'qy', 'get', "qy_join:errCode:$errCode");

        if ($errCode == 0) {
            /**
             * 确认建立连接
             */
            $this->model()->update(
                'xxt_mpaccount', 
                array('qy_joined'=>'Y'), 
                "mpid='$mpid'"
            );
            $logger->log($mpid, 'qy', 'get', "qy_join:reture:$sEchoStr");
            header('Content-Type: text/html; charset=utf-8');
            die($sEchoStr);
        } else
            die($errCode);
    }
    /**
     * 处理收到的消息
     *
     * 当普通易信用户向公众帐号发消息时，易信服务器将POST该消息到填写的URL上。
     * XML编码格式为UTF-8
     */
    private function handle($mpid, $call) 
    {
        /**
         * 记录消息日志
         */
        $msg = $call->to_array();
        $msg['mpid'] = $mpid;
        $this->model('log')->receive($msg);
        /**
         * 消息分流处理
         * 【信息墙】需要从现有信息处理流程中形成分支，分支中进行处理就可以了。
         * 如果分支进行了处理，可以通过返回值告知是否还需要进行处理
         */
        if ($this->fork($msg)) {
            /**
             * 分支活动负责处理
             */
            die('');
        } else {
            /**
             * 处理消息
             */
            switch ($msg['type']) {
            case 'text':
                $this->text_call($msg);
                break;
            case 'voice':
                $this->voice_call($msg);
                break;
            case 'event':
                $this->event_call($msg);
                break;
            case 'location':
                if ($reply = $this->model('reply')->other_call($mpid, 'location')) {
                    $cls = $reply->matter_type.'Reply';
                    $r = new $cls($msg, $reply->matter_id);
                    $r->exec();
                }
            }
            die('');
        }
    }
    /**
     * 消息分流处理
     */
    private function fork($msg) 
    {
        if ($fa = $this->currentForkActivity($msg)){
            /**
             * 由分支活动负责处理消息
             */
            $reply = $fa[1]->handle($fa[0], $msg, $this);
            if (is_string($reply)) {
                /**
                 * 返回分支活动的回复
                 */
                $tr = new TextReply($msg, $reply, false);
                $tr->exec();
            } else
                /**
                 * 只允许在一个活动中进行处理
                 */
                return $reply;
        } else
            /**
             * 没有进行处理
             */
            return false;
    }
    /**
     * 获得有效的分支活动
     *
     * 目前只有信息墙一种活动
     * 判断当前用户是否已经加入了活动，且仍然处于活动状态
     *
     * return array 0：活动的ID，1：活动实例
     *
     */
    private function currentForkActivity($msg)
    {
        $mpid = $msg['mpid'];
        $openid = $msg['from_user'];
        $src = $msg['src'];
        $activity = $this->model('activity/wall');

        if ($wid = $activity->joined($mpid, $openid, $src))
            return array($wid, $activity);
        else
            return false;
    }
    /**
     * 事件消息处理
     */
    private function event_call($data) 
    {
        //$this->model('log')->log($data['mpid'], $data['src'], 'event', json_encode($data));
        $e = json_decode($data['data']);
        $t = $e[0]; 
        $k = isset($e[1]) ? $e[1] : null; 

        switch ($t) {
        case 'subscribe':
            $this->subscribe_call($data, $k);
            break;
        case 'unsubscribe':
            $this->unsubscribe_call($data);
            break;
        case 'TEMPLATESENDJOBFINISH':
            $this->template_call($data, $k, $e[2]);
            break;
        case 'card_pass_check':
        case 'card_not_pass_check':
        case 'user_get_card':
        case 'user_del_card':
            $this->card_call($data);
            break;
        case 'scan':
        case 'SCAN':
            $this->qrcode_call($data, 'scan', $k);
            break;
        case 'click':
        case 'CLICK':
            $this->menu_call($data, $k);
            break;

        }
        die('');
    }
    /**
     * 新关注用户
     *
     * $data
     * $k 场景二维码的scene_id
     */
    private function subscribe_call($data, $scene_id=null) 
    {
        /**
         * 记录粉丝关注信息
         */
        $current = time();
        $mpid = $data['mpid'];
        $src = $data['src'];
        $openid = $data['from_user'];
        $fanpk = "mpid='$mpid' and src='$src' and openid='$openid'";
        $q = array('count(*)', 'xxt_fans', $fanpk);
        if ((int)$this->model()->query_val_ss($q) === 1) {
            /**
             * 重新关注
             */
            $this->model()->update(
                'xxt_fans', 
                array(
                    'subscribe_at'=>$current,
                    'unsubscribe_at'=>0,
                    'sync_at'=>$current
                ), 
                $fanpk
            );
        } else { 
            if ($src === 'qy') {
                $result = $this->getFanInfo($mpid, $src, $openid, false);
                if ($result[0] === false) {
                    $tr = new TextReply($data, $result[1], false);
                    $tr->exec();
                }
                $user = $result[1];
                $rst = $this->createQyFan($mpid, $user);
                if (is_string($rst)) {
                    $tr = new TextReply($call, $rst, false);
                    $tr->exec();
                }
            } else {
                /**
                 * new fan
                 */
                $fan = array(
                    'fid' => $this->model('user/fans')->calcId($mpid, $src, $openid),
                    'mpid' => $mpid,
                    'src' => $src,
                    'openid' => $openid,
                    'subscribe_at' => $current,
                    'sync_at' => $current
                );
                $this->model()->insert('xxt_fans', $fan, false);
            }
        }
        /**
         * 用户关注公众账号时是首次获得【touser】信息的机会
         * 需要更新和mpid的匹配关系
         */
        /**
         * 如果开通了高级接口，获得粉丝信息
         */
        if ($src !== 'qy') {
            $this->model()->update(
                'xxt_mpaccount', 
                array($src.'_mpid'=>$data['to_user']),
                "mpid='$mpid'"
            );
            $apis = $this->model('mp\mpaccount')->getApis($mpid);
            if ($apis && $apis->{$src.'_fans'} === 'Y') {
                /**
                 * 获取粉丝信息并更新
                 * todo 是否应该更新用户所属的分组？
                 */
                $fanInfo = $this->getFanInfo($mpid, $src, $openid);
                if ($fanInfo[0]) {
                    $u = array(
                        'nickname' => mysql_real_escape_string($fanInfo[1]->nickname),
                        'sex' => $fanInfo[1]->sex,
                        'city' => $fanInfo[1]->city
                    );
                    isset($fanInfo[1]->headimgurl) && $u['headimgurl'] = $fanInfo[1]->headimgurl;
                    isset($fanInfo[1]->icon) && $u['headimgurl'] = $fanInfo[1]->icon; // 易信认证号接口
                    isset($fanInfo[1]->province) && $u['province'] = $fanInfo[1]->province;
                    isset($fanInfo[1]->country) && $u['country'] = $fanInfo[1]->country;
                    $this->model()->update('xxt_fans', $u, $fanpk);
                }
            }
        }
        if (!empty($scene_id)) {
            /**
             * 通过扫描场景二维码关注
             * 将关注事件转换为场景二维码事件
             */
            $scene_id = substr($scene_id, strlen('qrscene_'));
            $scandata = $data;
            $scandata['data'] = json_encode(array('scan',$scene_id));
            if ($reply = $this->qrcode_call($scandata))
                is_object($reply) && $reply->exec();
        }
        /**
         * subscribe reply.
         */
        if ($reply = $this->model('reply')->other_call($mpid, 'subscribe')) {
            $cls = $reply->matter_type.'Reply';
            $r = new $cls($data, $reply->matter_id);
            $r->exec();
        }
    }
    /**
     * 取消关注
     */
    private function unsubscribe_call($data) 
    {
        $mpid = $data['mpid'];
        $src = $data['src'];
        $openid = $data['from_user'];
        $unsubscribe_at = time();
        $rst = $this->model()->update(
            'xxt_fans', 
            array('unsubscribe_at'=>$unsubscribe_at), 
            "mpid='$mpid' and src='$src' and openid='$openid'"
        );

        return $rst;
    }
    /**
     * 模板消息处理结果
     *
     * 仅限微信
     */
    private function template_call($data, $msgid, $status)
    {
        $mpid = $data['mpid'];
        $openid = $data['from_user'];
        /**
         * 更新数据状态
         */
        $rst = $this->model()->update(
            'xxt_tmplmsg_log',
            array('status'=>$status),
            "mpid='$mpid' and openid='$openid' and msgid='$msgid'"    
        );
        /**
         * 处理事件响应，选择消息转发事件，通知模板消息处理结果
         */
        if ($reply = $this->model('reply')->other_call($mpid, 'templatemsg')) {
            $cls = $reply->matter_type.'Reply';
            $r = new $cls($data, $reply->matter_id);
            $r->exec();
        }
    }
    /**
     * 卡卷事件
     */
    private function card_call($data)
    {
        $mpid = $data['mpid'];
        /**
         * 处理事件响应，消息转发事件
         */
        if ($reply = $this->model('reply')->other_call($mpid, 'cardevent')) {
            $cls = $reply->matter_type.'Reply';
            $r = new $cls($data, $reply->matter_id);
            $r->exec();
        }
    }
    /**
     * 文本消息响应
     * 如果没有定义如何响应，就调用缺省的响应内容
     */
    private function text_call($call) 
    {
        $mpid = $_GET['mpid'];
        $text = $call['data'];
        if ($reply = $this->model('reply')->text_call($mpid, $text)) {
            if ($reply->access_control === 'Y')
                $this->access_control($call, 'Text', $reply->keyword, $reply->authapis);
            $cls = $reply->matter_type . 'Reply';
            $r = new $cls($call, $reply->matter_id, $reply->keyword);
            $r->exec();
        } else
            $this->universal_call($call);
    }
    /**
     * 语音消息响应 
     */
    private function voice_call($call) 
    {
        $mpid = $_GET['mpid'];
        $data = $call['data'];
        if (!empty($data[2])) {
            $tr = new TextReply($call, $data[2], false);
            $tr->exec();
        } else {
            $tr = new TextReply($call, '未开通语音识别接口', false);
            $tr->exec();
        }
    }
    /**
     * menu call
     */
    private function menu_call($call, $k) 
    {
        $mpid = $_GET['mpid'];
        $src = $call['src'];
        $openid = $call['from_user'];
        if ($reply = $this->model('reply')->menu_call($mpid, $k)) {
            $this->model('log')->log($mpid, $src, 'menu', $k);
            if ($reply->access_control === 'Y')
                $this->access_control($call, 'Menu', $k, $reply->authapis);
            if (!empty($reply->matter_type)) {
                /**
                 * demo auto reply
                 * todo 临时代码
                 */
                if ($k === 'demoautoreply') {
                    /**
                     * 原始消息
                     */
                    $model = $this->model("matter/".lcfirst($reply->matter_type)); 
                    $message = $model->forCustomPush($mpid, $reply->matter_id);
                    $this->send_to_user($mpid, $src, $openid, $message);
                    /**
                     * 附加消息
                     */
                    $fan = $this->model('user/fans')->byOpenid($mpid, $openid, 'nickname');
                    $txt = $fan->nickname.'，送你100M[<a href="http://yxs.im/3etcE4">免费流量</a>]尽情听歌，[<a href="http://yxs.im/3etcE4">点此</a>]领取';
                    $message = array(
                        "msgtype"=>"text",
                        "text"=>array(
                            "content"=>$txt
                        )
                    );
                    $this->send_to_user($mpid, $src, $openid, $message);
                } else {
                    $cls = $reply->matter_type.'Reply';
                    $r = new $cls($call, $reply->matter_id);
                    $r->exec();
                }
            }
        } else {
            $this->universal_call($call);
        }
    }
    /**
     * 缺省回复
     */
    private function universal_call($data) 
    {
        $mpid = $data['mpid'];
        if ($reply = $this->model('reply')->other_call($mpid, 'universal')) {
            $cls = $reply->matter_type.'Reply';
            $r = new $cls($data, $reply->matter_id);
            $r->exec();
        }
    }
    /**
     * 扫描二维码事件
     *
     * 企业号目前不支持场景二维码
     * 由于目前易信的场景二维码客户端无法收到回复信息，因此改为推动客户消息替代
     */
    private function qrcode_call($call)
    {
        $mpid = $call['mpid'];
        $src = $call['src'];
        $openid = $call['from_user'];
        $data = json_decode($call['data']);
        if ($reply = $this->model('reply')->qrcode_call($mpid, $data[1])) {
            if ($reply->expire_at > 0) {
                /**
                 * 一次性二维码，用完后就删除
                 */
                $this->model()->delete('xxt_qrcode_call_reply', "id=$reply->id");
            }
            if ($src === 'wx') {
                $cls = ucfirst($reply->matter_type) . 'Reply';
                $r = new $cls($call, $reply->matter_id);
                $r->exec();
            } else {
                /**
                 * 易信，发送客户消息
                 */
                $setting = $this->model('mp\mpaccount')->getSetting($mpid, 'yx_custom_push');
                if ($setting->yx_custom_push === 'N')
                    return;
                switch (lcfirst($reply->matter_type)) {
                case 'activitysignin':
                    $cls = ucfirst($reply->matter_type) . 'Reply';
                    $r = new $cls($call, $reply->matter_id, false);
                    $r2 = $r->exec();
                    if (lcfirst($r2['matter_type']) === 'activity')
                        $message = $this->model("matter/activity")->forCustomPush($mpid, $r2['matter_id']);
                    else if (lcfirst($r2['matter_type']) === 'joinwall') {
                        $cls = ucfirst($r2['matter_type']) . 'Reply';
                        $r = new $cls($call, $r2['matter_id']);
                        $tip = $r->exec(false);
                        if (!empty($tip)) 
                            $message = array(
                                "msgtype"=>"text",
                                "text"=>array(
                                    "content"=>urlencode($tip)
                                )
                            );
                    } else
                        $message = $this->model("matter/".lcfirst($r2['matter_type']))->forCustomPush($mpid, $r2['matter_id']);
                    break;
                case 'joinwall':
                    $cls = ucfirst($reply->matter_type) . 'Reply';
                    $r = new $cls($call, $reply->matter_id);
                    $tip = $r->exec(false);
                    if (!empty($tip)) 
                        $message = array(
                            "msgtype"=>"text",
                            "text"=>array(
                                "content"=>urlencode($tip)
                            )
                        );
                    break;
                case 'activity':
                    $message = $this->model("matter/activity")->forCustomPush($mpid, $reply->matter_id);
                    break;
                case 'lottery':
                    $message = $this->model("activity/lottery")->forCustomPush($mpid, $reply->matter_id);
                    break;
                default:
                    $message = $this->model("matter/$reply->matter_type")->forCustomPush($mpid, $reply->matter_id);
                }
                /**
                 * 发送消息
                 */
                if (isset($message)) $this->send_to_user($mpid, $src, $openid, $message);
            }
        }
    }
    /**
     * 访问控制设置
     * 
     * 检查当前的粉丝用户是否为已经通过认证的注册用户
     * 检查当前的粉丝用户是否在白名单中
     *
     * $call
     * $call_type [Menu|Text]
     * $keyword
     * $authapis
     */
    private function access_control($call, $call_type, $keyword, $authapis) 
    {
        /**
         * check bind data.
         * 获得当前粉丝用户的身份信息
         */
        $mpid = $call['mpid'];
        $src = $call['src'];
        $openid = $call['from_user'];
        $members = $this->getUserMembers($mpid, $src, $openid, $authapis);

        /**
         * 无法确认用户的身份，要求进行身份认证
         */
        if (empty($members)) $this->auth_reply($call, $authapis);

        /**
         * 检查用户是否通过了邮箱验证
         * 如果不需要进行邮箱验证，邮箱会被设置为已通过验证的状态
         * 如果同时拥有多个认证身份，只要有一个通过验证，就认为当前用户通过验证
         */
        $requireEmailVerified = true;
        foreach ($members as $member) {
            if ($member->email_verified === 'Y') {
                $requireEmailVerified = false;
                break;
            }
        }
        if ($requireEmailVerified) {
            /**
             * 提醒用户进行邮箱验证
             * 如果支持多个身份认证接口，应该允许用户自己选择用哪个身份认证
             */
            $tip = array();
            foreach ($members as $member) {
                $tip[] = $this->model('user/authapi')->getNotpassStatement(
                    $member->authapi_id, $mpid, $src, $openid
                ); 
            }
            $tip = implode("\n", $tip);
            $tr = new TextReply($call, $tip, false);
            $tr->exec();
        }

        /**
         * 是否在白名单中，只要有一个身份匹配就允许访问
         */
        $matched = false;
        foreach ($members as $member)
            $matched = $this->model('acl')->canAccessCall($mpid, $call_type, $keyword, $member, $authapis);

        /**
         * 不y允许访问，通知用户
         */
        if (!$matched) {
            $tip = array();
            foreach ($members as $member) {
                $tip[] = $this->model('user/authapi')->getAclStatement(
                    $member->authapi_id, $runningMpid, $src, $openid
                ); 
            }
            $tip = implode("\n", $tip);
            $tr = new TextReply($call, $tip, false);
            $tr->exec();
        }
    }
}
