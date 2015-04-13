<?php
/**
 *
 */
class main extends TMS_CONTROLLER {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 检索页面
     */
    public function index_action($pid=null) 
    {
        if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
            if ($pid === null) {
                $uid = TMS_CLIENT::get_client_uid();
                $pages = $this->model('code/page')->byUser($uid);
                return new ResponseData(array($pages, count($pages)));
            } else {
                $page = $this->model('code/page')->byId($pid);
                return new ResponseData($page);
            }
        } else {
            if ($pid === null) {
                $this->view_action('/code/main');
            } else {
                $page = $this->model('code/page')->byId($pid);
                TPL::assign('page', $page);
                $this->view_action('/code/page');
            }
        }
    }
    /**
     * 创建新页面
     */
    public function create_action()
    {
        $uid = TMS_CLIENT::get_client_uid();
        $page = $this->model('code/page')->create($uid);

        return new ResponseData($page);
    }
    /**
     * 删除页面
     */
    public function remove_action($id)
    {
        $rst = $this->model('code/page')->remove($id);

        return new ResponseData($rst);
    }
    /**
     *
     */
    public function update_action($id)
    {
        $nv = $this->getPostJson();

        foreach ($nv as $n=>$v) {
            if (in_array($n, array('summary','html','css','js')))
                $nv->$n = mysql_real_escape_string($v);
        }

        $rst = $this->model()->update(
            'xxt_code_page',
            (array)$nv,
            "id=$id"
        );

        return new ResponseData($rst);
    }
}
