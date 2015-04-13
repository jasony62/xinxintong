<?php
require_once dirname(__FILE__).'/matter_ctrl.php';

class joinwall extends mp_controller {

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
            'wid id,title', 
            'xxt_wall',
            "mpid='$this->mpid'"
        );
        $walls = $this->model()->query_objs_ss($p);

        return new ResponseData($walls);
    }
}
