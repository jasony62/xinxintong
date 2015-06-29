<?php
namespace matter;

require_once dirname(__FILE__).'/app_base.php';
/**
 *
 */
class addressbook_model extends app_base {
    /**
     *
     */
    protected function table()
    {
        return 'xxt_addressbook';
    }
    /**
     *
     */
    protected function getMatterType() 
    {
        return 'addressbook';
    }
    /**
    *
    */
    public function getTypeName()
    {
        return 'addressbook';
    }
    /**
    *
    */
    public function getEntryUrl($runningMpid, $id)
    {
        $url = "http://".$_SERVER['HTTP_HOST'];
        $url .= "/rest/app/addressbook";
        $url .= "?mpid=$runningMpid&id=".$id;

        return $url;
    }
    /**
     *
     */
    public function byMpid($mpid)
    {
        $q = array(
            '*',
            'xxt_addressbook',
            "mpid='$mpid'"
        );
        $q2 = array(
            'o'=>'modify_at desc'
        );

        $abs = $this->query_objs_ss($q, $q2);

        return $abs;
    }
    /**
     *
     */
    public function insert_ab($mpid, $uid, $title) 
    {
        $current = time();

        $ab['mpid'] = $mpid;
        $ab['title'] = $title;
        $ab['creater'] = $uid;
        $ab['create_at'] = $current;
        $ab['modify_at'] = $current;

        return $this->insert('xxt_addressbook', $ab, true);
    }
    /**
     *
     */
    public function remove_ab($mpid, $abid)
    {
        $q = array(
            'count(*)',
            'xxt_ab_person_dept',
            "mpid='$mpid' and ab_id=$abid"
        );
        if ($this->query_val_ss($q))
            return array(false, '通讯录不为空不允许删除');

        $q = array(
            'count(*)',
            'xxt_ab_person',
            "mpid='$mpid' and ab_id=$abid"
        );
        if ($this->query_val_ss($q))
            return array(false, '通讯录中的用户不为空不允许删除');

        $q = array(
            'count(*)',
            'xxt_ab_dept',
            "mpid='$mpid' and ab_id=$abid"
        );
        if ($this->query_val_ss($q))
            return array(false, '通讯录中的部门不为空不允许删除');

        $q = array(
            'count(*)',
            'xxt_ab_title',
            "mpid='$mpid' and ab_id=$abid"
        );
        if ($this->query_val_ss($q))
            return array(false, '通讯录中的岗位不为空不允许删除');
        
        $this->delete('xxt_addressbook', "mpid='$mpid' and id=$abid");
        
        return array(true);
    }
    /**
     * 添加部门
     */
    public function addDept($mpid, $abid, $name, $pid, $seq=null) 
    {
        $isAppend = true;
        if ($seq===null) {
            /**
             * 加到父节点的尾 
             */
            $q = array(
                'count(*)',
                'xxt_ab_dept',
                "mpid='$mpid' and ab_id=$abid and pid=$pid"
            );
            $lastSeq = (int)$this->query_val_ss($q);
            $seq = $lastSeq + 1;
        } else {
            $isAppend = false;
        }
        $i = array(
            'mpid' => $mpid,
            'ab_id' => $abid,
            'pid' => $pid,
            'seq' => $seq,
            'name' => $name
        );
        $id = $this->insert('xxt_ab_dept', $i, true);
        /**
         * 更新fullpath
         */
        if ($pid == 0)
            $fullpath = "$id";
        else {
            $q = array(
                'fullpath',
                'xxt_ab_dept',
                "mpid='$mpid' and ab_id=$abid and id=$pid"
            );
            $fullpath = $this->query_val_ss($q);
            $fullpath .= ",$id";
        }
        $this->update(
            'xxt_ab_dept', 
            array('fullpath'=>$fullpath), 
            "mpid='$mpid' and ab_id=$abid and id=$id"
        );

        $dept = $this->query_obj_ss(array('*', 'xxt_ab_dept', "id=$id"));

        return $dept;
    }
    /**
     * 删除部门
     *
     * 如果存在子部门不允许删除
     * 如果存在部门成员不允许删除
     */
    public function delDept($mpid, $id)
    {
        $q = array(
            'pid,seq',
            'xxt_ab_dept',
            "mpid='$mpid' and id=$id"
        );
        if (false === ($dept = $this->query_obj_ss($q))) 
            return array(false, '部门不存在'); 
        /**
         * 是否存在子部门？
         */
        $q = array(
            'count(*)',
            'xxt_ab_dept',
            "mpid='$mpid' and pid=$id"
        );
        if (0 < (int)$this->query_val_ss($q))
            return array(false, '存在子部门，不允许删除'); 
        /**
         * 是否存在成员？
         */
        $q = array(
            'count(*)',
            'xxt_ab_person_dept',
            "mpid='$mpid' and dept_id=$id"
        );
        if (0 < (int)$this->query_val_ss($q))
            return array(false, '存在用户，不允许删除');
        /**
         * 删除部门
         */
        $rst = (int)$this->delete(
            'xxt_ab_dept',
            "mpid='$mpid' and id=$id"
        );
        if ($rst === 1) {
            /**
             * 更新兄弟部门的序号
             */
            $sql = 'update xxt_ab_dept';
            $sql .= ' set seq=seq-1';
            $sql .= " where mpid='$mpid' and pid=$dept->pid and seq>$dept->seq";
            $this->update($sql);
        }

        return array(true);
    }
    /**
     *
     * $mpid
     * $name
     * $email
     * $tels
     * $strict email is unique. true:error,false:return exist id.
     */
    public function createPerson($mpid, $abid, $name, $email=null, $tels=null, $strict = true) 
    {
        if (empty($email)) {
            $q[] = 'id,name';
            $q[] = 'xxt_ab_person';
            $q[] = "mpid='$mpid' and ab_id=$abid and name='$name'";
            if ($p = $this->query_obj_ss($q)) {
                // data replicated
            }
        } else {
            $q[] = 'id,name';
            $q[] = 'xxt_ab_person';
            $q[] = "mpid='$mpid' and ab_id=$abid and email='$email'";
            if ($p = $this->query_obj_ss($q)) {
                if ($p->name != $name) {
                    // data inconsistent.
                }
                if ($strict) {
                    // data replicated.
                }
                return $p->id;
            }
        }
        $i['mpid'] = $mpid;
        $i['ab_id'] = $abid;
        $i['name'] = $name;
        $i['pinyin'] = pinyin($name, 'UTF-8');
        $i['email'] = $email;
        $i['tels'] = $tels;

        $person_id = $this->insert('xxt_ab_person', $i, true);

        return $person_id;
    }
    /**
     * 给用户添加部门
     */
    public function addPersonDept($mpid, $abid, $person_id, $dept_id) 
    {
        $q[] = 'id';
        $q[] = 'xxt_ab_person_dept';
        $q[] = "mpid='$mpid' and ab_id=$abid and person_id=$person_id and dept_id=$dept_id";
        if ($id = $this->query_val_ss($q))
            return $id; 

        $i['mpid'] = $mpid;
        $i['ab_id'] = $abid;
        $i['person_id'] = $person_id;
        $i['dept_id'] = $dept_id;

        return $this->insert('xxt_ab_person_dept', $i, true);
    }
    /**
     *
     */
    public function getPersonById($person_id) 
    {
        $cols = 'id,name,email,tels,remark,tags,ab_id';

        return $this->query_obj($cols, 'xxt_ab_person', "id=$person_id");
    }
    /**
     *
     */
    public function getDeptByPerson($person_id) 
    {
        $q = array(
            'pd.id,pd.dept_id,d.name,d.pid',
            'xxt_ab_person_dept pd,xxt_ab_dept d',
            "pd.dept_id = d.id and pd.person_id = $person_id"
        );

        return $this->query_objs_ss($q);
    }
    /**
     *
     */
    public function getPersonByAb($mpid, $abid, $abbr = null, $dept_id = null, $offset = 0, $limit = null) 
    {
        $mpa = \TMS_APP::model('mp\mpaccount')->byId($mpid, 'parent_mpid');
        //
        $cols = 'SQL_CALC_FOUND_ROWS id,name,email,tels';
        $from = 'xxt_ab_person';
        $where = "(mpid='$mpid' or mpid='$mpa->parent_mpid')";

        if (!empty($abid)) $where .= " and ab_id=$abid";

        if ($abbr) {
            if (ord($abbr[0]) > 0x80)
                $where .= " and (name like '%$abbr%')";
            else { //ascii
                $abbr_len = strlen($abbr);
                $abbr_cond = ".*";
                for ($i = 0; $i < $abbr_len; $i++)
                    $abbr_cond .= $abbr[$i] . ".*";
                $where .= " and ((pinyin regexp '";
                $where .= $abbr_cond;
                $where .= "') = 1)";
            }
        }
        if ($dept_id) {
            $where .= " and (";
            $where .= " id in(";
            $where .= "select person_id from xxt_ab_person_dept";
            $where .= " where dept_id = $dept_id";
            $where .= ")";
            $where .= ")";
        }
        //
        $result = new \stdClass;
        $result->objects = $this->query_objs($cols, 'xxt_ab_person', $where, null, null, $offset, $limit);
        $result->amount = $this->found_rows();

        return $result;
    }
    /**
     * 检索通讯录
     *
     * 如果按关键词搜索，如果是中文，优先看是否能匹配部门，如果匹配了，就不再配置用户，提高执行速度
     */
    public function searchPersons($mpid, $abid, $abbr = null, $deptid = null, $page = 1, $size = 20) 
    {
        $q = array(
            'id,name,email,tels',
            'xxt_ab_person',
            "mpid='$mpid' and ab_id=$abid"
        );
        if (!empty($abbr)) {
            if (ord($abbr[0]) > 0x80) {
                $q[2] .= " and (name like '%$abbr%')";
            } else { //ascii
                $abbr_len = strlen($abbr);
                $abbr_cond = ".*";
                for ($i = 0; $i < $abbr_len; $i++)
                    $abbr_cond .= $abbr[$i] . ".*";
                $q[2] .= " and ((pinyin regexp '";
                $q[2] .= $abbr_cond;
                $q[2] .= "') = 1)";
            }
        }
        if (!empty($deptid)) {
            $q[2] .= " and (";
            $q[2] .= " id in(";
            $q[2] .= "select person_id from xxt_ab_person_dept";
            $q[2] .= " where dept_id=$deptid";
            $q[2] .= ")";
            $q[2] .= ")";
        }

        $q2 = array();
        $q2['r'] = array('o'=>($page-1)*$size, 'l'=>$size);
        $q2['o'] = 'pinyin';

        $persons = $this->query_objs_ss($q, $q2);

        $q[0] = 'count(*)';
        $amount = $this->query_val_ss($q);
        /**
         * 获得用户所属的部门
         */
        $q = array(
            'd.id,d.name',
            'xxt_ab_person_dept pd,xxt_ab_dept d',
        );
        foreach ($persons as &$p) {
            $q[2] = "pd.dept_id=d.id and pd.person_id=$p->id";
            $p->depts = $this->query_objs_ss($q);
        }

        return array($persons, $amount);
    }
}