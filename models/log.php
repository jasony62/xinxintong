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
     *
     * $user 这里的openid并不一定是当前打开材料的用户，只的是从公众账号获取这个材料的用户。
     *      如果一个用户A向公众账号发消息，获得了打开素材的书签，然后将这个书签转发给了用户B，B打开了素材。
     *      那么vid对应的是B，user对应的是A。          
     */
    public function writeMatterReadLog($vid, $mpid, $matterId, $matterType, $matterTitle, $ooid, $shareby, $openid_agent, $client_ip) 
    {
        $current = time();
        $d = array(); 
        $d['vid'] = $vid;
        $d['ooid'] = $ooid;
        $d['read_at'] = $current;
        $d['mpid'] = $mpid;
        $d['matter_id'] = $matterId;
        $d['matter_type'] = $matterType;
        $d['matter_title'] = $matterTitle;
        $d['matter_shareby'] = $shareby;
        $d['user_agent'] = $openid_agent;
        $d['client_ip'] = $client_ip;

        $logid = $this->insert('xxt_log_matter_read', $d, true);

        // 日志汇总
        $this->writeUserActionLog($mpid, $vid, $ooid, $current, 'R', $logid);
        $this->writeMatterActionLog($mpid, $matterType, $matterId, $matterTitle, $current, 'R', $logid);

        return $logid;
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
    public function writeShareActionLog($shareid, $vid, $ooid, $shareto, $shareby, $mpid, $id, $type, $title, $openid_agent, $client_ip)
    {
        $mopenid = '';
        $mshareid = '';
        $current = time();

        $d = array();
        $d['shareid'] = $shareid;
        $d['vid'] = $vid;
        $d['ooid'] = $ooid;
        $d['share_at'] = $current;
        $d['share_to'] = $shareto;
        $d['mpid'] = $mpid;
        $d['matter_id'] = $id;
        $d['matter_type'] = $type;
        $d['matter_title'] = $title;
        $d['matter_shareby'] = $shareby;
        $d['user_agent'] = $openid_agent;
        $d['client_ip'] = $client_ip;

        $logid = $this->insert('xxt_log_matter_share', $d, true);

        // 日志汇总
        $this->writeUserActionLog($mpid, $vid, $ooid, $current, 'S'.$shareto, $logid);
        $this->writeMatterActionLog($mpid, $type, $id, $title, $current, 'S'.$shareto, $logid);

        return $logid;
    }
    /**
     * 用户行为汇总日志
     * 为了便于进行数据统计
     */
    private function writeUserActionLog($mpid, $vid, $openid, $action_at, $action_name, $original_logid)
    {
        $d = array();
        $d['mpid'] = $mpid;
        $d['vid'] = $vid;
        $d['openid'] = $openid;
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

        return true;
    }
    /**
     * 素材行为汇总日志
     * 为了便于进行数据统计
     */
    private function writeMatterActionLog($mpid, $matter_type, $matter_id, $title, $action_at, $action_name, $original_logid)
    {
        $d = array();
        $d['mpid'] = $mpid;
        $d['matter_type'] = $matter_type;
        $d['matter_id'] = $matter_id;
        $d['matter_title'] = $title;
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
