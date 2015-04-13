<?php
require_once dirname(__FILE__).'/matter_ctrl.php';

class activity extends mp_controller {

    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';
        return $rule_action;
    }
    /**
     *
     */
    public function index_action($src) 
    {
        /**
         * 登记活动来源 
         */
        $mpid = (!empty($src) && $src==='p') ? $this->getParentMpid() : $this->mpid;

        $q = array(
            'aid id,title', 
            'xxt_activity',
            "mpid='$mpid'"
        );
        $q2 = array('o'=>'create_at desc');

        $acts = $this->model()->query_objs_ss($q, $q2);

        return new ResponseData($acts);
    }
}
