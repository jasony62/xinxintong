<?php
namespace matter;

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
    *
    */
    public function getTypeName()
    {
        return 'article';
    }
    /**
    *
    */
    public function &byCreater($mpid, $creater, $fields='*', $cascade=false)
    {
        $q = array(
            $fields,
            'xxt_article',
            "mpid='$mpid' and creater='$creater' and state=1");
        $q2 = array('o'=>'modify_at desc');

        $articles = $this->query_objs_ss($q, $q2);
        
        if (!empty($articles) && $cascade) foreach ($articles as &$a) {
            $a->channels = \TMS_APP::M('matter\channel')->byMatter($a->id, 'article');
        }

        return $articles;
    }
    /**
     * 根据投稿来源
     */
    public function &byEntry($mpid, $entry, $creater, $fields='*', $cascade=false)
    {
        $q = array(
            $fields,
            'xxt_article',
            "mpid='$mpid' and entry='$entry' and creater='$creater' and state=1");
        $q2 = array('o'=>'modify_at desc');

        $articles = $this->query_objs_ss($q, $q2);
        
        if (!empty($articles) && $cascade) foreach ($articles as &$a) {
            $a->channels = \TMS_APP::M('matter\channel')->byMatter($a->id, 'article');
        }

        return $articles;
    }
    /**
     * $mid member's 仅限认证用户
     * $entry 指定的投稿活动
     * $phase 
     */
    public function &byReviewer($mid, $entry, $phase, $fields='*', $cascade=false)
    {
        $q = array(
            'a.*',
            'xxt_article a',
            "a.entry='$entry' and exists(select 1 from xxt_article_review_log l where a.id=l.article_id and l.mid='$mid' and phase='R')"
        );
        $q2 = array('o'=>'a.create_at desc');

        $articles = $this->query_objs_ss($q, $q2);
        if (!empty($articles) && $cascade) foreach ($articles as &$a) {
            $a->disposer = $this->disposer($a->id);
        }
        return $articles;
    }
    /**
     * 获得审核通过的文稿
     * 
     * $mpid
     */
    public function &getApproved($mpid, $entry=null, $page=1, $size=30)
    {
        $q = array(
            'a.*',
            'xxt_article a',
            "a.mpid='$mpid' and a.approved='Y' and state=1"
        );
        !empty($entry) && $q[2] .= " and a.entry='$entry'";
        
        $q2 = array('o'=>'a.create_at desc');

        $articles = $this->query_objs_ss($q, $q2);
        
        return $articles;
    }
    /**
     * 这个是基类要求的方法
     * todo 应该用抽象类的机制处理
     */
    public function &getMatters($id) 
    {
        $article = $this->byId($id, "id,mpid,title,author,summary,pic,body,url");
        $article->type = 'article';
        $articles = array($article);

        return $articles;
    }
    /**
     * 返回进行推送的消息格式
     */
    public function &getArticles($id) 
    {
        $article = $this->byId($id, 'id,mpid,title,author,summary,pic,body,url');
        $article->type = 'article';
        $articles = array($article);

        return $articles;
    }
    /**
     * 文章打开的次数
     * todo 应该用哪个openid，根据oauth是否开放来决定？
     */
    public function readLog($id)
    {
        $q = array(
            'f.fid,f.nickname,f.openid,l.read_at',
            'xxt_log_matter_read l,xxt_fans f',
            "l.mpid=f.mpid and l.matter_type='article' and l.matter_id='$id' and l.ooid=f.openid"
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
            'xxt_log_matter_read',
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
            'r.*,f.nickname,f.fid',
            'xxt_article_remark r,xxt_fans f',
            "r.article_id='$articleId' and r.fid=f.fid"
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
     * 文章评论
     *
     * $range 分页参数
     */
    public function remarkers($articleId)
    {
        $remarkers = array();
        
        $q = array(
            'distinct fid',
            'xxt_article_remark r',
            "r.article_id='$articleId'"
        );
        $remarks = $this->query_objs_ss($q);
        foreach ($remarks as $remark) {
            $remarkers[] = \TMS_APP::M('user/fans')->byId($remark->fid, 'openid,nickname');
        }
        
        return $remarkers;
    }
    /**
     * 全文检索单图文，将符合条件的结果组成多图文
     */
    public function fullsearch_its($mpid, $keyword, $page = 1, $limit = 5) 
    {
        $s = "id,mpid,title,author,summary,pic,body,url,'article' type";
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
    /**
     * 审核记录
     *
     * $mpid
     * $id article'id
     * $mid member's id
     * $phase
     */
    public function forward($mpid, $id, $mid, $phase, $remark='')
    {
        $q = array(
            '*', 
            'xxt_article_review_log', 
            "mpid='$mpid' and article_id='$id'"
        );
        $q2 = array(
            'o'=>'seq desc',
            'r'=>array('o'=>0,'l'=>1)
        );
        $last = $this->query_objs_ss($q, $q2);
        if (!empty($last)) {
            $last = $last[0];
            $this->update(
                'xxt_article_review_log', 
                array('state'=>'F'),
                "id=$last->id"
            );
        }
        
        $member = \TMS_APP::M('user/member')->byId($mid);
        if (!empty($meber->name)) {
            $disposerName = $member->name;
        } else {
            $fan = \TMS_APP::M('user/fans')->byId($member->fid);
            $disposerName = $fan->nickname;
        }
        
        $seq = empty($last) ? 1 : $last->seq + 1;
        
        $newlog = array( 
            'mpid' => $mpid,
            'article_id' => $id,
            'seq' => $seq,
            'mid' => $mid,
            'disposer_name' => $disposerName,
            'send_at' => time(),
            'state' => 'P',
            'phase' => $phase,
            'remark' => $remark
        );
        $newlog['id'] = $this->insert('xxt_article_review_log', $newlog, true);
        
        return (object)$newlog;
    }
    /**
     * 获得当前处理人
     * 状态为等待处理（Pending），或正在处理（Dispose）
     */
    public function &disposer($id) 
    {
        $q = array(
            'id,seq,mid,phase,state,send_at,receive_at,read_at',
            'xxt_article_review_log',
            "article_id='$id' and state in ('P','D')"
        );
        $lastlog = $this->query_obj_ss($q);
        
        return $lastlog;
    }
    /**
     *
     */
    public function &reviewlogs($id) 
    {
        $q = array(
            'id,seq,mid,phase,state,disposer_name,send_at,receive_at,read_at,remark',
            'xxt_article_review_log',
            "article_id='$id'"
        );
        $q2 = array('o' => 'seq desc');
        
        $logs = $this->query_objs_ss($q);
        
        return $logs;
    }
}
