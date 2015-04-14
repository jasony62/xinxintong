<?php
require_once dirname(dirname(dirname(__FILE__))).'/member_base.php';
/**
 * member's article
 */
class article extends member_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 获得当前用户的信息
     */
    public function index_action($mpid, $code=null, $state=null, $openid=null) 
    {
        if ($code != null && $state != null)
            $who = $this->getOAuthUserByCode($mpid, $code);
        else {
            $state = $this->model()->encrypt($mpid, 'ENCODE', 'article');
            if (!empty($openid))
                /**
                 * 如果是直接打开认证页，而且提供了openid，就在cookie中保留信息，用于进行用户身份的绑定
                 */
                $this->setCookieOAuthUser($mpid, $openid);
            else {
                /**
                 * 如果支持OAuth，强制使用OAuth
                 * 如果进入之前的页面已经做过，不会再重复认证
                 */
                $this->oauth($mpid, $state);
            }
            $who = null;
        }
        $this->afterOAuth($state, $who);
    }
    /**
     *
     */
    public function afterOAuth($state, $who=null) 
    {
        $mpid = $this->model()->encrypt($state, 'DECODE', 'article');

        $articles = array();

        if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
            return new ResponseData($articles);
        } else {
            $params = array();
            $params['mpid'] = $mpid;

            TPL::assign('params', $params);
            $this->view_action('/member/matter/article');
        }
    }
}
