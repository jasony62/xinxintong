<?php
/**
 *
 */
class help_model extends TMS_MODEL {
    /**
     *
     */
    public function docById($id)
    {
        $q = array('*', 'tms_helpdoc', "id=$id");

        $doc = $this->query_obj_ss($q);

        return $doc;
    }
    /**
     *
     */
    public function getDocs($fields=null)
    {
        $fields===null && $fields = 'id,title,creater,create_at,modify_at';

        $q = array($fields, 'tms_helpdoc');

        $docs = $this->query_objs_ss($q);

        return $docs;
    }
    /**
     * 创建一个新文档
     */
    public function addDoc()
    {
        $current = time();
        $uid = TMS_CLIENT::get_client_uid();

        $i = array(
            'creater'=>$uid,
            'create_at'=>$current,
            'modify_at'=>$current,
            'title'=>'新文档',
            'content'=>'新文档'
        );

        $docid = $this->insert('tms_helpdoc', $i, true);

        return $this->docById($docid);
    }
    /**
     *
     */
    public function saveDoc($id, $doc)
    {
        $rst = $this->update('tms_helpdoc', $doc, "id=$id");

        return $rst;
    }
}
