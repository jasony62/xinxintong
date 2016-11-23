<?php
namespace discuss;
/**
 * 评论
 */
class post_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id) {
		$q = [
			'*',
			'xxt_discuss_post',
			"id=$id",
		];
		$post = $this->query_obj_ss($q);

		return $post;
	}
	/**
	 *
	 */
	public function &byThread($threadId, $page = null, $size = null, $order = 'desc') {
		$q = [
			'*',
			'xxt_discuss_post',
			"thread_id=$threadId",
		];
		$q2['o'] = 'create_at ' . $order;
		if ($page && $size) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		$posts = $this->query_objs_ss($q, $q2);

		return $posts;
	}
	/**
	 *
	 */
	public function &create($threadId, &$data, &$author) {
		$modelUsr = $this->model('discuss\user');
		if (false === ($threadUser = $modelUsr->byKey($threadId, $author->key))) {
			/* 如果用户不存在先创建用户 */
			$threadUser = $modelUsr->create($threadId, $author->key, $author->name);
		}
		/* 创建评论 */
		$post = new \stdClass;
		$post->thread_id = $threadId;
		$post->create_at = time();
		$post->message = isset($data->message) ? $this->escape($data->message) : '';

		$post->post_key = isset($data->post_key) ? $data->post_key : '';
		$post->parent_id = isset($data->parent_id) ? $data->parent_id : 0;
		$post->root_id = isset($data->root_id) ? $data->root_id : 0;

		$post->is_anonymous = isset($data->is_anonymous) ? $data->is_anonymous : 'N';
		$post->author_key = isset($author->key) ? $author->key : '';
		$post->author_name = isset($author->name) ? $this->escape($author->name) : '';

		$post->id = $this->insert('xxt_discuss_post', $post, true);
		$post->status = 0;
		$post->comments = 0;
		$post->likes = 0;

		/* 更新用户信息 */
		$userPosts = empty($threadUser->posts) ? $post->id : $threadUser->posts . ',' . $post->id;
		$this->update(
			'xxt_discuss_thread_user',
			['posts' => $userPosts],
			"id={$threadUser->id}"
		);

		return $post;
	}
	/**
	 *
	 */
	public function vote($postId, $vote, &$user) {
		$post = $this->byId($postId);
		$modelUsr = $this->model('discuss\user');
		if (false === ($threadUser = $modelUsr->byKey($post->thread_id, $user->key))) {
			/* 如果用户不存在先创建用户 */
			$threadUser = $modelUsr->create($post->thread_id, $user->key, $user->name);
		}
		/* 更新评论信息 */
		$postLikes = empty($threadUser->like_posts) ? [] : explode(',', $threadUser->like_posts);
		if ($vote === 'Y') {
			if (!in_array($postId, $postLikes)) {
				$postLikes[] = $postId;
				$this->update('update xxt_discuss_post set likes=likes+1 where id=' . $postId);
			}
		} else if ($vote === 'N') {
			if ($key = array_search($postId, $postLikes)) {
				array_splice($postLikes, $key, 1);
				$this->update('update xxt_discuss_post set likes=likes-1 where id=' . $postId);
			}
		}
		/* 更新用户信息 */
		$postLikes = implode(',', $postLikes);
		$rst = $this->update(
			'xxt_discuss_thread_user',
			['like_posts' => $postLikes],
			"id={$threadUser->id}"
		);

		return $rst;
	}
}