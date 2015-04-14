<?php
require_once dirname(dirname(__FILE__)).'/member_base.php';
/**
 * 讨论组 
 */
class discuss extends member_base {
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
     * 进入讨论组首页 
     *
     * $mpid
     * $wid
     *
     * 1、进入指定的组
     * 2、所有的组
     */
    public function index_action($mpid, $wid=null, $openid='', $src='', $code=null, $state=null) 
    {
        if (!empty($wid)) {
            if ($code !== null && $state !== null)
                $who = $this->getOAuthUserByCode($mpid, $code);
            else {
                $state =  json_encode(array($mpid, $wid));
                $state = $this->model()->encrypt($state, 'ENCODE', 'discuss');
                if (!empty($openid) && !empty($src)) {
                    /**
                     * 为测试方便使用
                     */
                    $who = array($openid, $src);
                    $encoded = $this->model()->encrypt(json_encode($who), 'ENCODE', $mpid);
                    $this->mySetcookie("_{$mpid}_oauth", $encoded);
                } else {
                    $this->oauth($wall->mpid, $state);
                    $who = null;
                }
                $this->afterOAuth($state, $who);
            }
        } else {
            if ($code !== null && $state !== null)
                $who = $this->getOAuthUserByCode($mpid, $code);
            else {
                $state =  json_encode(array($mpid));
                /**
                 * 为测试方便使用
                 */
                if (!empty($openid) && !empty($src)) {
                    $who = array($openid, $src);
                    $encoded = $this->model()->encrypt(json_encode($who), 'ENCODE', $mpid);
                    $this->mySetcookie("_{$mpid}_oauth", $encoded);
                } else {
                    /**
                     * 讨论组的列表页，显示所有可加入的访问组
                     */
                    $this->oauth($mpid, $state);
                    $who = null;
                }
            }
            $this->afterOAuth($state, $who);
        }
    }
    /**
     * 需要通过OAuth确认用户的身份
     */
    protected function afterOAuth($state, $who=null)
    {
        list($mpid, $wid) = json_decode($this->model()->encrypt($state, 'DECODE', 'discuss'));
        if (isset($wid)) { 
            $wid = $state->wid;
            $model = $this->model('activity/wall');
            $wall = $model->byId($wid, 'wid,mpid,title,entry_ele,entry_css,access_control,authapis');

            list($ooid, $osrc) = !empty($who) ? $who : $this->getCookieOAuthUser($wall->mpid);

            if ($osrc !== 'qy' && $wall->access_control === 'Y')
                $this->accessControl($wall->mpid, $wid, $wall->authapis, array($ooid, $osrc), $wall);

            TPL::assign('title', $wall->title);
            TPL::assign('mpid', $mpid);
            TPL::assign('wid', $wid);
            TPL::assign('ooid', $ooid);
            TPL::assign('entry_ele', $wall->entry_ele);
            TPL::assign('entry_css', $wall->entry_css);
            if ($this->model('activity/wall')->joinedWall($mpid, $wid, $ooid, $osrc))
                TPL::assign('joined', 'Y');

            $this->view_action('/activity/discuss/main');
        } else {
            /**
             * 所有处于活动状态的讨论组
             * todo 目前不支持对讨论组分组，为了把信息墙排除在外，列表中只显示需要进行访问控制的
             */
            $q = array(
                '*', 
                'xxt_wall', 
                "mpid='$mpid' and active='Y' and access_control='Y'"
            );
            $q2['o'] = 'create_at desc';
            $walls = $this->model()->query_objs_ss($q);

            list($ooid, $osrc) = !empty($who) ? $who : $this->getCookieOAuthUser($mpid);
            /**
             * 当前用户可访问的讨论组
             */
            $q = array(
                '*',
                'xxt_member',
                "mpid='$mpid' and ooid='$ooid' and osrc='$osrc'"
            );
            $me = $this->model()->query_obj_ss($q);
            $mywalls = array();
            foreach ($walls as $wall) {
                if ($this->model('acl')->canAccessAct($mpid, $wall->wid, 'W', $me, $wall->authapis))
                    $mywalls[] = $wall;
            }
            if (count($mywalls) === 1) {
                $this->redirect('/rest/activity/discuss?wid='.$mywalls[0]->wid);
            } else {
                TPL::assign('ooid', $ooid);
                TPL::assign('title', '讨论组');
                TPL::assign('walls', $mywalls);
                $this->view_action('/activity/discuss/index');
            }
        }
    }
    /**
     *
     */
    protected function canAccessObj($mpid, $wid, $member, $authapis, $wall)
    {
        return $this->model('acl')->canAccessAct($mpid, $wid, 'W', $member, $authapis);
    }
    /**
     * 用户进入讨论组
     *
     * $wid
     */
    public function join_action($wid)
    {
        if (empty($wid))
            return new ResponseError('参数错误');

        $model = $this->model('activity/wall');
        $wall = $model->byId($wid, 'mpid');

        if (false === ($fan = $this->myGetcookie("_{$wall->mpid}_oauth")))
            return new ResponseError('无法获得用户身份');

        $fan = json_decode($this->model()->encrypt($fan, 'DECODE', $wall->mpid));
        list($ooid, $osrc) = $fan;

        $reply = $model->join($wall->mpid, $wid, $ooid, $osrc);

        $message = array(
            "touser"=>$ooid,
            "msgtype"=>"text",
            "text"=>array(
                "content"=>urlencode($reply)
            )
        );
        $this->send_to_qyuser($wall->mpid, $message);

        return new ResponseData('success');
    }
    /**
     * 用户退出讨论组
     *
     * $mpid
     * $wid
     */
    public function unjoin_action($mpid, $wid)
    {
        if (empty($mpid)) return new ResponseError('mpid为空');
        if (empty($wid)) return new ResponseError('wid为空');

        list($ooid, $osrc) = $this->getCookieOAuthUser();
        if (empty($ooid)) return new ResponseError('无法获得用户身份');

        $model = $this->model('activity/wall');
        $reply = $model->unjoin($mpid, $wid, $ooid, $osrc);

        $message = array(
            "touser"=>$ooid,
            "msgtype"=>"text",
            "text"=>array(
                "content"=>urlencode($reply)
            )
        );
        $this->send_to_qyuser($mpid, $message);

        return new ResponseData('success');
    }
    /**
     * 讨论组内的指定用户
     *
     * $wid
     */
    public function members_action($wid)
    {
        $model = $this->model('activity/wall');
        $wall = $model->byId($wid, 'mpid,title,user_url');

        $users = $this->model('acl')->wallUsers($wall->mpid, $wid);

        $q = array(
            'f.nickname,f.headimgurl,f.openid,f.src,m.mid,m.depts,m.tags',
            'xxt_member m,xxt_fans f',
            "m.mpid='$wall->mpid' and m.mpid=f.mpid and m.fid=f.fid and f.openid in('".implode("','", $users)."')"
        );

        $members = $this->model()->query_objs_ss($q);
        foreach ($members as &$member)
            $member->depts = $this->model('user/member')->getDepts($member->mid, $member->depts);

        TPL::assign('title', "$wall->title".'成员');
        TPL::assign('members', $members);
        TPL::assign('member_url', urlencode($wall->user_url));
        $this->view_action('/activity/discuss/members');
    }
    /**
     *
     */
    public function wall_action($mpid, $wid)
    {
        $model = $this->model('activity/wall');
        if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
            /**
             * define data.
             */
            $last = $this->getGet('last', 0);
            $m = $model->approvedMessages($mpid, $wid, $last);
            return new ResponseData($m);
        } else {
            $w = $model->byId($wid, 'title,body_css');
            TPL::assign('title', $w->title);
            TPL::assign('body_css', $w->body_css);
            $params = array();
            $params['mpid'] = $mpid;
            $params['wid'] = $wid;
            TPL::assign('params', $params);

            $this->view_action('/activity/discuss/wall');
        }
    }
}
