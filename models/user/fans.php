<?php
/**
 *
 */
class fans_model extends TMS_MODEL {
    /**
     *
     */
    public function byId($fid, $fields='*')
    {
        $q = array(
            $fields,
            'xxt_fans',
            "fid='$fid'"
        );
        $fan = $this->query_obj_ss($q);

        return $fan;
    }
    /**
     *
     */
    public function byOpenid($mpid, $openid, $fields='*')
    {
        $q = array(
            $fields,
            'xxt_fans',
            "mpid='$mpid' and openid='$openid'"
        );
        $fan = $this->query_obj_ss($q);

        return $fan;
    }
    /**
     *
     */
    public function byMid($mid)
    {
        $q = array(
            'f.*',
            'xxt_fans f',
            "exists(select 1 from xxt_member m where f.fid=m.fid and m.mid='$mid')"
        );

        $fan = $this->query_obj_ss($q);

        return $fan;

    }
    /**
     * 是否关注了公众号
     *
     * todo 企业号的用户如何判断？
     */
    public function isFollow($mpid, $openid, $src='')
    {
        if (empty($openid))
            return false;

        $q = array(
            'count(*)',
            'xxt_fans',
            "mpid='$mpid' and openid='$openid' and unsubscribe_at=0"
        );

        $isFollow = (1 === (int)$this->query_val_ss($q));

        return $isFollow;
    }
    /**
     * 计算关注用户的内部id
     */
    public function calcId($mpid, $src, $openid)
    {
        return md5($mpid.$src.$openid);
    }
    /**
     *
     */
    public function getAll($mpid, $src) 
    {
        $q = array(
            '*',
            'xxt_fans',
            "mpid='$mpid' and src='$src'"
        );
        $fans = $this->query_objs_ss($q);

        return $fans;
    }
    /**
     *
     */
    public function getGroups($mpid, $src=null)
    {
        $q = array(
            'id,name,src',
            'xxt_fansgroup',
            "mpid='$mpid'".(empty($src) ? '':"and src='$src'")
        );
        $q2 = array('o'=>'id');
        $groups = $this->query_objs_ss($q, $q2);

        return $groups;
    }
}
