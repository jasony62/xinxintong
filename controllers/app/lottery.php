<?php
namespace app;

require_once dirname(dirname(__FILE__)).'/member_base.php';
/**
 * 抽奖活动引擎
 */
class lottery extends \member_base {
    /**
     *
     */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 获得轮盘抽奖活动的页面或定义
     *
     * $mpid
     * $lid 抽奖活动id
     * $shareby 谁做的分享
     */
    public function index_action($mpid, $lid, $shareby='', $mocker=null, $code=null) 
    {
        empty($mpid) && $this->outputError('没有指定当前运行的公众号');
        empty($lid) && $this->outputError('抽奖活动id为空');

        $model = $this->model('app\lottery');
        $lot = $model->byId($lid);
        $current = time();
        /**
         * start?
         */
        if ($current < $lot->start_at) {
            \TPL::assign('title', $lot->title);
            \TPL::assign('body', empty($lot->nostart_alert) ? '活动未开始' : $lot->nostart_alert);
            \TPL::output('info');
            exit;
        }
        /**
         * end?
         */
        if ($current > $lot->end_at) {
            \TPL::assign('title', $lot->title);
            \TPL::assign('body', empty($lot->hasend_alert) ? '活动已结束' : $lot->hasend_alert);
            \TPL::output('info');
            exit;
        }
        if ($code !== null) {
            $who = $this->getOAuthUserByCode($mpid, $code);
        } else {
            if (!empty($mocker)) {
                $who = $mocker;
                $this->setCookieOAuthUser($mpid, $mocker);
            } else {
                if (!$this->oauth($mpid))
                    $who = null;
            }
        }
        $this->afterOAuth($mpid, $lid, $shareby, $who);
    }
    /**
     * 返回页面信息
     *
     * $state state/id
     * $who OAuth的结果
     * $preactivitydone 是否来源于前置操作的回调
     * 
     */
    private function afterOAuth($mpid, $lid, $shareby=null, $who=null, $preactivitydone=false)
    {
        $model = $this->model('app\lottery');
        $lot = $model->byId($lid);
        /**
         * 当前访问用户
         */
        $ooid = !empty($who) ? $who : $this->getCookieOAuthUser($mpid);
        $vid = $this->getVisitorId($mpid);
        /**
         * 要求先关注再参与
         */
        if ($lot->fans_enter_only === 'Y')
            $this->askFollow($mpid, $ooid);
        /**
         * 访问控制
         */
        if ($lot->access_control === 'Y')
            $this->accessControl($mpid, $lot->id, $lot->authapis, $ooid, $lot);

        $params = array();
        $params['visitor'] = array();
        /**
         * 返回抽奖活动页面
         */
        $params['mpid'] = $mpid;
        $params['shareby'] = $shareby;
        $params['visitor']['openid'] = isset($ooid) ? $ooid : '';
        $params['visitor']['vid'] = $vid;
        /**
         * 处理前置活动
         */
        if ($lot->precondition === 'Y' && !$preactivitydone) {
            if ($lot->preactivitycount === 'E') {
                \TPL::assign('preactivity', $lot->preactivity);
            } else { 
                $expire = (int)$lot->end_at;
                $precondition = $this->myGetCookie("_{$lid}_precondition");
                if ($precondition !== 'done')
                    \TPL::assign('preactivity', $lot->preactivity);
            }
        }
        /**
         * 记录日志，完成前置活动再次进入的情况不算
         */
        if ($preactivitydone) {
            $openid_agent = $_SERVER['HTTP_USER_AGENT'];
            $client_ip = $this->client_ip();
            $this->model('log')->writeMatterReadLog(
                $vid, 
                $mpid, 
                $lot->id, 
                'lottery', 
                $lot->title,
                isset($ooid) ? $ooid : '',
                $shareby, 
                $openid_agent, 
                $client_ip
            );
        }
        /**
         * is member? 
         */
        $mid = null;
        if ($lot->access_control) {
            $aAuthapis = explode(',', $lot->authapis);
            if ($members = $this->getCookieMember($mpid, $aAuthapis))
                $mid = $members[0]->mid;
        }
        /**
         * 抽奖活动定义
         */
        $r = $model->byId($lid, 'id,pic,summary,title,show_greeting,show_winners,autostop,maxstep,chance max_chance', array('award','plate'));
        /**
         * 获得当前用户的抽奖数据
         */
        $r->myAwards = $model->getLog($lid, $mid, $ooid);
        $r->chance = $model->getChance($lid, $mid, $ooid);
        $params['lottery'] = $r;

        \TPL::assign('params', $params);

        $mpsetting = $this->getCommonSetting($mpid);
        \TPL::assign('body_ele', $mpsetting->body_ele);
        \TPL::assign('body_css', $mpsetting->body_css);

        if ($lot->custom_body === 'Y') {
            $page = $this->model('code/page')->byId($lot->page_id);
            \TPL::assign('extra_ele', $page->html);
            \TPL::assign('extra_css', $page->css);
            \TPL::assign('extra_js', $page->js);
            \TPL::output('/app/lottery/play');
        } else {
            \TPL::assign('extra_css', $lot->extra_css);
            \TPL::assign('extra_ele', $lot->extra_ele);
            \TPL::assign('extra_js', $lot->extra_js);
            $this->view_action('/app/lottery/roulette');
        }
        exit;
    }
    /**
     *
     */
    protected function canAccessObj($mpid, $lid, $member, $authapis, $lot)
    {
        return $this->model('acl')->canAccessMatter($mpid, 'lottery', $lid, $member, $authapis);
    }
    /**
     * 完成前置活动
     *
     * $mpid
     * $lid
     * $code 支持OAuth
     *
     */
    public function preactiondone_action($mpid, $lid, $code=null)
    {
        if ($code !== null) {
            $who = $this->getOAuthUserByCode($mpid, $code);
        } else {
            //$shareid = $this->myGetCookie("_{$lid}_shareid");
            if (!$this->oauth($mpid))
                $who = null;
        }
        /**
         * 记录前置活动执行状态
         */
        $lot = $this->model('app\lottery')->byId($lid, 'end_at');
        $expire = (int)$lot->end_at;
        $this->mySetCookie("_{$lid}_precondition", 'done', $expire);

        $this->afterOAuth($mpid, $lid, null, $who, true);
    }
    /**
     * 最近的获奖者清单
     */
    public function winners_action($lid) 
    {
        $winners = $this->model('app\lottery')->getWinners($lid);

        return new \ResponseData($winners);
    }
    /**
     * 进行抽奖
     */
    public function play_action($mpid, $lid) 
    {
        $model = $this->model('app\lottery');
        /**
         * define data.
         */
        $r = $model->byId($lid, '*', array('award','plate'));
        /**
         * 如果仅限关注用户参与，获得openid
         */
        $openid = $this->getCookieOAuthUser($mpid);

        if ($r->fans_only === 'Y') {
            if (empty($openid))
                return new \ResponseData(null, 302, $r->nonfans_alert);
            $q = array(
                'count(*)',
                'xxt_fans',
                "mpid='$mpid' and openid='$openid' and unsubscribe_at=0"
            );
            if (1 !== (int)$this->model()->query_val_ss($q))
                return new \ResponseData(null, 302, $r->nonfans_alert);
        }
        /**
         * 如果仅限会员参与，获得用户身份信息 
         */
        if ($r->access_control === 'Y') {
            $aAuthapis = explode(',', $r->authapis);
            $members = $this->authenticate($mpid, $aAuthapis, false);
            $mid = $members[0]->mid;
        } else
            $mid = null;
        /**
         * 如果不能获得一个确定的身份信息，就无法将抽奖结果和用户关联
         * 因此无法确定用户身份时，就不允许进行抽奖
         */
        if (empty($openid) && empty($mid))
            return new \ComplianceError('无法确定您的身份信息，不能参与抽奖！');
        /**
         * 是否完成了指定内置任务
         */
        if ($task = $model->hasTask($lid, $mid, $openid))
            return new \ResponseData(null, 301, $task->description);
        /**
         * 还有参加抽奖的机会吗？
         */
        if (false === $model->canPlay($lid, $mid, $openid, true))
            return new \ResponseData(null, 301, $r->nochance_alert);
        /**
         * 抽奖
         */
        list($selectedSlot, $selectedAwardID, $myAward) = $this->drawAward($r);

        if (empty($myAward))
            return new \ResponseData(null, 301, '对不起，没有奖品了！');
        /**
         * record result
         */
        $model->recordResult($mpid, $lid, $mid, $openid, $selectedAwardID);
        /**
         * 领取非实体奖品
         */
        if ($myAward['type'] == 1 || $myAward['type'] == 2 || $myAward['type'] == 3)
            $model->acceptAward($lid, $mid, $openid, $myAward);
        /**
         * 检查剩余的机会
         */
        $chance = $model->getChance($r->id, $mid, $openid);
        /**
         * 返回奖项信息
         */
        foreach ($r->awards as $a) {
            if ($a->aid === $myAward['aid']) {
                $myAward2 = $a;
                break;
            }
        }

        return new \ResponseData(array($selectedSlot, $chance, $myAward2));
    }
    /**
     * 返回当前用户获得的奖品
     */
    public function myawards_action($mpid, $lid)
    {
        $model = $this->model('app\lottery');
        /**
         * 抽奖活动定义
         */
        $r = $model->byId($lid, 'access_control,authapis', array('award'));
        /**
         * is member? 
         */
        $mid = null;
        if ($r->access_control) {
            $aAuthapis = explode(',', $r->authapis);
            if ($members = $this->getCookieMember($mpid, $aAuthapis))
                $mid = $members[0]->mid;
        }

        $openid = $this->getCookieOAuthUser($mpid);

        $myAwards = $model->getLog($lid, $mid, $openid, true);

        return new \ResponseData($myAwards);
    }
    /**
     * 抽取奖品
     *
     * 奖品必须还有剩余
     */
    private function drawAward(&$r) 
    {
        /**
         * arrange relateion between award and plate's slots.
         */
        $awards = array();
        foreach ($r->awards as $a) {
            /**
             * 奖品的抽中概率为0，或者已经没有剩余的奖品，奖项就不再参与抽奖
             * 由于周期性抽奖，有可能改变奖品的数量，因此周期性抽奖即使没有奖品了也要允许抽 
             */
            if ((int)$a->prob === 0 || ($a->period === 'A' && $a->type==99 && ((int)$a->takeaway >= (int)$a->quantity)))
                continue;
            $awards[$a->aid] = array(
                'aid'=>$a->aid, 
                'prob'=>$a->prob, 
                'type'=>$a->type,
                'taskid'=>$a->taskid,
                'period'=>$a->period,
                'quantity'=>$a->quantity,
            );
        }
        /**
         * 没有可用的奖品了
         */
        if (empty($awards))
            return false;
        /**
         * 每个奖项所在位置
         * 跳过无效的奖品
         */
        for ($i=0; $i<$r->plate->size; $i++) {
            if (isset($awards[$r->plate->{"a$i"}]))
                $awards[$r->plate->{"a$i"}]['pos'][] = $i;
        }
        /**
         * 清除掉不在槽位中的奖项
         */
        foreach ($awards as $k=>$a)
            if (!isset($a['pos'])) unset($awards[$k]);
        /**
         * 按照概率从低到高排列奖品
         */
        uasort($awards, function($a, $b){
            if ((int)$a['prob'] === (int)$b['prob'])
                return 0;
            return ((int)$a['prob'] < (int)$b['prob']) ? -1 : 1;
        });
        /**
         * 计算位置和奖品 
         */
        $limit = 10; 
        while ($limit--) {
            $selectedSlot = $this->getAwardPos($awards);
            $selectedAwardID = $r->plate->{"a$selectedSlot"};
            $myAward = $awards[$selectedAwardID];
            if ($myAward['type'] == 99) {
                $current = time();
                /**
                 * 如果抽奖周期是天，当前用户的抽奖时间和最近一次领取奖品的时间不是同一天
                 * 那么先重置奖品领取信息
                 */
                if ($myAward['period'] === 'D') {
                    $cdate = getdate($current);
                    $ztime = mktime(0,0,0,(int)$cdate['mon'],(int)$cdate['mday'],(int)$cdate['year']);
                    $sql = "update xxt_lottery_award";
                    $sql .= " set takeaway=0,takeaway_at=0";
                    $sql .= " where aid='$selectedAwardID' and takeaway_at<$ztime";
                    $this->model()->update($sql);
                }
                /**
                 * 如果是实物奖品，更新被领取的奖品数量
                 * 如果没有剩余的奖品就重新抽奖
                 */
                $sql = "update xxt_lottery_award";
                $sql .= " set takeaway=takeaway+1,takeaway_at=$current";
                $sql .= " where aid='$selectedAwardID' and quantity>takeaway";
                $success = $this->model()->update($sql);
                if (1 === (int)$success)
                    break;
                else
                    unset($awards[$selectedAwardID]);
            } else {
                $success = 1;
                break;
            }

        }
        if ((int)$success !== 1) die('can not get an award, please set a default award.');

        return array($selectedSlot, $selectedAwardID, $myAward);
    }
    /**
     * 获得抽中奖品所在的位置
     */
    private function getAwardPos(&$proArr) 
    {
        $awardPos = null;
        /**
         * 概率数组的总概率
         */
        $proSum = 0;
        foreach ($proArr as $award)
            $proSum += (int)$award['prob'];
        /**
         * 概率数组循环
         */
        $randNum = mt_rand(1, $proSum);
        foreach ($proArr as $award) {
            if ($randNum <= (int)$award['prob']) {
                /**
                 * 在奖品的概率范围内
                 */
                if (count($award['pos']) === 1) {
                    /**
                     * 只有一个位置可选
                     */
                    $awardPos = $award['pos'][0];
                } else {
                    /**
                     * 随机挑选一个位置
                     */
                    $i = mt_rand(0, count($award['pos'])-1);
                    $awardPos = $award['pos'][$i];
                }
                break;
            } else {
                /**
                 * 缩小范围
                 */
                $randNum -= (int)$award['prob'];
            }
        }
        return $awardPos;
    }
}
