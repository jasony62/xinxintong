<?php
namespace app\enroll;

class record_model extends \TMS_MODEL {
    /**
     * 根据ID返回登记记录
     */
    public function byId($ek, $cascaded='Y')
    {
        $q = array(
            'e.*',
            'xxt_enroll_record e',
            "e.enroll_key='$ek'"
        );
        if (($record = $this->query_obj_ss($q)) && $cascaded === 'Y') {
            $record->data = $this->dataById($ek);
        }
        
        return $record;
    }
    /**
     * 活动报名名单
     *
     * 1、如果活动仅限会员报名，那么要叠加会员信息
     * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
     *
     * $mpid
     * $aid
     * $options
     * --creater openid 
     * --visitor openid 
     * --page
     * --size
     * --rid 轮次id
     * --kw 检索关键词
     * --by 检索字段
     * 
     *
     * return
     * [0] 数据列表
     * [1] 数据总条数
     * [2] 数据项的定义
     */
    public function byEnroll($mpid, $aid, $options=null) 
    {
        if ($options) {
            is_array($options) && $options = (object)$options;
            $creater = isset($options->creater) ? $options->creater : null;
            $visitor = isset($options->visitor) ? $options->visitor : '';
            $orderby = isset($options->orderby) ? $options->orderby : '';
            $page = isset($options->page) ? $options->page : null;
            $size = isset($options->size) ? $options->size : null;
            $rid = null;
            if (!empty($options->rid)) {
                if ($options->rid==='ALL')
                    $rid = null;
                else if (!empty($options->rid))
                    $rid = $options->rid;
            } else if ($activeRound = $this->getActiveRound($mpid, $aid))
                $rid = $activeRound->rid;

            $kw = isset($options->kw) ? $options->kw : null; 
            $by = isset($options->by) ? $options->by : null;
            $contain = isset($options->contain) ? $options->contain : null;
        }
        /**
         * 获得活动的定义
         */
        $q = array(
            'a.access_control',
            'xxt_enroll a',
            "a.id='$aid'"
        );
        $act = $this->query_obj_ss($q);
        
        $result = array(); // 返回的结果
        /**
         * 获得数据项定义
         */
        $modelPage = \TMS_APP::M('app\enroll\page');
        $result[2] = $modelPage->schemaByEnroll($aid);
        /**
         *
         */
        $contain = isset($contain) ? explode(',',$contain) : array();
        $w = "e.mpid='$mpid' and aid='$aid'";
        !empty($creater) && $w .= " and e.openid='$creater'";
        !empty($rid) && $w .= " and e.rid='$rid'";
        if (!empty($kw) && !empty($by)) {
            switch ($by) {
            case 'mobile':
                $kw && $w .= " and mobile like '%$kw%'";
                break;
            case 'nickname':
                $kw && $w .= " and nickname like '%$kw%'";
                break;
            }
        }
        // tags
        if (!empty($options->tags)) {
            $aTags = explode(',', $options->tags);
            foreach ($aTags as $tag) {
                $w .= "and concat(',',e.tags,',') like '%,$tag,%'";
            }
        }
        if ($act->access_control === 'Y') {
            $q = array(
                'e.enroll_key,m.name,m.mobile,e.enroll_at,e.signin_at,e.tags,e.score,e.remark_num,m.mid',
                "xxt_enroll_record e left join xxt_member m on m.forbidden='N' and e.mid=m.mid",
                $w
            );
        } else {
            $q = array(
                'e.enroll_key,f.fid,f.nickname,f.openid,f.headimgurl,e.enroll_at,signin_at,e.tags,e.score,s.score myscore,e.remark_num',
                "xxt_enroll_record e left join xxt_fans f on e.mpid=f.mpid and e.openid=f.openid left join xxt_enroll_record_score s on s.enroll_key=e.enroll_key and s.openid='$visitor'",
                $w
            );
        }
        $q2['r']['o'] = ($page-1) * $size;
        $q2['r']['l'] = $size;
        switch ($orderby) {
        case 'time':
            $q2['o'] = 'e.enroll_at desc';
            break;
        case 'score':
            $q2['o'] = 'e.score desc';
            break;
        default:
            $q2['o'] = 'e.enroll_at desc';
        }
        /**
         * 获得填写的登记数据
         */ 
        if ($roll = $this->query_objs_ss($q, $q2)) {
            /**
             * 获得自定义数据的值
             */
            foreach ($roll as &$r) {
                $qc = array(
                    'name,value',
                    'xxt_enroll_record_data',
                    "enroll_key='$r->enroll_key'"
                );
                $cds = $this->query_objs_ss($qc);
                $r->data = new \stdClass;
                foreach ($cds as $cd)
                    $r->data->{$cd->name} = $cd->value;
            }
            $result[0] = $roll;
            if (in_array('total', $contain)) {
                $q[0] = 'count(*)';
                $total = (int)$this->query_val_ss($q);
                $result[1] = $total;
            }
        }

        return $result;
    }
    /**
     * 获得用户的登记清单
     */
    public function byUser($mpid, $aid, $openid)
    {
        if (!empty($openid)) {
            $q = array(
                '*',
                'xxt_enroll_record',
                "mpid='$mpid' and aid='$aid' and openid='$openid'"
            );
            if ($activeRound = $this->getActiveRound($mpid, $aid))
                $q[2] .= " and rid='$activeRound->rid'";

            $q2 = array('o'=>'enroll_at desc');

            $list = $this->query_objs_ss($q, $q2);

            return $list;
        } else
            return false;
    }
    /**
     * 获得一条登记记录的数据
     */
    public function dataById($enrollKey)
    {
        $q = array(
            'name,value',
            'xxt_enroll_record_data',
            "enroll_key='$enrollKey'"
        );
        $cusdata = array();
        $cdata = $this->query_objs_ss($q);
        if (count($cdata) > 0) {
            foreach ($cdata as $cd)
                $cusdata[$cd->name] = $cd->value;
        }
        return $cusdata;
    }
}
