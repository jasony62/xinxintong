<?php
namespace cus\crccre\member;

require_once dirname(__FILE__).'/base2.php';
/**
 * crccre内部用户身份认证
 *
 * 通过定制的用户认证页获取用户身份信息
 * 调用统一登录认证接口
 * 认证通过后回调指定的接口
 */
class auth extends crccre_member_base2 {

    public function __construct() 
    {
        $this->authurl = '/rest/cus/crccre/member/auth';
    }
    /**
     * 打开认证页面
     */
    public function afterOAuth($mpid, $authid, $who=null)
    {
        /**
         * 已经认证过的用户身份
         */
        $ooid = empty($who) ? $this->getCookieOAuthUser($mpid) : $who;
        $member = $this->model('user/member')->byOpenid($mpid, $ooid, '*', $authid);

        \TPL::assign('mpid', $mpid);
        \TPL::assign('authid', $authid);
        \TPL::assign('authedMember', $member);

        $this->view_action("/cus/crccre/member/login");
    }
    /**
     * 调用铁建地产SSO接口进行用户身份验证
     */
    public function login_action($token, $authid)
    {
        if (empty($authid)) die('authid is empty.');
        /**
         * 公众号内部ID
         */
        $mpid = $this->myGetCookie("_{$token}_mpid");
        if (empty($mpid)) die('操作超时，请重新进入页面！');
        /**
         * 提交的用户名和口令
         */
        $up = $this->getPostJson();
        /**
         * 调用sso接口进行身份验证
         */
        $param = new \stdClass;
        $param->userName = $up->username;
        $param->passWord = $up->password;
        $param->groupUserType = 1;
        $ret = $this->soap()->GetUserGroupAuthenticate($param);
        if ($ret->GetUserGroupAuthenticateResult !== true) 
            return new \ResponseError('身份认证失败！');
        /**
         * 身份信息绑定
         */
        $mid = $this->bind($mpid, $authid, $up->username);
        /**
         * set cookie
         */
        $this->setCookie4Member($mpid, $authid, $mid);
        /**
         * 跳转回目标页面
         */
        if ($target = $this->myGetCookie("_{$mpid}_mauth_t")) {
            /**
             * 调用认证前记录的
             * 参见：member_base.php中的authenticate方法
             */
            $this->mySetcookie("_{$mpid}_mauth_t", '', 0);
            $target = $this->model()->encrypt($target, 'DECODE', $mpid);
        } else {
            /**
             * 进入缺省用户首页
             */
            $target = "/rest/cus/crccre/member/auth/authed?mpid=$mpid&authid=$authid";
        }
        /**
         * 清除数据
         */
        $this->mySetCookie("_{$token}_mpid", '', 0);

        return new \ResponseData($target);
    }
    /**
     * 打开通过认证页
     */
    public function authed_action()
    {
        $this->view_action('/cus/crccre/member/authed');
    }
    /**
     * 将认证通过的用户和关注用户绑定
     */
    private function bind($mpid, $authid, $username)
    {
        $ooid = $this->getCookieOAuthUser($mpid); 
        empty($ooid) && die('openid is empty.');
        /**
         * 获得用户身份信息
         */
        try {
            $param = new \stdClass;
            $param->userAccount = $username;    
            $ret = $this->soap()->GetUserByAccount($param);
            $xml = new \SimpleXMLElement($ret->GetUserByAccountResult);
            foreach ($xml->children() as $node) {
                $user = array();
                foreach ($node->attributes() as $k => $v)
                    $user[$k] = ''.$v;
            }
            if (!isset($user)) {
                \TPL::assign('title', '身份绑定未通过');
                \TPL::assign('body', '无法获取用户信息');
                \TPL::output('error');
                exit;
            }
            /**
             * 获得所属部门信息
             */
            //$depts = array();
            //$parentid = $user['parentid'];
            //$this->getSupDepartment($mpid, $parentid, $depts);
            //$deptids = array();
            //foreach ($depts as $dept)
            //    array_splice($deptids, 0, 0, $dept['deptid']);
        } catch (\Exception $e) {
            \TPL::assign('title', '身份绑定未通过');
            \TPL::assign('body', $e->getMessage());
            \TPL::output('error');
            exit;
        }
        /**
         * 只有关注用户才能成为认证用户
         */
        $fan = $this->model('user/fans')->byOpenid($mpid, $ooid);
        /**
         * 建立认证用户和访客之间的关联
         */
        $vid = $this->getVisitorId($mpid);
        $this->model()->update(
            'xxt_visitor', 
            array('fid'=>$fan->fid), 
            "mpid='$mpid' and vid='$vid'"
        );
        /**
         * 指定用户的分组
         * todo 如果失败了怎么办？
         */
        $this->setFansGroup($mpid, $ooid, 100);
        /**
         * 检查是否为一个已经存在的用户
         * 一个openid对应一个认证接口，只能绑定一个身份
         * 如果openid已经绑定过一个认证身份，将原有的身份设置为禁用，创建新身份
         * 如果不存在新创建一个注册用户
         */
        $q = array(
            'mid',
            'xxt_member',
            "mpid='$mpid' and forbidden='N' and ooid='$ooid' and authapi_id='$authid'"
        );
        if ($mid = $this->model()->query_val_ss($q)) {
            /**
             * 禁用原有的绑定关系
             */
            $this->model()->update(
                'xxt_member',
                array('forbidden'=>'Y'),
                "mpid='$mpid' and forbidden='N' and ooid='$ooid' and authapi_id='$authid'"
            );
        }
        /**
         * get auth settings.
         */
        $attrs = $this->model('user/authapi')->byId($authid, 'attr_mobile,attr_email,attr_name,attr_password,extattr'); 
        /**
         * 创建新认证用户
         */
        $member = array(
            'mpid'=>$mpid,
            'fid'=>$fan->fid,
            'ooid'=>$ooid,
            'authapi_id'=>$authid,
            'authed_identity'=>$username,
            'name'=>$user['title'],
            //'depts'=>json_encode(array($deptids)),
            'extattr'=>json_encode($user)
        );
        $rst = $this->model('user/member')->create($fan->fid, $member, $attrs);
        if ($rst[0] === false) {
            \TPL::assign('title', '身份绑定未通过');
            \TPL::assign('body', '添加用户信息失败');
            \TPL::output('error');
            exit;
        }
        $mid = $rst[1];

        return $mid;
    }
}
