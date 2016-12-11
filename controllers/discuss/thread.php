<?php
namespace discuss;

require_once dirname(__FILE__) . '/base.php';
/**
 * 评论主题访问控制器
 */
class thread extends \discuss\base {
	/**
	 * 获得主题的评论列表
	 *
	 */
	public function listPosts_action($domain, $threadKey = null, $title = null, $threadId = null, $page = 1, $size = 10, $order = 'desc') {
		/* 主题 */
		$modelTrd = $this->model('discuss\thread');
		if ($threadId) {
			/**
			 * 已存在的主题
			 */
			if (false === ($thread = $modelTrd->byId($threadId))) {
				return new \ResponseError('指定的主题不存在');
			}
		} else if ($threadKey) {
			if (false === ($thread = $modelTrd->byKey($threadKey))) {
				/**
				 * 主题不存在，创建主题
				 */
				if (empty($domain)) {
					return new \ResponseError('没有指定评论主题所属的域');
				}
				if (empty($threadKey)) {
					return new \ResponseError('没有指定评论主题所属的标识');
				}
				if (empty($title)) {
					return new \ResponseError('没有指定评论主题的标题');
				}
				$thread = $modelTrd->create($domain, $threadKey, $title);
			}
		} else {
			return new \ResponseError('参数不完整');
		}

		/* 评论 */
		$modelPost = $this->model('discuss\post');
		$posts = $modelPost->byThread($thread->id, $page, $size, $order);

		/* 用户 */
		if ($user = $this->getUser($domain)) {
			$modelUsr = $this->model('discuss\user');
			if ($threadUser = $modelUsr->byKey($thread->id, $user->key)) {
				$thread->user_vote = $threadUser->vote;
				if ($threadUser->like_posts) {
					$likePosts = explode(',', $threadUser->like_posts);
					foreach ($posts as &$post) {
						if (in_array($post->id, $likePosts)) {
							$post->user_vote = 'Y';
						}
					}
				}
			}
		} else {
			$threadUser = null;
		}

		$rsp = new \stdClass;
		$rsp->thread = $thread;
		$rsp->posts = $posts;
		$rsp->user = $threadUser;

		return new \ResponseData($rsp);
	}
	/**
	 * 点赞
	 */
	public function vote_action($domain) {
		$data = $this->getPostJson();
		if (false === ($user = $this->getUser($domain))) {
			return new \ResponseError('无法获得用户信息');
		}

		$modelTrd = $this->model('discuss\thread');
		if ($thread = $modelTrd->byId($data->thread_id)) {

			$rst = $modelTrd->vote($data->thread_id, $data->vote, $user);

			$this->model('matter\discuss')->vote($thread->thread_key, $user, $rst);

			return new \ResponseData($rst);
		}

		return new \ResponseError('指定的数据不存在');
	}
}