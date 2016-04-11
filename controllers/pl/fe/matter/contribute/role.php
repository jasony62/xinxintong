<?php
namespace pl\fe\matter\contribute;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 投稿活动主控制器
 */
class role extends \pl\fe\matter\base {
	/**
	 * 按角色设置参与投稿活动的人
	 */
	public function setUser_action($site, $app, $role) {
		$user = $this->getPostJson();

		if (empty($user->identity)) {
			return new \ResponseError('没有指定用户的唯一标识');
		}

		if (isset($user->id)) {
			$u['identity'] = $user->identity;
			$rst = $this->model()->update(
				'xxt_contribute_user',
				$u,
				"id=$user->id"
			);
			return new \ResponseData($rst);
		} else {
			$i['siteid'] = $site;
			$i['cid'] = $app;
			$i['role'] = $role;
			$i['identity'] = $user->identity;
			$i['idsrc'] = empty($user->idsrc) ? '' : $user->idsrc;
			$i['label'] = empty($user->label) ? $user->identity : $user->label;
			$i['id'] = $this->model()->insert('xxt_contribute_user', $i, true);

			return new \ResponseData($i);
		}
	}
	/**
	 * 按角色设置参与投稿活动的人
	 * $id
	 * $acl aclid
	 */
	public function delUser_action($site, $app, $acl) {
		$rst = $this->model()->delete(
			'xxt_contribute_user',
			"siteid='$site' and id=$acl"
		);

		return new \ResponseData($rst);
	}
}