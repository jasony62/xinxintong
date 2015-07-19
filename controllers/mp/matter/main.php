<?php
namespace mp\matter;

require_once dirname(__FILE__).'/matter_ctrl.php';
/**
 * 素材管理缺省入口
 */
class main extends matter_ctrl {
    /**
     *
     */
    public function index_action() 
    {
        if (!empty($this->entries)) {
            $entry = $this->entries[0];
            $this->view_action($entry['url']);
        } else {
            die('没有访问权限');
        }
    }
}
