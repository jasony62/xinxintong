<?php
namespace auth;

class auth extends \TMS_CONTROLLER {

    public function get_access_rule() 
    {
        $rule_action = array(
            'rule_type' => 'black',
            'actions' => array()
        );
		
		return $rule_action;
    }
    /**
     * 进入用户身份验证页面
     */
    public function index_action() 
    {
        $path = TMS_APP_VIEW_PREFIX.'/user/login';
        $path .= '?callback='.urlencode(TMS_APP_API_PREFIX.'/auth/auth/passed');
        $this->redirect($path);
    }
    /**
     * 验证通过后的回调页面 
     */
    public function passed_action($uid)
    {
        $fromip = $this->client_ip();
        $this->model('account')->update_last_login($uid, $fromip);
        /**
         * record account into session and cookie.
         */
        $act = $this->model('account')->byId($uid);
        /**
         * 记录客户端登陆状态
         */
        \TMS_CLIENT::account($act);
        /**
         * 跳转到缺省页
         */
        $this->redirect(TMS_APP_AUTHED);
    }
}
