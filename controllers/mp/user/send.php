<?php
namespace mp\user;

require_once dirname(dirname(__FILE__)).'/mp_controller.php';

class send extends \mp\mp_controller {

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
        $mpa = $this->getMpaccount();
        if ($mpa->asparent === 'Y') {
            $params = array();
            $params['mpaccount'] = $mpa;
            \TPL::assign('params', $params);
            
            $this->view_action('/mp/user/send/parentmp');
        } else {
            $this->view_action('/mp/user/send');
        }
    }
}
