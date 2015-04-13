<?php
require_once dirname(__FILE__).'/matter_ctrl.php';

class lottery extends mp_controller {

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
        $q = array(
            'lid id,title', 
            'xxt_lottery',
            "mpid='$this->mpid'"
        );

        $q2 = array('o'=>'create_at desc');

        $lots = $this->model()->query_objs_ss($q, $q2);

        return new ResponseData($lots);
    }
}
