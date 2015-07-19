<?php
namespace mp\matter;

require_once dirname(dirname(__FILE__)).'/mp_controller.php';

class tag extends \mp\mp_controller {

    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';
        $rule_action['actions'][] = 'index';
        return $rule_action;
    }
    /**
     *
     */
    public function index_action($resType) 
    {
        $tags = $this->model('tag')->get_tags($this->mpid, $resType);

        return new \ResponseData($tags);
    }
}
