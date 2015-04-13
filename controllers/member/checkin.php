<?php
require_once dirname(dirname(__FILE__)).'/member_base.php';
/**
 * member checkin
 */
class checkin extends member_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     *
     * return page/action by accept header.
     */
    public function index_action($mpid) 
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            /**
             * do checkin
             */
            // todo 参数应该通过配置获得
            $authapi = $this->model('user/authapi')->byUrl($mpid, '/rest/member/auth', 'authid,url');
            $aAuthapis[] = $authapi->authid;
            $members = $this->authenticate($mpid, $aAuthapis, false);
            $mid = $members[0]->mid;
            if ($gain = $this->model('user/checkin')->participate($mpid, $mid))
                return new ResponseData($gain);
            else 
                die('unknown error.');
        } else {
            $this->setCheckinInfo($mpid);

            $checkin = $this->model()->query_obj(
                'extra_css,extra_ele,extra_js',
                'xxt_checkin',
                "mpid='$mpid'");
            $checkin->extra_css && TPL::assign('extra_css',$checkin->extra_css);
            $checkin->extra_ele && TPL::assign('extra_ele',$checkin->extra_ele);
            $checkin->extra_js && TPL::assign('extra_js',$checkin->extra_js);

            $body_ele = $this->model()->query_value('body_ele', 'xxt_mpsetting', "mpid='$mpid'");
            TPL::assign('body_ele', $body_ele);

            $this->view_action('/member/checkin/main');
            exit;
        }
    }
    /**
     *
     */
    private function setCheckinInfo($mpid) 
    {
        // todo 需要指定认证接口
        if ($mid = $this->getCookieMember($mpid)) {
            $member = $this->model()->query_obj('*','xxt_member',"mid='$mid'");
            $mpid = $member->mpid;
            // member info
            TPL::assign('member.name',$member->name);
            //TPL::assign('member.nickname',$member->nickname);
            TPL::assign('member.cardno',$member->cardno);
            TPL::assign('member.credits',$member->credits);
            $level = $this->model('user/checkin')->calcLevel($member->credits);
            TPL::assign('member.level',$level[0]);
            TPL::assign('member.level_title',$level[1]);
            // checkin info
            if ($checkin_log = $this->model()->query_obj('*','xxt_checkin_log',"mid='$mid' and last=1")) {
                if ($this->model('user/checkin')->isBreak($checkin_log))
                    TPL::assign('member.times_accumulated', 0);
                else
                    TPL::assign('member.times_accumulated', $checkin_log->times_accumulated);
                if ($this->model('user/checkin')->isOpen($mpid, $mid, $checkin_log)){
                    TPL::assign('open', '1');
                } else {
                    TPL::assign('open', '0');
                }
            } else {
                TPL::assign('open', '1');
                TPL::assign('member.times_accumulated', 0);
            }
        } else {
            // member info
            TPL::assign('member.name','');
            //TPL::assign('member.nickname','');
            TPL::assign('member.cardno','');
            TPL::assign('member.credits',0);
            TPL::assign('member.level',0);
            TPL::assign('member.level_title','');
            TPL::assign('open', '1');
            TPL::assign('member.times_accumulated', 0);
        }
    }
}
