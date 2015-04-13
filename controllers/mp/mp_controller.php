<?php
require_once dirname(dirname(__FILE__)).'/xxt_base.php';

class mp_controller extends xxt_base {
    /**
     *
     */
    protected $mpid;
    /**
     *
     */
    private $yx_token;
    /**
     *
     */
    private $wx_token;
    /**
     *
     */
    public function __construct() 
    {
        if (isset($_GET['mpid']) && ($mpid = $_GET['mpid'])){
            $_SESSION['mpid'] = $mpid;
        }
        if (!isset($_SESSION['mpid']) || !($mpid = $_SESSION['mpid'])) {
            die('can not get valid mpid in sesssion.');
        } 
        $this->mpid = $mpid;
    }
    /**
     *
     */
    protected function getMpaccount()
    {
        return TMS_APP::M('mp\mpaccount')->byId($this->mpid,'name,mpid,mpsrc,asparent,parent_mpid,yx_joined,wx_joined,qy_joined');
    }
    /**
     * 获得父公众号的ID
     */
    protected function getParentMpid()
    {
        $q = array(
            'parent_mpid',
            'xxt_mpaccount',
            "mpid='$this->mpid'"
        );

        return $this->model()->query_val_ss($q);
    }
}
