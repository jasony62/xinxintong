<?php
namespace member;

require_once dirname(dirname(__FILE__)).'/member_base.php';
/**
 * member
 */
class card extends \member_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 会员卡
     */
    public function index_action($mpid) 
    {
        $q = array(
            'title,board_pic,badge_pic,title_color,cardno_color',
            'xxt_member_card',
            "mpid='$mpid'"
        );
        $card = $this->model()->query_obj_ss($q);
        \TPL::assign('title',$card->title);
        \TPL::assign('board_pic',$card->board_pic);
        \TPL::assign('badge_pic',$card->badge_pic);
        \TPL::assign('title_color',$card->title_color);
        \TPL::assign('cardno_color',$card->cardno_color);

        $body_ele = $this->model()->query_value('body_ele', 'xxt_mpsetting', "mpid='$mpid'");
        \TPL::assign('body_ele', $body_ele);
        
        $this->view_action('/member/card/main');
    }
    /**
     * 会员信息 
     */
    public function info_action($mpid) 
    {
        // todo 需要指定认证接口
        if (!($mid = $this->getCookieMember($mpid))) {
            $this->apply_view($mpid);
        } else {
            $q = array('*',
                'xxt_member',
                "mid='$mid'"
            );
            $member = $this->model()->query_obj_ss($q);
            if ($member->cardno)
                $this->show_view($mpid, $member);
            else
                $this->apply_view($mpid);
        }
    }
    /**
     * 显示会员卡申请信息
     */
    private function apply_view($mpid)
    {
        \TPL::assign('member.cardno', '0000000');
        $q = array(
            'apply_css,apply_ele,apply_js',
            'xxt_member_card',
            "mpid='$mpid'"
        );
        if ($extra = $this->model()->query_obj_ss($q)) {
            $extra->apply_css && \TPL::assign('extra_css',$extra->apply_css);
            $extra->apply_ele && \TPL::assign('extra_ele',$extra->apply_ele);
            $extra->apply_js && \TPL::assign('extra_js',$extra->apply_js);
        }

        $this->view_action('/member/card/apply');
        exit;
    }
    /**
     * 显示会员信息
     */
    private function show_view($mpid, &$member)
    {
        \TPL::assign('member.name',$member->name);
        //\TPL::assign('member.nickname',$member->nickname);
        \TPL::assign('member.cardno',$member->cardno);
        \TPL::assign('member.credits',$member->credits);
        $level = $this->model('user/checkin')->calcLevel((int)$member->credits);
        \TPL::assign('member.level',$level[0]);
        \TPL::assign('member.level_title',$level[1]);

        $q = array(
            'show_css,show_ele,show_js',
            'xxt_member_card',
            "mpid='$mpid'"
        );
        if ($extra = $this->model()->query_obj_ss($q)) {
            $extra->show_css && \TPL::assign('extra_css',$extra->show_css);
            $extra->show_ele && \TPL::assign('extra_ele',$extra->show_ele);
            $extra->show_js && \TPL::assign('extra_js',$extra->show_js);
        }

        $this->view_action('/member/card/show');
        exit;
    }
    /**
     * 申请会员卡
     */
    public function apply_action($mpid) 
    {
        // todo 参数应该通过配置获得
        $authapi = $this->model('user/authapi')->byUrl($mpid, '/rest/member/auth', 'authid,url');
        $aAuthapis[] = $authapi->authid;
        $members = $this->authenticate($mpid, $aAuthapis, false);
        $mid = $members[0]->mid;
        /**
         * generate random member's number.
         */
        //todo 应该将规则做成可配置的
        $cardno = rand(999, 9999999);
        $cardno = sprintf($cardno, '%07s', $cardno);
        $w = "mpid='$mpid' and forbidden='N' and cardno='$cardno'";
        while ($vvv = $this->model()->query_value('1','xxt_member', $w)) {
            $cardno = rand(999, 9999999);
            $cardno = sprintf($cardno, '%07s', $cardno);
            $w = "mpid='$mpid' and cardno='$cardno'";
        }
        /**
         * set cardno.
         */
        $d = array(
            'cardno'=>$cardno
        );
        $this->model()->update('xxt_member',$d,"mid='$mid'"); 

        return new \ResponseData($cardno);
    }
}
