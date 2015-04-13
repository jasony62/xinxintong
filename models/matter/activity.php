<?php
require_once dirname(dirname(__FILE__)).'/reply_class.php';

class activity_model extends TMS_MODEL {
    /**
     * 返回进行推送的客服消息格式
     *
     * $runningMpid
     * $id
     */
    public function &forCustomPush($runningMpid, $id) 
    {
        $act = TMS_APP::model('activity/enroll')->byId($id);
        $act->type = 'activity';
        $ma[] = array(
            'title'=>urlencode($act->title),
            'description'=>urlencode($act->summary),
            'url'=>TMS_APP::model('reply')->getMatterUrl($runningMpid, $act),
            'picurl'=>urlencode($act->pic)
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
