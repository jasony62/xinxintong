<?php
require_once dirname(dirname(__FILE__)).'/mp_controller.php';

class other extends mp_controller {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';

        return $rule_action;
    }
    /**
     * 其他事件
     *
     * 如果没有创建过相应的事件，系统自动创建
     *
     * subscribe,universal,templatemsg
     */
    public function index_action() 
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
            'xxt_other_call_reply',
            "mpid='$this->mpid'"
        );
        if ($calls = $this->model()->query_objs_ss($q)) {
            foreach ($calls as $call) {
                if ($events[$call->name]) unset($events[$call->name]);
                /**
                 * 回复素材
                 */
                if ($call->matter_id)
                    $call->matter = $this->matter($this->mpid, $call->matter_type, $call->matter_id);
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
                'xxt_other_call_reply',
                $call,
                true
            );
            unset($call['mpid']);
            $calls[] = (object)$call;
        }

        return new ResponseData($calls); 
    }
    /**
     *
     */
    private function matter($mpid, $type, $id)
    {
        $m = $this->model('matter/base')->get_by_id($type, $id);
        $m->type = $type;
        return $m;
    }
    /**
     * 设置回复素材 
     */
    public function setreply_action($id) 
    {
        $matter = $this->getPostJson();
        $matter->matter_type = ucfirst($matter->matter_type);

        $rst = $this->model()->update(
            'xxt_other_call_reply', 
            $matter, 
            "id=$id"
        );
        if (!empty($matter->matter_type)) {
            if (strtolower($matter->matter_type) === 'relay')
                $matter_table = 'xxt_mprelay';
            else 
                $matter_table = 'xxt_'.strtolower($matter->matter_type);

            if ($matter_table != 'xxt_inner') {
                $rst = $this->model()->update(
                    $matter_table,
                    array('used'=>1),
                    "id=" . $matter->matter_id
                );
            }
        }

        return new ResponseData($rst);
    }
}
