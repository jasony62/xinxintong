<?php
namespace mp\user;

require_once dirname(dirname(__FILE__)).'/mp_controller.php';

class fansgroup extends \mp\mp_controller {

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
        $this->view_action('/mp/user/fansgroup');
    }
}
