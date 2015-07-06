<?php
namespace mi;

require_once dirname(dirname(__FILE__)).'/member_base.php';
require_once dirname(__FILE__).'/matter_page_base.php';
/**
 * 根据用户请求的资源返回页面
 */
class matter extends \member_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 打开指定的素材
     *
     * 这个接口是由浏览器直接调动，不能可靠提供关注用户（openid）信息
     *
     * $mpid 当前正在运行的公众号id。因为素材可能来源于父账号，所以素材的公众号可能和当前运行的公众号并不一致。
     * $id int matter's id
     * $type article|news|link|channel|addressbook
     * $shareby 谁分享的素材
     * $openid optional 不一定是当前访问用户，只代表从公众号获取到该素材的用户
     * $code
     *
     */
    public function index_action($mpid, $id, $type, $shareby='', $openid='', $mocker=null, $code=null) 
    {
        empty($mpid) && $this->outputError('没有指定当前运行的公众号');
        empty($id) && $this->outputError('素材id为空');
        empty($type) && $this->outputError('素材type为空');

        $who = $this->doAuth($mpid, $code, $mocker);

        $this->afterOAuth($mpid, $id, $type, $shareby, $openid, $who);
    }
    /** 
     * 返回请求的素材
     *
     * $mpid
     * $id
     * $type
     * $shareby
     * $openid
     * $who
     */
    private function afterOAuth($mpid, $id, $type, $shareby, $openid, $who=null)
    {
        /**
         * visit fans.
         */
        $ooid = empty($who) ? $this->getCookieOAuthUser($mpid) : $who;
        /**
         * 根据类型获得处理素材的对象
         */
        switch ($type) {
        case 'article':
            require_once dirname(__FILE__).'/page_article.php';
            $page = new page_article($id, $ooid, $shareby);
            break;
        case 'news':
            require_once dirname(__FILE__).'/page_news.php';
            $page = new page_news($id, $ooid);
            break;
        case 'channel':
            require_once dirname(__FILE__).'/page_channel.php';
            $page = new page_channel($id, $ooid);
            break;
        case 'addressbook':
            require_once dirname(__FILE__).'/page_addressbook.php';
            $page = new page_addressbook($id, $ooid);
            break;
        case 'link':
            require_once dirname(__FILE__).'/page_external.php';
            $link = $this->model('matter\link')->byIdWithParams($id);
            if ($link->fans_only === 'Y') {
                /**
                 * 仅限关注用户访问
                 */
                $q = array(
                    'count(*)',
                    'xxt_fans',
                    "mpid='$mpid' and openid='$ooid' and unsubscribe_at=0"
                );
                if (1 !== (int)$this->model()->query_val_ss($q)) {
                    /**
                     * 不是关注用户引导用户进行关注
                     */
                    $fea = $this->model('mp\mpaccount')->getFeatures($mpid);
                    \TPL::assign('follow_ele', $fea->follow_ele);
                    \TPL::assign('follow_css', $fea->follow_css);
                    \TPL::output('follow');
                    exit;
                }
            }
            $link->type = 'L';
            switch ($link->urlsrc) {
            case 0:
                $page = new page_external($link, $ooid);
                break;
            case 1:
                require_once dirname(__FILE__).'/page_news.php';
                $page = new page_news((int)$link->url, $ooid);
                break;
            case 2:
                $channelUrl = $this->model('matter\channel')->getEntryUrl($mpid, (int)$link->url);
                $this->redirect($channelUrl);
                break;
            }
            break;
        }

        $matter = $page->getMatter();
        empty($matter->mpid) && die("parameter($id:$type) error!");
        /**
         * 记录访客信息
         */
        $vid = $this->getVisitorId($mpid);
        /**
         * write log.
         */
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $clientIp = $this->client_ip();
        if ($type === 'article') {
            $this->model()->update("update xxt_article set read_num=read_num+1 where id='$id'");
        }
        $this->model('log')->writeMatterReadLog($vid, $mpid, $id, $type, $ooid, $shareby, $userAgent, $clientIp);
        /**
         * 访问控制
         */
        $mid = false;
        if (isset($matter->access_control) && $matter->access_control === 'Y')
            $this->accessControl($mpid, $matter->id, $matter->authapis, $ooid, $matter);

        $page->output($mpid, $mid, $vid, $this);
        exit;
    }
    /**
     * 文章点赞
     *
     * $mpid
     * $id article's id.
     */
    public function score_action($mpid, $id)
    {
        /**
         * 因为打开的文章的不一定是粉丝或者认证用户，但是一定是访客，所以记录访客ID
         */
        $vid = $this->getVisitorId($mpid);

        if ($this->model('matter\article')->praised($vid, $id)) {
            /**
             * 点了赞，再次点击，取消赞
             */
            $this->model()->delete('xxt_article_score', "article_id='$id' and vid='$vid'");
            $this->model()->update("update xxt_article set score=score-1 where id='$id'");
        } else {
            /**
             * 点赞
             */
            $i = array(
                'vid'=>$vid,
                'article_id'=>$id,
                'create_at'=>time(),
                'score'=>1
            );
            $this->model()->insert('xxt_article_score', $i);
            $this->model()->update("update xxt_article set score=score+1 where id='$id'");
        }
        /**
         * 获得点赞的总数
         */
        $score = $this->model('matter\article')->score($id);

        return new \ResponseData($score);
    }
    /**
     * 发表评论
     *
     * 如果公众号支持客服消息或者点对点，如果文章的投稿者具备接收客户消息的条件
     * 如果投稿人设定了接收客服消息
     * 那么每次有新的评论都发送一条提醒消息给投稿人
     *
     * $id article's id.
     * $mpid article's mpid.
     */
    public function remarkPublish_action($mpid, $id)
    {
        $remark = $this->getPost('remark');
        if (empty($remark))
            return new \ResponseError('评论不允许为空！');

        $openid = $this->getCookieOAuthUser($mpid);
        if (empty($openid))
            return new \ResponseError('无法获得用户标识，不允许发布评论');

        $remarker = $this->model('user/fans')->byOpenid($mpid, $openid);
        if (empty($remarker))
            return new \ResponseError("无法获得用户信息($openid)，不允许发布评论");
            
        $i = array(
            'fid'=>$remarker->fid,
            'article_id'=>$id, 
            'create_at'=>time(), 
            'remark'=>$this->model()->escape($remark)
        ); 
        $remarkId = $this->model()->insert('xxt_article_remark', $i, true);
        $this->model()->update("update xxt_article set remark_num=remark_num+1 where id='$id'");
        /**
         * 获得完整的评论数据
         */
        $remark = $this->model('matter\article')->remarks($id, $remarkId);
        /**
         * 是否为投稿文章，投稿人是否要接收评论
         */
        $receivers = array();
        $a = $this->model('matter\article')->byId($id, 'title,creater,creater_src,remark_notice,remark_notice_all');
        if ($a->creater_src === 'F' && $a->remark_notice === 'Y' && $a->creater !== $remarker->fid) {
            /**
             * 投稿人接收评论提醒
             */
            $creater = $this->model('user/fans')->byId($a->creater); 
            $receivers[] = $creater->openid;
        }
        /**
         * 通知指定的评论接收人
         */
        if ($a->remark_notice_all === 'Y') {
            /**
             * 获得所有发表过评论的人
             */
            $others = $this->model('matter\article')->remarkers($id);
            foreach ($others as $other) {
                $other->openid !== $remarker->openid && !in_array($other->openid, $receivers) && $receivers[] = $other->openid;
            }
        } else if (false !== strpos($remark->remark, '@')) {
            /**
             * 获得所有发表过评论的人
             */
            $others = $this->model('matter\article')->remarkers($id);
            foreach ($others as $other) {
                if (false !== strpos($remark->remark, '@'.$other->nickname)) {
                    $other->openid !== $remarker->openid && !in_array($other->openid, $receivers) && $receivers[] = $other->openid;
                }
            }
        }
        
        if (!empty($receivers)) {
            /**
             * 发送评论提醒
             */
            $url = 'http://'.$_SERVER['HTTP_HOST']."/rest/mi/matter?mpid=$mpid&id=$id&type=article";
            $text = urlencode($remark->nickname);
            $text .= urlencode('对【');
            $text .= '<a href="'.$url.'">';
            $text .= urlencode($a->title);
            $text .= urlencode('</a>】发表了评论：');
            $text .= urlencode($remark->remark);
            $message = array(
                "msgtype"=>"text",
                "text"=>array(
                    "content"=>$text
                )
            );
            /**
             * 获得所有发表过评论的人
             */
            foreach ($receivers as $receiver) {
                $this->send_to_user($mpid, $receiver, $message);
            }
        }

        return new \ResponseData($remark);
    }
    /**
     * 下载文件
     *
     * todo 仅对会员开放 
     */
    public function link_action($mpid, $user, $url, $text, $code) 
    {
        if ($mid = $this->getMemberId($call)) {
            $q = array(
                'email,email_verified',
                'xxt_member',
                "mpid='$mpid' and mid='$mid'"
            );
            $identity = $this->model()->query_obj_ss($q);
            if ($identity->email && $identity->email_verified === 'Y') {
                if (true !== ($msg = $this->send_link_email($mpid, $identity->email, $url, $text, $code))){
                    return new \ResponseData($msg);
                } else {
                    $rsp = '已通过【xin_xin_tong@163.com】将链接发送到你的个人邮箱，请在邮件内打开！';
                    return new \ResponseData($rsp);
                }
            } else {
                $rsp = '没有获取邮箱信息，请向指定个人邮箱！';
                return new \ResponseData($rsp);
            }
        } else {
            /**
             * 引导用户进行认证
             */
            $tr = $this->register_reply($call);
            $tr->exec();
        }
    }
    /**
     * 记录分享动作
     *
     * $shareid
     * $mpid 公众号ID，是当前用户
     * $id 分享的素材ID 
     * $type 分享的素材类型 
     * $share_to  分享给好友或朋友圈
     * $shareby 谁分享的当前素材ID
     *
     */
    public function logShare_action($shareid, $mpid, $id, $type, $shareto, $shareby='')
    {
        $vid = $this->getVisitorId($mpid);
        $ooid = $this->getCookieOAuthUser($mpid);
        $openid_agent = $_SERVER['HTTP_USER_AGENT'];
        $client_ip = $this->client_ip();

        $this->model('log')->writeShareActionLog(
            $shareid, $vid, $ooid, $shareto, $shareby, $mpid, $id, $type, $openid_agent, $client_ip);

        return new \ResponseData('finish');
    }
    /**
     *
     * $mpid
     * $id channel's id
     */
    public function byChannel_action($mpid, $id, $orderby='time', $page=null, $size=null)
    {
        $vid = $this->getVisitorId($mpid);

        $params = new \stdClass;
        $params->orderby = $orderby;
        if ($page !== null && $size !== null) {
            $params->page = $page;
            $params->size = $size;
        }
        
        $matters = \TMS_APP::M('matter\channel')->getMattersNoLimit($id, $vid, $params);
        $tagModel = $this->model('tag');
        foreach ($matters as $m) {
            $matterModel = \TMS_APP::M('matter\\'.$m->type); 
            $m->url = $matterModel->getEntryUrl($mpid, $m->id);
            $m->tags = $tagModel->tagsByRes($m->id, 'article');            
        }

        return new \ResponseData($matters);
    }
    /**
     *
     * $mpid
     * $id channel's id
     */
    public function byNews_action($mpid, $id)
    {
        $matters = \TMS_APP::M('matter\news')->getMatters($id);
        $tagModel = $this->model('tag');
        foreach ($matters as $m) {
            $matterModel = \TMS_APP::M('matter\\'.$m->type); 
            $m->url = $matterModel->getEntryUrl($mpid, $m->id);
            if ($m->type === 'article')
                $m->tags = $tagModel->tagsByRes($m->id, 'article');            
        }
        
        return new \ResponseData($matters);
    }
    /**
     *
     */
    public function articleAttachment_action($mpid, $articleid, $attachmentid)
    {
        $q = array(
            '*', 
            'xxt_article_attachment', 
            "article_id='$articleid' and id='$attachmentid'"
        );
        $att = $this->model()->query_obj_ss($q);
        
        $fs = $this->model('fs/attachment', $mpid);
        
        //header("Content-Type: application/force-download");
        header("Content-Type: $att->type");
        header("Content-Disposition: attachment; filename=".$att->name);
        header('Content-Length: '.$att->size);
        echo $fs->read($att->url);
        
        exit;
    }
    /**
     *
     */
    protected function canAccessObj($mpid, $matterId, $member, $authapis, &$matter)
    {
        return $this->model('acl')->canAccessMatter($mpid, $matter->type, $matterId, $member, $authapis);
    }
    /**
     * 发送含有链接的邮件
     * todo 邮件的内容不应该在代码中写死
     */
    private function send_link_email($mpid, $email, $url, $text = '打开', $code = null) 
    {
        $mp = $this->model('mp\mpaccount')->byId($mpid);

        $subject = $mp->name."-链接";

        $content = "<p></p>";
        $content .= "<p>请点击下面的链接完成操作：</p>";
        $content .= "<p></p>";
        $content .= "<p><a href='$url'>$text</a></p>";
        if (!empty($code)) {
            $content .= "<p></p>";
            $content .= "<p>密码：$code</p>";
        }
        if (true !== ($msg = $this->send_email($mpid, $subject, $content, $email)))
            return $msg;

        return true;
    }
}
