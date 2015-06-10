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

        $myArticles = $this->model('matter\article')->byReviewer($mid, $entry, 'R', '*', true);
        if (!empty($myArticles)) foreach ($myArticles as $a) {
            $disposer = $a->disposer; 
            if (!empty($disposer) && $disposer->mid === $mid && $disposer->phase === 'R' && $disposer->receive_at == 0) {
                $this->model()->update(
                    'xxt_article_review_log', 
                    array('receive_at'=>time()),
                    "id=".$a->disposer->id);
            }
        }

        $params = array();
        $params['mpid'] = $mpid;
        $params['articles'] = $myArticles;

        \TPL::assign('params', $params);
        $this->view_action('/app/contribute/review/list');
    }
    /**
     *
     */
    public function article_action($mpid, $id)
    {
        $article = $this->getArticle($mpid, $id);
        $disposer = $article->disposer;
        
        if ($disposer && $disposer->mid === $this->user->mid && $disposer->phase === 'R' && $disposer->state === 'P') {
            $this->model()->update(
                'xxt_article_review_log', 
                array('read_at'=>time(), 'state'=>'D'),
                "id=$disposer->id");
        }

        $this->view_action('/app/contribute/review/article');
    }
}
