<?php
require_once dirname(dirname(__FILE__)).'/xxt_base.php';
/**
 *
 */
class store extends xxt_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'] = array('pic');

        return $rule_action;
    }
    /**
     * 保存图片数据 
     */
    public function pic_action($mpid) 
    {
        $pics = $this->getPostJson();
        $fsmodel = $this->model('fs/user', $mpid);
        $urls = array();
        foreach ($pics as $pic) {
            if (!isset($pic->data) && !isset($pic->serverId))
                return new ResponseError('数据为空');

            if (0 === strpos($pic->data, 'http'))
                /**
                 * url
                 */
                $rst = $fsmodel->storeUrl($pic->data);
            else if (1 === preg_match('/data:image(.+?);base64/', $pic->data))
                /**
                 * base64
                 */
                $rst = $fsmodel->storeBase64($pic->data);
            else if ('wx' === $this->getClientSrc() && isset($pic->serverId)) {
                /**
                 * wx jssdk
                 */
                $rst = $this->model('mpproxy/wx', $mpid)->mediaGetUrl($pic->serverId);
                if ($rst[0] === false)
                    return new ResponseError($rst[1]);
                $rst = $fsmodel->storeUrl($rst[1]);
            } else
                return new ResponseError('数据格式错误');

            if ($rst[0] === false)
                return new ResponseError($rst[1]);
            $urls[] = $rst[1];
        }

        return new ResponseData($urls);
    }
}
