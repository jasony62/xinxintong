<?php
namespace matter;

require_once dirname(__FILE__).'/article_base.php';

class news_model extends article_base {
    /**
     *
     */
    protected function table()
    {
        return 'xxt_news';
    }
    /**
     *
     */
    public function getTypeName()
    {
        return 'news';
    }
    /**
    
     * $mid member's 仅限认证用户
     * $phase 
     */
    public function &byReviewer($mid, $phase, $fields='*', $cascade=false)
    {
        $q = array(
            'n.*',
            'xxt_news n',
            "exists(select 1 from xxt_news_review_log l where n.id=l.news_id and l.mid='$mid' and l.phase='R')"
        );
        $q2 = array('o'=>'n.create_at desc');

        $news = $this->query_objs_ss($q, $q2);
        if (!empty($news) && $cascade) foreach ($news as &$n) {
            $n->disposer = $this->disposer($n->id);
        }

        return $news;
    }
    /**
     * 返回多图文包含的素材
     *
     * $param int $news_id
     *
     * return array(article object)
     */
    public function &getMatters($news_id) 
    {
        $matters = array();
        /**
         * 单图文
         */
        $q = array(
            "a.id,a.mpid,a.title,a.pic,a.summary,a.url,a.create_at,nm.seq,'article' type,a.access_control,a.authapis",
            'xxt_article a,xxt_news_matter nm',
            "a.state=1 and a.approved='Y' and nm.matter_type='Article' and nm.news_id=$news_id and nm.matter_id=a.id"
        );
        $q2= array('o'=>'nm.seq');
        if ($articles = $this->query_objs_ss($q, $q2)){
            foreach ($articles as $a) {
                $matters[(int)$a->seq] = $a;
            }
        }
        /**
         * 链接
         */
        $q = array(
            "l.id,l.mpid,l.title,l.pic,l.summary,l.url,l.urlsrc,l.create_at,nm.seq,'link' type,method,open_directly,l.access_control,l.authapis",
            'xxt_link l,xxt_news_matter nm',
            "nm.matter_type='link' and nm.news_id=$news_id and nm.matter_id=l.id"
        );
        $q2 = array('o' => 'nm.seq');
        if ($links = $this->query_objs_ss($q, $q2)) {
            foreach ($links as $l) {
                $matters[(int)$l->seq] = $l;
            }
        }
        /**
         * 登记活动 
         */
        $q = array(
            "a.id,a.mpid,a.title,a.pic,a.summary,a.create_at,nm.seq,'enroll' type,a.access_control,a.authapis",
            'xxt_enroll a,xxt_news_matter nm',
            "nm.matter_type='enroll' and nm.news_id=$news_id and nm.matter_id=a.id"
        );
        $q2 = array('o' => 'nm.seq');
        if ($acts = $this->query_objs_ss($q, $q2)) {
            foreach ($acts as &$a)
                $matters[(int)$a->seq] = $a;
        }
        /**
         * 抽奖活动 
         */
        $q = array(
            "l.id,l.mpid,l.title,l.pic,l.summary,l.create_at,nm.seq,'lottery' type,l.access_control,l.authapis",
            'xxt_lottery l,xxt_news_matter nm',
            "nm.matter_type='lottery' and nm.news_id=$news_id and nm.matter_id=l.id"
        );
        $q2 = array('o' => 'nm.seq');
        if ($lots = $this->query_objs_ss($q, $q2)) {
            foreach ($lots as &$l)
                $matters[(int)$l->seq] = $l;
        }
        /**
         * 信息墙 
         */
        $q = array(
            "w.id,w.mpid,w.title,w.pic,w.summary,w.create_at,nm.seq,'wall' type,w.access_control,w.authapis",
            'xxt_wall w,xxt_news_matter nm',
            "nm.matter_type='wall' and nm.news_id=$news_id and nm.matter_id=w.id"
        );
        $q2 = array('o' => 'nm.seq');
        if ($walls = $this->query_objs_ss($q, $q2)) {
            foreach ($walls as &$w)
                $matters[(int)$w->seq] = $w;
        }

        ksort($matters);

        $matters2 = array();
        foreach ($matters as $m) {
            $matters2[] = $m;
        }
        
        return $matters2; 
    }
    /**
     * 返回多图文包含的单图文
     *
     * $param int $news_id
     *
     * return array(article object)
     */
    public function &getArticles($news_id) 
    {
        $articles = array();
        /**
         * 单图文
         */
        $q = array(
            "a.id,a.mpid,a.title,a.pic,a.summary,a.body,a.url,a.create_at,nm.seq,'article' type",
            'xxt_article a,xxt_news_matter nm',
            "nm.matter_type='article' and nm.news_id=$news_id and nm.matter_id=a.id"
        );
        $q2['o'] = 'nm.seq';

        $articles = $this->query_objs_ss($q, $q2);

        return $articles; 
    }
    /**
     *
     */
    public function &byMatter($id, $type)
    {
        $q = array(
            '*',
            'xxt_news n',
            "exists(select 1 from xxt_news_matter nm where nm.news_id=n.id and nm.matter_id='$id' and nm.matter_type='$type')"
        );
        $news = $this->query_objs_ss($q);
        
        return $news;
    }
    /**
     * 删除文稿
     */
    public function removeMatter($id, $matterId, $matterType)
    {
        $q = array(
            'seq',
            'xxt_news_matter',
            "news_id='$id' and matter_id='$matterId' and matter_type='$matterType'"
        );
        $seq = $this->query_val_ss($q);
        
        $rst = $this->delete('xxt_news_matter', "news_id='$id' and matter_id='$matterId' and matter_type='$matterType'");
        
        $rst && $this->update("update xxt_news_matter set seq=seq-1 where news_id='$id' and seq>$seq");
        
        return $rst;
    }
    /**
     * 多图文审核记录
     *
     * $mpid
     * $id news'id
     * $mid member's id
     * $phase
     */
    public function forward($mpid, $id, $mid, $phase)
    {
        $q = array(
            '*', 
            'xxt_news_review_log', 
            "mpid='$mpid' and news_id='$id'"
        );
        $q2 = array(
            'o'=>'seq desc',
            'r'=>array('o'=>0,'l'=>1)
        );
        $last = $this->query_objs_ss($q, $q2);
        if (!empty($last)) {
            $last = $last[0];
            $this->update(
                'xxt_news_review_log', 
                array('state'=>'F'),
                "id=$last->id"
            );
        }
        
        $seq = empty($last) ? 1 : $last->seq + 1;
        
        $newlog = array( 
            'mpid' => $mpid,
            'news_id' => $id,
            'seq' => $seq,
            'mid' => $mid,
            'send_at' => time(),
            'state' => 'P',
            'phase' => $phase
        );
        $newlog['id'] = $this->insert('xxt_news_review_log', $newlog, true);
        
        return (object)$newlog;
    }
    /**
     * 获得当前处理人
     */
    public function &disposer($id) 
    {
        $q = array(
            'id,seq,mid,phase,state,send_at,receive_at,read_at',
            'xxt_news_review_log',
            "news_id='$id' and state in ('P','D')"
        );
        $lastlog = $this->query_obj_ss($q);
        
        return $lastlog;
    }
}
