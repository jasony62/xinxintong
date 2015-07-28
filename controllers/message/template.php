<?php
namespace message;

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
        $messages = $this->getPostJson();
        !is_object($messages) && $messages = array($messages);

        $failed = array();

        if (!empty($auth_url)) {
            if (!($authapi = $this->model('user/authapi')->byUrl($mpid, $auth_url, 'authid')))
                return new \ResponseError('没有定义身份认证接口，无法进行身份转换，消息发送失败！');

            $authid = $authapi->authid;
        }
        /**
         *
         */
        $mpproxy = \TMS_APP::M('mpproxy/wx', $mpid);
        foreach ($messages as $msg) {
            $msg = (object)$msg;
            if (isset($authid)) {
                $authuser = $msg->touser;
                $q = array(
                    'openid',
                    'xxt_member m',
                    "m.forbidden='N' and m.authed_identity='$authuser'"
                );
                if (!($openid = $this->model()->query_val_ss($q))) {
                    $msg->errmsg = '无法获得openid'; 
                    $failed[] = $msg;
                }
                $msg->touser = $openid;
            }
            $rst = $mpproxy->messageTemplateSend($msg);
            if ($rst[0] === false) {
                $msg->errmsg = $rst[1]; 
                $failed[] = $msg;
            }
        }

        if (empty($failed))
            return new \ResponseData('finish');
        else
            return new \ResponseError($failed);
    }
}
