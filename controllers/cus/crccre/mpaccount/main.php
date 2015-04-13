<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))).'/xxt_base.php';
/**
 * 公众号平台基本服务 
 */
class main extends xxt_base {
    /**
     *
     */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'] = array();
        $rule_action['actions'][] = 'accessToken';
        $rule_action['actions'][] = 'wxjssdksignpackage';
        $rule_action['actions'][] = 'downloadMediaUrl';

        return $rule_action;
    }
    /**
     * 获得指定公众号的accesstoken 
     *
     * $mpid 公众号的内部ID
     */
    public function accessToken_action($mpid)
    {
        $rst = $this->access_token($mpid, 'wx');
        if ($rst[0] === false)
            return new ResponseError($rst[1]);
        else
            return new ResponseData($rst[1]);
    }
    /**
     *
     */
    public function wxjssdksignpackage_action($mpid, $url)
    {
        $rst = $this->getWxjssdkSignPackage($mpid, urldecode($url));
        if ($rst[0] === false) {
            header('Content-Type: text/javascript');
            die("alert('{$rst[1]}');");
        }

        $signPackage = $rst[1];

        $js = "signPackage={appId:'{$signPackage['appId']}'";
        $js .= ",nonceStr:'{$signPackage['nonceStr']}'";
        $js .= ",timestamp:'{$signPackage['timestamp']}'";
        $js .= ",url:'{$signPackage['url']}'";
        $js .= ",signature:'{$signPackage['signature']}'};";
        //$js .= ",rawString:'{$signPackage['rawString']}'};";

        header('Content-Type: text/javascript');
        die($js);
    }
    /**
     * 下载媒体文件
     *
     * $mpid
     * $mediaid
     */
    public function downloadMediaUrl_action($mpid, $mediaid)
    {
        $cmd = 'http://file.api.weixin.qq.com/cgi-bin/media/get';
        $params = array(
            'media_id'=>$mediaid
        );

        $token = $this->access_token($mpid, 'wx');
        if ($token[0] === false) return new ResponseError($token[1]);

        $url = $cmd;
        $url .= "?access_token={$token[1]}";
        !empty($params) && $url .= '&'.http_build_query($params);

        return new ResponseData($url);
    }
}
