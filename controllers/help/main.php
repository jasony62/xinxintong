<?php
/**
 *
 */
class main extends TMS_CONTROLLER {
    /**
     *
     */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     *
     */
    public function index_action($id=null) 
    {
        if (empty($id))
            $doc = $this->model('help')->getDocs();
        else
            $doc = $this->model('help')->docById($id);

        return new ResponseData($doc);
    }
    /**
     *
     */
    public function addDoc_action() 
    {
        $doc = $this->model('help')->addDoc();

        return new ResponseData($doc);
    }
    /**
     *
     */
    public function updateDoc_action($id)
    {
        $doc = $this->getPostJson();

        isset($doc->content) && $doc->content = mysql_real_escape_string($doc->content);

        $rst = $this->model('help')->saveDoc($id, (array)$doc);

        return new ResponseData($rst);
    }
    /**
     *
     */
    public function removeDoc_action($id) 
    {
        return new ResponseData($rst);
    }
}
