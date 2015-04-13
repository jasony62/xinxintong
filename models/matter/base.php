<?php
class base_model extends TMS_MODEL {
    /**
     * 根据类型和ID获得素材
     *
     * $type: Text,Article,News,Link,Inner,Channel,Joinwall
     */
    public function get_by_id($type, $id) 
    {
        switch (lcfirst($type)) {
        case 'joinwall':
            $q = array('*', 'xxt_wall', "wid='$id'");
            break;
        case 'relay':
            $q = array('*', 'xxt_mprelay', "id='$id'");
            break;
        case 'addressbook':
            $q = array('*', 'xxt_address_book', "id='$id'");
            break;
        case 'activity':
            $q = array('*', 'xxt_activity', "aid='$id'");
            break;
        case 'activitysignin':
            $q = array('*', 'xxt_activity', "aid='$id'");
            break;
        case 'discuss':
            $q = array('*', 'xxt_wall', "wid='$id'");
            break;
        case 'lottery':
            $q = array('*', 'xxt_lottery', "lid='$id'");
            break;
        default:
            $table = 'xxt_'.lcfirst($type);
            $q = array('*', $table, "id='$id'");
        }

        $matter = $this->query_obj_ss($q);

        return $matter;
    }
    /**
     *
     */
    public function &byId($id, $fields='*')
    {
        $q = array(
            $fields,
            $this->table(),
            "id='$id'"
        );
        $matter = $this->query_obj_ss($q);

        return $matter;
    }
}
