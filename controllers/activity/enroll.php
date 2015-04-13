<?php
include_once dirname(dirname(__FILE__)).'/member_base.php';
/**
 * 登记活动
 */
class enroll extends member_base {

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
     * $mpid 因为活动有可能来源于父账号，因此需要指明活动是在哪个公众号中进行的
     * $aid
     * $page 要进入活动的哪一页 
     * $ek 登记记录的id
     * $shareid 谁进行的分享
     * $openid 用于测试，模拟访问用户
     * $src 用于测试，模拟访问用户
     * $code
     * $state
     *
     */
    public function index_action($mpid, $aid, $shareby='', $page='', $ek='', $openid='', $src='', $code=null, $state=null) 
    {
        empty($mpid) && die('mpid is emtpy.');
        empty($aid) && die('aid is empty.');

        $act = $this->model('activity/enroll')->byId($aid);
        /**
         * 仅限微信易信客户端访问
         */
        if ($act->wxyx_only === 'Y') {
            $csrc = $this->getClientSrc();
            if ($csrc !== 'wx' && $csrc !== 'yx')
                $this->outputError('请用指定客户端打开！');
        }
        /**
         * 获得当前访问用户
         */
        if ($code !== null && $state !== null)
            $who = $this->getOAuthUserByCode($mpid, $code);
        else {
            /**
             * 传递状态参数
             */
            $state = json_encode(array($mpid, $aid, $page, $ek, $shareby));
            $state = $this->model()->encrypt($state, 'ENCODE', 'activity');
            if (!empty($openid)) {
                /**
                 * 模拟用户访问
                 */
                $who = array($openid, $src);
                $encoded = $this->model()->encrypt(json_encode($who), 'ENCODE', $mpid);
                $this->mySetcookie("_{$mpid}_oauth", $encoded);
            } else {
                $this->oauth($mpid, $state);
                $who = null;
            }
        }
        $this->afterOAuth($state, $who);
    }
    /**
     * 返回活动页面
     *
     * $act activity object or its it.
     */
    protected function afterOAuth($state, $who=null)
    {
        $state = json_decode($this->model()->encrypt($state, 'DECODE', 'activity'));
        list($mpid, $aid, $page, $ek, $shareby) = $state;

        $enrollModel = $this->model('activity/enroll');
        $act = $enrollModel->byId($aid);
        /**
         * 当前访问用户
         */
        list($ooid, $mid, $vid, $osrc) = $this->getCurrentUserInfo($mpid, $act, $who, true);
        /**
         * 要求先关注再参与
         */
        $act->fans_enter_only === 'Y' && $this->askFollow($mpid, $ooid, $osrc);
        /**
         * 页面所需的数据
         */
        $params = array();
        /**
         * 是否需要进行用户身份认证
         * 如果跳过介绍页就先进行身份验证，再进入页面，否则由页面发起身份认证
         */
        $params['require_auth'] = 'N';
        TPL::assign('title', $act->title);
        $params['mpid'] = $mpid;
        $params['shareby'] = $shareby;
        $params['activity'] = $act;
        /**
         * 当前用户信息
         */
        if (!empty($ooid) && $user = $this->getUserInfo($mpid, $ooid))
            $visitor = $user;
        else
            $visitor = new stdClass;
        $visitor->vid = $vid;
        $params['user'] = $visitor;
        /**
         * 全局设置
         */
        $mpsetting = $this->getCommonSetting($mpid);
        TPL::assign('body_ele', $mpsetting->body_ele);
        TPL::assign('body_css', $mpsetting->body_css);
        /**
         * 进入到哪一个状态页
         */
        $newForm = false;
        if (empty($page)) {
            if ($enrollModel->hasEnrolled($mpid, $act->aid, $ooid) && !empty($act->enrolled_entry_page))
                $page = $act->enrolled_entry_page;
            else
                $page = $act->entry_page;
            /**
             * 进入活动打开form页
             */
            $page === 'form' && $act->open_lastroll === 'N' && $newForm = true;
        } else
            /**
             * 指定新增登记数据
             */
            $page === 'form' && empty($ek) && $newForm = true;

        $params['subView'] = $page;
        $oPage = $act->pages[$page];
        !empty($oPage->html) && TPL::assign('extra_html', $oPage->html);
        !empty($oPage->css) && TPL::assign('extra_css', $oPage->css);
        !empty($oPage->js) && TPL::assign('extra_js', $oPage->js);
        /**
         * 页面数据设置 
         */
        list($openedek, $record, $statdata) = $this->getPageData($mpid, $act, $ek, $ooid, $page, $newForm);
        $params['enrollKey'] = $openedek;
        $params['record'] = $record;
        $params['statdata'] = $statdata;
        /**
         * 记录日志，完成前置活动再次进入的情况不算
         */
        $this->model('log')->writeMatterReadLog(
            $vid, 
            $mpid, 
            $act->aid, 
            'activity', 
            isset($ooid) ? $ooid : '',
            isset($osrc) ? $osrc : '',  
            $shareby, 
            $_SERVER['HTTP_USER_AGENT'], 
            $this->client_ip()
        );

        TPL::assign('params', $params);

        $this->view_action('/activity/enroll/page');
    }
    /**
     * 获得当前访问用户的信息
     *
     * $mpid
     * $act
     * $who
     */
    private function getCurrentUserInfo($mpid, $act, $fan=null, $askAuth=false) 
    {
        /**
         * 当前用户
         */
        list($ooid, $osrc) = empty($fan) ? $this->getOAuthUser($mpid) : $fan;
        /**
         * 确保只有认证过的用户才能提交数据
         * todo 企业号直接跳过这个限制？
         */
        $mid = '';
        if ($act->access_control === 'Y' && $osrc !== 'qy') {
            /**
             * 仅限注册用户报名，若不是注册用户，先要求进行注册
             */
            if ($askAuth) {
                $myUrl = 'http://'.$_SERVER['HTTP_HOST']."/rest/activity/enroll?mpid=$mpid&aid=$act->aid";
                $this->accessControl($mpid, $act->aid, $act->authapis, array($ooid, $osrc), $act, $myUrl);
            }
            $aAuthapis = explode(',', $act->authapis);
            $members = $this->getCookieMember($mpid, $aAuthapis);
            // todo 提示信息应该用认证接口中指定的内容，应该用定制报错页
            if (empty($members)) die('unauthenticated!');
            $mid = $members[0]->mid;
            if (empty($ooid)) { 
                $fan = $this->model('user/fans')->byMid($mid, 'openid'); 
                $ooid = $fan->openid;
            }
        }
        $vid = $this->getVisitorId($mpid);

        return array($ooid, $mid, $vid, $osrc);
    }
    /**
     *
     * $mpid
     * $act
     * $ek
     * $openid
     * $page
     * $newForm
     *
     */
    private function getPageData($mpid, $act, $ek, $openid, $page, $newForm=false)
    {
        $enrollModel = $this->model('activity/enroll');
        $openedek = $ek;
        $record = null;
        /**
         * 打开登记数据页
         */
        if (empty($openedek)) {
            if (!$newForm) {
                /**
                 * 获得最后一条登记数据 
                 */
                $enrollList = $enrollModel->getRecordList($mpid, $act->aid, $openid);
                if (!empty($enrollList)) {
                    $record = $enrollList[0];
                    $openedek = $record->enroll_key;
                    $record->data = $enrollModel->getRecordData($openedek);
                }
            }
        } else
            /**
             * 打开指定的登记记录
             */
            $record = $enrollModel->getRecordById($openedek);
        /**
         * 互动数据
         */
        if (!empty($openedek)) {
            /**
             * 登记人信息
             */
            $record->enroller = $this->getUserInfo($mpid, $openid);
            /**
             * 评论数据
             */
            $record->remarks = $this->model('activity/enroll')->getRecordRemarks($openedek);
        }

        $statdata = $enrollModel->getStat($act->aid);

        return array($openedek, $record, $statdata);
    }
    /**
     *
     */
    protected function canAccessObj($mpid, $aid, $member, $authapis, $act)
    {
        return $this->model('acl')->canAccessAct($mpid, $aid, 'A', $member, $authapis);
    }
    /**
     * 报名登记页，记录登记信息
     *
     * $mpid
     * $aid
     * $ek enrollKey 如果要更新之前已经提交的数据，需要指定
     */
    public function submit_action($mpid, $aid, $ek=null) 
    {
        empty($mpid) && die('mpid is empty.');
        empty($aid) && die('aid is empty.');

        $mpa = $this->model('mp\mpaccount')->getApis($mpid);

        $model = $this->model('activity/enroll');

        $posted = $this->getPostJson();

        $act = $model->byId($aid);
        /**
         * 当前用户
         */
        list($ooid, $mid, $vid, $osrc) = $this->getCurrentUserInfo($mpid, $act);

        if ($act->fans_only === 'Y') {
            if (!$this->model('user/fans')->isFollow($mpid, $ooid))
                return new ComplianceError($act->nonfans_alert);
        }
        /**
         * 处理提交数据 
         */
        if (!empty($ek)) {
            /**
             * 已经登记，更新原先提交的数据
             */
            $this->model()->update(
                'xxt_activity_enroll', 
                array('enroll_at'=>time()), 
                "enroll_key='$ek'"
            );
            /**
             * 重新插入新提交的数据
             */
            $model->setRollData($mpid, $aid, $ek, $posted, false);
        } else {
            /**
             * 插入报名数据
             */
            $ek = $model->enroll($mpid, $act, $ooid, $vid, $mid);
            /**
             * 处理自定义信息
             */
            $model->setRollData($mpid, $aid, $ek, $posted);
        }
        /**
         * 通知登记事件接收人
         */
        $users = TMS_APP::model('acl')->activityEnrollReceivers($mpid, $aid);
        if (!empty($users)) {
            if (!empty($act->receiver_page)) {
                $url = 'http://'.$_SERVER['HTTP_HOST']."/rest/activity/enroll?mpid=$mpid&aid=$act->aid&ek=$ek&page=$act->receiver_page";
                $txt = urlencode($act->title."有新登记数据，")."<a href=\"$url\">".urlencode("请处理")."</a>";
            } else
                $txt = urlencode($act->title."有新登记数据，请处理");
            $message = array(
                "msgtype"=>"text",
                "text"=>array(
                    "content"=>$txt
                )
            );
            if ($mpa->mpsrc === 'qy') {
                $message['touser'] = implode('|', $users);
                $this->send_to_qyuser($mpid, $message);
            } else if ($mpa->mpsrc === 'yx' && $mpa->yx_p2p === 'Y') {
                $this->send_to_yxuser_byp2p($mpid, $message, $users);
            } else {
                foreach ($users as $user)
                    $this->send_to_user($mpid, $osrc, $user, $message);
            }
        }

        return new ResponseData($ek);
    }
    /**
     * 登记记录点赞 
     *
     * $mpid
     * $ek
     */
    public function recordScore_action($mpid, $ek)
    {
        /**
         * 当前活动
         */
        $q = array('aid', 'xxt_activity_enroll', "enroll_key='$ek'");
        $aid = $this->model()->query_val_ss($q);
        $act = $this->model('activity/enroll')->byId($aid);
        /**
         * 当前用户
         */
        list($openid) = $this->getCurrentUserInfo($mpid, $act);

        if ($this->model('activity/enroll')->rollPraised($openid, $ek)) {
            /**
             * 点了赞，再次点击，取消赞
             */
            $this->model()->delete(
                'xxt_activity_enroll_score', 
                "enroll_key='$ek' and openid='$openid'"
            );
            $myScore = 0;
        } else {
            /**
             * 点赞
             */
            $i = array(
                'openid'=>$openid,
                'enroll_key'=>$ek,
                'create_at'=>time(),
                'score'=>1
            );
            $this->model()->insert('xxt_activity_enroll_score', $i, false);
            $myScore = 1;
        }
        /**
         * 获得点赞的总数
         */
        $score = $this->model('activity/enroll')->rollScore($ek);
        $this->model()->update('xxt_activity_enroll', array('score'=>$score), "enroll_key='$ek'");

        return new ResponseData(array($myScore, $score));
    }
    /**
     * 发表评论
     *
     * $mpid
     * $ek
     */
    public function recordRemark_action($mpid, $ek)
    {
        $data = $this->getPostJson();
        if (empty($data->remark))
            return new ResponseError('评论不允许为空！');
        /**
         * 当前活动
         */
        $q = array('aid', 'xxt_activity_enroll', "enroll_key='$ek'");
        $aid = $this->model()->query_val_ss($q);
        $act = $this->model('activity/enroll')->byId($aid);
        /**
         * 当前用户
         */
        list($openid) = $this->getCurrentUserInfo($mpid, $act);

        $fan = $this->model('user/fans')->byOpenid($mpid, $openid);
        $i = array(
            'openid'=>$openid,
            'enroll_key'=>$ek, 
            'create_at'=>time(), 
            'remark'=>mysql_real_escape_string($data->remark)
        ); 
        $i['id'] = $this->model()->insert('xxt_activity_enroll_remark', $i, true);
        $i['nickname'] = $fan->nickname;

        return new ResponseData($i);
    } 
    /**
     * 返回当前用户的报名数据
     *
     * $aid
     */
    public function hasEnrolled_action($aid) 
    {
        $act = $this->model('activity/enroll')->byId($aid);
        /**
         * 检查是否为关注用户
         */
        list($ooid, $osrc) = $this->getOAuthUser($act->mpid);

        if ($this->model('activity/enroll')->hasEnrolled($act->mpid, $aid, $ooid))
            return new ResponseData(true);
        else 
            return new ResponseError('没有报名');
    }
    /**
     * 列出所有的登记记录
     *
     * $mpid
     * $aid
     * $orderby
     * $openid
     * $page
     * $size
     *
     * return
     * [0] 数据列表
     * [1] 数据总条数
     * [2] 数据项的定义
     *
     */
    public function records_action($mpid, $aid, $rid='', $orderby='time', $openid=null, $page=1, $size=10)
    {
        $model = $this->model('activity/enroll');
        $act = $model->byId($aid);

        list($ooid) = $this->getCurrentUserInfo($mpid, $act);

        $options = array(
            'creater' => $openid,
            'visitor' => $ooid,
            'rid' => $rid,
            'page' => $page,
            'size' => $size,
            'orderby' => $orderby
        );

        $rst = $model->getRecords($mpid, $aid, $options);

        return new ResponseData($rst);
    }
    /**
     * 列出当前访问用户所有的登记记录
     *
     * $mpid
     * $aid
     * $orderby
     * $page
     * $size
     *
     * return
     * [0] 数据列表
     * [1] 数据总条数
     * [2] 数据项的定义
     *
     */
    public function myRecords_action($mpid, $aid, $rid='', $orderby='time', $page=1, $size=10)
    {
        $model = $this->model('activity/enroll');
        $act = $model->byId($aid);

        list($openid) = $this->getCurrentUserInfo($mpid, $act);

        $options = array(
            'creater' => $openid,
            'visitor' => $openid,
            'rid' => $rid,
            'page' => $page,
            'size' => $size,
            'orderby' => $orderby
        );

        $rst = $model->getRecords($mpid, $aid, $options);

        return new ResponseData($rst);
    }
    /**
     *
     * $mpid
     * $aid
     */
    public function rounds_action($mpid, $aid)
    {
        $rounds = $this->model('activity/enroll')->getRounds($mpid, $aid);

        return new ResponseData($rounds);
    }
    /**
     * 走马灯抽奖页面
     *
     * todo 和独立的抽奖有冲突
     */
    public function lottery2_action($aid)
    {
        /**
         * 获得活动的定义
         */
        $act = $this->model('activity/enroll')->byId($aid);

        TPL::assign('activity', $act);

        $this->view_action('/activity/enroll/carousel');
    }
    /**
     * 获得当前用户的相关信息
     *
     * todo 认证用户信息如何体现？
     */
    private function getUserInfo($mpid, $openid)
    {
        $user = $this->model('user/fans')->byOpenid($mpid, $openid);

        $members = $this->model('user/member')->byOpenid($mpid, $openid);
        foreach ($members as &$member) {
            if (!empty($member->depts))
                $member->depts = $this->model('user/member')->getDepts($member->mid, $member->depts);
            if (!empty($member->tags))
                $member->tags = $this->model('user/member')->getTags($member->mid, $member->tags);
        }

        $user->members = $members;

        return $user;
    }
}
