<?php
namespace pl\fe\matter\addressbook;

require_once dirname(dirname(__FILE__)) . '/base.php';

/**
 * 通讯录主控制器
 */
class main extends \pl\fe\matter\base {
  

    protected function getMatterType() {
        return 'addressbook';
    }

    /**
     * 通讯录默认方法
     */
    public function index_action() {
        \TPL::output('/pl/fe/matter/addressbook/frame');
        die;
    }

    /**
     * 使用tms_controller.php原类的model
     */
    public function get_action($site,$abid = null) {
        if (empty($abid)) {
            $abs = $this->model('matter\addressbook')->bySiteId($site);
            return new \ResponseData($abs);
        } else {
            $ab = $this->model('matter\addressbook')->byId($abid);
            /**
             * acl
             */
            $ab->acl = $this->model('acl')->byMatter($site, 'addressbook', $abid);

            return new \ResponseData($ab);
        }
    }

    /**
     * 创建通讯录
     */
    public function create_action($site,$title = '新通讯录') {
        $uid = \TMS_CLIENT::get_client_uid();

        $abid = $this->model('matter\addressbook')->insert_ab($site, $uid, $title);
        
        $app = $this->model('matter\addressbook')->byId($abid);
        $user=$this->accountUser();
        /* 记录操作日志 */
        $app->type = 'addressbook';
        $this->model('matter\log')->matterOp($site, $user, $app, 'C');
        return new \ResponseData($abid);
    }

    /**
     * 删除通讯录
     */
    public function remove_action($site,$id) {
         /*记录操作日志*/
        $user = $this->accountUser();
        $app = $this->model('matter\\addressbook')->byId($id);
        $app->type = 'addressbook';
        $this->model('matter\log')->matterOp($site, $user, $app, 'D');
        //删除
        $rst = $this->model('matter\addressbook')->remove_ab($site, $id);

        if ($rst[0])
            return new \ResponseData('success');
        else
            return new \ResponseError($rst[1]);
    }

    /**
     * 更新通讯录基本设置
     *
     * $nv pair of name and value
     */
    public function update_action($site,$abid) {
        $nv = (array) $this->getPostJson();

        $nv['modify_at'] = time();

        isset($nv['pic']) && $nv['pic'] = $this->model()->escape($nv['pic']);

        $rst = $this->model()->update(
                'xxt_addressbook', (array) $nv, "siteid='$site' and id='$abid'"
        );
        /*记录操作日志*/
        $user=$this->accountUser();
        $matter = $this->model('matter\addressbook')->byId($abid);
        $matter->type = 'addressbook';
        $this->model('matter\log')->matterOp($site, $user, $matter, 'U');

        return new \ResponseData($rst);
    }

    /**
     * 获得部门列表
     */
    public function dept_action($site,$abid, $pid = 0) {
        $q = array(
            'id,name',
            'xxt_ab_dept',
            "siteid='$site' and ab_id=$abid and pid=$pid"
        );

        $q2 = array('o' => 'seq');

        $depts = $this->model()->query_objs_ss($q, $q2);

        return new \ResponseData($depts);
    }

    /**
     * 添加部门
     *
     * $pid
     * $seq 如果没有指定位置，就插入到最后。序号从1开始。
     */
    public function addDept_action($site,$abid, $pid = 0, $seq = null) {
        $dept = $this->model('matter\addressbook')->addDept($site, $abid, '新部门', $pid, $seq);

        return new \ResponseData($dept);
    }

    /**
     * 更新部门信息
     *
     * $id
     */
    public function updateDept_action($site,$id) {
        $nv = $this->getPostJson();

        $rst = $this->model()->update(
                'xxt_ab_dept', (array) $nv, "siteid='$site' and id=$id"
        );

        return new \ResponseData($rst);
    }

    /**
     * 删除部门
     *
     * 如果存在子部门不允许删除
     * 如果存在部门成员不允许删除
     */
    public function delDept_action($site,$id) {
        $rst = $this->model('matter\addressbook')->delDept($site, $id);

        if ($rst[0] === false)
            return new \ResponseError($rst[1]);
        else
            return new \ResponseData(true);
    }

    /**
     * 设置部门的父部门
     */
    public function setDeptParent_action($site,$id, $pid) {
        $rst = $this->model()->update(
                'xxt_ab_dept', array('pid' => $pid), "siteid='$site' and id=$id"
        );

        return new \ResponseData($rst);
    }

    /**
     * 获得联系人信息（列表/详细）
     *
     * $id
     * $abbr
     * $page
     * $size
     */
    public function person_action($site,$abid, $id = null, $abbr = '', $page = 1, $size = 30) {
        $model = $this->model('matter\addressbook');

        if (empty($id)) {
            $offset = ($page - 1) * $size;
            $dept_id = null;

            $persons = $model->getPersonByAb($site, $abid, $abbr, $dept_id, $offset, $size);

            return new \ResponseData($persons);
        } else {
            $person = $model->getPersonById($id);
            $person->depts = $model->getDeptByPerson($id);

            return new \ResponseData($person);
        }
    }

    /**
     * 创建新联系人
     */
    public function personCreate_action($site,$abid) {
        $model = $this->model('matter\addressbook');
        $name = '新联系人';

        $id = $model->createPerson($site, $abid, $name);

        $person = $model->getPersonById($id);

        return new \ResponseData($person);
    }

    /**
     * 更新属性信息
     */
    public function personUpdate_action($site,$id) {
        $u = $this->getPostJson();

        isset($u->name) && $u->pinyin = pinyin($u->name, 'UTF-8');

        $u = (array) $u;
        if (empty($u))
            return new \ResponseData(0);

        $rst = $this->model()->update(
                'xxt_ab_person', $u, "siteid='$site' and id='$id'"
        );

        return new \ResponseData($rst);
    }

    /**
     * 更新联系人所属的部门
     *
     * $id person's id.
     */
    public function updPersonDept_action($site,$abid, $id) {
        $deptids = $this->getPostJson();
        $rels = array();
        foreach ($deptids as $deptid) {
            $r = array(
                'dept_id' => $deptid
            );
            $r['id'] = $this->model('matter\addressbook')->addPersonDept($site, $abid, $id, $deptid);
            $rels[] = $r;
        }

        return new \ResponseData($rels);
    }

    /**
     * 删除联系人和部门之间的关联
     */
    public function delPersonDept_action($site,$id, $deptid) {
        /**
         * 删除关联
         */
        $rst = $this->model()->delete(
                'xxt_ab_person_dept', "siteid='$site' and person_id=$id and dept_id=$deptid"
        );

        return new \ResponseData($rst);
    }

    /**
     * 删除通讯录中的一个联系人
     */
    public function personDelete_action($site,$id) {
        /**
         * remove relation with dept.
         */
        $this->model()->delete(
                'xxt_ab_person_dept', "siteid='$site' and person_id=$id"
        );
        /**
         * remove person.
         */
        $rst = $this->model()->delete(
                'xxt_ab_person', "siteid='$site' and id=$id"
        );

        return new \ResponseData($rst);
    }

    /**
     * 添加的标签
     *
     * $id person's id
     */
    public function personAddTag_action($id) {
        $modelPerson = $this->model('matter\addressbook\person');
        $modelTag = $this->model('matter\addressbook\tag');
        $person = $modelPerson->byId($id);
        /**
         * 是否需要建立新标签
         */
        $addedIds = array();
        $addeds = $this->getPostJson();
        foreach ($addeds as &$add) {
            if (empty($add->id)) {
                $existed = $modelTag->byTitle($person->ab_id, $add->name);
                if ($existed === false) {
                    $add->id = $modelTag->create($person->siteid, $person->ab_id, $add->name);
                } else {
                    $add->id = $existed->id;
                }
            }
            $addedIds[] = $add->id;
        }
        /**
         * 更新
         */
        $all = !empty($person->tags) ? array_merge(explode(',', $person->tags), $addedIds) : $addedIds;
        $all = implode(',', $all);
        $rst = $this->model()->update(
                'xxt_ab_person', array('tags' => $all), "id=$id"
        );

        return new \ResponseData($all);
    }

    /**
     * 删除人员的标签
     */
    public function personDelTag_action($id, $tagid) {
        $person = $this->model('matter\addressbook\person')->byId($id);

        $all = explode(',', $person->tags);
        $pos = array_search($tagid, $all);
        unset($all[$pos]);
        $all = implode(',', $all);
        $rst = $this->model()->update(
                'xxt_ab_person', array('tags' => $all), "id=$id"
        );

        return new \ResponseData($all);
    }

    /**
     *
     */
    public function tagGet_action($abid) {
        $tags = $this->model('matter\addressbook\tag')->byAbid($abid, 'id,name');

        return new \ResponseData($tags);
    }

    /**
     * import an address book(cvs,utf-8).
     *
     * support fields:name(1),email(1),tel(n),dept(n)
     *
     */
    public function import_action($site,$abid, $cleanExistent = 'N') {
        if ($cleanExistent === 'Y') {
            $this->model()->delete('xxt_ab_person_dept', "siteid='$site' and ab_id=$abid");
            $this->model()->delete('xxt_ab_person', "siteid='$site' and ab_id=$abid");
            $this->model()->delete('xxt_ab_dept', "siteid='$site' and ab_id=$abid");
            $this->model()->delete('xxt_ab_tag', "siteid='$site' and ab_id=$abid");
        }
        //solving: Maximum execution time of 30 seconds exceeded
        //@set_time_limit(0);

        if (!($file = fopen($_FILES['addressbook']['tmp_name'], "r")))
            return new \ResponseError('open file, failed.');

        $all_depts = $this->getDeptsByAbid($abid);
        $all_tags = $this->getTagsByAbid($abid);

        $headers = fgetcsv($file);
        $first_header = $headers[0];
        $first_header = preg_replace('/\xEF\xBB\xBF/', '', $first_header); //remove BOM
        $headers[0] = $first_header;
        /**
         * handle data.
         */
        $model = $this->model('matter\addressbook');
        $modelTag = $this->model('matter\addressbook\tag');
        for ($row = 0; ($contact = fgetcsv($file)) != false; $row++) {
            $name = $email = $remark = '';
            $tels = array();
            $tags = array();
            $depts = array();
            $titles = array();
            foreach ($headers as $h => $header) {
                switch ($header) {
                    case 'name':
                        $name = trim($contact[$h]);
                        $name = preg_replace('/\s/', '', $name);
                        break;
                    case 'email':
                        $email = trim($contact[$h]);
                        break;
                    case 'remark':
                        $remark = trim($contact[$h]);
                        break;
                    case 'tel':
                        $tel = trim($contact[$h]);
                        !empty($tel) && $tels[] = $tel;
                        break;
                    case 'tag':
                        $tag = trim($contact[$h]);
                        !empty($tag) && $tags[] = $tag;
                        break;
                    case 'org':
                    case 'dept':
                        $dept = trim($contact[$h]);
                        !empty($dept) && $depts[] = $dept;
                        break;
                }
            }
            /**
             * new person
             */
            $personId = $model->createPerson($site, $abid, $name, $email, implode($tels, ','), false);
            /**
             * remark
             */
            if (!empty($remark)) {
                $this->model()->update('xxt_ab_person', array('remark' => $remark), "id=$personId");
            }
            /**
             * tags
             */
            $personTagIds = array();
            foreach ($tags as $tagName) {
                if (isset($all_tags[$tagName]))
                    $oTag = $all_tags[$tagName];
                else {
                    $id = $modelTag->create($site, $abid, $tagName);
                    $oTag = new \stdClass;
                    $oTag->id = $id;
                    $oTag->name = $tagName;
                    $all_tags[$tagName] = $oTag;
                }
                $personTagIds[] = $oTag->id;
            }
            if (!empty($personTagIds)) {
                $this->model()->update('xxt_ab_person', array('tags' => implode(',', $personTagIds)), "id=$personId");
            }
            /**
             * depts
             */
            $dept_pid = 0;
            foreach ($depts as $sDept) {
                if (isset($all_depts[$sDept]))
                    $oDept = $all_depts[$sDept];
                else {
                    $oDept = $model->addDept($site, $abid, $sDept, $dept_pid);
                    $all_depts[$sDept] = $oDept;
                }
                $model->addPersonDept($site, $abid, $personId, $oDept->id);
                $dept_pid = $oDept->id;
            }
        }

        if (!feof($file)) {
            return new \ResponseError('unexpected fgets() fail.');
        }
        fclose($file);

        return new \ResponseData($row);
    }

    /**
     *
     */
    private function getDeptsByAbid($abid) {
        $map = array();

        $q[] = 'id,name';
        $q[] = 'xxt_ab_dept';
        $q[] = "ab_id='$abid'";
        if ($depts = $this->model()->query_objs_ss($q)) {
            foreach ($depts as $oDept) {
                $map[$oDept->name] = $oDept;
            }
        }
        return $map;
    }

    /**
     * 获得标签通过abid
     */
    private function getTagsByAbid($abid) {
        $map = array();

        $q[] = 'id,name';
        $q[] = 'xxt_ab_tag';
        $q[] = "ab_id='$abid'";
        if ($tags = $this->model()->query_objs_ss($q)) {
            foreach ($tags as $oTag) {
                $map[$oTag->name] = $oTag;
            }
        }

        return $map;
    }
        
        public function list_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$matters = [];

		return new \ResponseData($matters);
	}

}
