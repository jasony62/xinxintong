<?php
namespace matter;

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
    */
    public function getTypeName()
    {
        return 'channel';
    }
    /**
     * 获得一个账号下的频道
     */
    public function &byMpid($mpid, $acceptType=null)
    {
        $q = array(
            "c.*",
            'xxt_channel c', 
            "c.mpid='$mpid' and c.state=1"
        );
        !empty($acceptType) && $q[2] .= " and (c.matter_type='' or c.matter_type='$acceptType')";
        
        $q2['o'] = 'c.create_at desc';

        $channels = $this->query_objs_ss($q, $q2);

        return $channels;
    }
    /**
     *
     */
    public function &byMatter($id, $type)
    {
        $q = array(
            'c.id,c.title,cm.create_at',
            'xxt_channel_matter cm,xxt_channel c',
            "cm.matter_id='$id' and cm.matter_type='$type' and cm.channel_id=c.id and c.state=1"
        );
        $q2['o'] = 'cm.create_at desc';

        $channels = $this->query_objs_ss($q, $q2);

        return $channels;
    }
    /**
     *
     * $channel_id int 频道的id
     * $channel 频道
     * $mpid
     *
     * 置顶+动态+置底
     *
     * return 频道包含的文章，小于等于频道的容量
     */
    public function &getMatters($channel_id, $channel=null, $mpid=null) 
    {
        $fixed_num = 0;
        $matters = array();
        $matterTypes = array(
            'article'=>'xxt_article',
            'link'=>'xxt_link',
            'enroll'=>'xxt_enroll',
            'contribute'=>'xxt_contribute'
        );
        /**
         * load channel.
         */
        if (empty($channel))
            $channel = $this->byId($channel_id, 'id,mpid,volume,top_type,top_id,bottom_type,bottom_id');
            
        if ($mpid !== null && $mpid !== $channel->mpid) {
            $pmpid = $channel->mpid;
        }
        /**
         * top matter
         */
        if (!empty($channel->top_type)) {
            $qt[] = 'id,title,summary,pic,create_at,"'.$channel->top_type.'" type';
            $qt[] = $matterTypes[$channel->top_type];
            $qt[] = "id='$channel->top_id'";

            $top = $this->query_obj_ss($qt);
            $fixed_num++;
        }
        /**
         * bottom matter
         */
        if (!empty($channel->bottom_type)) {
            $qb[] = 'id,title,summary,pic,create_at,"'.$channel->bottom_type.'" type';
            $qb[] = $matterTypes[$channel->bottom_type];
            $qb[] = "id='$channel->bottom_id'";

            $bottom = $this->query_obj_ss($qb);
            $fixed_num++;
        }
        /**
         * in channel
         */
        foreach ($matterTypes as $type=>$table) {
            $q1 = array();
            $q1[] = "m.id,m.title,m.summary,m.pic,m.create_at,cm.create_at add_at,'".$type."' type";
            $q1[] = "$table m,xxt_channel_matter cm";
            $qaw = "m.state=1 and cm.channel_id=$channel_id and m.id=cm.matter_id and cm.matter_type='$type'";
            
            !empty($top) && $top->type === $type && $qaw .= " and m.id<>$top->id";
            
            !empty($bottom) && $bottom->type === $type && $qaw .= " and m.id<>$bottom->id";
            // in parent mp
            empty($pmpid) && $qaw .= " and m.mpid = '$mpid'";
            
            $q1[] = $qaw;
            $q2 = array();
            $q2['o'] = 'cm.create_at desc';
            $q2['r']['o'] = 0;
            $q2['r']['l'] = $channel->volume-$fixed_num;

            $typeMatters = $this->query_objs_ss($q1, $q2);

            $matters = array_merge($matters, $typeMatters);
        }
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
        if (!empty($channel->top_type) && $channel->top_type==='article') {
            $qt[] = "a.id,a.mpid,a.title,a.summary,a.pic,a.body,a.create_at";
            $qt[] = 'xxt_article a';
            $qt[] = "a.id='$channel->top_id' and a.state=1";
            $top = $this->query_obj_ss($qt);
        }
        /**
         * bottom matter
         */
        if (!empty($channel->bottom_type) && $channel->bottom_type==='article') {
            $qb[] = 'a.id,a.mpid,a.title,a.summary,a.pic,a.body,a.create_at';
            $qb[] = 'xxt_article a';
            $qb[] = "a.id='$channel->bottom_id' and a.state=1";
            $bottom = $this->query_obj_ss($qb);
        }
        /**
         * load articles.
         */
        $qa1[] = 'a.id,a.mpid,a.title,a.summary,a.pic,a.body,a.create_at,ca.create_at';
        $qa1[] = 'xxt_article a,xxt_channel_matter ca';
        $qaw = "ca.channel_id=$channel_id and a.id=ca.matter_id and ca.matter_type='article' and a.state=1";
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
    public function &getMattersNoLimit($channel_id, $vid, $params) 
    {
        /**
         * load channel.
         */
        $channel = $this->byId($channel_id, 'matter_type');
        /**
         * in channel
         */
        if ($channel->matter_type === 'article') {
            $q1 = array();
            $q1[] = "m.id,m.title,m.summary,m.pic,m.create_at,m.creater_name,cm.create_at add_at,'article' type,m.score,m.remark_num,s.score myscore";
            $q1[] = "xxt_article m left join xxt_article_score s on m.id=s.article_id and s.vid='$vid',xxt_channel_matter cm";
            $q1[] = "m.state=1 and m.approved='Y' and cm.channel_id=$channel_id and m.id=cm.matter_id and cm.matter_type='article'";

            $q2 = array();
            if ($params->orderby === 'time')
                $q2['o'] = 'cm.create_at desc';
            else if ($params->orderby === 'score')
                $q2['o'] = 'm.score + m.remark_num desc';

            if (isset($params->page) && isset($params->size)) {
                $q2['r'] = array(
                    'o' => ($params->page - 1) * $params->size,
                    'l' => $params->size
                );
            }

            $matters = $this->query_objs_ss($q1, $q2);
        } else {
            $matters = array();

            $q1 = array();
            $q1[] = 'cm.create_at,cm.matter_type,cm.matter_id';
            $q1[] = 'xxt_channel_matter cm';
            $q1[] = "cm.channel_id='$channel_id'";

            $q2['o'] = 'cm.create_at desc';
            if (isset($params->page) && isset($params->size)) {
                $q2['r'] = array(
                    'o' => ($params->page - 1) * $params->size,
                    'l' => $params->size
                );
            }

            $simpleMatters = $this->query_objs_ss($q1, $q2);
            foreach ($simpleMatters as $sm) {
                $fullMatter = \TMS_APP::M('matter\\'.$sm->matter_type)->byId($sm->matter_id);
                $fullMatter->type = $sm->matter_type;
                $fullMatter->add_at = $sm->create_at;
                $matters[] = $fullMatter;
            }
        }

        return $matters;
    }
    /**
     * 频道中增加素材
     *
     * $id
     * $matter
     */
    public function addMatter($id, $matter, $creater, $createrName, $createrSrc='A')
    {
        is_array($matter) && $matter = (object)$matter;

        $current = time();

        $newc['matter_id'] = $matter->id;
        $newc['matter_type'] = $matter->type;
        $newc['create_at'] = $current;
        $newc['creater'] = $creater;
        $newc['creater_src'] = $createrSrc;
        $newc['creater_name'] = $createrName;
        // check
        $q = array(
            'count(*)',
            'xxt_channel_matter',
            "channel_id=$id and matter_id='$matter->id' and matter_type='matter->type'"
        );
        if ('1' === $this->query_val_ss($q))
            return false;

        // new one
        $newc['channel_id'] = $id;

        $this->insert('xxt_channel_matter', $newc, false);

        return true;
    }
    /**
     * 从频道中移除素材
     */
    public function removeMatter($id, $matter)
    {
        is_array($matter) && $matter = (object)$matter;

        $rst = $this->delete(
            'xxt_channel_matter', 
            "matter_id='$matter->id' and matter_type='$matter->type' and channel_id=$id");

        return $rst;
    }
}
