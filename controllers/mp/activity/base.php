<?php
require_once dirname(dirname(__FILE__)).'/mp_controller.php';
/**
 *
 */
class act_base extends mp_controller {
    /**
     *
     */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 设置访问白名单
     */
    public function setAcl_action($actid)
    {
        $acl = $this->getPostJson();
        if (isset($acl->id)) {
            $u['identity'] = $acl->identity;
            $rst = $this->model()->update('xxt_act_acl', $u, "id=$acl->id");
            return new ResponseData($rst);
        } else {
            $i['mpid'] = $this->mpid;
            $i['act_type'] = $this->getActType();
            $i['act_id'] = $actid;
            $i['identity'] = $acl->identity;
            $i['idsrc'] = $acl->idsrc;
            $i['label'] = $acl->label;
            $i['id'] = $this->model()->insert('xxt_act_acl', $i, true);

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
        $rst = $this->model()->delete('xxt_act_acl', "mpid='$this->mpid' and id=$acl");

        return new ResponseData($rst);
    }
}
