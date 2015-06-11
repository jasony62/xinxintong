<?php
namespace mp\call;

require_once dirname(__FILE__).'/base.php';

class other extends call_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';

        return $rule_action;
    }
    /**
     * get all text call.
     */
    public function index_action() 
    {
        $this->view_action('/mp/reply/other');
    }
    /**
     * 其他事件
     *
     * 如果没有创建过相应的事件，系统自动创建
     *
     * subscribe,universal,templatemsg
     */
    public function get_action() 
    {
        /**
         * 支持的消息类型
         */
        $events = array(
            'subscribe'=> '关注',
            'universal'=> '缺省',
            'location'=> '地理位置',
            'templatemsg'=> '模板消息结果',
            'cardevent'=> '卡卷事件'
        );

        $q = array(
            'id,name,title,matter_type,matter_id',
            'xxt_call_other',
            "mpid='$this->mpid'"
        );
        if ($calls = $this->model()->query_objs_ss($q)) {
            foreach ($calls as $call) {
                if ($events[$call->name]) unset($events[$call->name]);
                /**
                 * 回复素材
                 */
                if ($call->matter_id)
                    $call->matter = $this->model('matter\base')->getMatterInfoById($call->matter_type, $call->matter_id);
            }
        }

        /**
         * 添加新支持的事件
         */
        foreach ($events as $n=>$t) {
            $call = array(
                'mpid'=>$this->mpid,
                'name'=>$n,
                'title'=>$t,
                'matter_type'=>'',
                'matter_id'=>''
            );
            $call['id'] = $this->model()->insert(
                'xxt_call_other',
                $call,
                true
            );
            unset($call['mpid']);
            $calls[] = (object)$call;
        }

        return new \ResponseData($calls); 
    }
    /**
     * 设置回复素材 
     */
    public function setreply_action($id) 
    {
        $matter = $this->getPostJson();

        $rst = $this->model()->update(
            'xxt_call_other', 
            $matter, 
            "id=$id"
        );

        return new \ResponseData($rst);
    }
}
