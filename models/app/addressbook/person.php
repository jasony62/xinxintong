<?php
namespace app\addressbook;
/**
 *
 */
class person_model extends \TMS_MODEL {
    /**
     *
     */
    public function &byId($id, $fields='*') 
    {
        $q = array(
            $fields, 
            'xxt_ab_person', 
            "id='$id'"
        );
        $p = $this->query_obj_ss($q);

        return $p;
    }
}
