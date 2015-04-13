<?php
require_once dirname(dirname(__FILE__)).'/mp_controller.php';

class fans extends mp_controller {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';

        return $rule_action;
    }
    /**
     * all fans.
     *
     * $keyword
     * $amount
     * $gid 关注用户分组
     */
    public function index_action($keyword='', $page=1, $size=30, $amount=null, $gid=null, $authid=null, $contain='') 
    {
        $contain = explode(',', $contain);

        if ($authid !== null) {
            $q[] = 'f.fid,f.src,f.openid,f.subscribe_at,f.nickname,f.sex,f.city,m.mid,m.authed_identity,m.tags,m.depts,m.email m_email,m.mobile m_mobile,m.name m_name,m.create_at,m.email_verified,m.extattr m_extattr';
            $q[] = "xxt_fans f left join xxt_member m on m.forbidden='N' and f.fid=m.fid and m.authapi_id=$authid";
            if (in_array('memberAttrs', $contain)) {
                /**
                 * member's fields setting
                 */
                $setting = $this->model('user/authapi')->byId($authid, 'attr_mobile,attr_email,attr_name,extattr');
                /**
                 * 注册用户的其他属性，例如：会员卡号，会员积分
                 */
                //$features = $this->model('mp\mpaccount')->getFeatures($this->mpid);
                //$setting->can_member_card = $features->can_member_card;
                //$setting->can_member_credits = $features->can_member_credits;
            }
        } else {
            $q[] = 'f.fid,f.src,f.openid,f.subscribe_at,f.nickname,f.sex,f.city';
            $q[] = 'xxt_fans f';
        }

        $w = "f.mpid='$this->mpid' and f.unsubscribe_at=0 and forbidden='N'";
        /**
         * search by keyword
         */
        if (!empty($keyword)) {
            $w .= " and (f.nickname like '%$keyword%'";
            if ($authid !== null)
                $w .= " or m.authed_identity like '%$keyword%'";
            $w .= ")";
        }
        /**
         * search by group
         */
        if ($gid !== null) $w .= " and f.groupid=$gid";

        $q[] = $w;

        $q2['o'] = 'subscribe_at desc';
        $q2['r'] = array('o'=>($page-1)*$size, 'l'=>$size);
        if ($fans = $this->model()->query_objs_ss($q, $q2)) {
            if (empty($amount)) {
                $q[0] = 'count(*)';
                $amount = (int)$this->model()->query_val_ss($q);
            }
            
            /**
             * 返回属性设置信息
             */
            return new ResponseData(array($fans, $amount, isset($setting)?$setting:null)); 
        }

        return new ResponseData(array(array(), 0)); 
    }
    /**
     * get one
     */
    public function fan_action($fid) 
    {
        $fan = $this->model('user/fans')->byId($fid);
        $mm = $this->model('user/member');
        if ($members = $mm->byFanid($this->mpid, $fid)) {
            foreach ($members as &$member) {
                !empty($member->depts) && $member->depts = $mm->getDepts($member->mid, $member->depts);
                !empty($member->tags) && $member->tags = $mm->getTags($member->mid, $member->tags);
            }
            $fan->members = $members;
        }

        return new ResponseData($fan);
    }
    /**
     * get groups
     */
    public function group_action($src=null)
    {
        $groups = $this->model('user/fans')->getGroups($this->mpid);

        return new ResponseData($groups);
    }
    /**
     * 用户的交互足迹
     */
    public function track_action($openid, $src, $page=1, $size=30)
    {
        $track = $this->model('log')->track($this->mpid, $openid, $page, $size);

        return new ResponseData($track);    
    }
    /**
     * 更新粉丝信息
     */
    public function update_action($openid, $src) 
    {
        $nv = $this->getPostJson();
        /**
         * 如果要更新粉丝的分组，需要先在公众平台上更新
         */
        if (isset($nv->groupid)) {
            /**
             * 更新公众平台上的数据
             */
            if ($src === 'yx')
                $cmd = "https://api.yixin.im/cgi-bin/groups/members/update";
            else
                $cmd = "https://api.weixin.qq.com/cgi-bin/groups/members/update";
            $posted = json_encode(array("openid"=>$openid,"to_groupid"=>$nv->groupid));
            $rst = $this->postToMp($this->mpid, $src, $cmd, $posted);

            if ($rst[0] === false)
                return new ResponseData($rst[1]);

            if (isset($rst[1]->errcode) && $rst[1]->errcode != 0) {
                return new ResponseError($rst[1]->errmsg);
            }
        }
        /**
         * 更新本地数据
         */
        $rst = $this->model()->update(
            'xxt_fans', 
            (array)$nv, 
            "mpid='$this->mpid' and openid='$openid' and src='$src'"
        );

        return new ResponseData($rst);
    }
    /**
     * 从公众平台同步所有粉丝的基本信息和分组信息
     *
     * 需要开通高级接口
     *
     * $step
     * $nextOpenid
     *
     */
    public function refreshAll_action($step=0, $nextOpenid='')
    {
        if ($step === 0) {
            $mpa = $this->getMpaccount();
            $fansCount = 0;
            /**
             * 获得所有粉丝的openid
             */
            $proxy = $this->model("mpproxy/$mpa->mpsrc", $this->mpid);
            $rst = $proxy->userGet($nextOpenid);
            if (false === $rst[0])
                return new ResponseError($rst[1]);

            $total = $rst[1]->total; // 所有粉丝的数量
            $openids = $rst[1]->data->openid; // 本次获得的粉丝id数组
            $nextOpenid = $rst[1]->next_openid !== $openids[count($openids)-1] ? $rst[1]->next_openid : '';
        } else {
            $stack = $_SESSION['fans_refreshAll_stack'];
            $mpa = $stack['mpa'];
            $total = $stack['total'];
            $fansCount = $stack['fansCount'];
            $openids = $stack['openids'];
            $proxy = $this->model("mpproxy/$mpa->mpsrc", $this->mpid);
        }
        /**
         * 更新粉丝
         */
        if (!empty($openids)) {
            $current = time();
            $ins = array(
                'mpid' => $this->mpid,
                'src' => $mpa->mpsrc,
                'subscribe_at' => $current,
                'sync_at' => $current
            );
            $finish = 0;
            foreach ($openids as $index=>$openid) {
                if ($index == 50 * ($step + 1)) {
                    $step++;
                    $stack = array(
                        'mpa' => $mpa,
                        'total' => $total,
                        'fansCount' => $fansCount,
                        'openids' => $openids
                    );
                    $_SESSION['fans_refreshAll_stack'] = $stack;
                    return new ResponseData(array('total'=>$total,'step'=>$step,'left'=>count($openids),'finish'=>$finish,'refreshCount'=>$fansCount,'nextOpenid'=>$nextOpenid));
                }

                $finish++;
                unset($openids[$index]);

                $lfan = $this->model('user/fans')->byOpenid($this->mpid, $openid);
                if ($lfan && $lfan->sync_at + 3600 > $current)
                    /**
                     * 一小时之内不同步
                     */
                    continue;
                /**
                 * 从公众号获得粉丝信息
                 */
                $info = $proxy->userInfo($openid, true); 
                if ($info[0] == false)
                    return new ResponseError($info[1]);

                $rfan = $info[1];
                if ($lfan) {
                    /**
                     * 更新关注状态粉丝信息
                     */
                    if ($rfan->subscribe !== 0) {
                        $upd = array(
                            'nickname' => mysql_real_escape_string($rfan->nickname),
                            'sex' => $rfan->sex,
                            'city' => $rfan->city,
                            'groupid' => $rfan->groupid,
                            'sync_at' => $current,
                        );
                        isset($rfan->headimgurl) && $upd['headimgurl'] = $rfan->headimgurl;
                        isset($rfan->province) && $upd['province'] = $rfan->province;
                        isset($rfan->country) && $upd['country'] = $rfan->country;
                        $this->model()->update(
                            'xxt_fans', 
                            $upd, 
                            "mpid='$this->mpid' and openid='$openid'"
                        );
                        $fansCount++;
                    }
                } else {
                    /**
                     * 新粉丝
                     */
                    $ins['fid'] = $this->model('user/fans')->calcId($this->mpid, $mpa->mpsrc, $openid);
                    $ins['openid'] = $openid;
                    if ($info[0]) {
                        $ins['groupid'] = $rfan->groupid;
                        $ins['nickname'] = mysql_real_escape_string($rfan->nickname);
                        $ins['sex'] = $rfan->sex;
                        $ins['city'] = $rfan->city;
                        isset($rfan->subscribe_time) && $ins['subscribe_at'] = $rfan->subscribe_time;
                        isset($rfan->headimgurl) && $ins['headimgurl'] = $rfan->headimgurl;
                        isset($rfan->province) && $ins['province'] = $rfan->province;
                        isset($rfan->country) && $ins['country'] = $rfan->country;
                        $this->model()->insert('xxt_fans', $ins, false);
                        $fansCount++;
                    }
                }
            }
        }

        return new ResponseData(array('total'=>$total,'step'=>$step,'left'=>count($openids),'finish'=>$finish,'refreshCount'=>$fansCount,'nextOpenid'=>$nextOpenid));
    }
    /**
     * 从公众平台同步指定粉丝的基本信息和分组信息
     *
     * todo 从公众号获得粉丝的代码是否应该挪走？
     */
    public function refreshOne_action($openid, $src)
    {
        if ($src === 'qy') {
            $member = $this->model('user/member')->byOpenid($this->mpid, $openid);
            if (count($member) !== 1)
                return array(false, '数据错误');
            $member = $member[0];

            $result = $this->getFanInfo($this->mpid, $src, $openid, false);
            if ($result[0] === false)
                return new ResponseError($result[1]);

            $user = $result[1];

            $this->updateQyFan($this->mpid, $member->fid, $user, $member->authapi_id);

            $fan = $this->model('user/fans')->byId($member->fid);

            return new ResponseData($fan);
        } else {
            $info = $this->getFanInfo($this->mpid, $src, $openid, true); 
            if ($info[0] && $info[1]->subscribe !== 0) {
                /**
                 * 更新数据
                 */
                $u = array(
                    'nickname' => mysql_real_escape_string($info[1]->nickname),
                    'sex' => $info[1]->sex,
                    'city' => $info[1]->city,
                    'groupid' => $info[1]->groupid
                );
                isset($info[1]->headimgurl) && $u['headimgurl'] = $info[1]->headimgurl;
                isset($info[1]->icon) && $u['headimgurl'] = $info[1]->icon;
                isset($info[1]->province) && $u['province'] = $info[1]->province;
                isset($info[1]->country) && $u['country'] = $info[1]->country;
                $this->model()->update(
                    'xxt_fans', 
                    $u, 
                    "mpid='$this->mpid' and src='$src' and openid='$openid'"
                );
                return new ResponseData($info[1]);
            }
        }
        return new ResponseError('用户未关注公众号！');
    }
    /**
     * 从公众平台更新粉丝分组信息
     *
     * 1、清除现有的分组
     * 2、同步公众的号的分组
     * 不更新粉丝所属的分组
     */
    public function refreshGroup_action()
    {
        $mpa = $this->getMpaccount();
        $proxy = $this->model("mpproxy/$mpa->mpsrc", $this->mpid);
        $rst = $proxy->groupsGet();
        if (false === $rst[0])
            return new ResponseError($rst[1]);

        $groups = $rst[1]->groups;

        $this->model()->delete('xxt_fansgroup', "mpid='$this->mpid'");
        foreach ($groups as $g){
            $i = array('id'=>$g->id,'mpid'=>$this->mpid,'src'=>$mpa->mpsrc,'name'=>$g->name);
            $this->model()->insert('xxt_fansgroup', $i, false);
        }

        return new ResponseData(count($groups));
    }
    /**
     * 添加粉丝分组
     *
     * 同时在公众平台和本地添加
     */
    public function addGroup_action()
    {
        $mpa = $this->getMpaccount();
        $group = $this->getPostJson();
        $src = $mpa->mpsrc;
        $name = $group->name;
        /**
         * 在公众平台上添加
         */
        if ($src === 'yx') {
            $posted = json_encode(array('group'=>$group));
            $cmd = 'https://api.yixin.im/cgi-bin/groups/create';
        } else if ($src === 'wx') {
            $group->name = urlencode($group->name);
            $posted = urldecode(json_encode(array('group'=>$group)));
            $cmd = 'https://api.weixin.qq.com/cgi-bin/groups/create';
        } else 
            return new ResponseError('无法获得公众号的来源');


        $rst = $this->postToMp($this->mpid, $src, $cmd, $posted);

        if ($rst[0] === false)
            return new ResponseError($rst[1]);

        if (isset($rst[1]->errcode))
            return new ResponseError($rst[1]->errmsg);

        $group = $rst[1]->group;
        /**
         * 在本地添加
         */
        $group->mpid = $this->mpid;
        $group->name = $name;
        $this->model()->insert('xxt_fansgroup', (array)$group, false);

        return new ResponseData($group);
    }
    /**
     * 更新粉丝分组的名称
     *
     * 同时修改公众平台的数据和本地数据
     */
    public function updateGroup_action()
    {
        $mpa = $this->getMpaccount();
        $group = $this->getPostJson();
        $name = $group->name;
        /**
         * 更新公众平台上的数据
         */
        if ($mpa->mpsrc === 'yx') {
            $posted = json_encode(array('group'=>$group));
            $cmd = "https://api.yixin.im/cgi-bin/groups/update";
        } else if ($mpa->mpsrc === 'wx') {
            $group->name = urlencode($group->name);
            $posted = urldecode(json_encode(array('group'=>$group)));
            $cmd = "https://api.weixin.qq.com/cgi-bin/groups/update";
        } else 
            return new ResponseError('无法获得公众号的来源');

        $rst = $this->postToMp($this->mpid, $mpa->mpsrc, $cmd, $posted);

        if ($rst[0] === false)
            return new ResponseError($rst[1]);

        if (isset($rst[1]->errcode) && $rst[1]->errcode != 0)
            return new ResponseError($rst[1]->errmsg);
        /**
         * 更新本地数据
         */
        $rst = $this->model()->update(
            'xxt_fansgroup', 
            array('name'=>$name), 
            "mpid='$this->mpid' and id='$group->id'"
        );

        return new ResponseData($rst);
    }
    /**
     * 删除粉丝分组
     *
     * todo 标准接口中不支持
     *
     * 同时删除公众平台上的数据和本地数据
     */
    public function removeGroup_action()
    {
        $group = $this->getPostJson();
        /**
         * 删除公众平台数据
         */
        if ($group->src === 'yx') {
            $cmd = "https://api.yixin.im/cgi-bin/groups/delete";
            $posted = json_encode(array('group'=>$group));
        } else {
            $group->name = urlencode($group->name);
            $posted = urldecode(json_encode(array('group'=>$group)));
            $cmd = "https://api.weixin.qq.com/cgi-bin/groups/delete";
        }
        $rst = $this->postToMp($this->mpid, $group->src, $cmd, $posted);

        if ($rst[0] === false)
            return new ResponseData($rst[1]);

        if (isset($rst[1]->errcode) && $rst[1]->errcode != 0) {
            return new ResponseError($rst[1]->errmsg);
        }
        /**
         * 删除本地数据
         * todo 级联更新粉丝所属分组数据
         */
        $rst = $this->model()->delete('xxt_fansgroup', "mpid='$this->mpid' and src='$group->src' and id='$group->id'");

        return new ResponseData($rst);
    }
    /**
     * 删除一个关注用户
     */
    public function removeOne_action($fid)
    {

        $mpa = $this->model('mp\mpaccount')->getApis($this->mpid);
        if ($mpa->qy_joined === 'Y') {
            $fan = $this->model('user/fans')->byId($fid, 'openid');
            $rst = $this->model('mpproxy/qy', $this->mpid)->userDelete($fan->openid);
            if ($rst[0] === false)
                return new ResponseError($rst[1]);
        }
        
        $this->model()->update('xxt_member', array('forbidden'=>'Y'), "fid='$fid'");

        $this->model()->update('xxt_fans', array('forbidden'=>'Y'), "fid='$fid'");

        return new ResponseData('success');
    }
    /**
     *
     */
    public function yxfans_action()
    {
        $fans = $this->yxfans();
        if ($fans[0]) 
            return new ResponseData($fans[1]);
        else
            return new ResponseError($fans[1]);
    }
    /**
     *
     */
    public function yxfansgroup_action()
    {
        $cmd = 'https://api.yixin.im/cgi-bin/groups/get';
        $groups = $this->getFromMp($this->mpid, 'yx', $cmd);
        if ($groups[0]) 
            return new ResponseData($groups[1]);
        else
            return new ResponseError($groups[1]);
    }
    /**
     *
     */
    public function wxfansgroup_action()
    {
        $cmd = 'https://api.weixin.qq.com/cgi-bin/groups/get';
        $groups = $this->getFromMp($this->mpid, 'wx', $cmd);
        if ($groups[0]) 
            return new ResponseData($groups[1]);
        else
            return new ResponseError($groups[1]);
    }
}
