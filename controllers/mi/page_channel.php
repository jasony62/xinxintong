<?php
/**
 * 返回页面形式的频道
 */
class page_channel extends matter_page_base {
    /**
     *
     */
    public function __construct($id, $openid, $src)
    {
        $q = array(
            'id,mpid,title,access_control,authapis,"C" type', 
            'xxt_channel', 
            "id=$id"
        );
        $this->channel = TMS_APP::model()->query_obj_ss($q);
        parent::__construct($this->channel, $openid, $src);
    }
    /**
     *
     * $runningMpid 当前运行的公众号
     */
    public function output($runningMpid)
    {
        $mpid = $this->channel->mpid;

        // todo 背景设置应该从哪个公众号区？
        $body_ele = TMS_APP::model()->query_value('body_ele', 'xxt_mpsetting', "mpid='$mpid'");
        /**
         * 不包含置顶和置底，不考虑容量限制
         */
        $matters = TMS_APP::model('matter/channel')->getAllMatters($this->channel->id);
        foreach ($matters as $m)
            $m->url = TMS_APP::model('reply')->getMatterUrl($runningMpid, $m, $this->openid, $this->src);

        TPL::assign('list_title', $this->channel->title);
        TPL::assign('body_ele', $body_ele);
        TPL::assign('matters', $matters);
        TPL::output('article-list');
    }
}
