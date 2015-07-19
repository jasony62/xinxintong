<?php
/**
 *
 */
class debug extends TMS_CONTROLLER {

    public function get_access_rule() {
        $rule_action['rule_type'] = 'black';

        return $rule_action;
    }
    /**
     *
     */
    public function index_action($mpid) 
    {
        TPL::output('debug');
    }
    /**
     * 清除用户认证信息
     */
    public function cleanMemberCookie_action($mpid) 
    {
        /**
         * member identity
         */
        $authapi = $this->model('user/authapi')->byUrl($mpid, '/rest/member/auth', 'authid');
        $this->mySetCookie("_{$mpid}_{$authapi->authid}_member", '', 0);

        return new ResponseData('清除成功！');
    }
}
