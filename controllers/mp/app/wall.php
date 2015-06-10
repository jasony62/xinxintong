<?php
namespace mp\app;

require_once dirname(__FILE__).'/base.php';
/**
 *
 */
class wall extends app_base {
    /**
     *
     */
    protected function getMatterType() 
    {
        return 'wall';
    }
    /**
     *
     */
    public function index_action() 
    {
        $this->view_action('/mp/app/wall');
    }
    
    /**
     *
     */
    public function get_action($wid=null, $src=null) 
    {
        if ($wid) {
            /**
             * wall url
             */
            $w = $this->model('app\wall')->byId($wid, '*');
            $w->wallUrl = "http://";
            $w->wallUrl .= $_SERVER['HTTP_HOST'];
            $w->wallUrl .= "/rest/app/wall/wall?mpid=$w->mpid&wid=$wid";
            /**
             * acl 
             */
            $w->acl = $this->model('acl')->byMatter($this->mpid, 'wall', $wid);

            return new \ResponseData($w);
        } else {
            /**
             * wall list
             */
            $q = array('*', 'xxt_wall');
            if ($src === 'p') {
                $pmpid = $this->getParentMpid();
                $q[2] = "mpid='$pmpid'";
            } else
                $q[2] = "mpid='$this->mpid'";

            $q2['o'] = 'create_at desc';

            $w = $this->model()->query_objs_ss($q, $q2);

            return new \ResponseData($w);
        }
    }
    /**
     * 创建一个讨论组
     */
    public function create_action() 
    {
        $wid = uniqid();
        $newone['id'] = $wid;
        $newone['mpid'] = $this->mpid;
        $newone['title'] = '新讨论组';
        $newone['creater'] = \TMS_CLIENT::get_client_uid();
        $newone['create_at'] = time();
        $newone['quit_cmd'] = 'q';
        $newone['join_reply'] = '欢迎加入';
        $newone['quit_reply'] = '已经退出';

        $this->model()->insert('xxt_wall', $newone, false);

        return new \ResponseData($wid);
    }
    /**
     * submit basic.
     */
    public function update_action($wid) 
    {
        $nv = $this->getPostJson();
        if (isset($nv->title))
            $nv->title = $this->model()->escape($nv->title);
        else if (isset($nv->join_reply))
            $nv->join_reply = $this->model()->escape($nv->join_reply);
        else if (isset($nv->quit_reply))
            $nv->quit_reply = $this->model()->escape($nv->quit_reply);
        else if (isset($nv->entry_ele))
            $nv->entry_ele = $this->model()->escape($nv->entry_ele);
        else if (isset($nv->entry_css))
            $nv->entry_css = $this->model()->escape($nv->entry_css);
        else if (isset($nv->body_css))
            $nv->body_css = $this->model()->escape($nv->body_css);

        $rst = $this->model()->update('xxt_wall', (array)$nv, "id='$wid'");

        return new \ResponseData($rst);
    }
    /**
     * 获得所有消息
     */
    public function messages_action($wid, $page=1, $size=30, $contain=null)
    {
        $contain = isset($contain) ? explode(',',$contain) : array();
        $messages = $this->model('app\wall')->messages($this->mpid, $wid, $page, $size, $contain);

        return new \ResponseData($messages);
    }
    /**
     * 获得墙内的所有用户
     */
    public function users_action($wid)
    {
        $q = array(
            'e.openid,e.join_at,e.last_msg_at,f.nickname',
            'xxt_wall_enroll e,xxt_fans f',
            "e.mpid='$this->mpid' and e.wid='$wid' and e.close_at=0 and e.mpid=f.mpid and e.openid=f.openid"
        );

        $users = $this->model()->query_objs_ss($q);

        return new \ResponseData($users);
    }
    /**
     * 将所有用户退出信息墙 
     */
    public function quitWall_action($wid)
    {
        /**
         * 清除所有加入的人
         */
        $rst = $this->model()->delete('xxt_wall_enroll', "wid='$wid'");

        return new \ResponseData($rst);
    }
    /**
     * 清空信息墙的所有数据
     */
    public function resetWall_action($wid)
    {
        /**
         * 清除所有加入的人
         */
        $this->model()->delete('xxt_wall_enroll', "wid='$wid'");
        /**
         * 清除所有留言
         */
        $rst = $this->model()->delete('xxt_wall_log', "wid='$wid'");

        return new \ResponseData($rst);
    }
    /**
     * 获得未审核的消息
     */
    public function pendingMessages_action($wid,$last=0)
    {
        $messages = $this->model('app\wall')->pendingMessages($this->mpid, $wid, $last);

        return new \ResponseData(array($messages, time()));
    }
    /**
     * 批准消息上墙
     *
     * 如果需要推送消息，将上墙信息推送给墙内所有用户
     *
     * $wid 信息墙ID 
     * $id 消息ID
     *
     */
    public function approve_action($wid, $id)
    {
        $model = $this->model('app\wall');
        /**
         * 批准消息
         */
        $v = $model->approve($this->mpid, $wid, $id, $this);
        /**
         * 是否需要推送消息
         */
        $wall = $model->byId($wid, 'quit_cmd,skip_approve,push_others,quit_reply,user_url');

        if ('Y' === $wall->push_others) {
            $approvedMsg = $model->messageById($wid, $id);

            $openid = $approvedMsg->openid;

            switch ($approvedMsg->data_type) {
            case 'text':
                $msg = array(
                    'type'=>$approvedMsg->data_type,
                    'data'=>$approvedMsg->data
                );
                break;
            case 'image':
                $msg = array(
                    'type'=>$approvedMsg->data_type,
                    'data'=>array($approvedMsg->data_media_id, $approvedMsg->data)
                );
                break;
            }
            $model->push_others($this->mpid, $openid, $msg, $wall, $wid, $this);
        }

        return new \ResponseData($v);
    }
    /**
     * 拒绝消息上墙
     *
     * $wid 
     * $id
     *
     */
    public function reject_action($wid, $id)
    {
        $v = $this->model('app\wall')->reject($wid, $id);

        return new \ResponseData($v);
    }
}
