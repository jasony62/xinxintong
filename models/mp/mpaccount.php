<?php
namespace mp;
/**
 *
 */
class mpaccount_model extends \TMS_MODEL {
    /**
     * 创建一个公众账号
     */
    public function create($data) 
    {
        /**
         * 公众号的基本信息
         */
        $uid = \TMS_CLIENT::get_client_uid();
        $mpid = $this->uuid($uid);
        $data['mpid'] = $mpid;
        $data['creater'] = $uid;
        $data['create_at'] = time();
        $this->insert('xxt_mpaccount', $data, false);
        /**
         * 公众号的配置信息
         */
        $setting = array(
            'mpid'=>$mpid,
            'follow_ele'=>'请关注公众号！'
        );
        $this->insert('xxt_mpsetting', $setting, false);

        return $mpid;
    }
    /**
     *
     */
    public function &byId($mpid, $fields='*')
    {
        $q = array(
            $fields,
            'xxt_mpaccount',
            "mpid='$mpid'"
        );

        $mpaccount = $this->query_obj_ss($q);

        return $mpaccount;
    }
    /**
     * 是否为账号管理员
     */
    public function isCreater($mpid=null, $uid=null)
    {
        empty($mpid) && $mpid = \TMS_APP::S('mpid');
        empty($uid) && $uid = \TMS_CLIENT::get_client_uid();
        /**
         * 账号的创建人
         */
        $mpcreater = $this->query_value('creater', 'xxt_mpaccount', "mpid='$mpid'");
        if ($uid === $mpcreater)
            return true;

        return false;
    }
    /**
     * 获得账号创建人的邮箱
     */
    public function getCreater($mpid)
    {
        $q = array(
            'a.authed_id,a.nickname',
            'account a,xxt_mpaccount m',
            "a.uid=m.creater and m.mpid='$mpid'"
        );
        $creater = $this->query_obj_ss($q); 

        return $creater;
    }
    /**
     * 获得账号创建人的联系方式
     */
    public function getAdminContact($mpid=null)
    {
        empty($mpid) && $mpid = \TMS_APP::S('mpid');

        empty($mpid) && die('mpid is empty!');

        $q = array(
            'admin_contact contact',
            'xxt_mpsetting m',
            "m.mpid='$mpid'"
        );
        $admin = $this->query_obj_ss($q);

        if (!$admin || empty($admin->contact)) {
            if ($creater = $this->getCreater($mpid))
                $contact = empty($creater->nickname) ? $creater->authed_id : $creater->nickname;
            else
                $contact = '';
        } else 
            $contact = $admin->contact;

        return $contact;
    }
    /**
     *
     */
    public function &getSetting($mpid, $fields='*')
    {
        $q = array(
            $fields,
            'xxt_mpsetting',
            "mpid='$mpid'"
        );

        $mpsetting = $this->query_obj_ss($q);

        return $mpsetting;
    }
    /**
     * 公众号的接口开放情况
     */
    public function &getApis($mpid)
    {
        $names = 'a.mpsrc,a.asparent,a.yx_joined,a.wx_joined,a.qy_joined';
        $names .= ',s.yx_menu,s.wx_menu';
        $names .= ',s.yx_group_push,s.wx_group_push,s.yx_custom_push,s.wx_custom_push';
        $names .= ',s.yx_fans,s.wx_fans,s.yx_fansgroup,s.wx_fansgroup';
        $names .= ',s.yx_qrcode,s.wx_qrcode';
        $names .= ',s.yx_oauth,s.wx_oauth';
        $names .= ',s.yx_p2p,s.yx_checkmobile';

        $q = array(
            $names,
            'xxt_mpaccount a,xxt_mpsetting s',
            "s.mpid='$mpid' and a.mpid=s.mpid"
        );

        $mpsetting = $this->query_obj_ss($q);

        return $mpsetting;
    }
    /**
     *
     */
    public function &getFeatures($mpid, $fields='*')
    {
        if ($fields === '*') {
            $names = 'can_article_remark_by_fans,can_article_remark,article_remark_authapis';
            $names .= ',body_ele,body_css,follow_ele,follow_css';
            $names .= ',matter_visible_to_creater';
            $names .= ',admin_contact,admin_email,admin_email_pwd,admin_email_smtp,admin_email_port,admin_email_tls';
            $names .= ',can_member,can_member_card,can_member_checkin,can_member_credits';
        } else {
            $names = $fields;
        }
        $q = array(
            $names,
            'xxt_mpsetting s',
            "s.mpid='$mpid'"
        );

        $mpsetting = $this->query_obj_ss($q);

        return $mpsetting;
    }
    /**
     * 获得定义的认证接口
     *
     * $mpid
     * $valid [null|Y|N]
     */
    public function &getAuthapis($mpid, $valid=null)
    {
        $q = array(
            '*',
            'xxt_member_authapi',
            "mpid='$mpid'"
        );

        if ($valid !== null)
            $q[2] .= " and valid='$valid'";

        if (!($apis = $this->query_objs_ss($q)))
            $apis = array();

        foreach ($apis as $api)
            if (!empty($api->extattr))
                $api->extattr = json_decode($api->extattr);

        return $apis;
    }
    /**
     * 获得定义的转发接口
     */
    public function &getRelays($mpid)
    {
        $q = array(
            '*',
            'xxt_mprelay r',
            "r.mpid='$mpid'"
        );

        if (!($mprelays = $this->query_objs_ss($q)))
            $mprelays = array();

        return $mprelays;
    }
    /**
     * 获得定义的转发接口
     */
    public function &getRelay($id)
    {
        $q = array(
            '*',
            'xxt_mprelay r',
            "r.id='$id'"
        );

        $mprelay = $this->query_obj_ss($q);

        return $mprelay;
    }
    /**
     *
     */
    public function addRelay($aRelay)
    {
        $rid = $this->insert('xxt_mprelay', $aRelay, true);
        $q = array(
            '*',
            'xxt_mprelay r',
            "r.id='$rid'"
        );
        $relay = $this->query_obj_ss($q);

        return $relay;
    }
}

