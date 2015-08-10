<?php
namespace mp;
/**
 * 推送素材任务
 */
class TaskPush {
    //
    private $mpid;
    //
    private $taksId;
    /**
     *
     */
    public function __construct($mpid, $taskId)
    {
        $this->id = $taskId;
        $this->mpid = $mpid;
    }
    /**
     *
     */
    public function __get($property_name)
    {
        if (isset($this->$property_name))
            return $this->$property_name;
        else
            return null;
    }
    /**
     * 执行任务
     */
    public function exec() 
    {
        return new \ResponseData('ok');
    }
}
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
    public function tasksByTime()
    {
        $q = array(
            '*',
            'xxt_timer_push',
            "enabled='Y'"
        );
        $schedules = $this->query_objs_ss($q);
        
        foreach ($schedules as $schedule) {
            $task = new TaskPush($schedule->mpid, $schedule->id);
            $tasks[] = $task;
        }
        
        return $tasks;
    }
}
