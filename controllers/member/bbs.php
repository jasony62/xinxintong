<?php
require_once dirname(dirname(__FILE__)).'/member_base.php';
/**
 *
 */
class bbs extends member_base {
    /**
     *
     */
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 进入微吧
     *
     * $mpid 公众号ID，每个公众号只提供一个微吧
     *
     */
    public function index_action($mpid) 
    {
        $body_ele = $this->model()->query_value('body_ele', 'xxt_mpsetting', "mpid='$mpid'");
        TPL::assign('body_ele', $body_ele);
        $this->view_action('/member/bbs/main');
        exit;
    }
    /**
     * 所有主题列表
     * 返回数据/页面
     */
    public function subjects_action($mpid)
    {
        if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
            $subjects = $this->model('bbs')->subjects($mpid);
            return new ResponseData($subjects);
        } else {
            if ($setting = $this->model()->query_obj('list_css,list_ele,list_js', 'xxt_bbs', "mpid='$mpid'")) {
                $setting->list_css && TPL::assign('extra_css', $setting->list_css);
                $setting->list_ele && TPL::assign('extra_ele', $setting->list_ele);
                $setting->list_js && TPL::assign('extra_js', $setting->list_js);
            }
            $this->view_action('/member/bbs/subject_list');
            exit;
        }
    }
    /**
     * 浏览一个主题
     *
     * $mpid 公众号ID
     * $sid 主题ID
     *
     */
    public function subject_action($mpid, $sid) 
    {
        if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
            /**
             * 列出一个主题的内容和所有回复（按创建时间倒序排列）
             */
            $q = array(
                's.*', 
                'xxt_bbs_subject s,xxt_member m',
                "s.sid=$sid and s.mpid='$mpid' and s.mpid=m.mpid and s.creater=m.mid"
            );
            if ($subject = $this->model()->query_obj_ss($q)) {
                $subject->replies = $this->model('bbs')->replies($mpid, $sid);
            }
            return new ResponseData($subject);
        } else {
            $q = array('subject_css,subject_ele,subject_js',
                'xxt_bbs',
                "mpid='$mpid'"
            );
            if ($extra = $this->model()->query_obj_ss($q)) {
                $extra->subject_css && TPL::assign('extra_css', $extra->subject_css);
                $extra->subject_ele && TPL::assign('extra_ele', $extra->subject_ele);
                $extra->subject_js && TPL::assign('extra_js', $extra->subject_js);
            }
            $this->view_action('/member/bbs/subject');
            exit;
        }
    }
    /**
     * 发表主题
     */
    public function publish_action($mpid) 
    {
        //
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // todo 参数应该通过配置获得
            $authapi = $this->model('user/authapi')->byUrl($mpid, '/rest/member/auth', 'authid,url');
            $aAuthapis[] = $authapi->authid;
            $members = $this->authenticate($mpid, $aAuthapis, false);
            $mid = $members[0]->mid;
            $current = time();
            // create a new subject.
            $subject['mpid'] = $mpid; 
            $subject['subject'] = $this->getPost('subject', '');
            $subject['content'] = $this->getPost('content', '');
            $subject['creater'] = $mid; 
            $subject['publish_at'] = $current; 
            $subject['reply_at'] = $current; 
            $sid = $this->model()->insert('xxt_bbs_subject', $subject, true);
            return new ResponseData($sid);
        } else {
            $q = array(
                'publish_css,publish_ele,publish_js',
                'xxt_bbs',
                "mpid='$mpid'"
            );
            if ($extra = $this->model()->query_obj_ss($q)) {
                $extra->publish_css && TPL::assign('extra_css', $extra->publish_css);
                $extra->publish_ele && TPL::assign('extra_ele', $extra->publish_ele);
                $extra->publish_js && TPL::assign('extra_js', $extra->publish_js);
            }
            $this->view_action('/member/bbs/subject_new');
            exit;
        }
    }
    /**
     * 回复主题
     */
    public function reply_action($mpid, $sid)
    {
        /**
         * 如果不是会员，打开注册页
         * 注册成功后通知原始页面提交成功，可以进行后续操作
         */
        // todo 参数应该通过配置获得
        $authapi = $this->model('user/authapi')->byUrl($mpid, '/rest/member/auth', 'authid,url');
        $aAuthapis[] = $authapi->authid;
        $members = $this->authenticate($mpid, $aAuthapis, false);
        $mid = $members[0]->mid;
        //
        $reply_at = time(); 
        $reply['mpid'] = $mpid; 
        $reply['sid'] = $sid; 
        $reply['content'] = $this->getPost('content', '');
        $reply['creater'] = $mid; 
        $reply['reply_at'] = $reply_at; 
        $rid = $this->model()->insert('xxt_bbs_reply', $reply, true);

        $this->model()->update('xxt_bbs_subject', array('reply_at'=>$reply_at), "sid=$sid");

        $reply['rid'] = $rid;
        //$reply['nickname'] = $this->model()->query_value('nickname', 'xxt_member', "mpid='$mpid' and mid='$mid'"); 

        return new ResponseData($reply);
    }
}
