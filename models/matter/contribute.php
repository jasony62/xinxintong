<?php
namespace matter;

require_once dirname(__FILE__).'/app_base.php';
/**
*
*/
class contribute_model extends app_base {
    /**
     *
     */
    protected function table()
    {
        return 'xxt_contribute';
    }
    /**
    *
    */
    public function getTypeName()
    {
        return 'contribute';
    }
    /**
    *
    */
    public function getEntryUrl($runningMpid, $id)
    {
        $url = "http://".$_SERVER['HTTP_HOST'];
        $url .= "/rest/app/contribute";
        $url .= "?mpid=$runningMpid&entry=contribute,".$id;

        return $url;
    }
}
