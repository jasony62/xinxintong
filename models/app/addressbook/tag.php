<?php
namespace app\addressbook;
/**
 *
 */
class tag_model extends \TMS_MODEL {
    /**
     *
     */
    public function &byId($id, $fields='*') 
    {
        $q = array(
            $fields, 
            'xxt_ab_tag', 
            "id='$id'"
        );
        $t = $this->query_obj_ss($q);

        return $t;
    }
    /**
     *
     */
    public function &byTitle($abid, $name, $fields='*') 
    {
        $q = array(
            $fields, 
            'xxt_ab_tag', 
            "ab_id=$abid and name='$name'"
        );
        $t = $this->query_obj_ss($q);

        return $t;
    }
    /**
     *
     */
    public function &byAbid($abid, $fields='*') 
    {
        $q = array(
            $fields, 
            'xxt_ab_tag', 
            "ab_id=$abid"
        );
        $tags = $this->query_objs_ss($q);

        return $tags;
    }
    /**
     *
     */
    public function create($mpid, $abid, $name) 
    {
        $tag = array(
            'ab_id' => $abid,
            'mpid' => $mpid,
            'name' => $name, 
        );
        $id = $this->insert('xxt_ab_tag', $tag, true);

        return $id;
    }
}
