<?php
namespace reply;

require_once dirname(__FILE__).'/base.php';
/**
 * 频道
 */
class channel_model extends MultiArticleReply {
    /**
     * 如果频道设置了【固定标题】，要用固定标题替换掉第一个图文的标题
     */
    protected function loadMatters() 
    {
        $mpid = $this->call['mpid'];
        
        $model = \TMS_APP::model('matter\channel');
        
        $matters = $model->getMatters($this->set_id, null, $mpid);

        $channel = $model->byId($this->set_id, 'fixed_title');
        if (!empty($matters) && !empty($channel->fixed_title))
            $matters[0]->title = $channel->fixed_title;    

        return $matters;
    }
}
