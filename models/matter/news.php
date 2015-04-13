<?php
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
            "a.id,a.mpid,a.title,a.pic,a.summary,a.url,a.create_at,nm.seq,'Article' type,a.access_control,a.authapis",
            'xxt_article a,xxt_news_matter nm',
            "nm.matter_type='Article' and nm.news_id=$news_id and nm.matter_id=a.id"
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
            "l.id,l.mpid,l.title,l.pic,l.summary,l.url,l.urlsrc,l.create_at,nm.seq,'Link' type,method,open_directly,l.access_control,l.authapis",
            'xxt_link l,xxt_news_matter nm',
            "nm.matter_type='Link' and nm.news_id=$news_id and nm.matter_id=l.id"
        );
        $q2 = array('o' => 'nm.seq');
        if ($links = $this->query_objs_ss($q, $q2)) {
            foreach ($links as $l) {
                $matters[(int)$l->seq] = $l;
            }
        }
        /**
         * 通用活动 
         */
        $q = array(
            "a.aid id,a.mpid,a.title,a.pic,a.summary,a.create_at,nm.seq,'activity' type,a.access_control,a.authapis",
            'xxt_activity a,xxt_news_matter nm',
            "nm.matter_type='activity' and nm.news_id=$news_id and nm.matter_id=a.aid"
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
            "l.lid id,l.mpid,l.title,l.pic,l.summary,l.create_at,nm.seq,'lottery' type,l.access_control,l.authapis",
            'xxt_lottery l,xxt_news_matter nm',
            "nm.matter_type='lottery' and nm.news_id=$news_id and nm.matter_id=l.lid"
        );
        $q2 = array('o' => 'nm.seq');
        if ($lots = $this->query_objs_ss($q, $q2)) {
            foreach ($lots as &$l)
                $matters[(int)$l->seq] = $l;
        }
        /**
         * 讨论组 
         */
        $q = array(
            "w.wid id,w.mpid,w.title,w.pic,w.summary,w.create_at,nm.seq,'discuss' type,w.access_control,w.authapis",
            'xxt_wall w,xxt_news_matter nm',
            "nm.matter_type='discuss' and nm.news_id=$news_id and nm.matter_id=w.wid"
        );
        $q2 = array('o' => 'nm.seq');
        if ($walls = $this->query_objs_ss($q, $q2)) {
            foreach ($walls as &$w)
                $matters[(int)$w->seq] = $w;
        }

        ksort($matters);

        return $matters; 
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
            "a.id,a.mpid,a.title,a.pic,a.summary,a.body,a.url,a.create_at,nm.seq",
            'xxt_article a,xxt_news_matter nm',
            "nm.matter_type='Article' and nm.news_id=$news_id and nm.matter_id=a.id"
        );
        $q2['o'] = 'nm.seq';

        $articles = $this->query_objs_ss($q, $q2);

        return $articles; 
    }
}
