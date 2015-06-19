<?php
namespace matter;

require_once dirname(__FILE__).'/base.php';
/**
 * 图文消息基类
 */
abstract class article_base extends base_model {
    /**
     * 返回进行推送的客服消息格式
     *
     * $runningMpid
     * $id
     *
     */
    public function &forCustomPush($runningMpid, $id) 
    {
        $matters = $this->getMatters($id);
        $ma = array();
        foreach ($matters as $m) {
            $ma[] = array(
                'title'=>urlencode($m->title),
                'description'=>urlencode($m->summary),
                'url'=>\TMS_APP::model('matter\\'.$m->type)->getEntryUrl($runningMpid, $m->id),
                'picurl'=>urlencode($m->pic)
            );
        }

        $msg = array(
            'msgtype'=>'news',
            'news'=>array(
                'articles'=>$ma
            )
        );

        return $msg;
    }
    /**
     * 返回进行推送的群发消息格式
     *
     * 群发的图文消息要上传的微信的服务器上，内容是必填项，因此只能发送图文消息
     *
     * 微信的群发消息不需要进行urlencode
     */
    public function &forWxGroupPush($runningMpid, $id) 
    {
        $ma = array();
        $articles = $this->getArticles($id);
        foreach ($articles as $a) {
            if (empty($a->title) || empty($a->pic) || empty($a->body))
                die('文章的标题、头图或者正文为空，不能向微信用户群发！');
            $ma[] = array(
                'title'=>$a->title,
                'description'=>$a->summary,
                'url'=>\TMS_APP::model('matter\\'.$a->type)->getEntryUrl($runningMpid, $a->id),
                'picurl'=>$a->pic,
                'body'=>$a->body
            );
        }
        $msg = array(
            'msgtype'=>'news',
            'news'=>array(
                'articles'=>$ma
            )
        );

        return $msg;
    }
    /**
     *
     */
    public function getEntryUrl($runningMpid, $id, $openid=null)
    {
        $url = "http://".$_SERVER['HTTP_HOST'];
        $url .= "/rest/mi/matter";
        $url .= "?mpid=$runningMpid&id=$id&type=".$this->getTypeName();
        if (!empty($openid))
            $url .= "&openid=$openid";

        return $url;
    }
}
