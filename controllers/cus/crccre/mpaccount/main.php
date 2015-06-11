<?php
namespace cus\crccre\mpaccount;

require_once dirname(dirname(dirname(dirname(__FILE__)))).'/xxt_base.php';
/**
 * 公众号平台基本服务 
 */
class main extends \xxt_base {
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
        $mpa = $this->model('mp\mpaacount')->byId($mpid);
        $mpproxy = $this->model('mpproxy/'.$mpa->mpsrc, $mpid);

        $rst = $mpproxy->accessToken($mpid, 'wx');

        if ($rst[0] === false)
            return new \ResponseError($rst[1]);
        else
            return new \ResponseData($rst[1]);
    }
    /**
     *
     */
    public function wxjssdksignpackage_action($mpid, $url)
    {
        $mpa = $this->model('mp\mpaccount')->byId($mpid);
        $mpproxy = $this->model('mpproxy/'.$mpa->mpsrc, $mpid);

        $rst = $mpproxy->getJssdkSignPackage(urldecode($url));

        header('Content-Type: text/javascript');
        if ($rst[0] === false)
            die("alert('{$rst[1]}');");

        die($rst[1]);
    }
    /**
     * 下载媒体文件
     *
     * $mpid
     * $mediaid
     */
    public function downloadMediaUrl_action($mpid, $mediaid)
    {
        $mpproxy = $this->model('mpproxy/wx', $mpid);

        $rst = $mpproxy->mediaGetUrl($mediaid);

        if ($rst[0] === false)
            return new \ResponseError($rst[1]);

        return new \ResponseData($rst[1]);
    }
}
