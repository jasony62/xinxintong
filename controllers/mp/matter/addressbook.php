<?php
namespace mp\matter;

require_once dirname(__FILE__).'/matter_ctrl.php';
/**
 *
 */
class addressbook extends matter_ctrl {
    /**
     *
     */
    protected function getMatterType()
    {
        return 'addressbook';
    }
    /**
     *
     */
    public function index_action($abid=null)
    {
        if (empty($abid)) {
            $abs = $this->model('matter\addressbook')->byMpid($this->mpid);
            return new \ResponseData($abs);
        } else {
            $ab = $this->model('matter\addressbook')->byId($abid);
            /**
             * acl
             */
            $ab->acl = $this->model('acl')->byMatter($this->mpid, 'addressbook', $abid);

            return new \ResponseData($ab);
        }
    }
    /**
     *
     */
    public function get_action($abid=null)
    {
        return $this->index_action($abid);
    }
}
