<?php
namespace mp;

require_once dirname(__FILE__) . '/mp_controller.php';

class analyze extends mp_controller {

    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';
        return $rule_action;
    }
    /**
     * 用户行为统计数据
     */
    public function userActions_action($orderby, $startAt, $endAt, $page=1, $size=30) 
    {
        /**
         * 分页数据 
         */
        $q = array();
        $s = 'l.openid,f.nickname';
        $s .= ',sum(l.act_read) read_num';
        $s .= ',sum(l.act_share_friend) share_friend_num';
        $s .= ',sum(l.act_share_timeline) share_timeline_num';
        $q[] = $s;
        $q[] = 'xxt_log_user_action l left join xxt_fans f on l.mpid=f.mpid and l.openid=f.openid';
        $w = "l.mpid='$this->mpid'";
        $w .= " and l.action_at>=$startAt and l.action_at<=$endAt";
        $q[] = $w;
        $q2 = array(
            'g'=>'openid',
            'o'=>"act_$orderby",
            'l'=>array('o'=>($page-1)*$size, 's'=>$size)
        );
        $stat = $this->model()->query_objs_ss($q, $q2);
        /**
         * 总数
         */
        $q = array(
            'count(distinct openid)',
            'xxt_log_user_action',
            "mpid='$this->mpid' and action_at>=$startAt and action_at<=$endAt"
        );
        $cnt = $this->model()->query_val_ss($q);

        return new \ResponseData(array($stat, $cnt));
    }
    /**
     * 素材行为统计数据
     */
    public function matterActions_action($orderby, $startAt, $endAt, $page=1, $size=30) 
    {
        /**
         * 分页数据
         */
        $s = 'l.matter_type,l.matter_id';
        $s .= ',sum(l.act_read) read_num';
        $s .= ',sum(l.act_share_friend) share_friend_num';
        $s .= ',sum(l.act_share_timeline) share_timeline_num';
        $q[] = $s;
        $q[] = 'xxt_log_matter_action l';
        $w = "l.mpid='$this->mpid'";
        $w .= " and l.action_at>=$startAt and l.action_at<=$endAt";
        $q[] = $w;
        $q2 = array(
            'g'=>'matter_type,matter_id',
            'o'=>"act_$orderby",
            'l'=>array('o'=>($page-1)*$size, 's'=>$size)
        );

        $stat = $this->model()->query_objs_ss($q, $q2);
        /**
         * 总数
         */
        $q = array(
            'count(distinct matter_type,matter_id)',
            'xxt_log_matter_action',
            "mpid='$this->mpid' and action_at>=$startAt and action_at<=$endAt"
        );
        $cnt = $this->model()->query_val_ss($q);

        return new \ResponseData(array($stat, $cnt));
    }
    /**
     * 群发消息事件统计
     */
    public function massmsg_action()
    {
        $logs = $this->model('log')->massByMpid($this->mpid);
        
        return new \ResponseData($logs);
    }
}
