<?php
namespace mp;
/**
 * 定时推送事件
 */
class timer_model extends \TMS_MODEL {
    /**
     * 获得定义的转发接口
     */
    public function &byMpid($mpid, $enabled=null)
    {
        $q = array(
            '*',
            'xxt_timer_push',
            "mpid='$mpid'"
        );
        $enabled !== null && $q[2] .= " and enabled='$enabled'";
        
        !($timers = $this->query_objs_ss($q)) && $timers = array();

        return $timers;
    }
    /**
     * 获得定义的转发接口
     */
    public function &byId($id)
    {
        $q = array(
            '*',
            'xxt_timer_push',
            "id='$id'"
        );
        $timer = $this->query_obj_ss($q);

        return $timer;
    }
    /**
     * 获得当前时间段要执行的任务
     */
    public function byTime()
    {
        $tasks = array();
        
        return $tasks;
    }
}
