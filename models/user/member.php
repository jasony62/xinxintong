<?php
class member_model extends TMS_MODEL {
    /**
     * 创建一个认证用户
     *
     * 要求认证用户必须管理一个关注用户
     *
     * $fid 关注用户id
     * $data
     * $attrs
     */
    public function create($fid, $data, $attrs) 
    {
        if (is_array($data)) $data = (object)$data;
        /**
         * 处理访问口令
         */
        if ($attrs->attr_password[0] === '0') {
            if (empty($data->password) || strlen($data->password) < 6)
                return array(false, '密码长度不符合要求');
            $salt = $this->gen_salt();
            $cpw = $this->compile_password($data->authed_identity, $data->password, $salt);
            $data->password = $cpw;
            $data->password_salt = $salt;
        }

        $create_at = time();
        $mid = md5(uniqid().$create_at); //member's id
        $data->mid = $mid;
        $data->fid = $fid;
        $data->create_at = $create_at;
        /**
         * 扩展属性
         */
        if (!empty($attrs->extattr)) {
            $extdata = array();
            foreach ($attrs->extattr as $ea) {
                if (isset($data->{$ea->id})) {
                    $extdata[$ea->id] = urlencode($data->{$ea->id});
                    unset($data->{$ea->id});
                }
            }
            $data->extattr = urldecode(json_encode($extdata));
        } else
            $data->extattr = '{}';

        $this->insert('xxt_member', (array)$data, false);

        return array(true, $mid);
    }
    /**
     * 获得认证用户的信息
     */
    public function &byId($mid, $fields='*', $contain=array())
    {
        $q = array(
            $fields,
            'xxt_member',
            "mid='$mid'"
        );
        $m = $this->query_obj_ss($q);
        /**
         * 部门信息
         */
        if (in_array('dept', $contain)) {
            if (isset($m->depts) && !empty($m->depts))
                $m->depts = $this->getDepts($m->mid, $m->depts);
            else if (!isset($m->depts))
                $m->depts = $this->getDepts($m->mid);
            else
                $m->depts = array();
        }
        /**
         * 标签信息
         */
        if (in_array('tag', $contain)) {
        }

        return $m;
    }
    /**
     * 一个关注用户，对应一个认证接口只能有一个认证身份
     * 所以不指定认证接口，返回用户的所有的认证身份，如果指定，只返回对应认证接口的认证身份
     * 只返回有效的认证身份，如果认证身份被禁用了，不返回
     *
     * $openid
     * $src
     * $fields
     * $authapi
     */
    public function &byOpenid($mpid, $openid, $fields='*', $authid=null)
    {
        $q = array(
            $fields,
            'xxt_member m,xxt_fans f',
            "m.mpid='$mpid' and m.fid=f.fid and f.openid='$openid' and m.forbidden='N' and exists(select 1 from xxt_member_authapi a where m.authapi_id=a.authid and a.valid='Y')"
        );
        if (empty($authid))
            $member = $this->query_objs_ss($q);
        else {
            $q[2] .= " and m.authapi_id=$authid";
            $member = $this->query_obj_ss($q);
        }

        return $member;
    }
    /**
     * 返回关注用户的认证用户信息
     */
    public function &byFanid($mpid, $fid, $fields='*', $authapi=null)
    {
        $q = array(
            $fields,
            'xxt_member m',
            "m.mpid='$mpid' and m.fid='$fid' and m.forbidden='N' and exists(select 1 from xxt_member_authapi a where m.authapi_id=a.authid and a.valid='Y')"
        );
        if (empty($authapi))
            $member = $this->query_objs_ss($q);
        else {
            $q[2] .= " and m.authapi_id=$authapi";
            $member = $this->query_obj_ss($q);
        }

        return $member;
    }
    /**
     * 获得指定成员的部门
     */
    public function getDepts($mid, $depts='')
    {
        if (empty($depts)) {
            $member = $this->byId($mid, 'depts');
            $depts = $member->depts;
        }
        if (empty($depts) || $depts === '[]') return array();

        $ids = array();
        $depts = json_decode($depts);
        foreach ($depts as $ds)
            $ids = array_merge($ids, $ds);
        $ids = implode(',',$ids);
        $q = array(
            'distinct id,name',
            'xxt_member_department',
            "id in ($ids)"
        );
        $q2 = array('o'=>'fullpath');

        $depts = $this->query_objs_ss($q, $q2); 

        return $depts;
    }
    /**
     *
     * $mid
     * $tags ids
     * $type
     *
     */
    public function getTags($mid, $tags='', $type=0) 
    {
        if (empty($tags)) {
            $member = $this->byId($mid, 'tags');
            $tags = $member->tags;
        }
        if (empty($tags)) return array();

        $q = array(
            'distinct id,name',
            'xxt_member_tag',
            "type=$type and id in ($tags)"
        );
        $tags = $this->query_objs_ss($q); 

        return $tags;
    }
    /**
     * 判断当前认证信息是否合法
     *
     * $member
     * $attrs array 用户认证信息定义
     *  0:hidden,1:mandatory,2:unique,3:immuatable,4:verification,5:identity
     *
     * return
     *  若不合法，返回描述原因的字符串
     *  合法返回false
     */
    public function rejectAuth($member, $attrs) 
    {
        empty($member->mpid) && die('mpid is empty.');

        $mpid = $member->mpid;
        if (isset($member->mobile) && (int)$attrs->attr_mobile[2] === 1) {
            /**
             * 检查手机号的唯一性
             */
            $mobile = $member->mobile;
            $q = array(
                '1', 
                'xxt_member', 
                "mpid='$mpid' and forbidden='N' and mobile='$mobile'"
            );
            if (1 === (int)$this->query_val_ss($q))
                return '手机号已经认证，不允许重复认证！';
        }
        if (isset($member->email) && (int)$attrs->attr_email[2] === 1) {
            /**
             * 检查邮箱的唯一性
             */
            $email = $member->email;
            $q = array(
                '1', 
                'xxt_member', 
                "mpid='$mpid' and forbidden='N' and email='$email'"
            );
            if (1 === (int)$this->query_val_ss($q))
                return '邮箱已经认证，不允许重复认证！';
        }

        return false;
    }
    /**
     * 根据提交的认证信息，查找已经存在认证用户
     *
     * 要求认证用户必须关联一个关注用户
     *
     * $member
     * $items array 用户认证信息定义
     * 0:hidden,1:mandatory,2:unique,3:immuatable,4:verification,5:identity
     */
    public function findMember($member, $attrs) 
    {
        empty($member->mpid) && die('mpid is empty.');

        $mpid = $member->mpid;
        if (isset($member->mobile) && $attrs->attr_mobile[5] === '1') {
            /**
             * 手机号唯一
             */
            $identity = $member->mobile;
        } else if (isset($member->email) && $attrs->attr_email[5] === '1') {
            /**
             * 邮箱唯一
             */
            $identity = $member->email;
        }
        if (isset($identity)) {
            $q = array(
                'mid,password,password_salt', 
                'xxt_member', 
                "mpid='$mpid' and fid!='' and forbidden='N' and authed_identity='$identity'"
            );
            $found = $this->query_obj_ss($q);
            if (!empty($found)) {
                if ($attrs->attr_password[0] === '0') {
                    /**
                     * 检查口令
                     */
                    $cpw = $this->compile_password($identity, $member->password, $found->password_salt);
                    if ($cpw !== $found->password)
                        return false;
                }
            }
        }

        return !empty($found) ? $found->mid : false;
    }
    /**
     *
     */
    public function addCredits($mid, $credits) 
    {
        $sql = 'update xxt_member';
        $sql .= " set credits=credits+$credits";
        $sql .= " where mid='$mid'";

        return $this->update($sql);
    }
}
