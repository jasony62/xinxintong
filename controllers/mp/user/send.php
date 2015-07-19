<?php
namespace mp\user;

require_once dirname(dirname(__FILE__)).'/mp_controller.php';

class send extends \mp\mp_controller {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';

        return $rule_action;
    }
    /**
     *
     */
    public function index_action()
    {
        $mpa = $this->getMpaccount();
        if ($mpa->asparent === 'Y') {
            $params = array();
            $params['mpaccount'] = $mpa;
            \TPL::assign('params', $params);
            
            $this->view_action('/mp/user/send/parentmp');
        } else {
            /**
             * 是否开通了支持发送消息的接口
             */
            $mpaccount = $this->getMpaccount();
            $apis = $this->model('mp\mpaccount')->getApis($mpaccount->mpid);
            $canWxGroup = $mpaccount->mpsrc === 'wx' && ($apis->wx_group_push==='Y'&&$apis->wx_fansgroup==='Y');
            $canYxGroup = $mpaccount->mpsrc === 'yx' && ($apis->yx_group_push==='Y'&&$apis->yx_fansgroup==='Y');
            $canMember = $apis->mpsrc === 'qy' || ($apis->mpsrc === 'yx' && $apis->yx_p2p==='Y');
            $canSend = $canWxGroup || $canYxGroup || $canMember;
            if ($canSend) 
                $this->view_action('/mp/user/send/main');
            else
                $this->view_action('/mp/user/send/unsupport');
        }
    }
}
