<?php
namespace cus\crccre\member;

require_once dirname(__FILE__).'/base.php';
/**
 * crccre用户数据同步
 */
class sync extends crccre_member_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 同步部门数据
     *
     * 清空原有数据？
     * 获得所有部门数据，并更新本地数据
     *
     * $mpid 指定将数据更新到哪个账号
     */
    public function department_action($mpid)
    {
        /**
         * 清空现有数据，有必要吗？
         */
        $this->model()->delete(
            'xxt_member_department',
            "mpid='$mpid'"
        );

        $depts = array();
        try {
            $param = new \stdClass;
            $param->titleType = 1;    
            $ret = $this->soap()->GetNodesByTitleType($param);
            $xml = new \SimpleXMLElement($ret->GetNodesByTitleTypeResult);
            foreach ($xml->children() as $node) {
                $dept = array();
                foreach ($node->attributes() as $k => $v)
                    $dept[$k] = ''.$v;
                $did = $this->addDept($mpid, 0, $dept);
                $depts[] = $dept;
                $this->getSubDepartment($mpid, $dept['guid'], $did, $depts);
            }
        } catch (\Exception $e) {
            die('exception: '.$e->getMessage());
        }
        return new \ResponseData(count($depts));
    }
    /**
     * 获得部门下面的子部门
     */
    private function getSubDepartment($mpid, $guid, $pid, &$depts)
    {
        try {
            $param = new \stdClass;
            $param->GUID = $guid;
            $ret = $this->soap()->GetNodesByGUID($param);
            $xml = new \SimpleXMLElement('<xml>'.$ret->GetNodesByGUIDResult.'</xml>');
            foreach ($xml->children() as $node) {
                $attributes = ''.$node->attributes();
                $titletype = ''.$attributes['titletype'];
                if (1 !== preg_match('/^[1,2,3].*/', $titletype)) continue;
                $dept = array();
                foreach ($attributes as $k => $v)
                    $dept[$k] = ''.$v;
                $did = $this->addDept($mpid, $pid, $dept);
                $depts[] = $dept;
                $this->getSubDepartment($mpid, $dept['guid'], $did, $depts);
            }
        } catch (\Exception $e) {
            echo 'exception: '.$e->getMessage();
        }
        return new \ResponseData(1);
    }
    /**
     * 添加部门
     */
    private function addDept($mpid, $pid, $dept) 
    {
        $name = $dept['title'];
        $extattr = json_encode($dept);
        /**
         * 加到父节点的尾 
         */
        $q = array(
            'count(*)',
            'xxt_member_department',
            "mpid='$mpid' and pid=$pid"
        );
        $lastSeq = (int)$this->model()->query_val_ss($q);
        $seq = $lastSeq + 1;

        $i = array(
            'mpid' => $mpid,
            'pid' => $pid,
            'seq' => $seq,
            'name' => $name,
            'extattr' => $extattr,
        );
        $id = $this->model()->insert('xxt_member_department', $i, true);

        return $id;
    }
    /**
     * 更新已经通过认证的用户的信息
     */
    public function user_action($mpid)
    {
        /**
         * 清空原有关系数据
         */
        $this->model()->update(
            'xxt_member', 
            array('depts'=>''), 
            "mpid='$mpid'"
        );
        $q = array(
            'mid,authed_identity,authapi_id',
            'xxt_member',
            "mpid='$mpid'"
        );
        $users = $this->model()->query_objs_ss($q);
        foreach ($users as $u)
            $rst = $this->getOneUser($mpid, $u);

        return new \ResponseData(count($users));
    }
    /**
     *
     */
    private function getOneUser($mpid, $member) 
    {
        $userAccount = $member->authed_identity;
        /**
         * 获得所有属性信息
         */
        $param = new \stdClass;
        $param->userAccount = $userAccount;    
        $ret = $this->soap()->GetUserByAccount($param);
        $xml = new \SimpleXMLElement($ret->GetUserByAccountResult);
        foreach ($xml->children() as $node) {
            $user = array();
            foreach ($node->attributes() as $k => $v)
                $user[$k] = ''.$v;
        }
        if (!isset($user)) return 0;
        /**
         * 获得所属部门信息
         */
        $parentid = $user['parentid'];
        $depts = array();
        $this->getSupDepartment($mpid, $parentid, $depts);

        $deptids = array();
        foreach ($depts as $dept)
            array_splice($deptids, 0, 0, $dept['deptid']);

        $rst = $this->model()->update(
            'xxt_member',
            array(
                'name'=>$user['title'],
                'depts'=>json_encode(array($deptids))
            ),
            "mpid='$mpid' and mid='$member->mid'"
        );

        return $rst;
    }
    /**
     *
     */
    public function check1_action($mpid)
    {
        /*$sql = 'select * from xxt_fans';
        $sql .= " where openid='okWw3t86oKybXfBxBTboh7ORKnLw'";
        $fans = $this->model()->query_objs($sql);

        return new \ResponseData($fans);*/

        /*$sql = "select * from xxt_member";
        $sql .= " where mpid='$mpid' and forbidden='N' and ooid='okWw3t5Iv_qgzte6BtL4IdJby4i8' and authapi_id=4";

        $members = $this->model()->query_objs($sql);

        return new \ResponseData($members);*/

        /*$sql = 'select * from xxt_member_department';
        $sql .= " where mpid='$mpid'";
        $depts = $this->model()->query_objs($sql);

        return new \ResponseData($depts);*/

        /*$sql = "select * from xxt_member";
        $sql .= " where mpid='$mpid' and  fid = '4a56981fb1a2fc60317ae792909ca413'";
        $depts = $this->model()->query_objs($sql);

        return new \ResponseData($depts);*/

        /*$sql = "select * from xxt_member";
        $sql .= " where mpid='$mpid' and authapi_id=2";
        $members = $this->model()->query_objs($sql);*/

        //return new \ResponseData($members);

        $rst = $this->model()->delete('xxt_member', 'authapi_id=2');
        return new \ResponseData($rst);

        /*$rst = $this->model()->update(
            'xxt_member', 
            array('depts'=>''), 
            "mpid='$mpid' and  depts like '%false%'"
        );
        return new \ResponseData($rst);*/

        /*$rst = $this->model()->update(
            'xxt_member', 
            array('fid'=>'546f6497f2d22be49344605f39e4455f','ooid'=>'okWw3t86oKybXfBxBTboh7ORKnLw'), 
            "mpid='$mpid' and mid='d4a1f0c90b2ee33b027b10b53f183282'"
        );

        return new \ResponseData($rst);*/
    }
}
