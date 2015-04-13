<?php
require_once dirname(__FILE__).'/article_base.php';

class article_model extends article_base {
    /**
     *
     */
    protected function table()
    {
        return 'xxt_article';
    }
    /**
     * 这个是基类要求的方法
     * todo 应该用抽象类的机制处理
     */
    public function &getMatters($id) 
    {
        $article = $this->byId($id, "id,mpid,title,summary,pic,'Article' type");
        $articles = array($article);

        return $articles;
    }
    /**
     * 返回进行推送的消息格式
     */
    public function &getArticles($id) 
    {
        $article = $this->byId($id, 'id,mpid,title,summary,pic,body');
        $articles = array($article);

        return $articles;
    }
    /**
     * 根据文章的编号检索文章
     */
    public function &byCode($mpid, $code)
    {
        $q = array(
            '*',
            'xxt_article',
            "mpid='$mpid' and code='$code'"
        );
        $a = $this->query_obj_ss($q);

        return $a;
    }
    /**
     * 文章打开的次数
     * todo 应该用哪个openid，根据oauth是否开放来决定？
     */
    public function readLog($id)
    {
        $q = array(
            'f.fid,f.nickname,f.src,f.openid,l.read_at',
            'xxt_matter_read_log l,xxt_fans f',
            "l.mpid=f.mpid and l.matter_type='article' and l.matter_id='$id' and l.osrc=f.src and l.ooid=f.openid"
        );

        $log = $this->query_objs_ss($q);

        return $log;
    }
    /**
     * 当前访问用户是否已经点了赞
     */
    public function praised($vid, $article_id)
    {
        $q = array(
            'score',
            'xxt_article_score',
            "article_id='$article_id' and vid='$vid'"
        );

        return 1 === (int)$this->query_val_ss($q);
    }
    /**
     * 文章总的赞数
     */
    public function score($id)
    {
        $q = array(
            'count(*)',
            'xxt_article_score',
            "article_id='$id'" 
        );
        $score = $this->query_val_ss($q);

        return $score;
    }
    /**
     * 文章打开的次数
     */
    public function readNum($id)
    {
        $q = array(
            'count(*)',
            'xxt_matter_read_log',
            "matter_type='article' and matter_id='$id'"
        );

        $num = $this->query_val_ss($q);

        return $num;
    }
    /**
     * 文章评论
     *
     * $range 分页参数
     */
    public function remarks($articleId, $remarkId=null, $range=false)
    {
        $q = array(
            'r.*,m.email,m.mobile',
            'xxt_article_remark r,xxt_member m',
            "r.article_id='$articleId' and m.forbidden='N' and r.mid=m.mid"
        );

        if (!$range) {
            /**
             * 全部数据
             */
            if (empty($remarkId)) {
                $q2 = array('o'=>'r.create_at desc');
                $remarks = $this->query_objs_ss($q, $q2);
            } else {
                $q[2] .= " and id='$remarkId'";
                $remarks = $this->query_obj_ss($q);
            }
            return $remarks;
        } else {
            /**
             * 分页数据
             */
            $q2 = array(
                'o'=>'r.create_at desc',
                'r'=>array(
                    'o'=>(($range['p']-1)*$range['s']),
                    'l'=>($range['s'])
                )
            );
            $remarks = $this->query_objs_ss($q, $q2);
            /**
             * 总数
             */
            $q[0] = 'count(*)';
            $amount = $this->query_val_ss($q);

            return array($remarks, $amount);
        }
    }
    /**
     * 全文检索单图文，将符合条件的结果组成多图文
     */
    public function fullsearch_its($mpid, $keyword, $page = 1, $limit = 5) 
    {
        $s = 'id,mpid,title,summary,pic';
        $f = 'xxt_article';
        $w = "mpid='$mpid' and state=1 and approved='Y'";
        $w .= " and (title like '%$keyword%'";
        $w .= "or summary like '%$keyword%'";
        $w .= "or body like '%$keyword%')";
        $q = array($s, $f, $w);

        $q2['o'] = 'create_at desc';
        $q2['r']['o'] = ($page-1)*$limit;
        $q2['r']['l'] = $limit;

        $articles = parent::query_objs_ss($q, $q2);

        return $articles;
    }
}
