<?php
namespace site\fe\matter\addressbook;

require_once dirname(__FILE__).'/../../../../member_base.php';
/**
 * 讨论组 
 */
class main extends \member_base {
    /**
     *
     */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     *
     */
    public function index_action($siteid, $id)
    {
        $this->view_action('/site/fe/matter/addressbook/index');
    }
    /**
     * 通讯录查询
     *
     * $siteid
     * $abid
     * $abbr
     * $page
     * $size
     */
    public function get_action($siteid, $abid, $abbr='', $deptid=null, $page=1, $size=20)
    {
        $model = $this->model('matter\addressbook');
        
        $rst = $model->searchPersons($siteid, $abid, $abbr, $deptid, $page, $size);

        return new \ResponseData($rst);
    }
    /**
     *
     */
    public function deptGet_action($siteid, $id) 
    {
        $q = array(
            'id,name,pid',
            'xxt_ab_dept',
            "ab_id='$id'"
        );
        
        $depts = $this->model()->query_objs_ss($q);

        return new \ResponseData($depts);
    }
}
