<?php
require_once dirname(__FILE__).'/base.php';

class link_model extends base_model {
    /**
     *
     */
    protected function table()
    {
        return 'xxt_link';
    }
    /**
     * 返回链接和链接的参数
     */
    public function byIdWithParams($id, $fields='*')
    {
        $q = array(
            $fields, 
            'xxt_link', 
            "id=$id"
        );
        if ($link = $this->query_obj_ss($q)) {
            $q = array(
                'pname,pvalue,authapi_id', 
                'xxt_link_param', 
                "link_id=$id"
            );
            if ($params = $this->query_objs_ss($q))
                $link->params = $params;
        }

        return $link;
    }
    /**
     * 返回进行推送的消息格式
     *
     * $runningMpid
     * $id
     */
    public function &forCustomPush($runningMpid, $id) 
    {
        $link = $this->byId($id);
        $link->type = 'link';

        $msg = array(
            'msgtype'=>'news',
            'news'=>array(
                'articles'=>array(
                    array(
                        'title'=>urlencode($link->title),
                        'description'=>urlencode($link->summary),
                        'url'=>TMS_APP::model('reply')->getMatterUrl($runningMpid, $link),
                        'picurl'=>urlencode($link->pic),
                    )
                )
            )
        );

        return $msg;
    }
}
