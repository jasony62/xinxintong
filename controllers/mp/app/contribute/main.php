<?php
namespace mp\app\contribute;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *
 */
class main extends \mp\app\app_base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'contribute';
	}
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/mp/app/contribute');
	}
	/**
	 *
	 */
	public function detail_action() {
		$this->view_action('/mp/app/contribute/detail');
	}
	/**
	 * 返回投稿应用
	 */
	public function get_action($id = null) {
		$c = $this->model('app\contribute')->byId($id);
		/**
		 * belong to channel
		 */
		$c->channels = $this->model('matter\channel')->byMatter($id, 'contribute');
		/**
		 * 参与人
		 */
		$c->initiator = $this->model('app\contribute')->userAcls($this->mpid, $id, 'I');
		$c->reviewer = $this->model('app\contribute')->userAcls($this->mpid, $id, 'R');
		$c->typesetter = $this->model('app\contribute')->userAcls($this->mpid, $id, 'T');
		/**
		 * return
		 */
		return new \ResponseData($c);
	}
	/**
	 * 投稿活动列表
	 */
	public function list_action($page = 1, $size = 30) {
		$q = array(
			'*',
			'xxt_contribute',
			"mpid='$this->mpid' and state=1",
		);
		$q2['o'] = 'create_at desc';
		if ($c = $this->model()->query_objs_ss($q, $q2)) {
			$result['apps'] = $c;
			$q[0] = 'count(*)';
			$total = (int) $this->model()->query_val_ss($q);
			$result['total'] = $total;
			return new \ResponseData($result);
		}
		return new \ResponseData(array());
	}
	/**
	 * 创建投稿活动
	 */
	public function create_action() {
		$uid = \TMS_CLIENT::get_client_uid();
		/**
		 * 获得的基本信息
		 */
		$cid = uniqid();
		$newone['mpid'] = $this->mpid;
		$newone['id'] = $cid;
		$newone['title'] = '新投稿活动';
		$newone['creater'] = $uid;
		$newone['create_at'] = time();

		$this->model()->insert('xxt_contribute', $newone, false);

		$c = $this->model('app\contribute')->byId($cid);

		return new \ResponseData($c);
	}
	/**
	 * 更新
	 */
	public function update_action($id) {
		$nv = (array) $this->getPostJson();

		if (isset($nv['params'])) {
			$nv['params'] = json_encode($nv['params']);
		}

		$rst = $this->model()->update('xxt_contribute', $nv, "id='$id'");

		return new \ResponseData($rst);
	}
	/**
	 * 删除
	 */
	public function remove_action($id) {
		$rst = $this->model()->update(
			'xxt_contribute',
			array('state' => 0),
			"mpid='$this->mpid' and id='$id'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 按角色设置参与投稿活动的人
	 */
	public function setUser_action($cid, $role) {
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
			$i['mpid'] = $this->mpid;
			$i['cid'] = $cid;
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
	public function delUser_action($acl) {
		$rst = $this->model()->delete(
			'xxt_contribute_user',
			"mpid='$this->mpid' and id=$acl"
		);

		return new \ResponseData($rst);
	}
}