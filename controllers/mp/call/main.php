<?php
namespace mp\call;

require_once dirname(__FILE__).'/base.php';

class main extends call_base {

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
        $this->view_action('/mp/reply/text');
    }
}
