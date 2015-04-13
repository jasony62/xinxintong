<?php
require_once dirname(__FILE__)."/mp_controller.php";
/**
 *
 */
class mpaccount extends mp_controller {
    /**
     *
     */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';

        return $rule_action;
    }
    /**
     * 提供给公众平台进行对接的访问入口
     */
    private function apiurl() 
    {
        $url = 'http://';
        $url .= $_SERVER['HTTP_HOST'];
        $url .= '/rest/mi/api';
        return $url;
    }
    /**
     * 获得当前用户对公众号的操作权限
     */
    private function getUserPermissions()
    {
        $uid = TMS_CLIENT::get_client_uid();

        $perms = $this->model('mp\permission')->hasMpRight(
            $this->mpid, 
            array('mpsetting','mpsecurity'),
            array('create','read','update','delete'), 
            $uid
        );

        return $perms;
    }
    /**
     *
     */
    public function index_action() 
    {
        $modelMpa = $this->model('mp\mpaccount');
        $mpa = $modelMpa->byId($this->mpid);
        if ($mpa->asparent === 'N') {
            /**
             * 实体账号
             */
            $API_URL = $this->apiurl();
            $mpa->yx_url = "$API_URL?mpid=$this->mpid&src=yx";
            $mpa->wx_url = "$API_URL?mpid=$this->mpid&src=wx";
            $mpa->qy_url = "$API_URL?mpid=$this->mpid&src=qy";
            if (!empty($mpa->parent_mpid)) {
                /**
                 * 有父账号
                 */
                $pmp = $modelMpa->byId($mpa->parent_mpid, 'name');
                $mpa->parentname = $pmp->name;
            }
        }

        if ($_SERVER['HTTP_ACCEPT'] === 'application/json')
            return new ResponseData($mpa);
        else {
            $perms = $this->getUserPermissions();
            $params = array(
                'mpaccount' => $mpa
            );
            $apis = $modelMpa->getApis($this->mpid);
            isset($apis) && $params['apis'] = $apis;

            TPL::assign('params', $params);

            if ($perms===true || $perms['mpsetting']['update_p']==='Y')
                $this->view_action('/mp/mpaccount/main');
            else
                $this->view_action('/mp/mpaccount/read/main');
        }
    }
    /**
     *
     */
    public function feature_action($fields='*') 
    {
        $modelMpa = $this->model('mp\mpaccount');

        $features = $modelMpa->getFeatures($this->mpid, $fields);
        
        if ($_SERVER['HTTP_ACCEPT'] === 'application/json')
            return new ResponseData($features);
        else {
            $perms = $this->getUserPermissions();
            $params = array(
                'features' => $features
            );

            TPL::assign('params', $params);
            if ($perms===true || $perms['mpsetting']['update_p']==='Y')
                $this->view_action('/mp/mpaccount/feature');
            else
                $this->view_action('/mp/mpaccount/read/feature');
        }
    }
    /**
     *
     */
    public function customapi_action() 
    {
        $perms = $this->getUserPermissions();

        if ($perms===true || $perms['mpsetting']['update_p']==='Y')
            $this->view_action('/mp/mpaccount/customapi');
        else
            $this->view_action('/mp/mpaccount/read/customapi');
    }
    /**
     *
     */
    public function permission_action() 
    {
        $perms = $this->getUserPermissions();

        if ($perms===true || $perms['mpsecurity']['update_p']=='Y')
            $this->view_action('/mp/mpaccount/permission');
        else
            $this->view_action('/mp/mpaccount/read/permission');
    }
    /**
     *
     */
    public function administrator_action() 
    {
        $q = array(
            'a.uid,a.authed_id,a.email',
            'xxt_mpadministrator m, account a',
            "m.mpid='$this->mpid' and m.uid=a.uid"
        );
        $admins = $this->model()->query_objs_ss($q);
        foreach ($admins as &$a) 
            if (empty($a->authed_id))
                $a->authed_id = $a->email;

        $params['administrators'] = $admins;
        TPL::assign('params', $params);

        $this->view_action('/mp/mpaccount/administrator');
    }
    /**
     * 更新账号配置信息
     */
    public function update_action()
    {
        $nv = $this->getPostJson();

        $rst = $this->model()->update(
            'xxt_mpaccount', 
            (array)$nv,
            "mpid='$this->mpid'"
        );
        /**
         * 如果修改了token，需要重新重新进行验证
         */
        if (isset($nv->token))
            $rst = $this->model()->update(
                'xxt_mpaccount', 
                array('yx_joined'=>'N','wx_joined'=>'N','qy_joined'=>'Y'),
                "mpid='$this->mpid'"
            );

        return new ResponseData($rst);
    }
    /**
     *
     */
    private function hasYxJoined() 
    {
        $q = array('yx_joined', 'xxt_mpaccount', "mpid='$this->mpid'");
        return $this->model()->query_val_ss($q);
    }
    /**
     *
     */
    private function hasWxJoined() 
    {
        $q = array('wx_joined', 'xxt_mpaccount', "mpid='$this->mpid'");
        return $this->model()->query_val_ss($q);
    }
    /**
     *
     */
    public function checkJoin_action($src) 
    {
        switch ($src) {
        case 'yx':
            $v = $this->hasYxJoined();
            break;
        case 'wx':
            $v = $this->hasWxJoined();
            break;
        default:
            return new ResponseError("未知来源（$src）。");
        }
        return new ResponseData($v);    
    }
    /**
     * 获得当前用户的权限
     */
    public function mypermissions_action()
    {
        $perms = $this->getUserPermission();

        return new ResponseData($perms);
    }
    /**
     * 获得高级接口定义
     */
    public function apis_action()
    {
        $modelMpa = $this->model('mp\mpaccount');

        $apis = $modelMpa->getApis($this->mpid);

        return new ResponseData($apis);
    }
    /**
     * 更新内置用户注册设置
     */
    public function updateUserauth_action($authid) 
    {
        $nv = (array)$this->getPostJson();

        foreach ($nv as $k=>$v) {
            if (in_array($k, array('auth_css','auth_html','auth_js')))    
                $nv[$k] = mysql_real_escape_string($v);
        }

        $rst = $this->model()->update(
            'xxt_member_authapi', 
            $nv, 
            "authid=$authid and mpid='$this->mpid'"
        );

        return new ResponseData($rst);
    }
    /**
     *
     */
    public function updateApi_action() 
    {
        $nv = $this->getPostJson();

        $rst = $this->model()->update(
            'xxt_mpsetting',
            (array)$nv,
            "mpid='$this->mpid'"
        );

        return new ResponseData($rst);
    }
    /**
     *
     */
    public function updateFeature_action() 
    {
        $nv = $this->getPostJson();

        if (isset($nv->admin_email_pwd)) {
            /**
             * 邮箱口令要加密处理
             */
            $pwd = $this->model()->encrypt($nv->admin_email_pwd, 'ENCODE', $this->mpid);
            $rst = $this->model()->update(
                'xxt_mpsetting',
                array('admin_email_pwd'=>$pwd),
                "mpid='$this->mpid'"
            );
        } else {
            if (isset($nv->body_ele))
                $nv->body_ele = mysql_real_escape_string($nv->body_ele);
            else if (isset($nv->body_css))
                $nv->body_css = mysql_real_escape_string($nv->body_css);
            else if (isset($nv->follow_ele))
                $nv->follow_ele = mysql_real_escape_string($nv->follow_ele);
            else if (isset($nv->follow_css))
                $nv->follow_css = mysql_real_escape_string($nv->follow_css);

            $rst = $this->model()->update(
                'xxt_mpsetting',
                (array)$nv,
                "mpid='$this->mpid'"
            );
        }

        return new ResponseData($rst);
    }
    /**
     * 获得定义的认证接口
     *
     * 返回当前公众号和它的父账号的
     *
     * $valid
     */
    public function authapis_action($valid=null)
    {
        $modelMpa = $this->model('mp\mpaccount');

        $pmp = $modelMpa->byId($this->mpid, 'parent_mpid');
        if (!empty($pmp->parent_mpid))
            $papis = $modelMpa->getAuthapis($pmp->parent_mpid, $valid);

        $apis = $modelMpa->getAuthapis($this->mpid, $valid);

        !empty($papis) && $apis = array_merge($papis, $apis);

        return new ResponseData($apis);
    }
    /**
     *
     */
    public function updAuthapi_action($type, $id=null) 
    {
        $uid = TMS_CLIENT::get_client_uid();

        $nv = $this->getPostJson();

        if (empty($id) && $type === 'inner') {
            /**
             * 如果是首次使用内置接口，就创建新的接口定义
             */
            $i = array(
                'mpid'=>$this->mpid,
                'name'=>'内置认证',
                'type'=>'inner',
                'valid'=>'N',
                'creater'=>$uid,
                'create_at'=>time(),
                'url'=>TMS_APP_API_PREFIX."/member/auth"
            );
            $i = array_merge($i, (array)$nv);
            $id = $this->model()->insert('xxt_member_authapi', $i, true);
        } else {
            /**
             * 更新已有的认证接口定义
             */
            if (isset($nv->entry_statement))
                $nv->entry_statement = mysql_real_escape_string($nv->entry_statement);
            else if (isset($nv->acl_statement))
                $nv->acl_statement = mysql_real_escape_string($nv->acl_statement);
            else if (isset($nv->notpass_statement))
                $nv->notpass_statement = mysql_real_escape_string($nv->notpass_statement);
            else if (isset($nv->extattr)) {
                foreach ($nv->extattr as &$attr) {
                    $attr->id = urlencode($attr->id);
                    $attr->label = urlencode($attr->label);
                }
                $nv->extattr = urldecode(json_encode($nv->extattr));
            }

            $rst = $this->model()->update(
                'xxt_member_authapi',
                (array)$nv,
                "mpid='$this->mpid' and authid='$id'"
            );
        }

        $api = $this->model('user/authapi')->byId($id);

        return new ResponseData($api);
    }
    /**
     * 填加自定义认证接口
     * 自定义认证接口只有在本地部署版本中才有效
     */
    public function addAuthapi_action() 
    {
        $uid = TMS_CLIENT::get_client_uid();

        $i = array(
            'mpid'=>$this->mpid,
            'name'=>'',
            'type'=>'cus',
            'valid'=>'N',
            'creater'=>$uid,
            'create_at'=>time(),
            'url'=>''
        );
        $id = $this->model()->insert('xxt_member_authapi', $i, true);

        $q = array('*','xxt_member_authapi',"mpid='$this->mpid' and authid='$id'");

        $api = $this->model()->query_obj_ss($q);

        return new ResponseData($api);
    }
    /**
     * 只有没有被使用的自定义接口才允许被删除 
     */
    public function delAuthapi_action($id) 
    {
        $rst = $this->model()->delete('xxt_member_authapi',"mpid='$this->mpid' and authid='$id' and used=0");

        return new ResponseData($rst);
    }
    /**
     * 获得定义的转发接口
     */
    public function relays_action()
    {
        $relays = $this->model('mp\mpaccount')->getRelays($this->mpid);

        return new ResponseData($relays);
    }
    /**
     * 添加转发接口
     */
    public function addRelay_action()
    {
        $r['mpid'] = $this->mpid;
        $r['title'] = '新转发接口';

        $r = $this->model('mp\mpaccount')->addRelay($r);

        return new ResponseData($r);
    }
    /**
     * 更新转发接口
     */
    public function updateRelay_action($rid)
    {
        $nv = $this->getPostJson();

        $rst = $this->model()->update('xxt_mprelay', (array)$nv, "id='$rid'");

        return new ResponseData($rst);
    }
    /**
     * 只有没有被使用的自定义接口才允许被删除 
     */
    public function delRelay_action($rid) 
    {
        $res = $this->model()->delete('xxt_mprelay',"mpid='$this->mpid' and id='$rid' and used=0");

        return new ResponseData($rst);
    }
    /**
     * 设置的系统管理员
     */
    public function admins_action() 
    {
        $q = array(
            'a.uid,a.authed_id,a.email',
            'xxt_mpadministrator m, account a',
            "m.mpid='$this->mpid' and m.uid=a.uid"
        );
        $admins = $this->model()->query_objs_ss($q);
        foreach ($admins as &$a) 
            if (empty($a->authed_id))
                $a->authed_id = $a->email;

        return new ResponseData($admins);
    }
    /**
     * 添加系统管理员
     */
    public function addAdmin_action($authedid=null,$authapp='',$autoreg='N')
    {
        if (empty($authedid) && defined('TMS_APP_ADDON_EXTERNAL_ORG'))
            return new ResponseData(array('externalOrg'=>TMS_APP_ADDON_EXTERNAL_ORG));

        $model = $this->model('account');
        $account = $model->getAccountByAuthedId($authedid);

        if (!$account)
            if ($autoreg!=='Y')
                return new ResponseError('指定的账号不是注册账号，请先注册！');
            else
                $account = $model->authed_from($authedid, $authapp, '0.0.0.0', $authedid);

        /**
         * exist?
         */
        $q = array(
            'count(*)', 
            'xxt_mpadministrator', 
            "mpid='$this->mpid' and uid='$account->uid'"
        );
        if ((int)$this->model()->query_val_ss($q) > 0)
            return new ResponseError('该账号已经是系统管理员，不能重复添加！');

        $uid = TMS_CLIENT::get_client_uid();
        $this->model()->insert(
            'xxt_mpadministrator',
            array(
                'mpid'=>$this->mpid, 
                'uid'=>$account->uid, 
                'creater'=>$uid,
                'create_at'=>time()
            ),
            false
        );

        return new ResponseData(array('uid'=>$account->uid, 'authed_id'=>$authedid));
    }
    /**
     * 删除一个系统管理员
     */
    public function removeAdmin_action($uid)
    {
        $rst = $this->model()->delete(
            'xxt_mpadministrator', 
            "mpid='$this->mpid' and uid='$uid'"
        );
        return new ResponseData($rst);
    }
    /**
     * 生成当前公众号的父账号
     *
     * 1、生成一个新的父账号
     * 2、将当前账号设置为父账号的子账号
     * 3、将当前账号的素材迁移到父账号
     * 4、回复数据迁移
     * 5、活动数据迁移（不迁移）
     * 6、访问控制列表数据要迁移吗？（不迁移）
     */
    public function genParent_action()
    {
        $mpa = $this->model('mp\mpaccount')->byId($this->mpid);
        /**
         * 1、生成一个新的父账号
         */
        $d['name'] = $mpa->name.'（父账号）';
        $d['asparent'] = 'Y';

        $pmpid = $this->model('mp\mpaccount')->create($d);
        /**
         * 2、将当前账号设置为父账号的子账号
         */
        $rst = $this->model()->update(
            'xxt_mpaccount', 
            array('parent_mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        /**
         * 3、将当前账号的素材迁移到父账号
         */
        $rst = $this->model()->update(
            'xxt_tag', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_article', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_article_tag', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_text', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_news', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_link', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_channel', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        /**
         * 通讯录迁移
         */
        $rst = $this->model()->update(
            'xxt_address_book', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_ab_dept', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_ab_person', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_ab_person_dept', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_ab_title', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        /**
         * 回复响应事件迁移
         */
        $rst = $this->model()->update(
            'xxt_text_call_reply', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_menu_reply', 
            array('mpid'=>$pmpid,'pversion'=>-1),
            "mpid='$this->mpid'"
        );
        /**
         * 活动数据迁移
         */
        $rst = $this->model()->update(
            'xxt_activity', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_activity_receiver', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        //
        $rst = $this->model()->update(
            'xxt_lottery', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_lottery_award', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_lottery_plate', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        $rst = $this->model()->update(
            'xxt_lottery_task', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );
        //
        $rst = $this->model()->update(
            'xxt_wall', 
            array('mpid'=>$pmpid),
            "mpid='$this->mpid'"
        );

        return new ResponseData($pmpid);
    }
}
