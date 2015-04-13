<?php
require_once dirname(dirname(__FILE__)).'/mp_controller.php';

class main extends mp_controller {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';

        return $rule_action;
    }
    /**
     * 获得一个用户的完整信息
     *
     * $openid
     * $src
     */
    public function index_action($fid) 
    {
        // 关注用户信息
        $fan = $this->model('user/fans')->byId($fid);
        $mm = $this->model('user/member');
        if ($members = $mm->byFanid($this->mpid, $fid)) {
            foreach ($members as &$m) {
                $m->depts2 = $this->model('user/department')->strUserDepts($m->depts);
                $authapi = $this->model('user/authapi')->byId($m->authapi_id);
                $authapi->tags = $this->model('user/tag')->byMpid($this->mpid, $m->authapi_id);
                $authapi->depts = $this->model('user/department')->byMpid($this->mpid, $m->authapi_id);
                $m->authapi = $authapi;
            }
            $fan->members = $members;
        }

        $params = array();
        $params['fan'] = $fan;
        $params['authapis'] = $this->model('mp\mpaccount')->getAuthapis($this->mpid, 'Y');
        $params['groups'] = $this->model('user/fans')->getGroups($this->mpid);

        TPL::assign('params', $params);

        $this->view_action('/mp/user/user');
    }
    /**
     * 获得用户选择器的页面
     *
     */
    public function picker_action()
    {
        $this->view_action('/mp/user/picker');
    }
}
