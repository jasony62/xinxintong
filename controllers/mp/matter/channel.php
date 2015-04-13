<?php
require_once dirname(__FILE__).'/matter_ctrl.php';

class channel extends matter_ctrl {
    /**
     *
     * $src 是否从父账号获取资源
     * $cascade 是否获得频道内的素材和访问控制列表
     */
    public function index_action($src=null, $cascade='y') 
    {
        $uid = TMS_CLIENT::get_client_uid();
        /**
         * 素材的来源
         */
        $mpid = (!empty($src) && $src==='p') ? $this->getParentMpid() : $this->mpid;

        $q = array(
            "*,a.nickname creater_name,'$uid' uid",
            'xxt_channel c,account a', 
            "c.mpid='$mpid' and c.state=1 and c.creater=a.uid"
        );
        /**
         * 仅限作者和管理员？
         */
        if (!$this->model('mp\permission')->isAdmin($mpid, $uid, true)) {
            $visible = $this->model()->query_value('matter_visible_to_creater', 'xxt_mpsetting', "mpid='$mpid'");
            if ($visible === 'Y')
                $q[2] .= " and (creater='$uid' or public_visible='Y')";
        }

        $q2['o'] = 'create_at desc';
        $channels = $this->model()->query_objs_ss($q, $q2);
        /**
         * 获得子资源
         */
        if ($channels && $cascade == 'y') {
            foreach ($channels as $c) {
                /**
                 * matters
                 */
                $c->matters = $this->model('matter/channel')->getMatters($c->id, $c);
                /**
                 * acl
                 */
                $c->acl = $this->model('acl')->matter($mpid, 'C', $c->id);
            }
        }

        return new ResponseData($channels);
    }
    /**
     * 获得频道的子资源
     */
    public function cascade_action($id) 
    {
        $model = $this->model('matter/channel');
        /**
         *
         */
        $channel = $model->byId($id);
        /**
         * matters
         */
        $c['matters'] = $model->getMatters($id, $channel);
        /**
         * acl
         */
        $c['acl'] = $this->model('acl')->matter($channel->mpid, 'C', $id);

        return new ResponseData($c);
    }
    /**
     * 创建一个平道素材
     */
    public function create_action()
    {
        $uid = TMS_CLIENT::get_client_uid();

        $d = (array)$this->getPostJson();

        $d['mpid'] = $this->mpid;
        $d['creater'] = $uid;
        $d['create_at'] = time();
        $id = $this->model()->insert('xxt_channel', $d, true);

        $q = array(
            "c.*,a.nickname creater_name,'$uid' uid",
            'xxt_channel c,account a', 
            "c.id=$id and c.creater=a.uid"
        );
        $channel = $this->model()->query_obj_ss($q);

        return new ResponseData($channel);
    }
    /**
     * 更新频道的属性信息
     *
     * $id channel's id
     * $nv pair of name and value
     */
    public function update_action($id) 
    {
        $nv = $this->getPostJson();

        $rst = $this->model()->update('xxt_channel', 
            (array)$nv,
            "mpid='$this->mpid' and id=$id"
        );

        return new ResponseData($rst);
    }
    /**
     *
     * $id channel's id.
     * $pos top|bottom
     *
     * post
     * $t matter's type.
     * $id matter's id.
     *
     */
    public function setfixed_action($id, $pos) 
    {
        $matter = $this->getPostJson();

        if ($pos === 'top') {
            $this->model()->update('xxt_channel', 
                array(
                    'top_type'=>empty($matter->t) ? $matter->t : ucfirst($matter->t), 
                    'top_id'=>$matter->id
                ),
                "mpid='$this->mpid' and id=$id"
            );
        } else if ($pos === 'bottom') {
            $this->model()->update('xxt_channel', 
                array(
                    'bottom_type'=>empty($matter->t) ? $matter->t : ucfirst($matter->t), 
                    'bottom_id'=>$matter->id
                ),
                "mpid='$this->mpid' and id=$id"
            );
        }

        $matters = $this->model('matter/channel')->getMatters($id);

        return new ResponseData($matters);
    }
    /**
     * 删除频道中的图文
     *
     * 需要重新获得频道中的图文
     */
    public function removematter_action($id)
    {
        $removed = $this->getPostJson();

        $model = $this->model('matter/channel');

        $model->removeMatter($id, $removed);

        $matters = $model->getMatters($id);

        return new ResponseData($matters);
    }
    /**
     * 删除频道
     */
    public function delete_action($id)
    {
        if ($this->model()->delete('xxt_channel', "id=$id and used=0")){
            /**
             * 删除数据
             */
            $this->model()->delete('xxt_article_channel', "channel_id=$id");
            $this->model()->delete('xxt_link_channel', "channel_id=$id");
            $this->model()->delete('xxt_matter_acl',"mpid='$this->mpid' and matter_type='C' and matter_id=$id");
            return new ResponseData('success');
        } else if ($this->model()->update('xxt_channel', array('state'=>0),"id=$id and used=1")){
            /**
             * 将数据标记为删除
             */
            return new ResponseData('success');
        }
        return new ResponseError('数据无法删除！');
    }
    /**
     *
     */
    protected function getAclMatterType()
    {
        return 'C';
    }
}
