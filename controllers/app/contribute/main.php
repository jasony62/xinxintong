<?php
namespace app\contribute;

require_once dirname(dirname(dirname(__FILE__))).'/member_base.php';
/**
 * 投稿活动 
 */
class main extends \member_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 获得当前用户的信息
     * $mpid
     * $entry
     */
    public function index_action($mpid, $code=null, $mocker=null) 
    {
        $openid = $this->doAuth($mpid, $code, $mocker);
        $this->afterOAuth($mpid, $openid);
    }
    /**
     *
     */
    public function afterOAuth($mpid, $openid=null) 
    {
        $myUrl = 'http://'.$_SERVER['HTTP_HOST']."/rest/app/contribute?mpid=$mpid";
        list($fid, $openid, $mid) = $this->getCurrentUserInfo($mpid, $myUrl);
        
        $authids = array();
        $authapis = $this->model('user/authapi')->byMpid($mpid, 'Y');
        foreach ($authapis as $aa)
            $authids[] = $aa->authid;
        $authids = implode(',', $authids);
        $member = $this->model('user/member')->byId($mid);

        $mine = array();
        $entries = $this->model('app\contribute')->byMpid($mpid);
        if (!empty($entries)) foreach ($entries as $entry) {
            $set = "cid='$entry->id' and role='I'";
            $entry->isInitiator = $this->model('acl')->canAccess(
                $mpid, 
                'xxt_contribute_user',
                $set,
                $member->authed_identity,
                $authids, true);
            //
            $set = "cid='$entry->id' and role='R'";
            $entry->isReviewer = $this->model('acl')->canAccess(
                $mpid, 
                'xxt_contribute_user',
                $set,
                $member->authed_identity,
                $authids, true);
            //
            $set = "cid='$entry->id' and role='T'";
            $entry->isTypesetter = $this->model('acl')->canAccess(
                $mpid, 
                'xxt_contribute_user',
                $set,
                $member->authed_identity,
                $authids, true);
            //           
            if ($entry->isInitiator || $entry->isReviewer || $entry->isTypesetter) {
                $entry->pk = 'contribute,'.$entry->id;
                $mine[] = $entry;
            }
        }
        if (count($mine) === 1) {
            $entry = $mine[0];
            $roles = array();
            $entry->isInitiator && $roles[] = 'initiate';
            $entry->isReviewer && $roles[] = 'review';
            $entry->isTypesetter && $roles[] = 'typeset';
            if (count($roles) === 1) {
                $url = '/rest/app/contribute/'.$roles[0];
                $url .= '?mpid='.$mpid;
                $url .= '&entry='.$entry->pk;
                $this->redirect($url);
            }
        }
        $params = array();
        $params['mpid'] = $mpid;
        $params['entries'] = $mine;
        
        \TPL::assign('params', $params);
        $this->view_action('/app/contribute/main');
    }
    /**
     * 获得当前访问用户的信息
     *
     * $mpid
     */
    private function getCurrentUserInfo($mpid, $callbackUrl) 
    {
        $openid = $this->getCookieOAuthUser($mpid);
        
        $authapis = $this->model('user/authapi')->byMpid($mpid, 'Y');
        $aAuthids = array();
        foreach ($authapis as $a)
            $aAuthids[] = $a->authid;

        $members = $this->authenticate($mpid, $aAuthids, $callbackUrl, $openid);
        empty($members) && $this->outputError('无法获得用户认证信息');
       
        $mid = $members[0]->mid;
        $fan = $this->model('user/fans')->byMid($mid, 'fid,openid'); 
        $vid = $this->getVisitorId($mpid);

        return array($fan->fid, $fan->openid, $mid, $vid);
    }
}
