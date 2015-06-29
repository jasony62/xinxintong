<?php
namespace app;

require_once dirname(dirname(__FILE__)).'/member_base.php';
/**
 * 讨论组 
 */
class addressbook extends \member_base {
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
    public function index_action($mpid, $id)
    {
        $this->view_action('/app/addressbook/index');
    }
    /**
     * 通讯录查询
     *
     * $mpid
     * $abid
     * $abbr
     * $page
     * $size
     */
    public function get_action($mpid, $abid, $abbr='', $deptid=null, $page=1, $size=20)
    {
        $model = $this->model('matter\addressbook');
        
        $rst = $model->searchPersons($mpid, $abid, $abbr, $deptid, $page, $size);

        return new \ResponseData($rst);
    }
    /**
     *
     */
    public function deptGet_action($mpid, $id) 
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
