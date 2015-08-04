<?php
namespace app\enroll;

class lottery_model extends \TMS_MODEL {
    /**
     * 参加抽奖活动的人
     */
    public function players($aid, $rid) 
    {
        $result = array(array(),array());
        /**
         * 获得活动的定义
         */
        $q = array(
            'a.access_control,p.html form_html',
            'xxt_enroll a,xxt_code_page p',
            "a.id='$aid' and a.form_code_id=p.id"
        );
        $act = $this->query_obj_ss($q);
        
        $w = "e.aid='$aid'";
        $w .= " and not exists(select 1 from xxt_enroll_lottery l where e.enroll_key=l.enroll_key)";
        $q = array(
            'e.id,e.enroll_key,e.nickname,e.openid,e.enroll_at,signin_at,e.tags',
            'xxt_enroll_record e',
            $w
        );
        $q2['o'] = 'e.enroll_at desc';
        /**
         * 获得填写的登记数据
         */ 
        if (($players = $this->query_objs_ss($q, $q2)) && !empty($players))  {
            /**
             * 获得自定义数据的值
             */
            foreach ($players as &$player) {
                $qc = array(
                    'name,value',
                    'xxt_enroll_record_data',
                    "enroll_key='$player->enroll_key'"
                );
                $cds = $this->query_objs_ss($qc);
                foreach ($cds as $cd)
                    $player->{$cd->name} = $cd->value;
            }
            /**
             * 删除没有填写报名信息数据
             */
            $players2 = array();
            foreach ($players as $player2) {
                if (empty($player2->name) && empty($player2->mobile))
                    continue;
                //$player2->tags = explode(',', $player2->tags);
                $players2[] = $player2;
            }
            $result[0] = $players2;
        }
        /**
         * 已经抽中的人
         */
        $q = array(
            'l.*,e.enroll_key',
            'xxt_enroll_lottery l,xxt_enroll_record e',
            "l.aid='$aid' and l.aid=e.aid and l.enroll_key=e.enroll_key and round_id='$rid'"
        );
        $q2 = array('o'=>'draw_at');
        if ($winners = $this->query_objs_ss($q, $q2)) {
            /**
             * 获得自定义数据的值
             */
            foreach ($winners as &$w) {
                $qc = array(
                    'name,value',
                    'xxt_enroll_record_data',
                    "enroll_key='$w->enroll_key'"
                );
                $cds = $this->query_objs_ss($qc);
                foreach ($cds as $cd) {
                    $w->{$cd->name} = $cd->value;
                }
            }
            $result[1] = $winners;
        }

        return $result;
    }
    /**
     *
     */
    public function rounds($aid) 
    {
        /**
         * 获得活动的定义
         */
        $q = array(
            'access_control',
            'xxt_enroll',
            "id='$aid'"
        );
        $act = $this->query_obj_ss($q);
        /**
         * 获得抽奖的轮次
         */
        $q = array(
            '*',
            'xxt_enroll_lottery_round',
            "aid='$aid'"
        );
        $rounds = $this->query_objs_ss($q);

        return $rounds;
    }
    /**
     * 活动中奖名单
     *
     * todo 临时
     *
     */
    public function getLotteryWinners($aid, $rid=null) 
    {
        /**
         * 获得活动的定义
         */
        $q = array(
            'access_control',
            'xxt_enroll',
            "id='$aid'"
        );
        $act = $this->query_obj_ss($q);
        /**
         * 已经抽中的人
         */
        $q = array(
            'l.*,r.title,e.enroll_key',
            'xxt_enroll_lottery l,xxt_enroll_lottery_round r,xxt_enroll_record e',
            "l.aid='$aid' and l.round_id=r.round_id and l.aid=e.aid and l.enroll_key=e.enroll_key"
        );
        if (!empty($rid)) $q[2] .= " and l.round_id='$rid'";
        $q2 = array('o'=>'l.round_id,l.draw_at');
        if ($winners = $this->query_objs_ss($q, $q2)) {
            /**
             * 获得自定义数据的值
             */
            foreach ($winners as &$w) {
                $qc = array(
                    'name,value',
                    'xxt_enroll_record_data',
                    "enroll_key='$w->enroll_key'"
                );
                $cds = $this->query_objs_ss($qc);
                foreach ($cds as $cd)
                    $w->{$cd->name} = $cd->value;
            }
        }

        return $winners;
    }
}
