<?php
namespace mp\matter;

require_once dirname(__FILE__).'/matter_ctrl.php';

class main extends matter_ctrl {

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
        $this->view_action('/mp/matter/articles');
    }
}
