<?php
namespace cus\crccre\member;

require_once dirname(__FILE__).'/base.php';
/**
 * crccre用户身份认证基类
 *
 * 通过定制的用户认证页获取用户身份信息
 * 调用统一登录认证接口
 * 认证通过后回调指定的接口
 */
class crccre_member_base2 extends crccre_member_base {
    /**
     * 身份认证的入口地址
     */
    protected $authurl;

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 打开用户认证页
     *
     * $mpid
     * $authid
     */
    public function index_action($mpid, $authid, $code=null) 
    {
        empty($mpid) && die('mpid is empty.');
        empty($authid) && die('authid is empty.');
        /**
         * 用于在多个请求之间传递数据
         * 设置一种一次性进入机制，必须要求从头开始整个流程
         */
        $token = uniqid();
        \TPL::assign('token', $token);
        /**
         * 在cookie中保留mpid
         */
        $this->mySetcookie("_{$token}_mpid", $mpid, time()+300);

        if ($code !== null)
            $who = $this->getOAuthUserByCode($mpid, $code);
        else {
            $this->oauth($mpid);
            $who = null;
        }
        $this->afterOAuth($mpid, $authid, $who);
    }
    /**
     * 设置关注用户的分组
     */
    protected function setFansGroup($mpid, $openid, $groupid=100)
    {
        /**
         * 更新公众平台上的数据
         */
        $mpproxy = \TMS_APP::M('mpproxy/wx', $mpid);
        $rst = $mpproxy->groupsMembersUpdate($openid, $groupid);

        if ($rst[0] === false)
            return $rst[1];
        /**
         * 更新本地数据
         */
        $rst = $this->model()->update(
            'xxt_fans', 
            array('groupid'=>$groupid), 
            "mpid='$mpid' and openid='$openid'"
        );

        return ($rst) === 1;
    } 
    /**
     * 获得openid验证通过的身份信息
     *
     * $mpid
     * $authapi 通过那个接口进行的身份验证(url表示)
     * $openid 
     */
    public function authedInfo_action($mpid, $authapi, $openid)
    {
        /**
         * 获得认证接口
         */
        if (!($authapi = $this->model('user/authapi')->byUrl($mpid, $authapi, 'authid')))
            return new \ResponseError("authentication's api invalid.");

        $q = array(
            'authed_identity',
            'xxt_member',
            "mpid='$mpid' and forbidden='N' and authapi_id=$authapi->authid and ooid='$openid'"
        );

        if ($member = $this->model()->query_objs_ss($q)) {
            if (count($member) === 1) {
                $member = $member[0];
                $ret = array(
                    'userid'=>$member->authed_identity
                );
                return new \ResponseData($ret);
            } else 
                return new \ResponseError('invalid data');
        } else
            return new \ResponseError('not exists');
    }
    /**
     * 禁用绑定的用户认证信息
     */
    public function unbind_action($mpid, $authapi, $userid)
    {
        /**
         * 获得认证接口
         */
        if (!($authapi = $this->model('user/authapi')->byUrl($mpid, $authapi, 'authid')))
            return new \ResponseError("authentication's api invalid.");

        $rst = $this->model()->update(
            'xxt_member',
            array('forbidden'=>'Y'),
            "mpid='$mpid' and authapi_id=$authapi->authid and authed_identity='$userid'"
        );
        return new \ResponseData($rst);
    }
    /**
     * 更新用户绑定信息
     */
    public function refresh_action($mpid, $authapi, $userid)
    {
        return new \ResponseData(1);
    }
    /**
     * 返回组织机构组件
     */
    public function memberSelector_action($authid)
    {
        $addon = array(
            'js'=>'/views/default/cus/crccre/member/memberSelector.js',
            'view'=>"/rest/cus/crccre/member/auth/organization?authid=$authid"
        );
        return new \ResponseData($addon);
    }
    /**
     *
     */
    public function organization_action($authid)
    {
        $this->view_action('/cus/crccre/member/memberSelector');
    }
    /**
     * 检查指定用户是否在acl列表中
     *
     * 允许多个微信号或易信号对应一个业务用户
     *
     * $authid
     * $uid
     */
    public function checkAcl_action($authid, $uid)
    {
        $q = array(
            '*',
            'xxt_member',
            "authapi_id=$authid and authed_identity='$uid' and forbidden='N'"
        );
        $members = $this->model()->query_objs_ss($q);
        if (empty($members)) return new \ResponseError('指定的用户不存在');

        $acls = $this->getPostJson();

        foreach ($acls as $acl) {
            switch ($acl->idsrc) {
            case 'D':
                $url = "http://um.crccre.cn/REST/UM/GetAccountExitGroup.aspx";
                $url .= "?UserAccount=$uid";
                $url .= "&GUIDs=$acl->identity";
                $rsp = file_get_contents($url);
                if ($rsp === 'true')
                    return new \ResponseData('passed');
                break;
            case 'M':
                foreach ($members as $member)
                    if ($member->authed_identity === $acl->identity)
                        return new \ResponseData('passed');
                break;
            }
        }

        return new \ResponseError('no matched');
    }
    /**
     * 将内部组织结构数据全量导入到企业号通讯录 
     *
     * $mpid
     * $authid
     */
    public function import2Qy_action($mpid, $authid)
    {
        return new \ResponseError('not support');
    }
    /**
     * 将内部组织结构数据增量导入到企业号通讯录 
     *
     * $mpid
     * $authid
     */
    public function sync2Qy_action($mpid, $authid)
    {
        return new \ResponseError('not support');
    }
    /**
     * 将内部组织结构数据增量导入到企业号通讯录 
     *
     * $mpid
     * $authid
     */
    public function syncFromQy_action($mpid, $authid)
    {
        return new \ResponseError('not support');
    }
}
