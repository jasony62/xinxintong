<?php
namespace mp\app\enroll;

require_once dirname(dirname(__FILE__)).'/base.php';
/**
 *
 */
class main extends \mp\app\app_base {
    /**
     *
     */
    protected function getMatterType() 
    {
        return 'enroll';
    }
    /**
     *
     */
    public function index_action() 
    {
        $this->view_action('/mp/app/enroll');
    }
    /**
     *
     */
    public function detail_action() 
    {
        $this->view_action('/mp/app/enroll/detail');
    }
    /**
     *
     */
    public function page_action() 
    {
        $this->view_action('/mp/app/enroll/detail');
    }
    /**
     *
     */
    public function record_action() 
    {
        $this->view_action('/mp/app/enroll/detail');
    }
    /**
     *
     */
    public function stat_action() 
    {
        $this->view_action('/mp/app/enroll/detail');
    }
    /**
     * 返回一个活动，或者活动列表
     *
     * $src 是否来源于父账号，=p
     */
    public function get_action($src=null, $aid=null, $page=1, $size=30, $contain=null) 
    {
        $uid = \TMS_CLIENT::get_client_uid();
        if ($aid) {
            $a = $this->model('app\enroll')->byId($aid);
            $a->uid = $uid;
            $a->url = 'http://'.$_SERVER['HTTP_HOST']."/rest/app/enroll?mpid=$this->mpid&aid=$aid";
            /**
             * 活动签到回复消息
             */
            if ($a->success_matter_type && $a->success_matter_id) {
                $m = $this->model('matter\base')->getMatterInfoById($a->success_matter_type, $a->success_matter_id);
                $a->successMatter = $m;
            }
            if ($a->failure_matter_type && $a->failure_matter_id) {
                $m = $this->model('matter\base')->getMatterInfoById($a->failure_matter_type, $a->failure_matter_id);
                $a->failureMatter = $m;
            }
            /**
             * channels
             */
            $a->channels = $this->model('matter\channel')->byMatter($aid, 'enroll');
            /**
             * acl
             */
            $a->acl = $this->model('acl')->byMatter($this->mpid, 'enroll', $aid);
            /**
             * 登记通知接收人
             */
            $a->receiver = $this->model('acl')->enrollReceiver($this->mpid, $aid);
            /**
             * 获得的轮次
             */
            if ($rounds = $this->model('app\enroll')->getRounds($this->mpid, $aid))
                !empty($rounds) && $a->rounds = $rounds;

            return new \ResponseData($a);
        } else {
            $contain = isset($contain) ? explode(',',$contain) : array();
            $q = array('a.*', 'xxt_enroll a');
            if ($src === 'p') {
                $pmpid = $this->getParentMpid();
                $q[2] = "mpid='$pmpid' and state=1";
            } else
                $q[2] = "mpid='$this->mpid' and state=1";
            /**
             * 限作者和管理员
             */
            if (!$this->model('mp\permission')->isAdmin($this->mpid, $uid, true)) {
                $limit = $this->model()->query_value('matter_visible_to_creater', 'xxt_mpsetting', "mpid='$this->mpid'");
                if ($limit === 'Y')
                    $q[2] .= " and (a.creater='$uid' or a.public_visible='Y')";
            }

            $q2['o'] = 'a.create_at desc';
            $q2['r']['o'] = ($page-1) * $size;
            $q2['r']['l'] = $size;
            if ($a = $this->model()->query_objs_ss($q, $q2)) {
                $result[] = $a;
                //if (in_array('total', $contain)) {
                    $q[0] = 'count(*)';
                    $total = (int)$this->model()->query_val_ss($q);
                    $result[] = $total;
                //}
                return new \ResponseData($result); 
            }
            return new \ResponseData(array());
        }
    }
    /**
     * 创建一个空的登记活动
     */
    public function create_action() 
    {
        $uid = \TMS_CLIENT::get_client_uid();
        $mpa = $this->model('mp\mpaccount')->getFeatures($this->mpid, 'heading_pic');
        /**
         * 获得的基本信息
         */
        $aid = uniqid();
        $newone['mpid'] = $this->mpid;
        $newone['id'] = $aid;
        $newone['title'] = '新登记活动';
        $newone['pic'] = $mpa->heading_pic;
        $newone['creater'] = $uid;
        $newone['creater_src'] = 'A';
        $newone['creater_name'] = \TMS_CLIENT::account()->nickname;
        $newone['create_at'] = time();
        $newone['entry_rule'] = "{}";
        $newone['nonfans_alert'] = "请先关注公众号，再参与活动！";
        /**
         * 创建定制页
         */
        $page = $this->model('code/page')->create($uid);
        $newone['form_code_id'] = $page->id;
        $page = array(
            'title' => '查看结果页',
            'type' => 'V',
        );
        $this->model('app\enroll')->addPage($this->mpid, $aid, $page);
        
        $this->model()->insert('xxt_enroll', $newone, false);

        $act = $this->model('app\enroll')->byId($aid);

        return new \ResponseData($act);
    }
    /**
     * 复制一个登记活动
     */
    public function copy_action($aid=null, $shopid=null)
    {
        $uid = \TMS_CLIENT::get_client_uid();
        $uname = \TMS_CLIENT::account()->nickname;
        $current = time();
        $enrollModel = $this->model('app\enroll');
        $codeModel = $this->model('code/page');

        if (!empty($aid)) {
            $copied = $enrollModel->byId($aid);
        } else if (!empty($shopid)) {
            $shopItem = $this->model('shop\shelf')->byId($shopid);
            $aid = $shopItem->matter_id;
            $copied = $enrollModel->byId($aid);
            $copied->title = $shopItem->title;
            $copied->summary = $shopItem->summary;
            $copied->pic = $shopItem->pic;
        } else {
            return new \ResponseError('没有指定要复制登记活动id');
        }
        /**
         * 获得的基本信息
         */
        $newaid = uniqid();
        $newact['mpid'] = $this->mpid;
        $newact['id'] = $newaid;
        $newact['creater'] = $uid;
        $newact['creater_src'] = 'A';
        $newact['creater_name'] = $uname;
        $newact['create_at'] = $current;
        $newact['title'] = $copied->title.'（副本）';
        $newact['pic'] = $copied->pic;
        $newact['summary'] = $copied->summary;
        $newact['public_visible'] = $copied->public_visible;
        $newact['wxyx_only'] = $copied->wxyx_only;
        $newact['fans_only'] = $copied->fans_only;
        $newact['fans_enter_only'] = $copied->fans_enter_only;
        $newact['nonfans_alert'] = $copied->nonfans_alert;
        $newact['open_lastroll'] = $copied->open_lastroll;
        $newact['can_signin'] = $copied->can_signin;
        $newact['can_lottery'] = $copied->can_lottery;
        $newact['tags'] = $copied->tags;
        $newact['entry_page'] = $copied->entry_page;
        $newact['enrolled_entry_page'] = $copied->enrolled_entry_page;
        $newact['receiver_page'] = $copied->receiver_page;
        $newact['entry_rule'] = $copied->entry_rule;
        if ($copied->mpid === $this->mpid) {
            $newact['access_control'] = $copied->access_control;
            $newact['authapis'] = $copied->authapis;
            $newact['success_matter_type'] = $copied->success_matter_type;
            $newact['success_matter_id'] = $copied->success_matter_id;
            $newact['failure_matter_type'] = $copied->failure_matter_type;
            $newact['failure_matter_id'] = $copied->failure_matter_id;
        }
        /**
         * 复制固定页面 
         */
        $code = $codeModel->create($uid);
        $copiedCode = $codeModel->byId($copied->form_code_id);
        $data = array(
            'html'=>$copiedCode->html,
            'css'=>$copiedCode->css,
            'js'=>$copiedCode->js
        );
        $codeModel->modify($code->id, $data);
        $newact['form_code_id'] = $code->id;
        
        $this->model()->insert('xxt_enroll', $newact, false);
        /**
         * 复制自定义页面
         */
        $extraPages = $enrollModel->getPages($aid);
        foreach ($extraPages as $ep) {
            $newPage = $enrollModel->addPage($this->mpid, $newaid); 
            $rst = $this->model()->update(
                'xxt_enroll_page', 
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
        if ($copied->mpid === $this->mpid) {
            /**
             * 复制所属频道
             */
            $sql = 'insert into xxt_channel_matter(channel_id,matter_id,matter_type,creater,creater_src,creater_name,create_at)';
            $sql .= " select channel_id,'$newaid','enroll','$uid','A','$uname',$current";
            $sql .= ' from xxt_channel_matter';
            $sql .= " where matter_id='$aid' and matter_type='enroll'";
            $this->model()->insert($sql, '', false);
            /**
             * 复制登记事件接收人
             */
            $sql = 'insert into xxt_enroll_receiver(mpid,aid,identity,idsrc)';
            $sql .= " select '$this->mpid','$newaid',identity,idsrc";
            $sql .= ' from xxt_enroll_receiver';
            $sql .= " where aid='$aid'";
            $this->model()->insert($sql, '', false);
            /**
             * 复制ACL
             */
            $sql = 'insert into xxt_matter_acl(mpid,matter_type,matter_id,identity,idsrc,label)';
            $sql .= " select '$this->mpid',matter_type,'$newaid',identity,idsrc,label";
            $sql .= ' from xxt_matter_acl';
            $sql .= " where matter_id='$aid'";
            $this->model()->insert($sql, '', false);
        }

        $act = $enrollModel->byId($newaid);

        return new \ResponseData($act);
    }
    /**
     * 更新活动的属性信息
     */
    public function update_action($aid) 
    {
        $nv = (array)$this->getPostJson();
        foreach ($nv as $n=>$v) {
            if (in_array($n, array('entry_rule'))) {
                $nv[$n] = $this->model()->escape(urldecode($v));
            }
            if (in_array($n, array('nonfans_alert'))) {
                $nv[$n] = $this->model()->escape($v);
            } 
        }

        $rst = $this->model()->update('xxt_enroll', $nv, "id='$aid'");

        return new \ResponseData($rst);
    }
    /**
     * 添加活动页面
     *
     * $aid 获动的id
     */
    public function addPage_action($aid)
    {
        $newPage = $this->model('app\enroll')->addPage($this->mpid, $aid); 

        return new \ResponseData($newPage);
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
                'html'=>urldecode($nv->html)
            );
            $rst = $this->model('code/page')->modify($cid, $data);
        } else if (isset($nv->js)) {
            $data = array(
                'js'=>urldecode($nv->js)
            );
            $rst = $this->model('code/page')->modify($cid, $data);
        } else {
            if ($pid != 0) {
                $rst = $this->model()->update(
                    'xxt_enroll_page', 
                    (array)$nv, 
                    "aid='$aid' and id=$pid"
                );        
            }
        }

        return new \ResponseData($rst);
    }
    /**
     * 删除活动的页面
     *
     * $aid
     * $pid
     */
    public function delPage_action($aid, $pid)
    {
        $page = $this->model('app\enroll')->getPage($aid, $pid);

        $this->model('code/page')->remove($page->code_id);

        $rst = $this->model()->delete('xxt_enroll_page', "aid='$aid' and id=$pid");

        return new \ResponseData($rst);
    }
    /**
     * 添加轮次
     *
     * $aid
     */
    public function addRound_action($aid)
    {
        if ($lastRound = $this->model('app\enroll')->getLastRound($this->mpid, $aid)) {
            /**
             * 检查或更新上一轮状态
             */
            if ((int)$lastRound->state === 0)
                return new \ResponseError("最近一个轮次（$lastRound->title）是新建状态，不允许创建新轮次");
            if ((int)$lastRound->state === 1)
                $this->model()->update(
                    'xxt_enroll_round', 
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
            'creater'=>\TMS_CLIENT::get_client_uid(),
            'create_at'=>time(),
            'title'=>$posted->title,
            'state'=>$posted->state
        );

        $this->model()->insert('xxt_enroll_round', $round, false);

        $q = array(
            '*',
            'xxt_enroll_round',
            "mpid='$this->mpid' and aid='$aid' and rid='$roundId'"
        );
        $round = $this->model()->query_obj_ss($q);

        return new \ResponseData($round);
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
            if ($lastRound = $this->model('app\enroll')->getLastRound($this->mpid, $aid)) {
                if ((int)$lastRound->state !== 2)
                    $this->model()->update(
                        'xxt_enroll_round', 
                        array('state'=>2), 
                        "mpid='$this->mpid' and aid='$aid' and rid='$lastRound->rid'"
                    );
            }
        }

        $rst = $this->model()->update(
            'xxt_enroll_round', 
            $posted, 
            "mpid='$this->mpid' and aid='$aid' and rid='$rid'"
        );

        return new \ResponseData($rst);
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
            'xxt_enroll_round', 
            "mpid='$this->mpid' and aid='$aid' and rid='$rid'"
        );

        return new \ResponseData($rst);
    }
    /**
     * 参与抽奖的人
     *
     * todo 临时
     */
    public function lotteryRoll_action($aid, $rid) 
    {
        $result = $this->model('app\enroll')->getLotteryRoll($aid, $rid);

        return new \ResponseData($result);
    }
    /**
     * 抽奖的轮次 
     *
     * todo 临时
     */
    public function lotteryRounds_action($aid) 
    {
        $result = $this->model('app\enroll')->getLotteryRounds($aid);

        return new \ResponseData($result);
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
        $this->model()->insert('xxt_enroll_lottery_round', $r, false);

        return new \ResponseData($r);
    }
    /**
     * 抽奖的轮次 
     *
     * todo 临时
     */
    public function updateLotteryRound_action($aid, $rid) 
    {
        $nv = $this->getPostJson();

        if (isset($nv->targets)) $nv->targets = $this->model()->escape($nv->targets);

        $rst = $this->model()->update(
            'xxt_enroll_lottery_round', 
            (array)$nv, 
            "aid='$aid' and round_id='$rid'" 
        );

        return new \ResponseData($rst);
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
            'xxt_enroll_lottery',
            "aid='$aid' and round_id='$rid'" 
        );
        if (0 < (int)$this->model()->query_val_ss($q)) 
            return new \ResponseError('已经有抽奖数据，不允许删除轮次！');

        $rst = $this->model()->delete(
            'xxt_enroll_lottery_round', 
            "aid='$aid' and round_id='$rid'" 
        );

        return new \ResponseData($rst);
    }
    /**
     * 中奖的人
     *
     * todo 临时
     */
    public function lotteryWinners_action($aid, $rid=null) 
    {
        $result = $this->model('app\enroll')->getLotteryWinners($aid, $rid);

        return new \ResponseData($result);
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

        $this->model()->insert('xxt_enroll_lottery', $i, false);

        return new \ResponseData('success');
    }
    /**
     * 清空参与抽奖的人
     *
     * todo 临时
     */
    public function lotteryClean_action($aid) 
    {
        $rst = $this->model()->delete('xxt_enroll_lottery', "aid='$aid'");

        return new \ResponseData($result);
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
            'xxt_enroll_record',
            "mpid='$this->mpid' and aid='$aid'"
        );
        if ((int)$this->model()->query_val_ss($q) > 0)
            $rst = $this->model()->update(
                'xxt_enroll', 
                array('state'=>0),
                "mpid='$this->mpid' and id='$aid'"
            );
        else
            $rst = $this->model()->delete(
                'xxt_enroll', 
                "mpid='$this->mpid' and id='$aid'"
            );

        return new \ResponseData($rst);
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
    public function statGet_action($aid)
    {
        $result = $this->model('app\enroll')->getStat($aid);

        return new \ResponseData($result);
    }
    /**
     * 活动签到成功回复
     */
    public function setSuccessReply_action($aid)
    {
        $matter = $this->getPostJson();

        $ret = $this->model()->update(
            'xxt_enroll', 
            array(
                'success_matter_type'=>$matter->mt, 
                'success_matter_id'=>$matter->mid
            ),
            "mpid='$this->mpid' and id='$aid'"
        );

        return new \ResponseData($ret);
    }
    /**
     * 活动签到失败回复
     */
    public function setFailureReply_action($aid)
    {
        $matter = $this->getPostJson();

        $ret = $this->model()->update(
            'xxt_enroll', 
            array(
                'failure_matter_type'=>$matter->mt, 
                'failure_matter_id'=>$matter->mid
            ),
            "mpid='$this->mpid' and id='$aid'"
        );

        return new \ResponseData($ret);
    }
    /**
     * 设置登记通知的接收人
     */
    public function setEnrollReceiver_action($aid)
    {
        $receiver = $this->getPostJson();

        if (empty($receiver->identity))
            return new \ResponseError('没有指定用户的唯一标识');

        if (isset($receiver->id)) {
            $u['identity'] = $receiver->identity;
            $rst = $this->model()->update(
                'xxt_enroll_receiver', 
                $u, 
                "id=$receiver->id"
            );
            return new \ResponseData($rst);
        } else {
            $i['mpid'] = $this->mpid;
            $i['aid'] = $aid;
            $i['identity'] = $receiver->identity;
            $i['idsrc'] = empty($receiver->idsrc) ? '' : $receiver->idsrc;
            $i['id'] = $this->model()->insert('xxt_enroll_receiver', $i, true);
            $i['label'] = empty($receiver->label) ? $receiver->identity : $receiver->label;

            return new \ResponseData($i);
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
            'xxt_enroll_receiver', 
            "mpid='$this->mpid' and id=$acl"
        );

        return new \ResponseData($ret);
    }
}
