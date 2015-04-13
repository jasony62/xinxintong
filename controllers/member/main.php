<?php
require_once dirname(dirname(__FILE__)).'/member_base.php';
/**
 * member
 */
class main extends member_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 获得当前用户的信息
     *
     * $mpid 必须是属于一个公众号的用户
     * $authid 必须指定用户是通过那个接口进行的身份认证
     *
     */
    public function index_action($mpid, $authid) 
    {
        $this->getVisitorId($mpid);

        $aAuthapis[] = $authid;

        $members = $this->authenticate($mpid, $aAuthapis);
        $mid = $members[0]->mid;

        $member = $this->model('user/member')->byId($mid);
        return new ResponseData($member);
    }
    /**
     * 进入选择认证接口页
     *
     * 如果被访问的页面支持多个认证接口，要求用户选择一种认证接口
     */
    public function authoptions_action($mpid, $authids, $src=null, $openid=null)
    {
        $params = "mpid=$mpid";
        if (!empty($src) && !empty($openid))
            $params .= "&src=$src&openid=$openid";

        $aAuthapis = array();
        $aAuthids = explode(',', $authids);
        foreach ($aAuthids as $authid) {
            $authapi = $this->model('user/authapi')->byId($authid, 'name,url');
            $authapi->url .= "?authid=$authid&$params";
            $aAuthapis[] = $authapi;
        }

        TPL::assign('authapis',$aAuthapis);

        $this->view_action('/member/authoptions');
    }
}
