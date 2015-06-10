<?php
namespace matter;

require_once dirname(__FILE__).'/base.php';
/**
*
*/
abstract class app_base extends base_model {
    /**
     * 返回进行推送的客服消息格式
     *
     * $runningMpid
     * $id
     */
    public function &forCustomPush($runningMpid, $id) 
    {
        $app = $this->byId($id);

        $ma[] = array(
            'title'=>urlencode($app->title),
            'description'=>urlencode($app->summary),
            'url'=>$this->getEntryUrl($runningMpid, $id),
            'picurl'=>urlencode($app->pic)
            );
        $msg = array(
            'msgtype'=>'news',
            'news'=>array(
                'articles'=>$ma
                )
            );

        return $msg;
    }
}
