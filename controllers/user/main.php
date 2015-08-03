<?php
namespace user;
/**
 *
 */
class main extends \TMS_CONTROLLER {

    public function get_access_rule() 
    {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array();
        $rule_action['actions'][] = 'hello';
        $rule_action['actions'][] = 'view';
        $rule_action['actions'][] = 'register';
        $rule_action['actions'][] = 'login';
		
		return $rule_action;
    }
    /**
     * register a new account.
     *
     * $param string $email
     * $param string $password
     */
    public function register_action() 
    {
        $data = $this->getPostJson();
        $email = $data->email;
        $password = $data->password;

        $nickname = str_replace(strstr($email, '@'), '', $email);
        $fromip = $this->client_ip();
        /*
        * check
        */
        if (strlen($email) == 0 || strlen($nickname) == 0 || strlen($password) == 0)
            return new ParameterError("注册失败，参数不完整。");
        // email existed?
        if ($this->model('account')->check_email($email))
            return new DataExistedError('注册失败，注册账号已经存在。');
        //
        $account = $this->model('account')->register($email, $password, $nickname, $fromip);
        /**
         * record account into session and cookie.
         */
        \TMS_CLIENT::account($account);

        return new \ResponseData($account);
    }
    /**
     * login
     *
     * $param string $email
     * $param string $password
     */
    public function login_action() 
    {
        $data = $this->getPostJson();

        $result = $this->model('account')->validate($data->email, $data->password);
        if ($result->err_code != 0) {
            return $result;
        }
        $account = $result->data;

        $fromip = $this->client_ip();
        $this->model('account')->update_last_login($account->uid, $fromip);

        return new \ResponseData($account->uid);
    }
    /**
     * 结束登录状态
     */ 
    public function logout_action() 
    {
        \TMS_CLIENT::logout();
        $this->redirect('');
    }
    /**
     * 修改当前用户的口令
     */
    public function changePwd_action() 
    {
        $account = \TMS_CLIENT::account();
        if ($account === false)
            return new \ResponseError('长时间未操作，请重新登陆！');
            
        $data = $this->getPostJson();
        /**
         * check old password
         */
        $old_pwd = $data->opwd;
        $result = $this->model('account')->validate($account->email, $old_pwd);
        if ($result->err_code != 0)
            return $result;
        /**
         * set new password
         */
        $new_pwd = $data->npwd;
        $this->model('account')->change_password($account->email, $new_pwd, $account->salt);

        return new \ResponseData($account->uid);
    }
}
