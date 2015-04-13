<?php
require_once dirname(__FILE__).'/matter_ctrl.php';

class link extends matter_ctrl {
    /**
     *
     */
    public function index_action($src=null, $id=null, $cascade='y') 
    {
        $uid = TMS_CLIENT::get_client_uid();

        $pmpid = isset($_SESSION['mpaccount']->parent_mpid) ? $_SESSION['mpaccount']->parent_mpid : false;

        if (!empty($id)) {
            $q = array(
                "l.*,a.nickname creater_name,'$uid' uid",
                'xxt_link l,account a',
                "(l.mpid='$this->mpid' or l.mpid='$pmpid') and l.id='$id' and l.state=1 and l.creater=a.uid"
            );
            $link = $this->model()->query_obj_ss($q);
            /**
             * params
             */
            $q = array(
                'id,pname,pvalue,authapi_id',
                'xxt_link_param',
                "link_id='$id'"
            );
            $link->params = $this->model()->query_objs_ss($q);
            /**
             * channels
             */
            $q = array(
                'c.id,c.title,lc.create_at',
                'xxt_link_channel lc,xxt_channel c',
                "lc.link_id='$id' and lc.channel_id=c.id"
            );
            $q2['o'] = 'lc.create_at desc';
            $link->channels = $this->model()->query_objs_ss($q, $q2);
            /**
             * acl
             */
            $link->acl = $this->model('acl')->matter($this->mpid, 'L', $id);

            return new ResponseData($link);
        } else {
            /**
             * 本公众号内的素材
             */
            $mpid = (!empty($src) && $src==='p') ? $this->getParentMpid() : $this->mpid;
            /**
             * get links
             */
            $q = array(
                "l.*,a.nickname creater_name,'$uid' uid",
                'xxt_link l,account a',
                "l.mpid='$mpid' and l.state=1 and l.creater=a.uid"
            );
            /**
             * 仅限作者和管理员？
             */
            if (!$this->model('mp\permission')->isAdmin($mpid, $uid, true)) {
                $limit = $this->model()->query_value('matter_visible_to_creater', 'xxt_mpsetting', "mpid='$mpid'");
                if ($limit === 'Y')
                    $q[2] .= " and (creater='$uid' or public_visible='Y')";
            }

            $q2['o'] = 'create_at desc';
            $links = $this->model()->query_objs_ss($q, $q2);
            /**
             * get params and channels
             */
            if ($cascade === 'y') {
                foreach($links as $l) {
                    /**
                     * params
                     */
                    $q = array('id,pname,pvalue,authapi_id',
                        'xxt_link_param',
                        "link_id=$l->id");
                    $l->params = $this->model()->query_objs_ss($q);
                    /**
                     * channels
                     */
                    $q = array('c.id,c.title,lc.create_at',
                        'xxt_link_channel lc,xxt_channel c',
                        "lc.link_id=$l->id and lc.channel_id=c.id");
                    $q2['o'] = 'lc.create_at desc';
                    $l->channels = $this->model()->query_objs_ss($q, $q2);
                    /**
                     * acl
                     */
                    $l->acl = $this->model('acl')->matter($mpid, 'L', $l->id);
                }
            }

            return new ResponseData($links);
        }
    }
    /**
     *
     */
    public function cascade_action($id)
    {
        /**
         * params
         */
        $q = array(
            'id,pname,pvalue,authapi_id',
            'xxt_link_param',
            "link_id='$id'"
        );
        $l['params'] = $this->model()->query_objs_ss($q);
        /**
         * channels
         */
        $q = array(
            'c.id,c.title,lc.create_at',
            'xxt_link_channel lc,xxt_channel c',
            "lc.link_id='$id' and lc.channel_id=c.id"
        );
        $q2['o'] = 'lc.create_at desc';
        $l['channels'] = $this->model()->query_objs_ss($q, $q2);
        /**
         * acl
         */
        $l['acl'] = $this->model('acl')->matter($this->mpid, 'L', $id);

        return new ResponseData($l);
    }
    /**
     * 创建外部链接素材
     */
    public function create_action($title='新外部链接')
    {
        $uid = TMS_CLIENT::get_client_uid(); 
        $d['mpid'] = $this->mpid;
        $d['creater'] = $uid;
        $d['create_at'] = time();
        $d['title'] = $title;

        $id = $this->model()->insert('xxt_link', $d, true);

        $q = array(
            "l.*,a.nickname creater_name,'$uid' uid",
            'xxt_link l,account a',
            "l.id=$id and l.creater=a.uid"
        );

        $link = $this->model()->query_obj_ss($q);

        return new ResponseData($link);
    }
    /**
     * 删除链接
     */
    public function remove_action($id)
    {
        if ($this->model()->delete('xxt_link', "id=$id and used=0")){
            // 删除和频道的关联
            $this->model()->delete('xxt_link_channel',"link_id=$id");
            // 删除链接参数
            $this->model()->delete('xxt_link_param',"link_id=$id");
            // 删除ACL
            $this->model()->delete('xxt_matter_acl',"mpid='$this->mpid' and matter_type='L' and matter_id=$id");
            return new ResponseData('success');
        } else if ($this->model()->update('xxt_link', array('state'=>0),"id=$id and used=1")){
            /**
             * 标记为删除
             */
            return new ResponseData('success');
        }
        return new ResponseError('数据无法删除！');
    }
    /**
     * 更新链接属性
     */
    public function update_action($id) 
    {
        $nv = $this->getPostJson();
        $ret = $this->model()->update('xxt_link', $nv, "mpid='$this->mpid' and id=$id");

        return new ResponseData($ret);
    }
    /**
     *
     * $linkid link's id
     */
    public function addParam_action($linkid) 
    {
        $p['link_id'] = $linkid;

        $id = $this->model()->insert('xxt_link_param', $p);

        return new ResponseData($id);
    }
    /**
     *
     * 更新参数定义
     *
     * 因为参数的属性之间存在关联，因此要整体更新
     *
     * $id parameter's id
     */
    public function updateParam_action($id) 
    {
        $p = $this->getPostJson();

        $rst = $this->model()->update(
            'xxt_link_param', 
            (array)$p, 
            "id=$id"
        );

        return new ResponseData($rst);
    }
    /**
     *
     * $id parameter's id
     */
    public function removeParam_action($id) 
    {
        $rst = $this->model()->delete('xxt_link_param', "id=$id");

        return new ResponseData($rst);
    }
    /**
     *
     * $id link's id.
     */
    public function addChannel_action($id) 
    {
        $c = $this->getPostJson();
        /**
         * insert new relation.
         */
        $current = time();
        foreach ($c as $channel) {
            // check
            $q = array(
                'count(*)',
                'xxt_link_channel',
                "link_id=$id and channel_id=$channel->id"
            );
            if (1 === (int)$this->model()->query_val_ss($q)) {
                continue;
            }
            // new 
            $dc['link_id'] = $id;
            $dc['channel_id'] = $channel->id;
            $dc['create_at'] = $current;
            $this->model()->insert('xxt_link_channel', $dc, false);
            $this->model()->update('xxt_channel',array('used'=>1),"id={$channel->id}");
        }
        return new ResponseData('success');
    }
    /**
     *
     */
    public function deleteChannel_action($id, $cid) 
    {
        $rst = $this->model()->delete('xxt_link_channel', "link_id=$id and channel_id=$cid");

        return new ResponseData($rst);
    }
    /**
     *
     */
    protected function getAclMatterType()
    {
        return 'L';
    }
}
