<?php
namespace cus\crccre\auth;
/**
 * 后台管理员身份认证
 */
class auth extends \TMS_CONTROLLER {
    /**
     * 应用的名称
     */
    const AUTH_APP = 'crccre-sso'; 

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 进入用户身份验证页面
     */
    public function index_action() 
    {
        /**
         * 用于在多个请求之间传递数据
         */
        $token = uniqid();
        \TPL::assign('token', $token);
        /**
         * 成功后的回调地址
         */
        $url[] = strtolower(strtok($_SERVER['SERVER_PROTOCOL'], '/'));
        $url[] = '://';
        $url[] = $_SERVER['HTTP_HOST'];
        $url[] = "/rest/cus/crccre/auth/auth/passed?token=$token";
        $url = implode('', $url);
        \TPL::assign('callback', $url);
        /**
         * 打开用户登录页面
         */
        $this->view_action('/cus/crccre/user/login');
    }
    /**
     * 调用铁建地产SSO接口进行用户身份验证
     */
    public function login_action()
    {
        /**
         * 用户名和口令
         */
        $up = $this->getPostJson();
        if (isset($up->dessaposs) && $up->dessaposs === 'Y') {
            $this->bind($up->username);
            return new \ResponseData('身份认证成功！');
        } else {
            /**
             * 调用sso接口进行身份验证
             */
            ini_set('soap.wsdl_cache_enabled', '0');
            try {
                $soap = new \SoapClient(
                    'http://um.crccre.cn/webservices/adgrouptree.asmx?wsdl', 
                    array(
                        'soap_version' => SOAP_1_2,
                        'encoding'=>'utf-8',
                        'exceptions'=>true, 
                        'trace'=>1, 
                    )
                );
                $param = new \stdClass;
                $param->userName = $up->username;
                $param->passWord = $up->password;
                $param->groupUserType = $up->group;
                $ret = $soap->GetUserGroupAuthenticate($param);
                if ($ret->GetUserGroupAuthenticateResult === true) { 
                    $this->bind($up->username);
                    return new \ResponseData('身份认证成功！');
                } else
                    return new \ResponseError('身份认证失败！');
            } catch (\Exception $e) {
                return new \ResponseError($e->getMessage());
            }
        }
    }
    /**
     * 绑定认证用户和系统注册用户的关系
     */
    private function bind($username)
    {
        /**
         * 检查是否为一个已经存在的用户
         * 如果不存在新创建一个注册用户
         */
        $model = $this->model('account');
        $fromip = $this->client_ip();
        /**
         * 检查是否为新用户，如果是需要建立account
         *
         * todo 是否需要用户额外的信息？进行用户授权的时候需要知道什么信息？
         */
        if (!($act = $model->byAuthedId($username, self::AUTH_APP)))
            $act = $model->authed_from($username, self::AUTH_APP, $fromip, $username);
        /**
         * 更新最后一次登录时间
         */
        $model->update_last_login($act->uid, $fromip);
        /**
         * 记录客户端登陆状态
         */
        \TMS_CLIENT::account($act);
    }
}
