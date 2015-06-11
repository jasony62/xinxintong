<?php
namespace cus\crccre\message;

require_once dirname(dirname(dirname(dirname(__FILE__)))).'/xxt_base.php';
/**
 * 向公众号用户发送模板消息 
 */
class template extends \xxt_base {
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
     * 发送模板消息 
     *
     * $mpid
     * $authid 如果传递的消息中用户的ID需要翻译为openid，那么需要提供进行身份认证的接口
     */
    public function send_action($mpid, $authapi=null)
    {
        $messages = $this->getPostJson();
        !is_array($messages) && $messages = array($messages);

        $failed = array();
        /**
         * 接收人身份认证消息接口
         */
        if (!empty($authapi)) {
            if (!($authapi = $this->model('user/authapi')->byUrl($mpid, $authapi, 'authid')))
                return new \ResponseError('没有定义身份认证接口，无法进行身份转换，消息发送失败！');

            $authid = $authapi->authid;
        }
        /**
         * 处理消息数据并发送
         */
        $mpproxy = \TMS_APP::M('mpproxy/wx', $mpid);
        foreach ($messages as $msg) {
            if (isset($authid)) {
                /**
                 * 转换用户标识
                 */
                $authuser = $msg->touser;
                $q = array(
                    'openid',
                    'xxt_member m,xxt_fans f',
                    "m.fid=f.fid and forbidden='N' and m.authed_identity='$authuser' and f.unsubscribe_at=0"
                );
                if (!($openid = $this->model()->query_val_ss($q))) {
                    $msg->errmsg = '无法获得openid'; 
                    $failed[] = $msg;
                }
                $msg->touser = $openid;
            }
            /**
             * 发送消息
             */
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
    /**
     * 发送待办事项模板消息 
     *
     * $userid 用户的业务身份标识
     * $text 发送的文本信息
     * $url 消息中打开业务链接的url
     * $mpid todo 需要根据运行环境指定
     */
    public function senddbshx_action($userid, $text, $url='', $mpid='acb98ae744dc305b8dc51c857982452f')
    {
        /**
         * 向服务号发送
         */
        $q = array(
            'f.openid',
            'xxt_member m,xxt_fans f',
            "f.mpid='$mpid' and m.fid=f.fid and m.forbidden='N' and m.authed_identity='$userid' and f.unsubscribe_at=0 and exists(select 1 from xxt_member_authapi a where m.authapi_id=a.authid and a.valid='Y')"
        );
        $openids = $this->model()->query_vals_ss($q);
        if (!empty($openids)) {
            if (!empty($url)) {
                $mpsrc = strpos($url, '?') ? '&' : '?';
                $mpsrc .= 'mpsrc=wx'; 
                $url .= $mpsrc;
            } 
            /**
             * 处理消息数据并发送
             */
            $msg = array(
                'template_id'=>'yHKOEV6FxO7WhMIbm0ncKzH2lgY37s3DqPk11hniAZU',
                'url'=>$url,
                'topcolor'=>'#FF0000',
                'data'=>array(
                    'first'=>array(
                        'value'=>'您好，您有以下工作项需要处理。',
                        'color'=>'#0050A0'
                    ),
                    'keynote1'=>array(
                        'value'=>$text,
                        'color'=>'#0050A0'
                    ),
                    'keynote2'=>array(
                        'value'=>date("Y-m-d H:i"),
                        'color'=>'#0050A0'
                    ),
                    'remark'=>array(
                        'value'=>'请您及时办理，如有疑问，请联系电话010-52689973。',
                        'color'=>'#0050A0'
                    )
                )
            );
            /**
             * 发送消息
             */
            $mpproxy = \TMS_APP::M('mpproxy/wx', $mpid);
            foreach ($openids as $openid) {
                $msg['touser'] = $openid;
                $rst = $mpproxy->messageTemplateSend($msg);
                if ($rst[0] === false)
                    return new \ResponseError($rst[1]);
            }
        }
        /**
         * todo 临时方法，向企业号同时发布
         */
        $content = urlencode($text);
        if (!empty($url)) {
            $mpsrc = strpos($url, '?') ? '&' : '?';
            $mpsrc .= 'mpsrc=qy'; 
            $content .= " <a href='$url$mpsrc'>".urlencode('详情')."</a>";
        }
        $message = array(
            "msgtype"=>"text",
            "touser"=>$userid, 
            "text"=>array(
                "content"=>$content
            )
        );
        $rsp = $this->send_to_qyuser('cbf5e6c0b273373fc085a985e4af7725', $message);
        if (true === $rsp[0])
            return new \ResponseData('ok');
        else
            return new \ResponseError($rsp[1]);
    }
}
