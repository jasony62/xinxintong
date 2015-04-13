<?php
require_once dirname(dirname(__FILE__)).'/mp_controller.php';

class matter_ctrl extends mp_controller {

    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';
        return $rule_action;
    }
    /**
     * 设置访问白名单
     */
    public function setAcl_action($id)
    {
        if (empty($id))
            die('parameters invalid.');

        $acl = $this->getPostJson();
        if (isset($acl->id)) {
            $u['identity'] = $acl->identity;
            $rst = $this->model()->update('xxt_matter_acl', $u, "id=$acl->id");

            return new ResponseData($rst);
        } else {
            $i['mpid'] = $this->mpid;
            $i['matter_type'] = $this->getAclMatterType();
            $i['matter_id'] = $id;
            $i['identity'] = $acl->identity;
            $i['idsrc'] = $acl->idsrc;
            $i['label'] = isset($acl->label) ? $acl->label : '';
            $i['id'] = $this->model()->insert('xxt_matter_acl', $i, true);

            return new ResponseData($i);
        }
    }
    /**
     * 删除访问控制列表
     * $acl aclid
     */
    public function removeAcl_action($acl)
    {
        $rst = $this->model()->delete(
            'xxt_matter_acl', 
            "mpid='$this->mpid' and id=$acl"
        );
        return new ResponseData($rst);
    }
}
