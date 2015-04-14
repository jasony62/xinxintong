<?php
include_once dirname(dirname(__FILE__)).'/member_base.php';
/**
 * common activity
 */
class mywall extends member_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 返回活动页
     *
     * 活动是否只向会员开放，如果是要求先成为会员，否则允许直接
     * 如果已经报过名如何判断？
     * 如果已经是会员，则可以查看和会员的关联
     * 如果不是会员，临时分配一个key，保存在cookie中，允许重新报名
     *
     */
    public function index_action($mpid, $wid, $openid='', $src='', $code=null, $state=null) 
    {
        empty($mpid) die('mpid is empty.');
        empty($wid) die('wid is empty.');

        if ($code !== null && $state !== null) {
            $who = $this->getOAuthUserByCode($mpid, $code);
        } else {
            $state = json_encode(array($mpid, $wid));
            $state = $this->model()->encrypt($state, 'ENCODE', 'discuss');
            /**
             * 为测试方便使用
             */
            if (!empty($openid) && !empty($src)) {
                $who = array($openid, $src);
                $encoded = $this->model()->encrypt(json_encode($who), 'ENCODE', $mpid);
                $this->mySetcookie("_{$mpid}_oauth", $encoded);
            } else {
                $this->oauth($mpid, $state);
                $who = null;
            }
            $this->afterOAuth($state, $who);
        }
    }
    /**
     * 返回活动页面
     *
     * $wall activity object or its it.
     */
    protected function afterOAuth($state, $who=null)
    {
        $state = json_decode($this->model()->encrypt($state, 'DECODE', 'discuss'));
        list($mpid, $wid) = $state;

        $model = $this->model('activity/wall');
        $wall = $model->byId($wid);
        /**
         * 当前访问用户
         */
        list($ooid, $osrc) = $this->getCookieOAuthUser($wall->mpid);

        $data = $model->approvedMessages($mpid, $wid, 0);
        $messages = $data ? $data[0] : array();

        TPL::assign('title', '聊天记录');
        TPL::assign('wid', $wid);
        TPL::assign('openid', $ooid);
        TPL::assign('src', $osrc);
        TPL::assign('messages', $messages);
        $this->view_action('/activity/discuss/mywall');
    }
}
