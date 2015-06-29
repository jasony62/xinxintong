<?php
namespace mp\app;

require_once dirname(dirname(__FILE__)).'/matter/matter_ctrl.php';
/**
 *
 */
class app_base extends \mp\matter\matter_ctrl {
	/**
     *
     */
    public function __construct()
    {
        parent::__construct();
        
        $mpa = $this->model('mp\mpaccount')->byId($this->mpid);
        
        $prights = $this->model('mp\permission')->hasMpRight(
            $this->mpid, 
            array('app_enroll', 'app_lottery', 'app_wall', 'app_addressbook', 'app_contribute'), 
            'read'
        );
        $entries = array();
        (true === $prights || $prights['app_enroll']['read_p'] === 'Y') && $entries[] = array('url'=>'/mp/app/enroll','title'=>'登记活动');
        (true === $prights || $prights['app_lottery']['read_p'] === 'Y') && $entries[] = array('url'=>'/mp/app/lottery','title'=>'抽奖活动');
        (true === $prights || $prights['app_wall']['read_p'] === 'Y') && $entries[] = array('url'=>'/mp/app/wall','title'=>'信息墙');
        (true === $prights || $prights['app_addressbook']['read_p'] === 'Y') && $entries[] = array('url'=>'/mp/app/addressbook','title'=>'通讯录');
        (true === $prights || $prights['app_contribute']['read_p'] === 'Y') && $entries[] = array('url'=>'/mp/app/contribute','title'=>'投稿');
        $entries[] = array('url'=>'/mp/app/merchant','title'=>'订购');
        
        \TPL::assign('app_view_entries', $entries);
    }
}
