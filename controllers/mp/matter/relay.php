<?php
require_once dirname(__FILE__).'/matter_ctrl.php';

class relay extends mp_controller {

    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';
        return $rule_action;
    }
    /**
     *
     * $src 转发接口的来源
     */
    public function index_action($src=null) 
    {
        $mpid = (!empty($src) && $src==='p') ? $this->getParentMpid() : $this->mpid;

        $q = array(
            'id,title,url', 
            'xxt_mprelay',
            "mpid='$mpid'"
        );
        $relays = $this->model()->query_objs_ss($q);

        return new ResponseData($relays);
    }
}
