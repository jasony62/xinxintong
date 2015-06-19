<?php
namespace app\contribute;

require_once dirname(__FILE__).'/base.php';
/**
 * 发起投稿
 */
class initiate extends base {
    /**
     * 进入缺省页面
     */
    public function afterOAuth($mpid, $entry, $openid=null) 
    {
        $myUrl = 'http://'.$_SERVER['HTTP_HOST']."/rest/app/contribute/initiate?mpid=$mpid&entry=$entry";
        list($fid) = $this->getCurrentUserInfo($mpid, $myUrl);

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
        
        $this->view_action('/app/contribute/initiate/list');
    }
    /**
     * 单篇文稿页面
     */
    public function article_action($mpid, $id)
    {
        $article = $this->getArticle($mpid, $id);
        
        $disposer = $article->disposer;
        if ($disposer && $disposer->mid === $this->user->mid && $disposer->phase === 'I' && $disposer->state === 'P') {
            $this->model()->update(
                'xxt_article_review_log', 
                array('read_at'=>time(), 'state'=>'D'),
                "id=$disposer->id");
        }

        list($entryType, $entryId) = explode(',', $article->entry);
        $initiators = $this->model('app\contribute')->userAcls($mpid, $entryId, 'I'); // todo ???
        
        $params = array();
        $params['fid'] = $this->user->fid;
        $params['needReview'] = empty($initiators) ? 'N' : 'Y';
        
        \TPL::assign('params', $params);

        if (empty($article->disposer) || $article->disposer->phase === 'I')
            $this->view_action('/app/contribute/initiate/article');
        else
            $this->view_action('/app/contribute/initiate/article-r');
    }
    /**
     * 当前用户文稿
     */
    public function articleList_action($mpid, $entry, $openid=null) 
    {
        $articleModel = $this->model('matter\article');
        $myArticles = $articleModel->byCreater($mpid, $this->user->mid, '*');
        if (!empty($myArticles)) foreach ($myArticles as &$a) {
            $a->disposer = $articleModel->disposer($a->id);
            $disposer = $a->disposer; 
            if (!empty($disposer) && $disposer->mid === $this->user->mid && $disposer->phase === 'I' && $disposer->receive_at == 0) {
                $this->model()->update(
                    'xxt_article_review_log', 
                    array('receive_at'=>time()),
                    "id=".$a->disposer->id);
            }
        }
        
        return new \ResponseData($myArticles);
    }
    /**
     * 新建一个文稿
     *
     * $mpid
     * $entry 
     */
    public function articleCreate_action($mpid, $entry)
    {
        $mpa = $this->model('mp\mpaccount')->getFeatures($mpid, 'heading_pic');
        
        $current = time();

        $fan = $this->model('user/fans')->byId($this->user->fid, 'nickname'); 

        $article = array();
        $article['mpid'] = $mpid;
        $article['entry'] = $entry;
        $article['creater'] = $this->user->mid;
        $article['creater_name'] = $fan->nickname;
        $article['creater_src'] = 'M';
        $article['create_at'] = $current;
        $article['modify_at'] = $current;
        $article['title'] = '新文稿';
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
     * 删除一个文稿
     */
    public function articleRemove_action($mpid, $id)
    {
        $rst = $this->model()->update(
            'xxt_article',
            array('state'=>0, 'modify_at'=>time()),
            "creater='".$this->user->mid."' and id='$id'");

        return new \ResponseData($rst);
    }
    /**
     * 转发给指定人进行处理
     *
     * $mpid 公众平台ID
     * $id 文章ID
     * $phase 处理的阶段
     */
    public function articleForward_action($mpid, $id, $phase)
    {
        $rst = $this->model()->update(
            'xxt_article', 
            array('finished'=>'Y'),
            "mpid='$mpid' and id='$id'"
        );
        
        return parent::articleForward_action($mpid, $id, $phase);
    }
}
