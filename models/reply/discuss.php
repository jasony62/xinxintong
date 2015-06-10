<?php
namespace reply;

require_once dirname(__FILE__).'/base.php';
/**
 * 信息墙的信息卡片
 */
class wall_model extends MultiArticleReply {

    protected function loadMatters() 
    {
        $w = \TMS_APP::model('app\wall')->byId($this->set_id);
        $w->type = 'wall';
        return array($w);
    }
}
