<?php
namespace mp\user;

require_once dirname(dirname(__FILE__)).'/mp_controller.php';

class messages extends \mp\mp_controller {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';

        return $rule_action;
    }
    /**
     *
     */
    public function index_action()
    {
        $this->view_action('/mp/user/received');
    }
    /**
     * all messages.
     *
     * $offset
     * $size
     */
    public function get_action($keyword='', $page=1, $size=30, $amount=null) 
    {
        $q = array(
            'l.id,l.openid,l.nickname,l.create_at,l.data',
            'xxt_log_mpreceive l', 
            "l.mpid='$this->mpid' and l.type='text'"
        );
        !empty($keyword) && $q[2] .= " and data like '%$keyword%'";

        $q2['o'] = 'create_at desc';
        $q2['r'] = array('o'=>($page-1)*$size, 'l'=>$size);

        if ($messages = $this->model()->query_objs_ss($q, $q2)) {
            if (empty($amount)) {
                $q[0] = 'count(*)';
                $amount = (int)$this->model()->query_val_ss($q);
            }
            return new \ResponseData(array($messages, $amount)); 
        }

        return new \ResponseData(array(array(),0));
    }
}
