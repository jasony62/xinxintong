<?php
require_once dirname(__FILE__).'/submit_base.php';
/**
 * 提交建议单
 */
class propose extends submit_base {
    /**
     *
     */
    public function index_action($projectid, $code=null, $openid='') 
    {
        /**
         * 为测试方便使用
         */
        if (!empty($openid)) {
            $encoded = $this->model()->encrypt($openid, 'ENCODE', $projectid);
            $this->mySetcookie("_{$projectid}_oauth", $encoded);
        } else
            $openid = $code === null ? $this->oauth($projectid) : $this->getWxOAuthUser($projectid, $code);

        $this->openView($projectid, $openid, '/custom/propose');
    }
    /**
     *
     */
    public function submit_action($projectid)
    {
        $openid = $this->getOAuthUser($projectid);

        empty($openid) && $this->outputError('无法获得当前用户的openid。');

        $data = $this->getPostJson();
        $billType = "建议单";

        return $this->doSubmit($openid, $projectid, $data, $billType);
    }
}
