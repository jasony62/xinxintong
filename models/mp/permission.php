<?php
namespace mp;
/**
 *
 */
class permission_model extends \TMS_MODEL {
    /**
     *
     */
    private $RIGHTS = array(
        'mpsetting'=>'MP',
        'mpsetting_setting'=>'MP',
        'mpsetting_feature'=>'MP',
        'mpsetting_customapi'=>'MP',
        'mpsetting_permission'=>'MP',
        'mpsetting_administrator'=>'MP',
        'matter'=>'MP',
        'matter_article'=>'MP',
        'matter_text'=>'MP',
        'matter_news'=>'MP',
        'matter_channel'=>'MP',
        'matter_link'=>'MP',
        'matter_tmplmsg'=>'MP',
        'matter_media'=>'MP',
        'reply'=>'MP',
        'reply_text'=>'MP',
        'reply_menu'=>'MP',
        'reply_qrcode'=>'MP',
        'reply_other'=>'MP',
        'user'=>'MP',
        'user_received'=>'MP',
        'user_send'=>'MP',
        'user_fans'=>'MP',
        'user_member'=>'MP',
        'user_department'=>'MP',
        'user_tag'=>'MP',
        'user_fansgroup'=>'MP',
        'app'=>'MP',
        'app_enroll'=>'MP',
        'app_lottery'=>'MP',
        'app_wall'=>'MP',
        'app_addressbook'=>'MP',
        'app_contribute'=>'MP',
        'app_merchant'=>'MP',
        'analyze'=>'MP',
        'p_mpgroup_create'=>'SYS',
        'p_mp_create'=>'SYS',
        'p_mp_permission'=>'SYS',
    );
    /**
     *
     * $sRight 权限的名称
     * $sCRUD
     * $mpid
     * $uid
     */
    public function can($sRight, $sCRUD=null, $mpid=null, $uid=null) 
    {
        /**
         * 权限不存在
         */
        if (!isset($this->RIGHTS[$sRight]))
            return false;
        switch ($this->RIGHTS[$sRight]) {
        case 'MP':
            empty($mpid) && $mpid = \TMS_APP::S('mpid');
            return $this->hasMpRight($mpid, $sRight, $sCRUD, $uid);
        case 'SYS':
            return $this->hasSystemRight($sRight, $uid);
        }

        return false; 
    }
    /**
     * Returns true if a user has permissions in the particular mp
     *
     * @param $uid User ID - if not given the one of the current user is used
     * @param $sActPermission 操作权限
     * @param $mpid The mp ID
     * @param $sMpPermission 公众号权限
     * @param $sCRUD
     * @return bool
     */
    public function hasMpRight($mpid, $sMpPermission=null, $sCRUD=null, $uid=null)
    {

        if (!($thismp = \TMS_APP::model('mp\mpaccount')->byId($mpid, 'creater')))
            return false;

        empty($uid) && $uid = \TMS_CLIENT::get_client_uid();

        /**
         * 检查是否为账号的管理员
         */
        if ($uid === $thismp->creater || $this->isAdmin($mpid, $uid))
            return true;
        /**
         * 检查参数的合法性
         */
        if (is_array($sCRUD)) {
            if (0 !== count(array_diff($sCRUD, array('create','read','update','delete'))))
                return false;
            else
                array_walk($sCRUD, function(&$item){$item .= '_p';});
        } else{ 
            if (!in_array($sCRUD, array('create','read','update','delete')))
                return false;
            else
                $sCRUD = array($sCRUD.'_p');
        }
        $bPermission = false;
        if (is_array($sMpPermission)) {
            $sMpPermission = implode("','", $sMpPermission);
            $q = array(
                "permission,".implode(',',$sCRUD), 
                'xxt_mppermission',
                "mpid='$mpid' and uid='$uid' and permission in('$sMpPermission')"
            ); 
            if ($permissions = \TMS_APP::model()->query_objs_ss($q)) 
            {
                foreach($permissions as $p) {
                    foreach ($sCRUD as $CRUD)
                        $bPermission[$p->permission][$CRUD] = $p->$CRUD;
                }
            }
        } else {
            if ($permission = \TMS_APP::model()->query_obj(implode(',',$sCRUD), 'xxt_mppermission', "mpid='$mpid' and uid='$uid' and permission='$sMpPermission'")) {
                if (1 === count($sCRUD)) {
                    $bPermission = $permission->{$sCRUD[0]} === 'Y';
                } else
                    $bPermission = $permission;
            } else
                $bPermission = false;
        }
        return $bPermission;
    }
    /**
     * 系统功能权限
     */
    public function hasSystemRight($sPermission, $uid=null)
    {
        empty($uid) && $uid = \TMS_CLIENT::get_client_uid();

        if (is_array($sPermission)) {
            $sPermission = implode("','", $sPermission);
            $q = array(
                $sPermission,
                'account_group g,account_in_group i',
                "g.group_id=i.group_id and i.account_uid='$uid'"
            );
            if ($permissions = \TMS_APP::model()->query_obj_ss($q)) 
            {
                foreach($permissions as $p=>$v) {
                    $bPermission[$p] = $v == 1; 
                }
            }
        } else {
            $q = array(
                $sPermission,
                'account_group g,account_in_group i',
                "g.group_id=i.group_id and i.account_uid='$uid'"
            );
            $bPermission = (1 === (int)\TMS_APP::model()->query_val_ss($q));
        }
        return $bPermission;
    }
    /**
     * 是否为账号管理员
     */
    public function isAdmin($mpid=null, $uid=null, $includeCreater=false)
    {
        empty($mpid) && $mpid = \TMS_APP::S('mpid');
        empty($uid) && $uid = \TMS_CLIENT::get_client_uid();
        /**
         * 账号的创建人
         */
        if ($includeCreater) {
            $mpcreater = $this->query_value('creater', 'xxt_mpaccount', "mpid='$mpid'");
            if ($uid === $mpcreater)
                return true;
        }
        /**
         * 设置的系统管理员
         */
        $q = array(
            'count(*)',
            'xxt_mpadministrator',
            "mpid='$mpid' and uid='$uid'"
        );
        return (int)$this->query_val_ss($q) > 0;
    }
}
