<?php
require_once dirname(__FILE__).'/matter_ctrl.php';

class inner extends mp_controller {

    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';
        return $rule_action;
    }
    /**
     *
     */
    public function index_action() 
    {
        $p = array(
            'id,title,name', 
            'xxt_inner'
        );
        $replies = $this->model()->query_objs_ss($p);
        return new ResponseData($replies);
    }
}
