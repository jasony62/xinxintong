<?php
class log_model extends TMS_MODEL {
    /**
     *
     */
    public function log($mpid, $src, $method, $data)
    {
        $current = time();
        $i['mpid'] = $mpid;
        $i['method'] = $method;
        $i['create_at'] = $current;
        $i['src'] = $src;
        $i['data'] = mysql_real_escape_string($data);

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
        $r['nickname'] = $fan->nickname;  
        $r['create_at'] = $createAt;  
        $r['type'] = $msg['type'];
        if (is_array($msg['data'])) {
            $data = array();
            foreach ($msg['data'] as $d)
                $data[] = urlencode($d);
            $r['data'] = mysql_real_escape_string(urldecode(json_encode($data)));  
        } else
            $r['data'] = mysql_real_escape_string($msg['data']);  

        $this->insert('xxt_mpreceive_log', $r, false);

        return true;
    }
    /**
     * 记录所有发送给用户的消息
     */
    public function send($mpid, $src, $openid, $groupid, $content, $matter)
    {
        $i['mpid'] = $mpid;
        $i['creater'] = TMS_CLIENT::get_client_uid();
        $i['create_at'] = time();
        !empty($openid) && $i['openid'] = $openid;
        !empty($groupid) && $i['groupid'] = $groupid;
        !empty($content) && $i['content'] = mysql_real_escape_string($content);
        if (!empty($matter)) {
            $i['matter_id'] = $matter->id;
            $i['matter_type'] = $matter->type;
        }
        $this->insert('xxt_mpsend_log', $i, false);

        return true;
    }
    /**
     *
     */
    public function read()
    {
    }
    /**
     * 用户是否可以接收客服消息
     */
    public function canReceiveCustomPush($mpid, $src, $openid)
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
            'xxt_mpsend_log',
            "mpid='$mpid' and openid='$openid'"
        );
        $q2 = array(
            'r'=>array('o'=>($page-1)*$size,'l'=>$size),
            'o'=>'create_at desc'
        );

        $sendlogs = $this->query_objs_ss($q, $q2);

        $q = array(
            'create_at,data content',
            'xxt_mpreceive_log',
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
    public function writeMatterReadLog($vid, $mpid, $id, $type, $ooid, $osrc, $shareby, $openid_agent, $client_ip) 
    {
        $current = time();
        $d = array(); 
        $d['vid'] = $vid;
        $d['osrc'] = $osrc;
        $d['ooid'] = $ooid;
        $d['read_at'] = $current;
        $d['mpid'] = $mpid;
        $d['matter_id'] = $id;
        $d['matter_type'] = $type;
        $d['matter_shareby'] = $shareby;
        $d['user_agent'] = $openid_agent;
        $d['client_ip'] = $client_ip;

        $id = $this->insert('xxt_matter_read_log', $d, true);

        // 日志汇总
        $this->writeUserActionLog($mpid, $vid, $osrc, $ooid, $current, 'R', $id);
        $this->writeMatterActionLog($mpid, $type, $id, $current, 'R', $id);

        return $id;
    }
    /**
     * 记录分享动作
     *
     * $vid  访客ID
     * $mpid 公众号ID，是当前用户
     * $matter_id 分享的素材ID 
     * $matter_type 分享的素材类型 
     * $ooid  谁进行的分享
     * $osrc  谁进行的分享
     * $user_agent  谁进行的分享 
     * $client_ip  谁进行的分享
     * $share_at 什么时间做的分享
     * $share_to  分享给好友或朋友圈
     * $mshareid 素材的分享ID
     * 
     */
    public function writeShareActionLog($shareid, $vid, $osrc, $ooid, $shareto, $shareby, $mpid, $id, $type, $openid_agent, $client_ip)
    {
        $mopenid = '';
        $mshareid = '';
        $current = time();

        $d = array();
        $d['shareid'] = $shareid;
        $d['vid'] = $vid;
        $d['osrc'] = $osrc;
        $d['ooid'] = $ooid;
        $d['share_at'] = $current;
        $d['share_to'] = $shareto;
        $d['mpid'] = $mpid;
        $d['matter_id'] = $id;
        $d['matter_type'] = $type;
        $d['matter_shareby'] = $shareby;
        $d['user_agent'] = $openid_agent;
        $d['client_ip'] = $client_ip;

        $id = $this->insert('xxt_shareaction_log', $d, true);

        // 日志汇总
        $this->writeUserActionLog($mpid, $vid, $osrc, $ooid, $current, 'S'.$shareto, $id);
        $this->writeMatterActionLog($mpid, $type, $id, $current, 'S'.$shareto, $id);

        return $id;
    }
    /**
     * 用户行为汇总日志
     * 为了便于进行数据统计
     */
    private function writeUserActionLog($mpid, $vid, $src, $openid, $action_at, $action_name, $original_logid)
    {
        $d = array();
        $d['mpid'] = $mpid;
        $d['vid'] = $vid;
        $d['src'] = $src;
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
        $this->insert('xxt_user_action_log', $d, false);

        return true;
    }
    /**
     * 素材行为汇总日志
     * 为了便于进行数据统计
     */
    private function writeMatterActionLog($mpid, $matter_type, $matter_id, $action_at, $action_name, $original_logid)
    {
        $d = array();
        $d['mpid'] = $mpid;
        $d['matter_type'] = $matter_type;
        $d['matter_id'] = $matter_id;
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
        $this->insert('xxt_matter_action_log', $d, false);

        return true;
    }
}
