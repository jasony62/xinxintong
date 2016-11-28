<?php
namespace discuss;
/**
 * 参与主题的用户
 */
class user_model extends \TMS_MODEL {
	/**
	 * 参与主题的用户
	 */
	public function &byKey($threadId, $key) {
		$q = [
			'*',
			'xxt_discuss_thread_user',
			"thread_id=$threadId and user_key='$key'",
		];
		$user = $this->query_obj_ss($q);

		return $user;
	}
	/**
	 *
	 */
	public function create($threadId, $userKey, $username) {
		$user = new \stdClass;
		$user->thread_id = $threadId;
		$user->user_key = $userKey;
		$user->user_name = $username;
		$user->id = $this->insert('xxt_discuss_thread_user', $user, true);
		$user->vote = 'N';
		$user->posts = '';
		$user->like_posts = '';

		return $user;
	}
}