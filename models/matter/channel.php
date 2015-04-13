<?php
require_once dirname(__FILE__).'/article_base.php';

class channel_model extends article_base {
    /**
     *
     */
    protected function table()
    {
        return 'xxt_channel';
    }
    /**
     *
     * $channel_id int 频道的id
     * $channel 频道
     *
     * 置顶+动态+置底
     *
     * return 频道包含的文章，小于等于频道的容量
     */
    public function &getMatters($channel_id, $channel=null) 
    {
        $fixed_num = 0;
        $matters = array();
        /**
         * load channel.
         */
        if (empty($channel)) {
            $channel = $this->byId($channel_id, 'id,mpid,volume,top_type,top_id,bottom_type,bottom_id');
        }
        /**
         * top matter
         */
        if (!empty($channel->top_type)) {
            switch ($channel->top_type){
            case 'Article':
                $qt[] = 'a.id,a.title,a.summary,a.pic,a.create_at,"Article" type';
                $qt[] = 'xxt_article a';
                $qt[] = "a.id='$channel->top_id' and a.state=1";
                break;
            case 'Link':
                $qt[] = 'l.id,l.title,l.summary,l.pic,l.create_at,"Link" type';
                $qt[] = 'xxt_link l';
                $qt[] = "l.id='$channel->top_id' and l.state=1";
                break;
            }
            $top = $this->query_obj_ss($qt);
            $fixed_num++;
        }
        /**
         * bottom matter
         */
        if (!empty($channel->bottom_type)) {
            switch ($channel->bottom_type){
            case 'Article':
                $qb[] = 'a.id,a.title,a.summary,a.pic,a.create_at,"Article" type';
                $qb[] = 'xxt_article a';
                $qb[] = "a.id='$channel->bottom_id' and a.state=1";
                break;
            case 'Link':
                $qb[] = 'l.id,l.title,l.summary,l.pic,l.create_at,"Link" type';
                $qb[] = 'xxt_link l';
                $qb[] = "l.id='$channel->bottom_id' and l.state=1";
                break;
            }
            $bottom = $this->query_obj_ss($qb);
            $fixed_num++;
        }
        /**
         * load articles.
         */
        $qa1[] = 'a.id,a.title,a.summary,a.pic,a.create_at,ca.create_at add_at,"Article" type';
        $qa1[] = 'xxt_article a,xxt_article_channel ca';
        $qaw = "ca.channel_id=$channel_id and a.id=ca.article_id and a.state=1";
        if (!empty($top) && $top->type === 'Article') {
            $qaw .= " and a.id<>$top->id";
        }
        if (!empty($bottom) && $bottom->type === 'Article') {
            $qaw .= " and a.id<>$bottom->id";
        }
        $qa1[] = $qaw;
        $qa2['o'] = 'ca.create_at desc';
        $qa2['r']['o'] = 0;
        $qa2['r']['l'] = $channel->volume-$fixed_num;
        $articles = $this->query_objs_ss($qa1, $qa2);
        /**
         * load links.
         */
        $ql1[] = 'l.id,l.title,l.summary,l.pic,l.create_at,lc.create_at add_at,"Link" type';
        $ql1[] = 'xxt_link l,xxt_link_channel lc';
        $qlw = "lc.channel_id=$channel_id and l.id=lc.link_id and l.state=1";
        if (!empty($top) && $top->type === 'Link') {
            $qlw .= " and l.id<>$top->id";
        }
        if (!empty($bottom) && $bottom->type === 'Link') {
            $qlw .= " and l.id<>$bottom->id";
        }
        $ql1[] = $qlw;
        $ql2['o'] = 'lc.create_at desc';
        $ql2['r']['o'] = 0;
        $ql2['r']['l'] = $channel->volume-$fixed_num;
        $links = $this->query_objs_ss($ql1, $ql2);

        $matters = array_merge($articles, $links);
        /**
         * order by create_at
         */
        usort($matters, function($a, $b){
            return $b->add_at - $a->add_at; 
        });
        /**
         * add top and bottom.
         */
        !empty($top) && $matters = array_merge(array($top), $matters);
        !empty($bottom) && $matters = array_merge($matters, array($bottom));
        /**
         * size
         */
        $matters = array_slice($matters, 0, $channel->volume);

        return $matters;
    }
    /**
     * 只返回频道内包含的图文不包括连接
     * 因为微信的群发消息只能发送图文
     *
     * $channel_id int 频道的id
     * $channel 置顶频道
     *
     * return 频道包含的文章，小于等于频道的容量
     */
    public function &getArticles($channel_id, $channel=null) 
    {
        $articles = array();
        /**
         * load channel.
         */
        if (empty($channel)) {
            $channel = $this->byId($channel_id, 'id,mpid,volume,top_type,top_id,bottom_type,bottom_id');
        }
        /**
         * top matter
         */
        if (!empty($channel->top_type) && $channel->top_type==='Article') {
            $qt[] = "a.id,a.mpid,a.title,a.summary,a.pic,a.body,a.create_at";
            $qt[] = 'xxt_article a';
            $qt[] = "a.id='$channel->top_id' and a.state=1";
            $top = $this->query_obj_ss($qt);
        }
        /**
         * bottom matter
         */
        if (!empty($channel->bottom_type) && $channel->bottom_type==='Article') {
            $qb[] = 'a.id,a.mpid,a.title,a.summary,a.pic,a.body,a.create_at';
            $qb[] = 'xxt_article a';
            $qb[] = "a.id='$channel->bottom_id' and a.state=1";
            $bottom = $this->query_obj_ss($qb);
        }
        /**
         * load articles.
         */
        $qa1[] = 'a.id,a.mpid,a.title,a.summary,a.pic,a.body,a.create_at,ca.create_at';
        $qa1[] = 'xxt_article a,xxt_article_channel ca';
        $qaw = "ca.channel_id=$channel_id and a.id=ca.article_id and a.state=1";
        if (!empty($top)) {
            $qaw .= " and a.id<>$top->id";
        }
        if (!empty($bottom)) {
            $qaw .= " and a.id<>$bottom->id";
        }
        $qa1[] = $qaw;
        $qa2['o'] = 'ca.create_at desc';
        $qa2['r']['o'] = 0;
        $qa2['r']['l'] = $channel->volume;
        $articles = $this->query_objs_ss($qa1, $qa2);
        /**
         * add top and bottom.
         */
        !empty($top) && $articles = array_merge(array($top), $articles);
        !empty($bottom) && $articles = array_merge($articles, array($bottom));
        /**
         * size
         */
        $articles = array_slice($articles, 0, $channel->volume);

        return $articles;
    }
    /**
     * 直接打开频道的情况下（不是返回信息卡片），忽略置顶和置底，返回频道中的所有条目
     *
     * $channel_id int 频道的id
     * $channel 频道
     *
     * return 频道包含的所有条目
     */
    public function &getAllMatters($channel_id, $channel=null) 
    {
        $matters = array();
        /**
         * load channel.
         */
        if (empty($channel)) {
            $channel = $this->byId($channel_id, 'id,mpid,volume,top_type,top_id,bottom_type,bottom_id');
        }
        /**
         * load articles.
         */
        $qa1[] = 'a.id,a.mpid,a.title,a.summary,a.pic,a.create_at,ca.create_at add_at,"Article" type';
        $qa1[] = 'xxt_article a,xxt_article_channel ca';
        $qa1[] = "ca.channel_id=$channel_id and a.id=ca.article_id and a.state=1";
        $qa2['o'] = 'ca.create_at desc';
        $articles = $this->query_objs_ss($qa1, $qa2);
        /**
         * load links.
         */
        $ql1[] = 'l.id,l.mpid,l.title,l.summary,l.pic,l.create_at,lc.create_at add_at,"Link" type';
        $ql1[] = 'xxt_link l,xxt_link_channel lc';
        $ql1[] = "lc.channel_id=$channel_id and l.id=lc.link_id and l.state=1";
        $ql2['o'] = 'lc.create_at desc';
        $links = $this->query_objs_ss($ql1, $ql2);

        $matters = array_merge($articles, $links);
        /**
         * order by create_at
         */
        usort($matters, function($a, $b){
            return $b->add_at - $a->add_at; 
        });

        return $matters;
    }
    /**
     * 从频道中移除素材
     */
    public function removeMatter($id, $matter)
    {
        switch (strtolower($matter->type)) {
        case 'article':
            $rst = $this->delete('xxt_article_channel', "channel_id='$id' and article_id='$matter->id'");
            break;
        case 'link':
            $rst = $this->delete('xxt_link_channel', "channel_id='$id' and link_id='$matter->id'");
            break;
        }
        return $rst;
    }
}
