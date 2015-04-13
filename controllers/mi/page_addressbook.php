<?php
class page_addressbook extends matter_page_base {
    /**
     *
     */
    public function __construct($id, $openid, $src)
    {
        $q = array(
            "*,'B' type", 
            'xxt_address_book', 
            "id=$id"
        );
        $this->addressbook = TMS_APP::model()->query_obj_ss($q);
        parent::__construct($this->addressbook, $openid, $src);
    }
    /**
     *
     */
    public function output($runningMpid, $mid, $vid, $ctrl)
    {
        $mpid = $this->addressbook->mpid;
        $depts = $this->deptByAb($this->addressbook->id);

        TPL::assign('mpid', $mpid);
        TPL::assign('title', $this->addressbook->title);
        TPL::assign('depts', $depts);
        
        $ctrl->view_action('/matter/ab/index');
    }
    /**
     *
     */
    private function deptByAb($abid) 
    {
        $q = array(
            'id,name,pid',
            'xxt_ab_dept',
            "ab_id='$abid'"
        );
        $depts = TMS_APP::model()->query_objs_ss($q);

        return $depts;
    }
}
