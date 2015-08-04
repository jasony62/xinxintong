<?php
namespace op\enroll;

require_once dirname(dirname(dirname(__FILE__))).'/member_base.php';
/**
 * 登记活动
 */
class lottery extends \member_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 走马灯抽奖页面
     */
    public function index_action($aid)
    {
        /**
         * 获得活动的定义
         */
        $act = $this->model('app\enroll')->byId($aid);

        \TPL::assign('enroll', $act);

        $this->view_action('/op/enroll/carousel');
    }
    /**
     * 抽奖的轮次 
     */
    public function roundsGet_action($aid) 
    {
        $result = $this->model('app\enroll\lottery')->rounds($aid);

        return new \ResponseData($result);
    }
    /**
     * 参与抽奖的人
     */
    public function playersGet_action($aid, $rid) 
    {
        $result = $this->model('app\enroll\lottery')->players($aid, $rid);

        return new \ResponseData($result);
    }
    /**
     * 清空参与抽奖的人
     */
    public function empty_action($aid) 
    {
        $rst = $this->model()->delete('xxt_enroll_lottery', "aid='$aid'");

        return new \ResponseData($rst);
    }
    /**
     * 记录中奖人
     */
    public function done_action($aid, $rid, $ek)
    {
        $fans = $this->getPostJson();

        $i = array(
            'aid'=>$aid,
            'round_id'=>$rid,
            'enroll_key'=>$ek,
            'openid'=>$fans->openid,
            'draw_at'=>time()
        );

        $this->model()->insert('xxt_enroll_lottery', $i, false);

        return new \ResponseData('success');
    }
}
