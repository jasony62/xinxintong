<?php
require_once dirname(__FILE__).'/matter_ctrl.php';

class article extends matter_ctrl {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';
        return $rule_action;
    }
    /**
     * 判断当前的图文是否允许编辑
     */
    public function view_action($path)
    {
        if (isset($_GET['id']))
            if ($creater = $this->model('matter/article')->byId($_GET['id'], 'creater'))
                TPL::assign('creater', $creater);
        parent::view_action($path);
    }
    /**
     * 获得可见的图文列表
     *
     * $id article's id
     *
     * $src p:从父账号检索图文
     * $id
     * $tag
     * $page
     * $size
     * $order
     * $fields
     *
     */
    public function index_action($src=null, $id=null, $tag=null, $page=1, $size=30, $order='time', $fields=array()) 
    {
        if ($id) {
            $article = $this->getOne($this->mpid, $id);
            return new ResponseData($article);
        } else {
            $uid = TMS_CLIENT::get_client_uid();
            /**
             * 单图文来源 
             */
            $mpid = (!empty($src) && $src==='p') ? $this->getParentMpid() : $this->mpid;
            /**
             * select fields
             */
            $s = "a.id,a.mpid,a.title,a.code,a.summary,a.custom_body,a.create_at,a.modify_at,a.used,a.approved,a.creater,act.nickname creater_name,'$uid' uid";
            /**
             * where
             */
            $w = "a.mpid='$mpid' and a.state=1 and a.creater=act.uid";
            /**
             * 限作者和管理员
             */
            if (!$this->model('mp\permission')->isAdmin($mpid, $uid, true)) {
                $limit = $this->model()->query_value('matter_visible_to_creater', 'xxt_mpsetting', "mpid='$mpid'");
                if ($limit === 'Y')
                    $w .= " and (creater='$uid' or public_visible='Y')";
            }

            if (empty($tag)) {
                $q = array(
                    $s, 
                    'xxt_article a,account act', 
                    $w
                );
                if ($order === 'title')
                    $q2['o'] = 'CONVERT(a.title USING gbk ) COLLATE gbk_chinese_ci';
                else 
                    $q2['o'] = 'a.modify_at desc';
            } else {
                /**
                 * 按标签过滤
                 */
                is_array($tag) && $tag = implode(',',$tag); 
                $w .= " and a.mpid=at.mpid and a.id=at.res_id and at.tag_id in($tag)";
                $q = array(
                    $s, 
                    'xxt_article a,account act,xxt_article_tag at', 
                    $w
                );
                $q2['g'] = 'a.id';
                if ($order === 'title')
                    $q2['o'] = 'count(*),CONVERT(a.title USING gbk ) COLLATE gbk_chinese_ci';
                else 
                    $q2['o'] = 'count(*) desc,a.modify_at desc';
            }
            /**
             * limit
             */
            $q2['r'] = array('o'=>($page-1)*$size, 'l'=>$size);

            if ($articles = $this->model()->query_objs_ss($q, $q2)) {
                /**
                 * amount
                 */
                $q[0] = 'count(*)';
                $amount = (int)$this->model()->query_val_ss($q);
                /**
                 * 获得每个图文的tag
                 */
                foreach ($articles as &$a) {
                    $ids[] = $a->id;
                    $map[$a->id] = &$a;
                }
                $rels = $this->model('tag')->tagsByRes($ids, 'article');
                foreach ($rels as $aid => &$tags)
                    $map[$aid]->tags = $tags;
                return new ResponseData(array($articles, $amount)); 
            }
            return new ResponseData(array(array(),0));
        }
    }
    /**
     * 一个单图文的完整信息
     */
    private function &getOne($mpid, $id) 
    {
        $uid = TMS_CLIENT::get_client_uid();

        $pmpid = isset($_SESSION['mpaccount']->parent_mpid) ? $_SESSION['mpaccount']->parent_mpid : false; 

        $q = array(
            "a.*,act.nickname creater_name,'$uid' uid",
            'xxt_article a,account act',
            "(a.mpid='$mpid' or a.mpid='$pmpid') and a.state=1 and a.id=$id and a.creater=act.uid"
        );
        if ($article = $this->model()->query_obj_ss($q)) {
            /**
             * channels
             */
            $q = array(
                'c.id,c.title,ac.create_at',
                'xxt_article_channel ac,xxt_channel c',
                "ac.article_id=$article->id and ac.channel_id=c.id"
            );
            $q2['o'] = 'ac.create_at desc';
            $article->channels = $this->model()->query_objs_ss($q, $q2);
            /**
             * tags
             */
            $article->tags = $this->model('tag')->tagsByRes($article->id, 'article');
            /**
             * acl
             */
            $article->acl = $this->model('acl')->matter($mpid, 'A', $id);
        }

        return $article;
    }
    /**
     * 图文的阅读情况
     */
    public function read_action($id)
    {
        $model = $this->model('matter/article');

        $reads = $model->readLog($id);

        return new ResponseData($reads);
    }
    /**
     * 获得指定文章的所有评论
     *
     * $id article's id
     */
    public function remarks_action($id, $page=1, $size=30)
    {
        $range = array(
            'p'=>$page, 
            's'=>$size
        );
        $rst = $this->model('matter/article')->remarks($id, null, $range);

        return new ResponseData($rst);
    }
    /**
     * 图文的统计数据
     */
    public function stat_action($id)
    {
        $model = $this->model('matter/article');
        /**
         * 阅读次数
         */
        $stat['readNum'] = $model->readNum($id);
        /**
         * 赞的数量
         */
        $stat['score'] = $model->score($id);

        return new ResponseData($stat);
    }
    /**
     * 创建新图文
     */
    public function create_action()
    {
        /**
         * 单图文在同一个公众号内有唯一编码
         */
        $acode = rand(100000,999999);
        $q = array(
            'count(*)',
            'xxt_article',
            "mpid='$this->mpid' and code='$acode'"
        );
        while (1===(int)$this->model()->query_val_ss($q)) {
            $acode = rand(100000,999999);
        }
        $current = time();
        $d['mpid'] = $this->mpid;
        $d['creater'] = TMS_CLIENT::get_client_uid();
        $d['create_at'] = $current;
        $d['modify_at'] = $current;
        $d['title'] = '新单图文';
        $d['code'] = $acode;
        $d['pic'] = '';
        $d['hide_pic'] = 'N';
        $d['summary'] = '';
        $d['url'] = '';
        $d['body'] = '';
        $id = $this->model()->insert('xxt_article', $d);
        $rsp = $this->model('matter/article')->byId($id);

        return new ResponseData($rsp);
    }
    /**
     * 更新单图文的字段
     *
     * $id article's id
     * $nv pair of name and value
     */
    public function update_action($id) 
    {
        $pmpid = isset($_SESSION['mpaccount']->parent_mpid) ? $_SESSION['mpaccount']->parent_mpid : false; 

        $nv = (array)$this->getPostJson();

        if (isset($nv['body'])) $nv['body'] = mysql_real_escape_string($nv['body']);

        $nv['modify_at'] = time();

        $rst = $this->model()->update(
            'xxt_article', 
            (array)$nv,
            "(mpid='$this->mpid' or mpid='$pmpid') and id='$id'"
        );

        return new ResponseData($rst);
    }
    /**
     * 删除一个单图文
     */
    public function remove_action($id)
    {
        if ($this->model()->delete('xxt_article', "id=$id and used=0")){
            /**
             * 删除数据
             */
            $this->model()->delete('xxt_matter_acl',"mpid='$this->mpid' and matter_type='A' and matter_id=$id");
            return new ResponseData('success');
        } else if ($this->model()->update('xxt_article', array('state'=>0),"id=$id and used=1")){
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
                'xxt_article_channel',
                "article_id=$id and channel_id=$channel->id"
            );
            if (1 === (int)$this->model()->query_val_ss($q)) {
                continue;
            }
            // new 
            $dc['article_id'] = $id;
            $dc['channel_id'] = $channel->id;
            $dc['create_at'] = $current;
            $this->model()->insert('xxt_article_channel', $dc, false);
            $this->model()->update('xxt_channel',array('used'=>1),"id={$channel->id}");
        }
        return new ResponseData('success');
    }
    /**
     *
     */
    public function deleteChannel_action($mpid, $id, $cid) 
    {
        $this->model()->delete('xxt_article_channel', "article_id=$id and channel_id=$cid");
        return new ResponseData('success');
    }
    /**
     * 添加图文的标签
     */
    public function addTag_action($id)
    {
        $tags = $this->getPostJson();

        $this->model('tag')->save(
            $this->mpid, $id, 'article', $tags, null);

        return new ResponseData('success');
    }
    /**
     * 删除图文的标签
     */
    public function removeTag_action($id)
    {
        $tags = $this->getPostJson();

        $this->model('tag')->save(
            $this->mpid, $id, 'article', null, $tags
        );

        return new ResponseData('success');
    }
    /**
     *
     */
    protected function getAclMatterType()
    {
        return 'A';
    }
}
