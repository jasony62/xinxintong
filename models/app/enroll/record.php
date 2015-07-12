<?php
namespace app\enroll;

class record_model extends \TMS_MODEL {
    /**
     * 获得用户的登记清单
     */
    public function byId($ek, $cascaded='Y')
    {
        $q = array(
            'e.*,f.nickname',
            'xxt_enroll_record e left join xxt_fans f on e.mpid=f.mpid and e.openid=f.openid',
            "e.enroll_key='$ek'"
        );
        if (($record = $this->query_obj_ss($q)) && $cascaded === 'Y') {
            $record->data = $this->dataById($ek);
        }
        
        return $record;
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
