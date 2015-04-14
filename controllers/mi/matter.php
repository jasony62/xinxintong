<?php
require_once dirname(dirname(__FILE__)).'/member_base.php';
require_once dirname(__FILE__).'/matter_page_base.php';
/**
 * 根据用户请求的资源返回页面
 */
class matter extends member_base {

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
     * $src optional
     * $code
     * $state
     *
     */
    public function index_action($mpid, $id, $type, $shareby='', $openid='', $src='', $code=null, $state=null) 
    {
        empty($mpid) && $this->outputError('没有指定当前运行的公众号');
        empty($id) && $this->outputError('素材id为空');
        empty($type) && $this->outputError('素材type为空');

        if ($code !== null && $state !== null)
            $who = $this->getOAuthUserByCode($mpid, $code);
        else {
            $state = json_encode(array($mpid, $id, $type, $openid, $src, $shareby));
            $state = $this->model()->encrypt($state, 'ENCODE', 'matter');
            $this->oauth($mpid, $state);
            $who = null;
        }

        $this->afterOAuth($state, $who);
    }
    /** 
     * 返回请求的素材
     */
    public function afterOAuth($state, $who=null)
    {
        list($mpid, $id, $type, $openid, $src, $shareby) = json_decode($this->model()->encrypt($state, 'DECODE', 'matter'));
        /**
         * visit fans.
         */
        list($ooid, $osrc) = empty($who) ? $this->getCookieOAuthUser($mpid) : $who;
        /**
         * 根据类型获得处理素材的对象
         */
        switch ($type) {
        case 'article':
            require_once dirname(__FILE__).'/page_article.php';
            $page = new page_article($id, $openid, $src, $shareby);
            break;
        case 'news':
            require_once dirname(__FILE__).'/page_news.php';
            $page = new page_news($id, $ooid, $osrc);
            break;
        case 'channel':
            require_once dirname(__FILE__).'/page_channel.php';
            $page = new page_channel($id, $ooid, $osrc);
            break;
        case 'addressbook':
            require_once dirname(__FILE__).'/page_addressbook.php';
            $page = new page_addressbook($id, $ooid, $osrc);
            break;
        case 'link':
            require_once dirname(__FILE__).'/page_external.php';
            $link = $this->model('matter/link')->byIdWithParams($id);
            if ($link->fans_only === 'Y') {
                /**
                 * 仅限关注用户访问
                 */
                $q = array(
                    'count(*)',
                    'xxt_fans',
                    "mpid='$mpid' and openid='$ooid' and src='$osrc' and unsubscribe_at=0"
                );
                if (1 !== (int)$this->model()->query_val_ss($q)) {
                    /**
                     * 不是关注用户引导用户进行关注
                     */
                    $fea = $this->model('mp\mpaccount')->getFeatures($mpid);
                    TPL::assign('follow_ele', $fea->follow_ele);
                    TPL::assign('follow_css', $fea->follow_css);
                    TPL::output('follow');
                    exit;
                }
            }
            $link->type = 'L';
            switch ($link->urlsrc) {
            case 0:
                $page = new page_external($link, $openid, $src);
                break;
            case 1:
                require_once dirname(__FILE__).'/page_news.php';
                $page = new page_news((int)$link->url, $openid, $src);
                break;
            case 2:
                require_once dirname(__FILE__).'/page_channel.php';
                $page = new page_channel((int)$link->url, $openid, $src);
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
        $openid_agent = $_SERVER['HTTP_USER_AGENT'];
        $client_ip = $this->client_ip();
        $this->model('log')->writeMatterReadLog(
            $vid, $mpid, $id, $type, $ooid, $osrc, $shareby, $openid_agent, $client_ip);
        /**
         * 访问控制
         */
        $mid = false;
        if (isset($matter->access_control) && $matter->access_control === 'Y') {
            $fan = empty($ooid) ? null : array($ooid, $osrc);
            $this->accessControl($mpid, $matter->id, $matter->authapis, $fan, $matter);
        }

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

        if ($this->model('matter/article')->praised($vid, $id)) {
            /**
             * 点了赞，再次点击，取消赞
             */
            $this->model()->delete('xxt_article_score', "article_id='$id' and vid='$vid'");
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
        }
        /**
         * 获得点赞的总数
         */
        $score = $this->model('matter/article')->score($id);

        return new ResponseData($score);
    }
    /**
     * 发表评论
     *
     * 如果公众号支持客户消息，如果文章的投稿者具备接收客户消息的条件
     * 如果投稿人设定了接收客服消息
     * 那么每次有新的评论都发送一条提醒消息给投稿人
     *
     * $id article's id.
     * $mpid article's mpid.
     */
    public function publishRemark_action($id)
    {
        $remark = $this->getPost('remark');
        if (empty($remark))
            return new ResponseError('评论不允许为空！');
        /**
         * 文章的基本信息
         */
        $a = $this->model('matter/article')->byId($id, 'mpid,writer,src,remark_notice');
        /**
         * 仅限认证用户发表评论，如果没有认证，先引导用户进行认证
         * todo 存在多个用户的问题
         *
         * 没有开通认证接口怎么办？
         * 如果是限关注用户评论怎么办？提示先关注再评论
         * remark记录的信息怎么办？
         * 需要扩展remark字段
         */
        $features = $this->model('mp\mpaccount')->getFeatures($a->mpid); 
        $aAuthapis = explode(',', $features->article_remark_authapis);
        $members = $this->authenticate($a->mpid, $aAuthapis, false);
        $i = array(
            'mid'=>$members[0]->mid,
            'article_id'=>$id, 
            'create_at'=>time(), 
            'remark'=>mysql_real_escape_string($remark)
        ); 
        $remarkId = $this->model()->insert('xxt_article_remark', $i);
        /**
         * 获得完整的评论数据
         */
        $remark = $this->model('matter/article')->remarks($id, $remarkId);
        /**
         * 是否为投稿文章，投稿人是否要接收评论
         */
        if (!empty($a->writer) && !empty($a->src) && $a->remark_notice === 'Y') {
            /**
             * 公众号是否已经开通客服接口
             */
            $apis = $this->model('mp\mpaccount')->getApis($a->mpid);
            if ($apis && $apis->{$a->src.'_custom_push'} == 1) {
                /**
                 * 投稿人是否可以接收客户消息
                 */
                if ($this->model('log')->canReceiveCustomPush($a->mpid, $a->src, $a->writer)) {
                    /**
                     * 发送评论提醒
                     */
                    $nickname = empty($remark->nickname) ? (empty($remark->email) ? '': strtok('@', $remark->email)) : $remark->nickname; 
                    $text = "$nickname:$remark->remark";
                    $message = array(
                        "msgtype"=>"text",
                        "text"=>array(
                            "content"=>urlencode($text)
                        )
                    );
                    /**
                     * 发送消息
                     */
                    $rst = $this->send_to_user($a->mpid, $a->src, $a->writer, $message);
                    /**
                     * 记录日志
                     */
                    $this->model('log')->send($a->mpid, $a->src, $a->writer, null, $text, null);
                }
            }
        }

        return new ResponseData($remark);
    }
    /**
     * 下载文件
     *
     * todo 仅对会员开放 
     */
    public function link_action($mpid, $src, $user, $url, $text, $code) 
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
                    return new ResponseData($msg);
                } else {
                    $rsp = '已通过【xin_xin_tong@163.com】将链接发送到你的个人邮箱，请在邮件内打开！';
                    return new ResponseData($rsp);
                }
            } else {
                $rsp = '没有获取邮箱信息，请向指定个人邮箱！';
                return new ResponseData($rsp);
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
        list($ooid, $osrc) = $this->getCookieOAuthUser($mpid);
        $openid_agent = $_SERVER['HTTP_USER_AGENT'];
        $client_ip = $this->client_ip();

        $this->model('log')->writeShareActionLog(
            $shareid, $vid, $osrc, $ooid, $shareto, $shareby, $mpid, $id, $type, $openid_agent, $client_ip);

        return new ResponseData('finish');
    }
    /**
     * 通讯录查询
     *
     * $mpid
     * $abbr
     * $page
     * $size
     */
    public function addressbook_action($mpid, $abbr='', $deptid=null, $page=1, $size=20)
    {
        $model = $this->model('matter/addressbook');
        $rst = $model->searchPersons($mpid, $abbr, $deptid, $page, $size);

        return new ResponseData($rst);
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
