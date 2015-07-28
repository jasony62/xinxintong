<?php
namespace mp\app;

require_once dirname(__FILE__).'/base.php';

class main extends app_base {
    /**
     * 应用管理的缺省入口
     */
    public function index_action() 
    {
        if (!empty($this->entries)) {
            $entry = $this->entries[0];
            $this->view_action($entry['url']);
        } else {
            header('Content-Type: text/plain; charset=utf-8');
            die('没有访问权限');
        }
    }
}
