<?php
namespace discuss;
/**
 * 主题
 */
class thread_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id) {
		$q = [
			'*',
			'xxt_discuss_thread',
			"id=$id",
		];
		$thread = $this->query_obj_ss($q);

		return $thread;
	}
	/**
	 *
	 */
	public function &byKey($key) {
		$q = [
			'*',
			'xxt_discuss_thread',
			"thread_key='$key'",
		];
		$thread = $this->query_obj_ss($q);

		return $thread;
	}
	/**
	 *
	 */
	public function &create($domain, $key, $title, $excerpt = '') {
		$thread = new \stdClass;
		$thread->domain = $domain;
		$thread->thread_key = $key;
		$thread->title = $this->escape($title);
		$thread->excerpt = $this->escape($excerpt);
		$thread->create_at = time();

		$thread->id = $this->insert('xxt_discuss_thread', $thread, true);
		$thread->comments = 0;
		$thread->likes = 0;
		$thread->dislikes = 0;

		return $thread;
	}
	/**
	 *
	 */
	public function vote($threadId, $vote, &$user) {
		$thread = $this->byId($threadId);

		$modelUsr = $this->model('discuss\user');
		if (false === ($threadUser = $modelUsr->byKey($threadId, $user->key))) {
			/* 如果用户不存在先创建用户 */
			$threadUser = $modelUsr->create($threadId, $user->key, $user->name);
		}

		/* 更新主题信息 */
		if ($vote === 'Y' && $threadUser->vote !== 'Y') {
			$this->update('update xxt_discuss_thread set likes=likes+1 where id=' . $threadId);
		} else if ($vote === 'N' && $threadUser->vote === 'Y') {
			$this->update('update xxt_discuss_thread set likes=likes-1 where id=' . $threadId);
		}

		/* 更新用户信息 */
		$rst = $this->update(
			'xxt_discuss_thread_user',
			['vote' => $vote],
			"id={$threadUser->id}"
		);

		return $rst;
	}
	/**
	 *
	 */
	public function modify($threadId, $data) {
		return $this->update('xxt_discuss_thread', $data, "id=$threadId");
	}
}