<?php
namespace mp\user;
/**
 *
 */
class checkin extends \mp\TMS_CONTROLLER {
    /**
     *
     */
    public function get_access_rule() {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 每个平台只会有一个签到活动
     */
    public function index_action() {
        $checkin = $this->model('user/checkin')->get($this->mpid);
        //todo
        $checkin->url = 'http://'.$_SERVER['HTTP_HOST'].'/rest/member/checkin?mpid='.$this->mpid;

        return new \ResponseData($checkin);
    }
    /**
     *
     */
    public function submit_action() 
    {
        $c = $this->getPostJson();

        $updated['extra_css'] = $this->model()->escape($c->extra_css);
        $updated['extra_ele'] = $this->model()->escape($c->extra_ele);
        $updated['extra_js'] = $this->model()->escape($c->extra_js);

        $this->model()->update('xxt_checkin', $updated, "mpid='$this->mpid'");

        return new \ResponseData('success');
    }
    /**
     * 返回签到日志
     */
    public function log_action($page=1, $size=30, $contain) 
    {
        $contain = isset($contain) ? explode(',',$contain) : array();
        $q = array(
            'm.name,m.cardno,m.mobile,l.checkin_at,l.times_accumulated', 
            'xxt_checkin_log l,xxt_member m', 
            "l.mpid='$this->mpid' and l.mpid=m.mpid and l.mid=m.mid"
        );
        $q2['r']['o'] = ($page-1) * $size;
        $q2['r']['l'] = $size;
        if ($log = $this->model()->query_objs_ss($q, $q2)) {
            $result[] = $log;
            if (in_array('total', $contain)) {
                $q[0] = 'count(*)';
                $total = (int)$this->model()->query_val_ss($q);
                $result[] = $total;
            }
            return new \ResponseData($result); 
        }
        return new \ResponseData(array());
    }
}
