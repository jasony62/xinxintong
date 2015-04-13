<?php
require_once dirname(__FILE__).'/matter_ctrl.php';

class news extends matter_ctrl {
    /**
     *
     */
    public function index_action($src=null, $cascade='y') 
    {
        $uid = TMS_CLIENT::get_client_uid();
        /**
         * 素材的来源
         */
        $mpid = (!empty($src) && $src==='p') ? $this->getParentMpid() : $this->mpid;

        $q = array(
            "n.*,a.nickname creater_name,'$uid' uid",
            'xxt_news n,account a',
            "n.mpid='$mpid' and n.state=1 and n.creater=a.uid"
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
        $news = $this->model()->query_objs_ss($q, $q2);
        /**
         * 获得子资源
         */
        if ($news) {
            foreach ($news as &$n) {
                if ($n->empty_reply_type && $n->empty_reply_id) {
                    $m = $this->model('matter/base')->get_by_id($n->empty_reply_type, $n->empty_reply_id);
                    $m->type = $n->empty_reply_type;
                    $n->emptyReply = $m;
                }
                if ( $cascade === 'y') {
                    $n->stuffs = $this->model('matter/news')->getMatters($n->id);
                    $n->acl = $this->model('acl')->matter($mpid, 'N', $n->id);
                }
            }
        }
        return new ResponseData($news);
    }
    /**
     *
     */
    public function cascade_action($id) 
    {
        /**
         * 包含的素材
         */
        $n['stuffs'] = $this->model('matter/news')->getMatters($id);

        $n['acl'] = $this->model('acl')->matter($this->mpid, 'N', $id);

        return new ResponseData($n);
    }
    /**
     *
     */
    public function update_action($id, $nv) 
    {
        $nv = (array)$this->getPostJson();

        $rst = $this->model()->update(
            'xxt_news', 
            $nv,
            "mpid='$this->mpid' and id=$id"
        );

        return new ResponseData($rst);
    }
    /**
     *
     */
    public function updateStuff_action($id) 
    {
        $s = $this->getPostJson();
        /**
         * delete relation.
         */
        $this->model()->delete('xxt_news_matter', "news_id=$id");
        /**
         * insert new relation.
         */
        $this->assign_news_stuff($id, $s);

        return new ResponseData('success');
    }
    /**
     *
     */
    private function assign_news_stuff($news_id, &$stuffs) 
    {
        foreach ($stuffs as $i=>$s) {
            $stuff_id = $s->id;
            $stuff_type = $s->type;
            $ns['news_id'] = $news_id;
            $ns['matter_id'] = $stuff_id;
            $ns['matter_type'] = $stuff_type;
            $ns['seq'] = $i;
            $this->model()->insert('xxt_news_matter', $ns);
            /**
             * set stuff state.
             */
            if ($stuff_type == 'Article')
                $this->model()->update('xxt_article',array('used'=>1),"id=$stuff_id");
            elseif ($stuff_type == 'Link')
                $this->model()->update('xxt_link',array('used'=>1),"id=$stuff_id");
        }
        return true;
    }
    /**
     * 创建一个多图文素材
     */
    public function create_action() 
    {
        $uid = TMS_CLIENT::get_client_uid();
        $news = $this->getPostJson();

        $d = array();
        $d['mpid'] = $this->mpid;
        $d['creater'] = $uid;
        $d['create_at'] = time();
        $d['title'] = isset($news->title) ? $news->title : '新多图文';
        $id = $this->model()->insert('xxt_news', $d, true);
        /**
         * stuffs
         */
        if (isset($news->stuffs))
            $this->assign_news_stuff($id, $news->stuffs);

        $q = array(
            "n.*,a.nickname creater_name,'$uid' uid",
            'xxt_news n,account a',
            "n.id='$id' and n.state=1 and n.creater=a.uid"
        );
        $news = $this->model()->query_obj_ss($q);

        $news->stuffs = $this->model('matter/news')->getMatters($news->id);

        return new ResponseData($news);
    }
    /**
     * 删除一个多图文素材
     */
    public function delete_action($id)
    {
        if ($this->model()->delete('xxt_news', "id=$id and used=0")){
            /**
             * 删除数据
             */
            $this->model()->delete('xxt_news_matter', "news_id=$id");
            $this->model()->delete('xxt_matter_acl',"mpid='$this->mpid' and matter_type='N' and matter_id=$id");
            return new ResponseData('success');
        } else if ($this->model()->update('xxt_news', array('state'=>0), "mpid='$this->mpid' and id=$id and used=1"))
            return new ResponseData('success');

        return new ResponseError('数据无法删除！');
    }
    /**
     *
     */
    protected function getAclMatterType()
    {
        return 'N';
    }
    /**
     * 内容为空时的回复
     */
    public function setEmptyReply_action($id)
    {
        $matter = $this->getPostJson();

        $ret = $this->model()->update(
            'xxt_news', 
            array(
                'empty_reply_type'=>ucfirst($matter->mt), 
                'empty_reply_id'=>$matter->mid
            ),
            "mpid='$this->mpid' and id='$id'"
        );

        return new ResponseData($ret);
    }
}
