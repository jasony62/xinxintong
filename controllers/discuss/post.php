<?php
namespace discuss;

require_once dirname(__FILE__) . '/base.php';
/**
 * 评论访问控制器
 */
class post extends \discuss\base {
	/**
	 * 发表评论
	 */
	public function create_action($domain) {
		$data = $this->getPostJson();
		$modelTrd = $this->model('discuss\thread');

		if (empty($data->thread_id)) {
			return new \ResponseError('没有指定评论对象');
		}
		if (false === ($thread = $modelTrd->byId($data->thread_id))) {
			return new \ResponseError('指定的评论对象不存在');
		}
		if (false === ($user = $this->getUser($domain))) {
			return new \ResponseError('无法获得发表评论的用户信息');
		}

		$modelPost = $this->model('discuss\post');
		$rsp = $modelPost->create($thread->id, $data, $user);

		/* 增加主题的评论数 */
		$thread->comments++;
		$modelTrd->modify($thread->id, ['comments' => $thread->comments]);

		return new \ResponseData($rsp);
	}
	/**
	 *
	 */
	public function vote_action($domain) {
		$data = $this->getPostJson();
		if (false === ($user = $this->getUser($domain))) {
			return new \ResponseError('无法获得用户信息');
		}

		$modelPost = $this->model('discuss\post');
		$rsp = $modelPost->vote($data->post_id, $data->vote, $user);

		return new \ResponseData($rsp);
	}
	/**
	 *
	 */
	public function remove_action($domain) {

	}
}