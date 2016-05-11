<?php
namespace mi;
/**
 * 单图文
 */
class page_article extends matter_page_base {
	/**
	 * $id 单图文的ID
	 * $openid 谁从公众号获得单图文，文章传播的起点
	 * $shareby 文章是通过那个分享活动获得的
	 * $ooid 谁在打开的单图文
	 */
	public function __construct($id, $openid, $shareby) {
		$article = \TMS_APP::model('matter\article')->byId($id, "*");
		$article->type = 'article';
		if ($article->has_attachment === 'Y') {
			$article->attachments = \TMS_APP::model()->query_objs_ss(array('*', 'xxt_article_attachment', "article_id='$id'"));
		}

		$this->article = $article;
		parent::__construct($this->article, $openid);

		$this->shareby = $shareby;
	}
	/**
	 *
	 * todo 需要处理多个回车换行的问题
	 */
	public function output($runningMpid, $mid, $vid, $ctrl) {
		if (!$this->article) {
			return false;
		}

		/**
		 * output an article
		 */
		$data = array();
		$this->article->shareby = $this->shareby;
		$data['mpid'] = $runningMpid;

		$visitor = new \stdClass;
		$visitor->openid = $this->openid;
		$visitor->vid = $vid;
		$data['visitor'] = $visitor;

		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match('/yixin/i', $user_agent)) {
			if ($mp = \TMS_APP::model()->query_obj('yx_cardname,yx_cardid', 'xxt_mpaccount', "mpid='$runningMpid'")) {
				$yx_cardname = $mp->yx_cardname;
				$yx_cardid = $mp->yx_cardid;
			}
		}
		\TPL::assign('yx_cardid', empty($yx_cardid) ? false : $yx_cardid);
		\TPL::assign('yx_cardname', empty($yx_cardname) ? false : $yx_cardname);
		/**
		 * 补充数据
		 */
		$model = \TMS_APP::model('matter\article');
		/**
		 * 评价信息
		 */
		$user = $this->getUser($runningMpid);
		$this->article->praised = $model->praised($user, $this->article->id);
		/**
		 * 评论
		 */
		$this->article->remarks = $mpsetting->can_article_remark === 'Y' ? $model->remarks($this->article->id) : false;

		$data['article'] = $this->article;
		\TPL::assign('data', $data);
		/**
		 * 选择模板
		 */
		if ($this->article->custom_body === 'N') {
			\TPL::output('article');
		} else {
			\TPL::assign('title', $this->article->title);
			$nl = array("\r\n", "\n", "\r");
			if ($this->article->page_id) {
				$page = \TMS_APP::M('code\page')->byId($this->article->page_id);
				$page->html && \TPL::assign('body', $page->html);
				$page->css && \TPL::assign('css', $page->css);
				$page->js && \TPL::assign('js', $page->js);
				\TPL::assign('ext_js', $page->ext_js);
				\TPL::assign('ext_css', $page->ext_css);
			} else {
				$body = str_replace($nl, '', $this->article->body);
				\TPL::assign('body', $body);
				$this->article->css && \TPL::assign('css', $this->article->css);
			}
			\TPL::output('custom');
		}
	}
}