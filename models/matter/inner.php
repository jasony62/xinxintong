<?php
namespace matter;

require_once dirname(__FILE__) . '/base.php';

class inner_model extends base_model {
    /**
     *
     */
    protected function table() {
        return 'xxt_inner';
    }
    /**
     * 返回进行推送的消息格式
     *
     * $runningSiteid
     * $id
     */
    public function &forCustomPush($runningSiteid, $id) {
        die('not support');
    }
    /**
     *
     */
    public function &bySite($site) {
        $q = array(
            'id,title,name',
            'xxt_inner',
        );
        $matters = $this->query_objs_ss($q);

        return $matters;
    }
}