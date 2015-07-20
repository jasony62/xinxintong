<?php
namespace app\contribute;

require_once dirname(__FILE__).'/base.php';
/**
 * 审核日志
 */
class reviewlog extends base {
    /**
     * 待审核稿件
     */
    public function list_action($mpid, $matterId, $matterType) 
    {
        list($fid, $openid, $mid) = $this->getCurrentUserInfo($mpid);
        
        $logs = $this->model('matter\\'.$matterType)->reviewlogs($matterId);
        
        return new \ResponseData($logs);
    }
}
