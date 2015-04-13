<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))).'/xxt_base.php';
/**
 * 向公众号用户发送模板消息 
 */
class qynotify extends xxt_base {
    /**
     *
     */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 发送待办事项模板消息 
     *
     * $userid 用户的业务身份标识
     * $text 发送的文本信息
     * $mpid todo 需要根据运行环境指定
     */
    public function senddbshx_action($userid, $text, $url='', $mpid='87b697a59119899e8dceb9d0daaf0e4b')
    {
        $content = urlencode($text);
        !empty($url) && $content .= " <a href='$url'>".urlencode('详情')."</a>";

        $message = array(
            "msgtype"=>"text",
            "text"=>array(
                "content"=>$content
            )
        );
        if (true === ($rsp = $this->send_to_user($mpid, 'qy', $userid, $message)))
            return new ResponseData('ok');
        else
            return new ResponseError($rsp);
    }
}
