<?php
require_once dirname(dirname(__FILE__)).'/xxt_base.php';

class article extends xxt_base {
    /**
     *
     */
    private $box;
    /**
     *
     */
    public function __construct() 
    {
        if (isset($_SESSION['WRITER_BOX']))
            $this->box = $_SESSION['WRITER_BOX'];
        else 
            $this->box = false;
    }
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
     * 获得图文 
     */
    public function index_action($id)
    {
        $article = $this->model('matter/article')->byId($id);
        /**
         * 每个公众账号的投稿人，有自己独立的图库
         */
        $galleryId = $this->box->src;
        $galleryId .= $this->box->mpid;
        $galleryId .= $this->box->openid;
        $galleryId = md5($galleryId);
        $article->galleryId = $galleryId;
        /**
         * 是否支持按文章编号检索
         */
        if ($k = $this->model('reply')->canCodesearch($this->box->mpid)) {
            $article->codesearchKeyword = $k;
        }
        /**
         * tags
         */
        $article->tags = $this->model('tag')->tagsByRes($article->id, 'article');

        return new ResponseData($article);

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
        if ($this->box === false)
            die('invalid data.');
        /**
         * 单图文在同一个公众号内有唯一编码
         */
        $acode = rand(100000,999999);
        $q = array(
            'count(*)',
            'xxt_article',
            "mpid='{$this->box->mpid}' and code='$acode'"
        );
        while (1===(int)$this->model()->query_val_ss($q)) {
            $acode = rand(100000,999999);
        }

        $current = time();
        $d['mpid'] = $this->box->mpid;
        $d['writer'] = $this->box->openid;
        $d['src'] = $this->box->src;
        $d['create_at'] = $current;
        $d['modify_at'] = $current;
        $d['title'] = '新单图文';
        $d['code'] = $acode;
        $d['pic'] = '';
        $d['hide_pic'] = 'N';
        $d['summary'] = '';
        $d['url'] = '';
        $d['body'] = '';
        $d['approved'] = 'N'; //等待审核
        
        $id = $this->model()->insert('xxt_article', $d);

        $rsp = $this->model('matter/article')->byId($id);

        return new ResponseData($rsp);
    }
    /**
     *
     * $id article's id
     * $nv pair of name and value
     */
    public function update_action($id) 
    {
        $nv = $this->getPostJson();

        if ($nv[0] == 'body') $nv[1] = mysql_real_escape_string($nv[1]);
        $rst = $this->model()->update(
            'xxt_article', 
            array($nv[0]=>$nv[1], 'modify_at'=>time()),
            "mpid='{$this->box->mpid}' and id='$id'"
        );

        return new ResponseData($rst);
    }
    /**
     * 删除图文
     *
     * 如果图文已经被使用，则标记为不可见
     */
    public function remove_action($id)
    {
        if ($this->model()->delete('xxt_article', "id=$id and used=0")){
            /**
             * 删除数据
             */
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
     * 获得图文可用的标签
     */
    public function tag_action() 
    {
        $tags = $this->model('tag')->get_tags($this->box->mpid);

        return new ResponseData($tags);
    }
    /**
     * 添加图文的标签
     */
    public function addTag_action($id)
    {
        $tags = $this->getPostJson();

        $this->model('tag')->save(
            $this->box->mpid, $id, 'article', $tags, null);

        return new ResponseData('success');
    }
    /**
     * 删除图文的标签
     */
    public function removeTag_action($id)
    {
        $tags = $this->getPostJson();

        $this->model('tag')->save(
            $this->box->mpid, $id, 'article', null, $tags
        );

        return new ResponseData('success');
    }
}
