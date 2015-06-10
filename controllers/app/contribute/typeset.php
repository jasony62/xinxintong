<?php
namespace app\contribute;

require_once dirname(__FILE__).'/base.php';
/**
 * 审核活动 
 */
class typeset extends base {
    /**
     *
     */
    public function afterOAuth($mpid, $entry, $openid=null) 
    {
        $myUrl = 'http://'.$_SERVER['HTTP_HOST']."/rest/app/contribute/typeset?mpid=$mpid&entry=$entry";
        list($fid, $openid, $mid) = $this->getCurrentUserInfo($mpid, $myUrl);

        $myArticles = $this->model('matter\article')->byReviewer($mid, $entry, 'T', '*', true);
        if (!empty($myArticles)) foreach ($myArticles as $a) {
            if (!empty($a->disposer) && $a->disposer->mid === $mid && $a->disposer->phase === 'T' && $a->disposer->receive_at == 0) {
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
        $this->view_action('/app/contribute/typeset/list');
    }
    /**
     *
     */
    public function article_action($mpid, $id)
    {
        $article = $this->getArticle($mpid, $id);
        $disposer = $article->disposer;
        if ($disposer && $disposer->mid === $this->user->mid && $disposer->phase === 'T' && $disposer->state === 'P') {
            $this->model()->update(
                'xxt_article_review_log', 
                array('read_at'=>time(), 'state'=>'D'),
                "id=$disposer->id");
        }
        if ($disposer && $disposer->mid === $this->user->mid && $disposer->phase === 'T' && ($disposer->state === 'P' || $disposer->state === 'D'))
            $this->view_action('/app/contribute/typeset/article');
        else
            $this->view_action('/app/contribute/typeset/article-r');
    }
    /**
     *
     */
    public function channelAddMatter_action($mpid)
    {
        $relations = $this->getPostJson();

        $creater = '';
        $createrName = '';

        $channels = $relations->channels;
        $matter = $relations->matter;

        $model = $this->model('matter\channel');
        foreach ($channels as $channel)
            $model->addMatter($channel->id, $matter, $creater, $createrName);

        return new \ResponseData('ok');
    }
    /**
     *
     */
    public function channelDelMatter_action($mpid, $id, $reload='N') 
    {
        $matter = $this->getPostJson();

        $model = $this->model('matter\channel');

        $rst = $model->removeMatter($id, $matter);

        if ($reload === 'Y') {
            $matters = $model->getMatters($id);
            return new \ResponseData($matters);
        } else {
            return new \ResponseData($rst);
        }
    }
}
