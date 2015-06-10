<?php
namespace app\contribute;

require_once dirname(__FILE__).'/base.php';
/**
 * 发起投稿
 */
class initiate extends base {
    /**
     *
     */
    public function afterOAuth($mpid, $entry, $openid=null) 
    {
        $myUrl = 'http://'.$_SERVER['HTTP_HOST']."/rest/app/contribute/initiate?mpid=$mpid&entry=$entry";
        list($fid) = $this->getCurrentUserInfo($mpid, $myUrl);

        $articleModel = $this->model('matter\article');
        $channelModel = $this->model('matter\channel');
        $myArticles = $articleModel->byCreater($mpid, $fid, '*');
        if (!empty($myArticles)) foreach ($myArticles as &$a) {
            $a->disposer = $articleModel->disposer($a->id);
            //$a->channels = $channelModel->byMatter($a->id, 'article');
        }
        
        list($entryType, $entryId) = explode(',', $entry);
        $oEntry = $this->model('matter\\'.$entryType)->byId($entryId);

        if ($this->getClientSrc() && isset($oEntry->shift2pc) && $oEntry->shift2pc === 'Y') {
            /**
             * 提示在PC端完成
             */
            $fea = $this->model('mp\mpaccount')->getFeatures($mpid,'shift2pc_page_id');
            $page = $this->model('code/page')->byId($fea->shift2pc_page_id, 'html,css,js'); 
            /**
             * 任务码
             */
            if ($oEntry->can_taskcode && $oEntry->can_taskcode === 'Y') {
                $taskCode = $this->model('task')->addTask($mpid, $fid, $myUrl);
                $page->html = str_replace('{{taskCode}}', $taskCode, $page->html);
            }
            \TPL::assign('shift2pcAlert', $page);
        }
        /**
         * 指定了投稿人
         */
        $initiators = $this->model('app\contribute')->userAcls($mpid, $entryId, 'I');
        
        $params = array();
        $params['mpid'] = $mpid;
        $params['entry'] = $entry;
        $params['articles'] = $myArticles;
        $params['needReview'] = empty($initiators) ? 'N' : 'Y';
        
        \TPL::assign('params', $params);
        $this->view_action('/app/contribute/initiate/list');
    }
    /**
     * 单篇文章
     */
    public function article_action($mpid, $id)
    {
        $article = $this->getArticle($mpid, $id);
        $disposer = $article->disposer;
        
        list($entryType, $entryId) = explode(',', $article->entry);
        $initiators = $this->model('app\contribute')->userAcls($mpid, $entryId, 'I');
        
        $params = array();
        $params['fid'] = $this->user->fid;
        $params['needReview'] = empty($initiators) ? 'N' : 'Y';
        
        \TPL::assign('params', $params);

        if (empty($disposer) || $disposer->phase === 'I')
            $this->view_action('/app/contribute/initiate/article');
        else
            $this->view_action('/app/contribute/initiate/article-r');
    }
    /**
     *
     */
    public function create_action($mpid, $entry)
    {
        $mpa = $this->model('mp\mpaccount')->getFeatures($mpid, 'heading_pic');
        
        $current = time();

        $fan = $this->model('user/fans')->byId($this->user->fid, 'nickname'); 

        $article = array();
        $article['mpid'] = $mpid;
        $article['entry'] = $entry;
        $article['creater'] = $this->user->fid;
        $article['creater_name'] = $fan->nickname;
        $article['creater_src'] = 'F';
        $article['create_at'] = $current;
        $article['modify_at'] = $current;
        $article['title'] = '新文章';
        $article['pic'] = $mpa->heading_pic;
        $article['hide_pic'] = 'N';
        $article['summary'] = '';
        $article['url'] = '';
        $article['weight'] = 70;
        $article['body'] = '';
        $article['finished'] = 'N';
        $article['approved'] = 'N';
        $article['public_visible'] = 'Y';
        $article['remark_notice'] = 'Y';

        $id = $this->model()->insert('xxt_article', $article, true);
        /**
         * 设置频道
         */
        list($entryType, $entryId) = explode(',', $entry);
        $entry = $this->model('matter\\'.$entryType)->byId($entryId, 'params');
        $params = json_decode($entry->params);
        if (!empty($params->channel)) {
            $channelId = $params->channel;
            $this->model('matter\channel')->addMatter($channelId, array('id'=>$id, 'type'=>'article'), $this->user->fid, $fan->nickname);
        } 

        $article = $this->model('matter\article')->byId($id);

        return new \ResponseData($article);
    }
    /**
     * 删除一个单图文
     */
    public function remove_action($mpid, $id)
    {
        $rst = $this->model()->update(
            'xxt_article',
            array('state'=>0, 'modify_at'=>time()),
            "creater='".$this->user->fid."' and id='$id'");

        return new \ResponseData($rst);
    }
}
