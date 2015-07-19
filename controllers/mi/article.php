<?php
namespace mi;

require_once dirname(dirname(__FILE__)).'/member_base.php';
/**
 * 根据用户请求的资源返回页面
 */
class article extends \member_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /** 
     * 返回请求的素材
     *
     * $mpid
     * $id
     */
    public function get_action($mpid, $id)
    {
        $openid = $this->getCookieOAuthUser($mpid);
        $vid = $this->getVisitorId($mpid);
        
        $data = array();
        
        $modelArticle = $this->model('matter\article');
        $article = $modelArticle->byId($id);
        $article->remarks =  $article->remark_num > 0 ? $modelArticle->remarks($id) : false;
        $article->praised =  $modelArticle->praised($vid, $id);
        $data['article'] = $article;

        $user = new \stdClass;
        $user->openid = $openid;
        $user->vid = $vid;
        $data['user'] = $user;
        
        $mpaccount = $this->getCommonSetting($mpid);
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/yixin/i', $user_agent)) {
            $modelMpa = $this->model('mp\mpaccount');
            $mpa = $modelMpa->byId($mpid, 'yx_cardname,yx_cardid');
            $mpaccount->yx_cardname = $mpa->yx_cardname;
            $mpaccount->yx_cardid = $mpa->yx_cardid;
        }
        $data['mpaccount'] = $mpaccount;

        return new \ResponseData($data);
    }
    /**
     * 文章点赞
     *
     * $mpid
     * $id article's id.
     * $scope 分数
     * $once 只允许投一次
     */
    public function score_action($mpid, $id, $score=1, $once='Y')
    {
        /**
         * 因为打开的文章的不一定是粉丝或者认证用户，但是一定是访客，所以记录访客ID
         */
        if ($once === 'Y') {
            $vid = $this->getVisitorId($mpid);
            if ($this->model('matter\article')->praised($vid, $id)) {
                /**
                 * 点了赞，再次点击，取消赞
                 */
                $this->model()->delete('xxt_article_score', "article_id='$id' and vid='$vid'");
                $this->model()->update("update xxt_article set score=score-$score where id='$id'");
                $praised = false;
            } else {
                /**
                 * 点赞
                 */
                $i = array(
                    'vid' => $vid,
                    'article_id' => $id,
                    'create_at' => time(),
                    'score' => $score
                );
                $this->model()->insert('xxt_article_score', $i);
                $this->model()->update("update xxt_article set score=score+$score where id='$id'");
                $praised = true;
            }
        } else {
            $this->model()->update("update xxt_article set score=score+$score where id='$id'");
            $praised = true;
        }
        /**
         * 获得点赞的总数
         */
        $article = $this->model('matter\article')->byId($id, 'score');

        return new \ResponseData(array($article->score, $praised));
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
    public function remark_action($mpid, $id)
    {
        $posted = $this->getPostJson();
        if (empty($posted->remark))
            return new \ResponseError('评论不允许为空！');
        
        $user = $this->getUser($mpid);
        if (empty($user->openid))
            return new \ResponseError('无法获得用户标识，不允许发布评论');
        /**
         * 插入一条新评论
         */
        $i = array(
            'fid' => $user->fan->fid,
            'article_id' => $id, 
            'create_at' => time(), 
            'remark' => $this->model()->escape($posted->remark)
        ); 
        $remarkId = $this->model()->insert('xxt_article_remark', $i, true);
        $this->model()->update("update xxt_article set remark_num=remark_num+1 where id='$id'");
        
        $modelArticle = $this->model('matter\article'); 
        /**
         * 获得完整的评论数据
         */
        $remark = $modelArticle->remarks($id, $remarkId);
        /**
         * 是否为投稿文章，投稿人是否要接收评论？？？
         */
        $receivers = array();
        $a = $modelArticle->byId($id, 'title,creater,creater_src,remark_notice,remark_notice_all');
        if ($a->creater_src === 'F' && $a->remark_notice === 'Y' && $a->creater !== $user->fan->fid) {
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
            $others = $modelArticle->remarkers($id);
            foreach ($others as $other) {
                $other->openid !== $user->openid && !in_array($other->openid, $receivers) && $receivers[] = $other->openid;
            }
        } else if (false !== strpos($remark->remark, '@')) {
            /**
             * 通知指定的人
             */
            $others = $modelArticle->remarkers($id);
            foreach ($others as $other) {
                if (false !== strpos($remark->remark, '@'.$other->nickname)) {
                    $other->openid !== $user->openid && !in_array($other->openid, $receivers) && $receivers[] = $other->openid;
                }
            }
        }
        
        if (!empty($receivers)) {
            /**
             * 发送评论提醒
             */
            $url = 'http://'.$_SERVER['HTTP_HOST']."/views/default/article2.html?mpid=$mpid&id=$id";
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
             * 获得所有发表过评论的人？？？
             */
            foreach ($receivers as $receiver) {
                $this->send_to_user($mpid, $receiver, $message);
            }
        }

        return new \ResponseData($remark);
    }
}
