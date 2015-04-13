<?php
/**
 * 会员卡
 */
class card extends TMS_CONTROLLER {

    private $mpid;

    public function __construct()
    {
        if (!isset($_SESSION['mpid']) || !($mpid = $_SESSION['mpid'])) {
            die('not get valid mpid.');
        } 
        $this->mpid = $mpid; 
    }
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
            $updated['apply_css'] = mysql_real_escape_string($card->apply_css);
            $updated['apply_ele'] = mysql_real_escape_string($card->apply_ele);
            $updated['apply_js'] = mysql_real_escape_string($card->apply_js);
            $updated['show_css'] = mysql_real_escape_string($card->show_css);
            $updated['show_ele'] = mysql_real_escape_string($card->show_ele);
            $updated['show_js'] = mysql_real_escape_string($card->show_js);
            $this->model()->update('xxt_member_card', $updated, "mpid='$this->mpid'");
            return new ResponseData('success');
        } else {
            $card = $this->model('user/card')->get($this->mpid);
            //todo
            $card->url = 'http://'.$_SERVER['HTTP_HOST'].'/rest/member/card?mpid='.$this->mpid;
            return new ResponseData($card);
        }
    }
}
