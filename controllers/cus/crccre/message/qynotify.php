<?php
namespace cus\crccre\message;

require_once dirname(dirname(dirname(dirname(__FILE__)))).'/xxt_base.php';
/**
 * 向公众号用户发送模板消息 
 */
class qynotify extends \xxt_base {
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
     * 发送待办事项图文消息 
     *
     * $userid 用户的业务身份标识
     * $title 发送的卡片消息标题
     * $text 发送的卡片消息正文
     * $mpid 对应的企业号应用ID
     */
    public function card_action($mpid, $userid, $title, $text, $summary='', $url='', $picurl='')
    {
        /**
         * 没有指定摘要的情况下，自动提取摘要
         */
        $textlen = mb_strlen($text, 'utf-8'); 
        if ($textlen > 120) 
            empty($summary) && $summary = mb_substr($text, 0, 120, 'utf-8');
        else
            $summary = $text;
        /**
         * 没有指定链接，且文本消息超过120个字的情况下，自动生成单图文
         */
        if (empty($url) && $textlen > 120) {
            $current = time();
            $d['mpid'] = $mpid;
            $d['creater'] = '';
            $d['creater_src'] = 'I';
            $d['creater_name'] = 'crccre';
            $d['create_at'] = $current;
            $d['modify_at'] = $current;
            $d['title'] = $title;
            $d['pic'] = $picurl;
            $d['hide_pic'] = 'Y';
            $d['summary'] = $summary;
            $d['url'] = '';
            $d['body'] = $text;
            $id = $this->model()->insert('xxt_article', $d, true);
            
            //$url = $this->model('matter\article')->getEntryUrl($mpid, $id);
            $url = "http://mi.crccre.com";
            $url .= "/rest/mi/matter";
            $url .= "?mpid=$mpid&id=$id&type=article";
        }
        
        $card = array(
            'title' => urlencode($title),
            'description' => urlencode($summary),
            'url' => $url,
            'picurl'=>$picurl,
        );
        
        $msg = array(
            'msgtype' => 'news',
            'news' => array(
                'articles'=>array($card)
            ),
            'touser' => $userid
        );

        $rst = $this->send_to_qyuser($mpid, $msg);
        
        if (false === $rst[0])
            return new \ResponseError($rst[1]);
        else
            return new \ResponseData('ok');
    }
}
