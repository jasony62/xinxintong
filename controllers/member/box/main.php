<?php
namespace member\box;

require_once dirname(dirname(dirname(__FILE__))).'/member_base.php';
/**
 * member
 */
class main extends \member_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     *
     * $mpid
     * $code
     * $mocker
     */
    public function index_action($mpid, $code=null, $mocker=null) 
    {
        $openid = $this->doAuth($mpid, $code, $mocker);

        $fan = $this->model('user/fans')->byOpenid($mpid, $openid);

        $params = array(
            'user'=>$fan
        );

        \TPL::assign('params', $params);
        $this->view_action('/member/box/main');
    }
    /**
     *
     */
    public function get_action($mpid, $code=null, $mocker=null) 
    {
        $openid = $this->doAuth($mpid, $code, $mocker);

        $fan = $this->model('user/fans')->byOpenid($mpid, $openid);
        /**
         * 话题
         */
        $q = array(
            "id matter_id,title,summary,pic,create_at,'article' matter_type",
            'xxt_article',
            "mpid='$mpid' and creater='$fan->fid' and creater_src='F'"
        );
        $q2 = array('o'=>'create_at desc');
        
        $articles = $this->model()->query_objs_ss($q, $q2);
        /**
         * 活动
         */
        $q = array(
            "id matter_id,title,summary,pic,create_at,'enroll' matter_type",
            'xxt_enroll',
            "mpid='$mpid' and creater='$fan->fid' and creater_src='F'"
        );
        $q2 = array('o'=>'create_at desc');

        $enrolls = $this->model()->query_objs_ss($q, $q2);
        
        $matters = array_merge($articles, $enrolls);
        
        return new \ResponseData($matters);
    }
}
