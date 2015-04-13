<?php
require_once dirname(dirname(__FILE__)).'/mp_controller.php';

class call_base extends mp_controller {
    /**
     * 设置访问白名单
     */
    public function setAcl_action($k)
    {
        if (empty($k)) die('parameters invalid.');

        $acl = $this->getPostJson();
        if (isset($acl->id)) {
            /**
             * 直接输入acl的情况下，才会产生更新操作，只允许更新identity这一个字段
             */
            $u['identity'] = $acl->identity;
            $rst = $this->model()->update('xxt_call_acl', $u, "id=$acl->id");
            return new ResponseData($rst);
        } else {
            $i['mpid'] = $this->mpid;
            $i['call_type'] = $this->getCallType();
            $i['keyword'] = $k;
            $i['identity'] = $acl->identity;
            $i['idsrc'] = $acl->idsrc;
            $i['label'] = empty($acl->label) ? '' : $acl->label;
            $i['id'] = $this->model()->insert('xxt_call_acl', $i, true);

            return new ResponseData($i);
        }
    }
    /**
     * 删除访问控制列表
     * $mpid
     * $id
     * $acl aclid
     */
    public function removeAcl_action($acl)
    {
        $ret = $this->model()->delete('xxt_call_acl', "mpid='$this->mpid' and id=$acl");

        return new ResponseData($ret);
    }
}
