<?php
class bbs extends TMS_CONTROLLER {

    public function __construct() 
    {
        if (!isset($_SESSION['mpid']) || !($mpid = $_SESSION['mpid'])) {
            die('not get valid mpid.');
        }
        $this->mpid = $mpid;
    }
    /**
     *
     */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';

        return $rule_action;
    }
    /**
     *
     */
    public function subjects_action() 
    {
        $subjects = $this->model('bbs')->subjects($this->mpid);
        return new ResponseData($subjects);
    }
    /**
     *
     */
    public function replies_action($sid) 
    {
        $replies = $this->model('bbs')->replies($this->mpid, $sid);
        return new ResponseData($replies);
    }
    /**
     *
     */
    public function removeSubject_action($sid)
    {
        $this->model('bbs')->removeSubject($sid);
        return new ResponseData('success');
    }
    /**
     *
     */
    public function removeReply_action($rid)
    {
        $this->model('bbs')->removeReply($rid);
        return new ResponseData('success');
    }
}
