<?php
class log_model extends TMS_MODEL {
    /**
     *
     */
    public function log($mpid, $method, $data)
    {
        $current = time();
        $i['mpid'] = $mpid;
        $i['method'] = $method;
        $i['create_at'] = $current;
        $i['data'] = $this->escape($data);

        $this->insert('xxt_log', $i, false);

        return true;
    }
    /**
     * 接收消息日志
     */
    public function receive($msg) 
    {
        $mpid = $msg['mpid'];  
        $openid = $msg['from_user'];

        $fan = TMS_APP::model('user/fans')->byOpenid($mpid, $openid, 'nickname');

        $createAt = $msg['create_at'];

        $r = array();
        $r['mpid'] = $mpid;  
        $r['msgid'] = $msg['msgid'];  
        $r['to_user'] = $msg['to_user'];  
        $r['openid'] = $openid;  
        $r['nickname'] = !empty($fan) ? $fan->nickname : '';  
        $r['create_at'] = $createAt;  
        $r['type'] = $msg['type'];
        if (is_array($msg['data'])) {
            $data = array();
            foreach ($msg['data'] as $d)
                $data[] = urlencode($d);
            $r['data'] = $this->escape(urldecode(json_encode($data)));  
        } else
            $r['data'] = $this->escape($msg['data']);  

        $this->insert('xxt_log_mpreceive', $r, false);

        return true;
    }
    /**
     * 记录所有发送给用户的消息
     */
    public function send($mpid, $openid, $groupid, $content, $matter)
    {
        $i['mpid'] = $mpid;
        $i['creater'] = TMS_CLIENT::get_client_uid();
        $i['create_at'] = time();
        !empty($openid) && $i['openid'] = $openid;
        !empty($groupid) && $i['groupid'] = $groupid;
        !empty($content) && $i['content'] = $this->escape($content);
        if (!empty($matter)) {
            $i['matter_id'] = $matter->id;
            $i['matter_type'] = $matter->type;
        }
        $this->insert('xxt_log_mpsend', $i, false);

        return true;
    }
    /**
     *
     */
    public function read()
    {
    }
    /**
     * 用户是否可以接收t推送消息
     */
    public function canReceivePush($mpid, $openid)
    {
        return true;
    }
    /**
     * 汇总各类日志，形成用户完整的踪迹
     */
    public function track($mpid, $openid, $page=1, $size=30)
    {
        $q = array(
            'creater,create_at,content,matter_id,matter_type',
            'xxt_log_mpsend',
            "mpid='$mpid' and openid='$openid'"
        );
        $q2 = array(
            'r'=>array('o'=>($page-1)*$size,'l'=>$size),
            'o'=>'create_at desc'
        );

        $sendlogs = $this->query_objs_ss($q, $q2);

        $q = array(
            'create_at,data content',
            'xxt_log_mpreceive',
            "mpid='$mpid' and openid='$openid' and type='text'"
        );
        $q2 = array(
            'r'=>array('o'=>($page-1)*$size,'l'=>$size),
            'o'=>'create_at desc'
        );

        $recelogs = $this->query_objs_ss($q, $q2);

        $logs = array_merge($sendlogs, $recelogs);

        /**
         * order by create_at
         */
        usort($logs, function($a, $b){
            return $b->create_at - $a->create_at; 
        });

        return $logs;
    }
    /**
     * 记录访问素材日志
     */
    public function writeMatterRead($mpid, $user, $matter, $client, $shareby) 
    {
        $current = time();
        $d = array(); 
        $d['mpid'] = $mpid;
        $d['vid'] = $user->vid;
        $d['openid'] = $user->openid;
        $d['nickname'] = $user->nickname;
        $d['read_at'] = $current;
        $d['matter_id'] = $matter->id;
        $d['matter_type'] = $matter->type;
        $d['matter_title'] = $matter->title;
        $d['matter_shareby'] = $shareby;
        $d['user_agent'] = $client->agent;
        $d['client_ip'] = $client->ip;

        $logid = $this->insert('xxt_log_matter_read', $d, true);

        // 日志汇总
        $this->writeUserAction($mpid, $user, $current, 'R', $logid);
        
        $this->writeMatterAction($mpid, $matter, $current, 'R', $logid);

        return $logid;
    }
    /**
     * 文章打开的次数
     * todo 应该用哪个openid，根据oauth是否开放来决定？
     */
    public function getMatterRead($type, $id, $page, $size)
    {
        $q = array(
            'l.openid,l.nickname,l.read_at',
            'xxt_log_matter_read l',
            "l.matter_type='$type' and l.matter_id='$id'"
        );
        /**
         * 分页数据
         */
        $q2 = array(
            'o' => 'l.read_at desc',
            'r' => array(
                'o' => (($page-1)*$size),
                'l' => $size
            )
        );

        $log = $this->query_objs_ss($q, $q2);

        return $log;
    }
    /**
     * 记录分享动作
     *
     * $vid  访客ID
     * $mpid 公众号ID，是当前用户
     * $matter_id 分享的素材ID 
     * $matter_type 分享的素材类型 
     * $ooid  谁进行的分享
     * $user_agent  谁进行的分享 
     * $client_ip  谁进行的分享
     * $share_at 什么时间做的分享
     * $share_to  分享给好友或朋友圈
     * $mshareid 素材的分享ID
     * 
     */
    public function writeShareAction($mpid, $shareid, $shareto, $shareby, $user, $matter, $client)
    {
        $mopenid = '';
        $mshareid = '';
        $current = time();

        $d = array();
        $d['mpid'] = $mpid;
        $d['shareid'] = $shareid;
        $d['share_at'] = $current;
        $d['share_to'] = $shareto;
        $d['vid'] = $user->vid;
        $d['openid'] = $user->openid;
        $d['nickname'] = $user->nickname;
        $d['matter_id'] = $matter->id;
        $d['matter_type'] = $matter->type;
        $d['matter_title'] = $matter->title;
        $d['matter_shareby'] = $shareby;
        $d['user_agent'] = $client->agent;
        $d['client_ip'] = $client->ip;

        $logid = $this->insert('xxt_log_matter_share', $d, true);

        // 日志汇总
        $this->writeUserAction($mpid, $user, $current, 'S'.$shareto, $logid);
        
        $this->writeMatterAction($mpid, $matter, $current, 'S'.$shareto, $logid);

        return $logid;
    }
    /**
     * 用户行为汇总日志
     * 为了便于进行数据统计
     */
    private function writeUserAction($mpid, $user, $action_at, $action_name, $original_logid)
    {
        $d = array();
        $d['mpid'] = $mpid;
        $d['vid'] = $user->vid;
        $d['openid'] = $user->openid;
        $d['nickname'] = $user->nickname;
        $d['action_at'] = $action_at;
        $d['original_logid'] = $original_logid;
        switch ($action_name){
        case 'R':
            $d['act_read'] = 1;
            break;
        case 'SF':
            $d['act_share_friend'] = 1;
            break;
        case 'ST':
            $d['act_share_timeline'] = 1;
            break;
        default:
            die('invalid parameter!');
        }
        $this->insert('xxt_log_user_action', $d, false);
        
        if (!empty($user->openid)) {
            switch ($action_name){
            case 'R':
                $this->update("update xxt_fans set read_num=read_num+1 where mpid='$mpid' and openid='$user->openid'");
                break;
            case 'SF':
                $this->update("update xxt_fans set share_friend_num=share_friend_num+1 where mpid='$mpid' and openid='$user->openid'");
                break;
            case 'ST':
                $this->update("update xxt_fans set share_timeline_num=share_timeline_num+1 where mpid='$mpid' and openid='$user->openid'");
                break;
            }    
        }

        return true;
    }
    /**
     * 素材行为汇总日志
     * 为了便于进行数据统计
     */
    private function writeMatterAction($mpid, $matter, $action_at, $action_name, $original_logid)
    {
        $d = array();
        $d['mpid'] = $mpid;
        $d['matter_type'] = $matter->type;
        $d['matter_id'] = $matter->id;
        $d['matter_title'] = $matter->title;
        $d['action_at'] = $action_at;
        $d['original_logid'] = $original_logid;
        switch ($action_name){
        case 'R':
            $d['act_read'] = 1;
            break;
        case 'SF':
            $d['act_share_friend'] = 1;
            break;
        case 'ST':
            $d['act_share_timeline'] = 1;
            break;
        default:
            die('invalid parameter!');
        }
        $this->insert('xxt_log_matter_action', $d, false);

        if (!empty($mpid)) {
            
            switch ($action_name){
            case 'R':
                $this->update("update xxt_log_mpa set read_inc=read_inc+1,read_sum=read_sum+1 where id='$logid'");
                break;
            case 'SF':
                $this->update("update xxt_log_mpa set sf_inc=sf_inc+1,sf_sum=sf_sum+1 where id='$logid'");
                break;
            case 'ST':
                $this->update("update xxt_log_mpa set st_inc=st_inc+1,st_sum=st_sum+1 where id='$logid'");
                break;
            }    
        }
        
        return true;
    }
    /**
     * 群发消息发送日志
     */
    public function mass($sender, $mpid, $matterId, $matterType, $message, $msgid, $result)
    {
        $log = array(
            'mpid' => $mpid,
            'matter_type' => $matterType,
            'matter_id' => $matterId,
            'sender' => $sender,
            'send_at' => time(),
            'message' => $this->escape(json_encode($message)),
            'result' => $result,
            'msgid' => $msgid
        );
        
        $this->insert('xxt_log_massmsg', $log, false);
        
        return true;
    }
    
    /**
     * 群发消息日志查询
     * 
     * $mpid 父账号或者子账号，父账号返回所有子账号的日志，子账号返回子账号的数据
     * $offset 相对于当前日期的偏移日期，以天为单位
     */
    public function massByMpid($mpid, $offset=0)
    {
        $modelMp = \TMS_APP::M('mp\mpaccount');
        
        $mpa = $modelMp->byId($mpid, 'mpid,mpsrc,asparent');
        
        if ($mpa->asparent === 'Y')
            $mpset = "exists(select 1 from xxt_mpaccount m where m.parent_mpid='$mpid' and l.mpid=m.mpid)";
        else
            $mpset = "mpid='$mpa->mpid'";
        
        $begin = mktime(0, 0, 0, date("m"), date("d") + $offset, date("Y"));
        $end = $begin + 86400;
        
        $q = array(
            'l.id,l.mpid,l.matter_id,l.matter_type,result',
            'xxt_log_massmsg l',
            "l.send_at>=$begin and l.send_at<$end and $mpset"
        );
        $q2 = array(
            'o' => 'mpid,send_at desc'   
        );
        
        $logs = $this->query_objs_ss($q, $q2);
        
        return $logs;
    }
}
