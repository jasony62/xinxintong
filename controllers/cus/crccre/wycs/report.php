<?php
namespace cus\crccre\wycs;

require_once dirname(__FILE__).'/submit_base.php';
/**
 * 提交维修单
 */
class report extends submit_base {
    /**
     *
     */
    public function submit_action($mpid, $mocker='')
    {
        $data = $this->getPostJson();

        if (empty($data->content))
            return new \ResponseError('没有提供报修内容');

        $openid = empty($mocker) ? $this->getCookieOAuthUser($mpid) : $mocker;

        if (empty($openid))
            return new \ResponseError('无法获得openid');

        $projectid = $this->getProjectId($mpid);
        $billType = "维修单";
        
        return $this->doSubmit($mpid, $openid, $projectid, $data, $billType);
    }
}
