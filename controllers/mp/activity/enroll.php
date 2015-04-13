<?php
require_once dirname(__FILE__).'/base.php';
/**
 *
 */
class enroll extends act_base {
    /**
     *
     */
    protected function getActType() 
    {
        return 'A';
    }
    /**
     * 返回一个活动，或者活动列表
     *
     * $src 是否来源于父账号，=p
     */
    public function index_action($src=null, $aid=null, $page=1, $size=30, $contain=null) 
    {
        $uid = TMS_CLIENT::get_client_uid();
        if ($aid) {
            $a = $this->model('activity/enroll')->byId($aid);
            $a->url = 'http://'.$_SERVER['HTTP_HOST']."/rest/activity/enroll?mpid=$this->mpid&aid=$aid";
            /**
             * 活动签到回复消息
             */
            if ($a->success_matter_type && $a->success_matter_id) {
                $m = $this->model('matter/base')->get_by_id($a->success_matter_type, $a->success_matter_id);
                $m->type = $a->success_matter_type;
                $a->successMatter = $m;
            }
            if ($a->failure_matter_type && $a->failure_matter_id) {
                $m = $this->model('matter/base')->get_by_id($a->failure_matter_type, $a->failure_matter_id);
                $m->type = $a->failure_matter_type;
                $a->failureMatter = $m;
            }
            /**
             * acl
             */
            $a->acl = $this->model('acl')->act($this->mpid, $aid, 'A');
            /**
             * 登记通知接收人
             */
            $a->receiver = $this->model('acl')->activityEnrollReceiver($this->mpid, $aid);
            /**
             * 获得的轮次
             */
            if ($rounds = $this->model('activity/enroll')->getRounds($this->mpid, $aid))
                !empty($rounds) && $a->rounds = $rounds;

            return new ResponseData($a);
        } else {
            $contain = isset($contain) ? explode(',',$contain) : array();
            $q = array('*', 'xxt_activity');
            if ($src === 'p') {
                $pmpid = $_SESSION['mpaccount']->parent_mpid;
                $q[2] = "mpid='$pmpid' and deleted='N'";
            } else
                $q[2] = "mpid='$this->mpid' and deleted='N'";

            $q2['o'] = 'create_at desc';
            $q2['r']['o'] = ($page-1) * $size;
            $q2['r']['l'] = $size;
            if ($a = $this->model()->query_objs_ss($q, $q2)) {
                $result[] = $a;
                if (in_array('total', $contain)) {
                    $q[0] = 'count(*)';
                    $total = (int)$this->model()->query_val_ss($q);
                    $result[] = $total;
                }
                return new ResponseData($result); 
            }
            return new ResponseData(array());
        }
    }
    /**
     * 创建一个空的登记活动
     */
    public function create_action() 
    {
        $uid = TMS_CLIENT::get_client_uid();
        /**
         * 获得的基本信息
         */
        $aid = uniqid();
        $newone['mpid'] = $this->mpid;
        $newone['aid'] = $aid;
        $newone['title'] = '新登记活动';
        $newone['creater'] = $uid;
        $newone['create_at'] = time();
        $newone['nonfans_alert'] = "请先关注公众号，再参与活动！";
        /**
         * 创建定制页
         */
        $page = $this->model('code/page')->create($uid);
        $newone['form_code_id'] = $page->id;
        $page = $this->model('code/page')->create($uid);
        $newone['result_code_id'] = $page->id;

        $this->model()->insert('xxt_activity', $newone, false);

        $act = $this->model('activity/enroll')->byId($aid);

        return new ResponseData($act);
    }
    /**
     * 复制一个登记活动
     */
    public function copy_action($aid)
    {
        $copyed = $this->model('activity/enroll')->byId($aid);
        $uid = TMS_CLIENT::get_client_uid();
        $codeModel = $this->model('code/page');
        $enrollModel = $this->model('activity/enroll');
        /**
         * 获得的基本信息
         */
        $newaid = uniqid();
        $newact['mpid'] = $this->mpid;
        $newact['aid'] = $newaid;
        $newact['title'] = $copyed->title.'（副本）';
        $newact['creater'] = $uid;
        $newact['create_at'] = time();
        $newact['pic'] = $copyed->pic;
        $newact['summary'] = $copyed->summary;
        $newact['wxyx_only'] = $copyed->wxyx_only;
        $newact['fans_only'] = $copyed->fans_only;
        $newact['fans_enter_only'] = $copyed->fans_enter_only;
        $newact['access_control'] = $copyed->access_control;
        $newact['authapis'] = $copyed->authapis;
        $newact['nonfans_alert'] = $copyed->nonfans_alert;
        $newact['open_lastroll'] = $copyed->open_lastroll;
        $newact['success_matter_type'] = $copyed->success_matter_type;
        $newact['success_matter_id'] = $copyed->success_matter_id;
        $newact['failure_matter_type'] = $copyed->failure_matter_type;
        $newact['failure_matter_id'] = $copyed->failure_matter_id;
        $newact['can_signin'] = $copyed->can_signin;
        $newact['can_lottery'] = $copyed->can_lottery;
        $newact['tags'] = $copyed->tags;
        $newact['receiver_page'] = $copyed->receiver_page;
        $newact['entry_page'] = $copyed->entry_page;
        $newact['enrolled_entry_page'] = $copyed->enrolled_entry_page;
        /**
         * 复制固定页面 
         */
        foreach (array('form','result') as $pageName) {
            $code = $codeModel->create($uid);
            $copyedCode = $codeModel->byId($copyed->{$pageName.'_code_id'});
            $data = array(
                'html'=>$copyedCode->html,
                'css'=>$copyedCode->css,
                'js'=>$copyedCode->js
            );
            $codeModel->modify($code->id, $data);
            $newact[$pageName.'_code_id'] = $code->id;
        }
        $this->model()->insert('xxt_activity', $newact, false);
        /**
         * 复制自定义页面
         */
        $extraPages = $enrollModel->getPages($aid);
        foreach ($extraPages as $ep) {
            $newPage = $enrollModel->addPage($this->mpid, $newaid); 
            $rst = $this->model()->update(
                'xxt_activity_page', 
                array('title'=>$ep->title,'name'=>$ep->name), 
                "aid='$newaid' and id=$newPage->id"
            );
            $data = array(
                'title'=>$ep->title,
                'html'=>$ep->html,
                'css'=>$ep->css,
                'js'=>$ep->js
            );
            $codeModel->modify($newPage->code_id, $data);
        }

        $act = $enrollModel->byId($newaid);

        return new ResponseData($act);
    }
    /**
     * 更新活动的属性信息
     */
    public function update_action($aid) 
    {
        $nv = (array)$this->getPostJson();
        foreach ($nv as $n=>$v) {
            if (in_array($n, array('nonfans_alert'))) {
                $nv[$n] = mysql_real_escape_string($v);
            } 
        }

        $rst = $this->model()->update('xxt_activity', $nv, "aid='$aid'");

        return new ResponseData($rst);
    }
    /**
     * 添加活动页面
     *
     * $aid 获动的id
     */
    public function addPage_action($aid)
    {
        $newPage = $this->model('activity/enroll')->addPage($this->mpid, $aid); 

        return new ResponseData($newPage);
    }
    /**
     * 更新活动的页面的属性信息
     *
     * $aid 活动的id
     * $pid 页面的id，如果id==0，是固定页面
     * $pname 页面的名称
     * $cid 页面对应code page id
     */
    public function updPage_action($aid, $pid, $pname, $cid) 
    {
        $nv = $this->getPostJson();

        $rst = 0;
        if (isset($nv->html)) {
            $data = array(
                'html'=>$nv->html
            );
            $rst = $this->model('code/page')->modify($cid, $data);
        } else if (isset($nv->js)) {
            $data = array(
                'js'=>$nv->js
            );
            $rst = $this->model('code/page')->modify($cid, $data);
        } else {
            if ($pid != 0) {
                $rst = $this->model()->update(
                    'xxt_activity_page', 
                    (array)$nv, 
                    "aid='$aid' and id=$pid"
                );        
            }
        }

        return new ResponseData($rst);
    }
    /**
     * 删除活动的页面
     *
     * $aid
     * $pid
     */
    public function delPage_action($aid, $pid)
    {
        $page = $this->model('activity/enroll')->getPage($aid, $pid);

        $this->model('code/page')->remove($page->code_id);

        $rst = $this->model()->delete('xxt_activity_page', "aid='$aid' and id=$pid");

        return new ResponseData($rst);
    }
    /**
     * 添加轮次
     *
     * $aid
     */
    public function addRound_action($aid)
    {
        if ($lastRound = $this->model('activity/enroll')->getLastRound($this->mpid, $aid)) {
            /**
             * 检查或更新上一轮状态
             */
            if ((int)$lastRound->state === 0)
                return new ResponseError("最近一个轮次（$lastRound->title）是新建状态，不允许创建新轮次");
            if ((int)$lastRound->state === 1)
                $this->model()->update(
                    'xxt_activity_round', 
                    array('state'=>2), 
                    "mpid='$this->mpid' and aid='$aid' and rid='$lastRound->rid'"
                );
        }
        $posted = $this->getPostJson();

        $roundId = uniqid();
        $round = array(
            'mpid'=>$this->mpid,
            'aid'=>$aid,
            'rid'=>$roundId,
            'creater'=>TMS_CLIENT::get_client_uid(),
            'create_at'=>time(),
            'title'=>$posted->title,
            'state'=>$posted->state
        );

        $this->model()->insert('xxt_activity_round', $round, false);

        $q = array(
            '*',
            'xxt_activity_round',
            "mpid='$this->mpid' and aid='$aid' and rid='$roundId'"
        );
        $round = $this->model()->query_obj_ss($q);

        return new ResponseData($round);
    }
    /**
     * 更新轮次
     *
     * $aid
     * $rid
     */
    public function updateRound_action($aid, $rid)
    {
        $posted = $this->getPostJson();

        if (isset($posted->state) && (int)$posted->state === 1) {
            /**
             * 启用一个轮次，要停用上一个轮次
             */
            if ($lastRound = $this->model('activity/enroll')->getLastRound($this->mpid, $aid)) {
                if ((int)$lastRound->state !== 2)
                    $this->model()->update(
                        'xxt_activity_round', 
                        array('state'=>2), 
                        "mpid='$this->mpid' and aid='$aid' and rid='$lastRound->rid'"
                    );
            }
        }

        $rst = $this->model()->update(
            'xxt_activity_round', 
            $posted, 
            "mpid='$this->mpid' and aid='$aid' and rid='$rid'"
        );

        return new ResponseData($rst);
    }
    /**
     * 删除轮次
     *
     * $aid
     * $rid
     */
    public function removeRound_action($aid, $rid)
    {
        $rst = $this->model()->delete(
            'xxt_activity_round', 
            "mpid='$this->mpid' and aid='$aid' and rid='$rid'"
        );

        return new ResponseData($rst);
    }
    /**
     * 活动报名名单
     *
     * 1、如果活动仅限会员报名，那么要叠加会员信息
     * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
     *
     * return
     * [0] 数据列表
     * [1] 数据总条数
     * [2] 数据项的定义
     */
    public function records_action($aid, $page=1, $size=30, $rid=null, $kw=null, $by=null, $contain=null) 
    {
        $options = array(
            'page' => $page,
            'size' => $size,
            'rid' => $rid,
            'kw' => $kw,
            'by' => $by,
            'contain' => $contain,
        );

        $result = $this->model('activity/enroll')->getRecords($this->mpid, $aid, $options);

        return new ResponseData($result);
    }
    /**
     * 清空一条登记信息
     */
    public function removeRoll_action($aid, $key)
    {
        $rst = $this->model('activity/enroll')->removeRoll($aid, $key);

        return new ResponseData($rst);
    }
    /**
     * 参与抽奖的人
     *
     * todo 临时
     */
    public function lotteryRoll_action($aid, $rid) 
    {
        $result = $this->model('activity/enroll')->getLotteryRoll($aid, $rid);

        return new ResponseData($result);
    }
    /**
     * 抽奖的轮次 
     *
     * todo 临时
     */
    public function lotteryRounds_action($aid) 
    {
        $result = $this->model('activity/enroll')->getLotteryRounds($aid);

        return new ResponseData($result);
    }
    /**
     * 抽奖的轮次 
     *
     * todo 临时
     */
    public function addLotteryRound_action($aid) 
    {
        $r = array(
            'aid'=>$aid,
            'round_id'=>uniqid(),
            'create_at'=>time(),
            'title'=>'新轮次',
            'targets'=>'',
        );
        $this->model()->insert('xxt_activity_lottery_round', $r, false);

        return new ResponseData($r);
    }
    /**
     * 抽奖的轮次 
     *
     * todo 临时
     */
    public function updateLotteryRound_action($aid, $rid) 
    {
        $nv = $this->getPostJson();

        if (isset($nv->targets)) $nv->targets = mysql_real_escape_string($nv->targets);

        $rst = $this->model()->update(
            'xxt_activity_lottery_round', 
            (array)$nv, 
            "aid='$aid' and round_id='$rid'" 
        );

        return new ResponseData($rst);
    }
    /**
     * 抽奖的轮次 
     *
     * todo 临时
     */
    public function removeLotteryRound_action($aid, $rid) 
    {
        /**
         * 已过已经有抽奖数据不允许删除
         */
        $q = array(
            'count(*)',
            'xxt_activity_lottery',
            "aid='$aid' and round_id='$rid'" 
        );
        if (0 < (int)$this->model()->query_val_ss($q)) 
            return new ResponseError('已经有抽奖数据，不允许删除轮次！');

        $rst = $this->model()->delete(
            'xxt_activity_lottery_round', 
            "aid='$aid' and round_id='$rid'" 
        );

        return new ResponseData($rst);
    }
    /**
     * 中奖的人
     *
     * todo 临时
     */
    public function lotteryWinners_action($aid, $rid=null) 
    {
        $result = $this->model('activity/enroll')->getLotteryWinners($aid, $rid);

        return new ResponseData($result);
    }
    /**
     * 抽奖
     *
     * todo 临时
     */
    public function lottery_action($aid, $rid, $ek)
    {
        $fans = $this->getPostJson();

        $i = array(
            'aid'=>$aid,
            'round_id'=>$rid,
            'enroll_key'=>$ek,
            'openid'=>$fans->openid,
            'src'=>$fans->src,
            'draw_at'=>time()
        );

        $this->model()->insert('xxt_activity_lottery', $i, false);

        return new ResponseData('success');
    }
    /**
     * 清空参与抽奖的人
     *
     * todo 临时
     */
    public function lotteryClean_action($aid) 
    {
        $rst = $this->model()->delete('xxt_activity_lottery', "aid='$aid'");

        return new ResponseData($result);
    }
    /**
     * 清空登记信息
     */
    public function clean_action($aid)
    {
        $rst = $this->model('activity/enroll')->cleanRoll($aid);

        return new ResponseData($rst);
    }
    /**
     * 删除一个活动
     *
     * 如果没有报名数据，就将活动彻底删除
     * 否则只是打标记
     */
    public function remove_action($aid)
    {
        $q = array(
            'count(*)',
            'xxt_activity_enroll',
            "mpid='$this->mpid' and aid='$aid'"
        );
        if ((int)$this->model()->query_val_ss($q) > 0)
            $rst = $this->model()->update(
                'xxt_activity', 
                array('deleted'=>'Y'),
                "mpid='$this->mpid' and aid='$aid'"
            );
        else
            $rst = $this->model()->delete(
                'xxt_activity', 
                "mpid='$this->mpid' and aid='$aid'"
            );

        return new ResponseData($rst);
    }
    /**
     * 统计登记信息
     *
     * 只统计radio/checkbox类型的数据项
     *
     * return
     * name => array(l=>label,c=>count)
     *
     */
    public function stat_action($aid)
    {
        $result = $this->model('activity/enroll')->getStat($aid);

        return new ResponseData($result);
    }
    /**
     * 更新报名信息
     *
     * $ek enroll_key
     */
    public function updateRoll_action($aid, $ek) 
    {
        $roll = $this->getPostJson();

        foreach ($roll as $k=>$v) {
            if (in_array($k, array('signin_at','tags','comment')))
                $this->model()->update(
                    'xxt_activity_enroll', 
                    array($k=>$v), 
                    "enroll_key='$ek'"
                );
            else if ($k === 'data' and is_object($v)) {
                foreach ($v as $cn=>$cv) {
                    /**
                     * 检查数据项是否存在，如果不存在就先创建一条
                     */
                    $q = array(
                        'count(*)',
                        'xxt_activity_enroll_cusdata',
                        "enroll_key='$ek' and name='$cn'"
                    );
                    if (1 === (int)$this->model()->query_val_ss($q))
                        $this->model()->update(
                            'xxt_activity_enroll_cusdata', 
                            array('value'=>$cv), 
                            "enroll_key='$ek' and name='$cn'"
                        );
                    else {
                        $cd = array(
                            'aid'=>$aid,
                            'enroll_key'=>$ek,
                            'name'=>$cn,
                            'value'=>$cv
                        );
                        $this->model()->insert(
                            'xxt_activity_enroll_cusdata', 
                            $cd
                        );
                    }
                }
            }
        }

        return new ResponseData('success');
    }
    /**
     * 手工添加报名信息
     */
    public function addRoll_action($aid) 
    {
        $d = (array)$this->getPostJson();
        /**
         * 报名记录
         */
        $current = time();
        $enroll_key = $this->model('activity/enroll')->genEnrollKey($this->mpid, $aid);
        $r = array();
        $r['aid'] = $aid;
        $r['mpid'] = $this->mpid;
        $r['enroll_key'] = $enroll_key;
        $r['enroll_at'] = $current;
        $r['signin_at'] = $current;
        if (isset($d['tags'])) $r['tags'] = $d['tags'];

        $id = $this->model()->insert('xxt_activity_enroll', $r, true);

        $r['id'] = $id;
        /**
         * 登记信息
         */
        foreach ($d as $n => $v) {
            if (in_array($n, array('signin_at','tags','comment')))
                continue;
            $cd = array(
                'aid'=>$aid,
                'enroll_key'=>$enroll_key,
                'name'=>$n,
                'value'=>$v
            );
            $this->model()->insert(
                'xxt_activity_enroll_cusdata', 
                $cd
            );
            $r[$n] = $v;
        }

        return new ResponseData($r);
    }
    /**
     * 手工添加报名信息
     */
    public function importRoll_action($aid) 
    {
        $mids = $this->getPostJson();

        $q = array(
            'count(*)',
            'xxt_activity_enroll'
        );
        $rolls = array();
        $current = time();
        foreach ($mids as $mid) {
            $member = $this->model('user/member')->byId($mid);
            $q[2] = "aid='$aid' and mid='$mid'";
            if (1===(int)$this->model()->query_val_ss($q))
                continue;
            /**
             * 报名记录
             */
            $enroll_key = $this->model('activity/enroll')->genEnrollKey($this->mpid, $aid);
            $r = array();
            $r['aid'] = $aid;
            $r['mpid'] = $this->mpid;
            $r['mid'] = $member->mid;
            $r['openid'] = $member->ooid;
            $r['enroll_key'] = $enroll_key;
            $r['enroll_at'] = $current;
            $r['signin_at'] = $current;

            $id = $this->model()->insert('xxt_activity_enroll', $r, true);

            $r['id'] = $id;
            $r['nickname'] = $member->name;

            $rolls[] = $r;
        }

        return new ResponseData($rolls);
    }
    /**
     * 通过已有的活动导入用户
     *
     * 目前支持指定的活动包括通用活动和讨论组活动
     * 目前仅支持指定一个通用活动和一个讨论组活动
     */
    public function importRoll2_action($aid)
    {
        $param = $this->getPostJson();
        $current = time();

        $caid = $param->checkedActs[0];
        $cwid = $param->checkedWalls[0];
        $q = array(
            'w.src,w.openid,a.enroll_key',
            'xxt_activity_enroll a,xxt_wall_enroll w',
            "a.aid='$caid' and w.wid='$cwid' and a.src=w.src and a.openid=w.openid and w.last_msg_at>0"
        );
        $fans = $this->model()->query_objs_ss($q);

        if (!empty($fans)) {
            foreach ($fans as $f) {
                /**
                 * 检查重复记录
                 */
                $q = array(
                    'count(*)',
                    'xxt_activity_enroll',
                    "mpid='$this->mpid' and aid='$aid' and src='$f->src' and openid='$f->openid'"
                );
                if (0 < (int)$this->model()->query_val_ss($q))
                    continue;
                /**
                 * 插入数据
                 */
                $enroll_key = $this->model('activity/enroll')->genEnrollKey($this->mpid, $aid);
                $r = array();
                $r['aid'] = $aid;
                $r['mpid'] = $this->mpid;
                $r['enroll_key'] = $enroll_key;
                $r['enroll_at'] = $current;
                $r['signin_at'] = $current;
                $r['src'] = $f->src;
                $r['openid'] = $f->openid;

                $this->model()->insert('xxt_activity_enroll', $r);
                /**
                 * 导入登记数据
                 * todo 临时方法
                 */
                $sql = 'insert into xxt_activity_enroll_cusdata(aid,enroll_key,name,value)';
                $sql .= " select '$aid','$enroll_key',name,value";
                $sql .= ' from xxt_activity_enroll_cusdata';
                $sql .= " where aid='$caid' and enroll_key='$f->enroll_key'";

                $this->model()->insert($sql);
            }
        }

        return new ResponseData(count($fans)); 

    }
    /**
     * 活动签到成功回复
     */
    public function setSuccessReply_action($aid)
    {
        $matter = $this->getPostJson();

        $ret = $this->model()->update(
            'xxt_activity', 
            array(
                'success_matter_type'=>ucfirst($matter->mt), 
                'success_matter_id'=>$matter->mid
            ),
            "mpid='$this->mpid' and aid='$aid'"
        );

        return new ResponseData($ret);
    }
    /**
     * 活动签到失败回复
     */
    public function setFailureReply_action($aid)
    {
        $matter = $this->getPostJson();

        $ret = $this->model()->update(
            'xxt_activity', 
            array(
                'failure_matter_type'=>ucfirst($matter->mt), 
                'failure_matter_id'=>$matter->mid
            ),
            "mpid='$this->mpid' and aid='$aid'"
        );

        return new ResponseData($ret);
    }
    /**
     * 设置登记通知的接收人
     */
    public function setEnrollReceiver_action($aid)
    {
        $receiver = $this->getPostJson();

        if (empty($receiver->identity))
            return new ResponseError('没有指定用户的唯一标识');
        
        if (isset($receiver->id)) {
            $u['identity'] = $receiver->identity;
            $rst = $this->model()->update(
                'xxt_activity_receiver', 
                $u, 
                "id=$receiver->id"
            );
            return new ResponseData($rst);
        } else {
            $i['mpid'] = $this->mpid;
            $i['aid'] = $aid;
            $i['identity'] = $receiver->identity;
            $i['idsrc'] = empty($receiver->idsrc) ? '' : $receiver->idsrc;
            $i['id'] = $this->model()->insert('xxt_activity_receiver', $i, true);
            $i['label'] = empty($receiver->label) ? $receiver->identity : $receiver->label;

            return new ResponseData($i);
        }
    }
    /**
     * 删除登记通知的接收人
     * $id
     * $acl aclid
     */
    public function delEnrollReceiver_action($acl)
    {
        $ret = $this->model()->delete(
            'xxt_activity_receiver', 
            "mpid='$this->mpid' and id=$acl"
        );

        return new ResponseData($ret);
    }
}
