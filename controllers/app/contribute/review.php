<?php
namespace app\contribute;

require_once dirname(__FILE__).'/base.php';
/**
 * 审核
 */
class review extends base {
    /**
     *
     */
    public function afterOAuth($mpid, $entry, $openid=null) 
    {
        $myUrl = 'http://'.$_SERVER['HTTP_HOST']."/rest/app/contribute/review?mpid=$mpid&entry=$entry";
        list($fid, $openid, $mid) = $this->getCurrentUserInfo($mpid, $myUrl);

        $this->view_action('/app/contribute/review/list');
    }
    /**
     * 待审核稿件
     */
    public function articleList_action($mpid, $entry) 
    {
        $myArticles = $this->model('matter\article')->byReviewer($this->user->mid, $entry, 'R', '*', true);
        if (!empty($myArticles)) foreach ($myArticles as $a) {
            $disposer = $a->disposer; 
            if (!empty($disposer) && $disposer->mid === $this->user->mid && $disposer->phase === 'R' && $disposer->receive_at == 0) {
                $this->model()->update(
                    'xxt_article_review_log', 
                    array('receive_at'=>time()),
                    "id=".$a->disposer->id);
            }
        }

        return new \ResponseData($myArticles);
    }
    /**
     * 页面
     */
    public function article_action($mpid, $id)
    {
        $article = $this->getArticle($mpid, $id);
        
        $disposer = $article->disposer;
        if ($disposer && $disposer->mid === $this->user->mid && $disposer->phase === 'R' && $disposer->state === 'P') {
            $this->model()->update(
                'xxt_article_review_log', 
                array('receive_at'=>time()),
                "id=$disposer->id and receive_at=0");
            $this->model()->update(
                'xxt_article_review_log', 
                array('read_at'=>time(), 'state'=>'D'),
                "id=$disposer->id");
        }

        $this->view_action('/app/contribute/review/article-r');
    }
    /**
     * 页面
     */
    public function reviewlog_action($mpid, $id)
    {
        $article = $this->getArticle($mpid, $id);
        
        $disposer = $article->disposer;
        if ($disposer && $disposer->mid === $this->user->mid && $disposer->phase === 'R' && $disposer->state === 'P') {
            $this->model()->update(
                'xxt_article_review_log', 
                array('read_at'=>time(), 'state'=>'D'),
                "id=$disposer->id");
        }

        $this->view_action('/app/contribute/review/article-r');
    }

    /**
     * 文稿审核通过
     */
    public function articlePass_action($mpid, $id)
    {
        $article = $this->getArticle($mpid, $id);
        /**
         * 更新日志状态
         */
        $disposer = $article->disposer;
        
        if ($disposer && $disposer->mid === $this->user->mid && $disposer->phase === 'R' && $disposer->state === 'D') {
            $this->model()->update(
                'xxt_article_review_log', 
                array('close_at'=>time(), 'state'=>'C'),
                "id=$disposer->id");
        }
        /**
         * 更新文稿状态
         */
        $rst = $this->model()->update(
            'xxt_article', 
            array('approved'=>'Y'),
            "mpid='$mpid' and id='$id'"
        );
        /**
         * 发送通知
         */
        $url = 'http://'. $_SERVER['HTTP_HOST'];
        $url .= '/rest/app/contribute/initiate/article';
        $url .= "?mpid=$mpid";
        $url .= "&entry=$article->entry";
        $url .= "&id=$id";
        
        $reply = urlencode('您的稿件【');
        $reply .= "<a href=\"$url\">";
        $reply .= urlencode($article->title);
        $reply .= "</a>";
        $reply .= urlencode('】已经通过审核。');
        
        $message = array(
            "msgtype" => "text",
            "text" => array(
                "content" => $reply
            )
        );
        
        $fan = $this->model('user/fans')->byMid($article->creater);

        $rst = $this->notify($mpid, $fan->openid, $message);
        
        return new \ResponseData('ok');
    }
    /**
     * 版面页面
     */
    public function news_action($mpid, $id)
    {
        $news = $this->getNews($mpid, $id);
        $disposer = $news->disposer;
        /**
         * 更改处理状态
         */
        if ($disposer && $disposer->mid === $this->user->mid && $disposer->phase === 'R' && $disposer->state === 'P') {
            $this->model()->update(
                'xxt_news_review_log', 
                array('read_at'=>time(), 'state'=>'D'),
                "id=$disposer->id");
        }
        
        $this->view_action('/app/contribute/review/news-r');
    }
    /**
     * 待审核版面
     */
    public function newsList_action($mpid, $id)
    {
        $myNews = $this->model('matter\news')->byReviewer($this->user->mid, 'R', '*', true);
        
        if (!empty($myNews)) foreach ($myNews as $n) {
            $disposer = $n->disposer; 
            if (!empty($disposer) && $disposer->mid === $this->user->mid && $disposer->phase === 'R' && $disposer->receive_at == 0) {
                $this->model()->update(
                    'xxt_news_review_log', 
                    array('receive_at'=>time()),
                    "id=".$n->disposer->id);
            }
        }
        
        return new \ResponseData($myNews);
    }
}
