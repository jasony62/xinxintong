<?php
namespace mp\call;

require_once dirname(__FILE__).'/base.php';

class text extends call_base {

    private $meterial; // 素材

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';

        return $rule_action;
    }
    /**
     * 事件的类型
     */
    protected function getCallType()
    {
        return 'Text';
    }
    /**
     * get all text call.
     */
    public function index_action() 
    {
        $this->view_action('/mp/reply/text');
    }
    /**
     * get all text call.
     */
    public function get_action($cascade='y') 
    {
        $calls = array();
        /**
         * 父账号定义的文本消息回复
         */
        if ($pmpid = $this->getParentMpid()) {
            $q = array(
                'id', 
                'xxt_call_text', 
                "mpid='$pmpid'"
            );
            $q2['o'] = 'id desc';
            if ($vs = $this->model()->query_objs_ss($q, $q2)) {
                foreach ($vs as $v) {
                    $call = $this->get_by_id($v->id, $cascade==='y' ? array('matter','acl'):array());
                    $call->fromParent = 'Y';
                    $calls[] = $call;
                }
            }
        }
        /**
         * 公众号自己的文本消息回复
         */
        $q = array(
            'id', 
            'xxt_call_text', 
            "mpid='$this->mpid'"
        );
        $q2['o'] = 'id desc';

        if ($vs = $this->model()->query_objs_ss($q, $q2)) {
            foreach ($vs as $v) {
                $call = $this->get_by_id($v->id, $cascade==='y' ? array('matter','acl'):array());
                $call->fromParent = 'N';
                $calls[] = $call;
            }
        }
        return new \ResponseData($calls); 
    }
    /**
     * 获得文本命令的子资源
     */
    public function cascade_action($id) 
    {
        /**
         * 文本命令的基本信息
         */
        $q = array(
            'mpid,keyword,matter_type,matter_id',
            'xxt_call_text',
            "id=$id"
        );
        $call = $this->model()->query_obj_ss($q);
        /**
         * 回复素材
         */
        if ($call->matter_id)
            $call->matter = $this->model('matter\base')->getMatterInfoById($call->matter_type, $call->matter_id);
        /**
         * acl
         */
        $call->acl = $this->model('acl')->textCall($call->mpid, $call->keyword);
        
        return new \ResponseData($call);
    }
    /**
     * get one text call.
     *
     * $id int text call id.
     * $contain array 
     */
    private function &get_by_id($id, $contain=array('matter','acl')) 
    {
        $q = array(
            'id,mpid,keyword,match_mode,matter_type,matter_id,access_control,authapis',
            'xxt_call_text',
            "id=$id"
        );
        $call = $this->model()->query_obj_ss($q);
        /**
         * 素材
         */
        if (!empty($contain) && in_array('matter', $contain))
            if ($call->matter_id)
                $call->matter = $this->model('matter\base')->getMatterInfoById($call->matter_type, $call->matter_id);
        /**
         * acl
         */
        if (!empty($contain) && in_array('acl', $contain))
            $call->acl = $this->model('acl')->textCall($call->mpid, $call->keyword);

        return $call;
    }
    /**
     * 添加文本命令
     */
    public function create_action() 
    {
        $matter = $this->getPostJson();

        $d['matter_type'] = $matter->type;
        $d['matter_id'] = $matter->id;
        $d['mpid'] = $this->mpid;
        $keyword = isset($_POST['keyword']) ? $_POST['keyword']:'新文本消息';
        $matchMode = isset($_POST['matchMode']) ? $_POST['matchMode']:'full';
        $d['keyword'] = $keyword;
        $d['match_mode'] = $matchMode;

        $id = $this->model()->insert('xxt_call_text', $d, true);

        $call = $this->get_by_id($id);
        $call->fromParent = 'N';

        return new \ResponseData($call);
    }
    /**
     * 删除文本命令
     */
    public function delete_action($id) 
    {
        $q = array('mpid,keyword', 'xxt_call_text', "id=$id");
        if ($call = $this->model()->query_obj_ss($q)) {
            /**
             * 清除文本命令的白名单
             */
            $this->model()->delete(
                'xxt_call_acl', "mpid='$call->mpid' and call_type='Text' and keyword='$call->keyword'");
        }
        /**
         * 删除文本命令
         */
        $rsp = $this->model()->delete('xxt_call_text',"id=$id");

        return new \ResponseData($rsp);
    }
    /**
     * 更新文本项的基本信息
     *
     * $mpid
     * $id
     * $nv array 0:name,1:value
     */
    public function update_action($id) 
    {
        $nv = $this->getPostJson();
        if (isset($nv->keyword)) {
            /**
             * 修改文本命令
             */
            $q = array(
                'keyword',
                'xxt_call_text',
                "mpid='$this->mpid' and id=$id"
            );
            if ($old = $this->model()->query_val_ss($q)) {
                /**
                 * 级联更新文本命令的白名单
                 */
                $this->model()->update(
                    'xxt_call_acl',
                    array('keyword'=>$nv->keyword),
                    "mpid='$this->mpid' and call_type='Text' and keyword='$old'"
                );
            }
        }
        $rst = $this->model()->update(
            'xxt_call_text', 
            (array)$nv,
            "mpid='$this->mpid' and id=$id"
        );
        return new \ResponseData($rst);
    }
    /**
     * 指定文本项的回复素材
     */
    public function setreply_action($id) 
    {
        $reply = $this->getPostJson();

        $ret = $this->model()->update(
            'xxt_call_text', 
            array(
                'matter_type'=>$reply->rt, 
                'matter_id'=>$reply->rid
            ),
            "mpid='$this->mpid' and id=$id"
        );

        return new \ResponseData($ret);
    }
}
