<?php
require_once dirname(dirname(__FILE__)).'/member_base.php';
/**
 * 同步企业号通讯录数据
 */
class sync extends member_base {
    /**
     *
     */
    private $mpid;

    public function __construct() 
    {
        if (!isset($_SESSION['mpid']) || !($mpid = $_SESSION['mpid']))
            die('not get valid mpid.');

        $this->mpid = $mpid;
    }

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';

        return $rule_action;
    }
    /**
     * 从企业号同步通讯录数据
     *
     * 必须是企业号
     * 必须设置了内置应用接口
     */
    public function index_action()
    {
        $mp = $this->model('mp\mpaccount')->byId($this->mpid, 'qy_joined');

        if ($mp->qy_joined !== 'Y')
            return new ResponseError('未与企业号连接，无法同步通讯录');

        $authapis = $this->model('mp\mpaccount')->getAuthapis($this->mpid, 'Y');
        if (empty($authapis))
            return new ResponseError('未设置内置认证接口，无法同步通讯录');

        $authid = false;
        foreach ($authapis as $authapi) {
            if ($authapi->type === 'inner') {
                $authid = $authapi->authid;
                break;
            }
        } 
        if (!$authid) return new ResponseError('未设置内置认证接口，无法同步通讯录');

        $timestamp = time(); // 进行同步操作的时间戳
        /**
         * 同步部门数据
         */
        $mapDeptR2L = array(); // 部门的远程ID和本地ID的映射
        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/department/list";
        $result = $this->getFromMp($this->mpid, 'qy', $cmd);
        if ($result[0] === false)
            return new ResponseError($result[1]);

        $rootDepts = array(); // 根部门
        $rdepts = $result[1]->department;
        foreach ($rdepts as $rdept) {
            $pid = $rdept->parentid == 0 ? 0 : isset($mapDeptR2L[$rdept->parentid]['id']) ? $mapDeptR2L[$rdept->parentid]['id'] : 0;
            if ($pid === 0) $rootDepts[] = $rdept;
            $rdeptName = $rdept->name;
            unset($rdept->name);
            /**
             * 如果已经同步过，怎更新数据和时间戳；否则创建新本地数据
             */
            $q = array(
                'id,fullpath',
                'xxt_member_department',
                "mpid='$this->mpid' and extattr like '%\"id\":$rdept->id,%'"
            );
            if (!($ldept = $this->model()->query_obj_ss($q)))
                $ldept = $this->model('user/department')->create($this->mpid, $authid, $pid, null);

            $this->model()->update(
                'xxt_member_department',
                array(
                    'pid'=>$pid,
                    'sync_at'=>$timestamp,
                    'name'=>$rdeptName,
                    'extattr'=>json_encode($rdept)
                ),
                "mpid='$this->mpid' and id=$ldept->id"
            );
            $mapDeptR2L[$rdept->id] = array('id'=>$ldept->id, 'path'=>$ldept->fullpath);
        }
        /**
         * 清空同步不存在的部门
         */
        $this->model()->delete(
            'xxt_member_department', 
            "mpid='$this->mpid' and sync_at<$timestamp"
        );
        foreach ($rootDepts as $rootDept) {
            /**
             * 同步成员
             */
            $params = array(
                'department_id'=>$rootDept->id,
                'fetch_child'=>1,
                'status'=>0
            );
            $cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/list";
            $result = $this->getFromMp($this->mpid, 'qy', $cmd, $params);
            if ($result[0] === false)
                return new ResponseError($result[1]);

            $users = $result[1]->userlist;
            foreach ($users as $user) {
                $q = array(
                    'mid,fid',
                    'xxt_member',
                    "mpid='$this->mpid' and ooid='$user->userid' and osrc='qy'"
                );
                if (!($luser = $this->model()->query_obj_ss($q)))
                    $this->createQyFan($this->mpid, $user, $authid, $timestamp, $mapDeptR2L);
                else
                    $this->updateQyFan($this->mpid, $luser->fid, $user, $authid, $timestamp, $mapDeptR2L);
            }
        }
        /**
         * 清空没有同步的粉丝数据
         */
        $this->model()->delete(
            'xxt_fans', 
            "mpid='$this->mpid' and fid in (select fid from xxt_member where mpid='$this->mpid' and sync_at<$timestamp)"
        );
        /**
         * 清空没有同步的成员数据
         */
        $this->model()->delete(
            'xxt_member', 
            "mpid='$this->mpid' and sync_at<$timestamp"
        );
        /**
         * 同步标签
         */
        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/list";
        $result = $this->getFromMp($this->mpid, 'qy', $cmd);
        if ($result[0] === false)
            return new ResponseError($result[1]);

        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/get";
        $tags = $result[1]->taglist;
        foreach ($tags as $tag) {
            $q = array(
                'id',
                'xxt_member_tag',
                "mpid='$this->mpid' and extattr like '{\"tagid\":$tag->tagid}%'"
            );
            if ($ltag = $this->model()->query_obj_ss($q)) {
                $memberTagId = $ltag->id;
                $t = array(
                    'sync_at'=>$timestamp,
                    'name'=>$tag->tagname
                );
                $this->model()->update(
                    'xxt_member_tag', 
                    $t, 
                    "mpid='$this->mpid' and id=$ltag->id"
                );
            } else {
                $t = array(
                    'mpid'=>$this->mpid,
                    'sync_at'=>$timestamp,
                    'name'=>$tag->tagname,
                    'authapi_id'=>$authid,
                    'extattr'=>json_encode(array('tagid'=>$tag->tagid))
                );
                $memberTagId = $this->model()->insert('xxt_member_tag', $t, true);
            }
            /**
             * 建立标签和成员、部门的关联
             */
            $result = $this->getFromMp($this->mpid, 'qy', $cmd, array('tagid'=>$tag->tagid));
            if ($result[0] === false)
                return new ResponseError($result[1]);
            $users = $result[1]->userlist;
            foreach ($users as $user) {
                $q = array(
                    'tags',
                    'xxt_member',
                    "mpid='$this->mpid' and ooid='$user->userid'"
                );
                $memeberTags = $this->model()->query_val_ss($q);
                if (empty($memeberTags)) 
                    $memeberTags = $memberTagId;
                else
                    $memeberTags .= ','.$memberTagId;
                $this->model()->update(
                    'xxt_member',
                    array('tags'=>$memeberTags),
                    "mpid='$this->mpid' and ooid='$user->userid'"
                );

            }
        }
        /**
         * 清空已有标签
         */
        $this->model()->delete(
            'xxt_member_tag', 
            "mpid='$this->mpid' and sync_at<$timestamp"
        );

        return new ResponseData(array(count($rdepts), count($users), count($tags)));
    }
}
