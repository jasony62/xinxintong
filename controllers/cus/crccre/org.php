<?php
namespace cus\crccre;

/**
 * 铁建地产组织机构
 */
class org extends \TMS_CONTROLLER {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'index';
        $rule_action['actions'][] = 'nodes';

        return $rule_action;
    }
    /**
     * 外部用户
     */
    public function index_action() 
    {
        $this->view_action('/cus/crccre/org');
    }
    /**
     *
     */
    public function nodes_action($pid=null)
    {
        $depts = $this->model('cus/org')->nodes($pid);

        return new \ResponseData($depts);
    }
}
