<?php
require_once dirname(dirname(__FILE__)).'/xxt_base.php';
/**
 * 向公众号用户发送模板消息 
 */
class template extends xxt_base {
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
     * 发送模板消息页面 
     *
     * $mpid
     * $authid 如果传递的消息中用户的ID需要翻译为openid，那么需要提供进行身份认证的接口
     */
    public function send_action($mpid, $auth_url=null)
    {
        $cmd = 'https://api.weixin.qq.com/cgi-bin/message/template/send';

        $messages = $this->getPostJson();
        !is_object($messages) && $messages = array($messages);

        /*$messages = array(
            array(
                'touser'=>'oXHmUjn5s00MC4av7uKN-iSmXVS0',
                'template_id'=>'zwZNVNjr7D_eZV5fc0mAs1QEHNTuuEcfY_tR1VkSFjI',
                'url'=>'http://www.baidu.com',
                'topcolor'=>'#FF0000',
                'data'=>array(
                    'first'=>array(
                        'value'=>'你好，杨戉，有待办事项',
                        'color'=>'#173177'
                    ),
                    'keyword1'=>array(
                        'value'=>'下午两点到铁建地产交流',
                        'color'=>'#173177'
                    ),
                    'keyword2'=>array(
                        'value'=>'2014年10月22日',
                        'color'=>'#173177'
                    ),
                    'remark'=>array(
                        'value'=>'需要带上方案',
                        'color'=>'#173177'
                    )
                )
            )
        );*/

        $failed = array();

        if (!empty($auth_url)) {
            if (!($authapi = $this->model('user/authapi')->byUrl($mpid, $auth_url, 'authid')))
                return new ResponseData('没有定义身份认证接口，无法进行身份转换，消息发送失败！');

            $authid = $authapi->authid;
        }

        foreach ($messages as $msg) {
            $msg = (object)$msg;
            if (isset($authid)) {
                $authuser = $msg->touser;
                $q = array(
                    'openid',
                    'xxt_member m,xxt_fans f',
                    "m.fid=f.fid and m.forbidden='N' and m.authed_identity='$authuser' and f.src='wx' and f.unsubscribe_at=0"
                );
                if (!($openid = $this->model()->query_val_ss($q))) {
                    $msg->errmsg = '无法获得openid'; 
                    $failed[] = $msg;
                }
                $msg->touser = $openid;
            }
            $posted = json_encode($msg);
            $rst = $this->postToMp($mpid, 'wx', $cmd, $posted);
            if (!$rst[0]) {
                $msg->errmsg = $rst[1]; 
                $failed[] = $msg;
            }
        }

        if (empty($failed))
            return new ResponseData('finish');
        else
            return new ResponseError($failed);
    }
}
