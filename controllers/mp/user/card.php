<?php
namespace mp\user;
/**
 * 会员卡
 */
class card extends \mp\TMS_CONTROLLER {
    /**
     *
     */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 每个平台只会有一个签到活动
     */
    public function index_action() 
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $card = $this->getPostJson();
            $updated['title'] = $card->title;
            $updated['board_pic'] = $card->board_pic;
            $updated['badge_pic'] = $card->badge_pic;
            $updated['title_color'] = $card->title_color;
            $updated['cardno_color'] = $card->cardno_color;
            $updated['apply_css'] = $this->model()->escape($card->apply_css);
            $updated['apply_ele'] = $this->model()->escape($card->apply_ele);
            $updated['apply_js'] = $this->model()->escape($card->apply_js);
            $updated['show_css'] = $this->model()->escape($card->show_css);
            $updated['show_ele'] = $this->model()->escape($card->show_ele);
            $updated['show_js'] = $this->model()->escape($card->show_js);
            $this->model()->update('xxt_member_card', $updated, "mpid='$this->mpid'");
            return new \ResponseData('success');
        } else {
            $card = $this->model('user/card')->get($this->mpid);
            //todo
            $card->url = 'http://'.$_SERVER['HTTP_HOST'].'/rest/member/card?mpid='.$this->mpid;
            return new \ResponseData($card);
        }
    }
}
