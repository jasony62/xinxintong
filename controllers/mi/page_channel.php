<?php
namespace mi;
/**
 * 返回页面形式的频道
 */
class page_channel extends matter_page_base {
    /**
     *
     */
    public function __construct($id, $openid)
    {
        $this->channel = \TMS_APP::M('matter\channel')->byId($id, "*");
        $this->channel->type = 'channel';
        parent::__construct($this->channel, $openid);
    }
    /**
     *
     * $runningMpid 当前运行的公众号
     */
    public function output($runningMpid, $mid, $vid, $ctrl)
    {
        // todo 背景设置应该从哪个公众号区？
        //$body_ele = \TMS_APP::model()->query_value('body_ele', 'xxt_mpsetting', "mpid='$runningMpid'");

        \TPL::assign('title', $this->channel->title);
        //TPL::assign('body_ele', $body_ele);

        $params = array(
            'channel' => $this->channel
        );
        \TPL::assign('params', $params);
        $ctrl->view_action('/channel');
    }
}
