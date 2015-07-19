<?php
namespace shop;

class shelf_model extends \TMS_MODEL {
    /**
     *
     */
    public function &byId($id, $fields='*')
    {
        $q = array(
            $fields,
            'xxt_shop_matter',
            "id='$id'"
        );
        $item = $this->query_obj_ss($q);

        return $item;
    }
    /**
     *
     */
    public function &byMatter($matterId, $matterType, $fields='*')
    {
        $q = array(
            $fields,
            'xxt_shop_matter',
            "matter_id='$matterId' && matter_type='$matterType'"
        );
        $item = $this->query_obj_ss($q);

        return $item;
    }
    /**
     *
     * $mpid
     * $matterId
     * $matterType
     */
    public function putMatter($mpid, $matter) 
    { 
        if ($item = $this->byMatter($matter->matter_id, $matter->matter_type))
            return false;

        $uid = \TMS_CLIENT::get_client_uid();
        $uname = \TMS_CLIENT::account()->nickname; 
        $current = time();

        $item = array(
            'creater' => $uid,
            'creater_name' => $uname,
            'put_at' => $current,
            'mpid' => $mpid,
            'matter_type' => $matter->matter_type,
            'matter_id' => $matter->matter_id,
            'title' => $matter->title,
            'pic' => $matter->pic,
            'summary' => $matter->summary
        );
        $id = $this->insert('xxt_shop_matter', $item, true);

        $item = $this->byId($id); 

        return $item;
    }
}
