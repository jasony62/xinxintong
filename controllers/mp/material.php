<?php
require_once dirname(__FILE__) . '/mp_controller.php';

class material extends mp_controller {

    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';
        return $rule_action;
    }
    /**
     *
     */
    public function tag_action($mpid, $resType) 
    {
        $tags = $this->model('tag')->get_tags($mpid, $resType);
        return new ResponseData($tags);
    }
    /**
     *
     */
    public function inner_action($mpid) 
    {
        $p = array('id,title,name', 'xxt_inner');
        $replies = $this->model()->query_objs_ss($p);
        return new ResponseData($replies);
    }
}
