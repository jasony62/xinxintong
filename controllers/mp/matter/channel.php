<?php
namespace mp\matter;

require_once dirname(__FILE__) . '/matter_ctrl.php';

class channel extends matter_ctrl {
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/mp/matter/channel');
	}
	/**
	 *
	 */
	public function edit_action() {
		$this->view_action('/mp/matter/channel');
	}
	/**
	 *
	 */
	public function read_action() {
		$this->view_action('/mp/matter/channel');
	}
	/**
	 *
	 */
	public function stat_action() {
		$this->view_action('/mp/matter/channel');
	}
	/**
	 *
	 * $src 是否从父账号获取资源
	 * $acceptType
	 * $cascade 是否获得频道内的素材和访问控制列表
	 */
	public function get_action($id = null, $acceptType = null, $cascade = 'Y') {
		$uid = \TMS_CLIENT::get_client_uid();

		if ($id !== null) {
			$channel = $this->model('matter\channel')->byId($id);
			$channel->uid = $uid;
			$channel->matters = $this->model('matter\channel')->getMatters($id, $channel, $this->mpid);
			$channel->acl = $this->model('acl')->byMatter($this->mpid, 'channel', $id);

			return new \ResponseData($channel);
		} else {
			$options = $this->getPostJson();
			/**
			 * 素材的来源
			 */
			$mpid = (!empty($options->src) && $options->src === 'p') ? $this->getParentMpid() : $this->mpid;
			$q = array(
				"c.*,'$uid' uid",
				'xxt_channel c',
				"c.mpid='$mpid' and c.state=1",
			);
			!empty($acceptType) && $q[2] .= " and (matter_type='' or matter_type='$acceptType')";
			/**
			 * 仅限作者和管理员？
			 */
			if (!$this->model('mp\permission')->isAdmin($mpid, $uid, true)) {
				$visible = $this->model()->query_value('matter_visible_to_creater', 'xxt_mpsetting', "mpid='$mpid'");
				$visible === 'Y' && $q[2] .= " and (creater='$uid' or public_visible='Y')";
			}
			$q2['o'] = 'create_at desc';
			$channels = $this->model()->query_objs_ss($q, $q2);
			/**
			 * 获得子资源
			 */
			if ($channels && $cascade == 'Y') {
				foreach ($channels as $c) {
					/**
					 * matters
					 */
					$c->matters = $this->model('matter\channel')->getMatters($c->id, $c, $this->mpid);
					/**
					 * acl
					 */
					$c->acl = $this->model('acl')->byMatter($mpid, 'channel', $c->id);
				}
			}

			return new \ResponseData($channels);
		}
	}
	/**
	 *
	 * $src 是否从父账号获取资源
	 * $acceptType
	 * $cascade 是否获得频道内的素材和访问控制列表
	 */
	public function list_action($acceptType = null, $cascade = 'Y') {
		$uid = \TMS_CLIENT::get_client_uid();

		$options = $this->getPostJson();
		/**
		 * 素材的来源
		 */
		$mpid = (!empty($options->src) && $options->src === 'p') ? $this->getParentMpid() : $this->mpid;
		$q = array(
			"c.*,'$uid' uid",
			'xxt_channel c',
			"c.mpid='$mpid' and c.state=1",
		);
		!empty($acceptType) && $q[2] .= " and (matter_type='' or matter_type='$acceptType')";
		/**
		 * 仅限作者和管理员？
		 */
		if (!$this->model('mp\permission')->isAdmin($mpid, $uid, true)) {
			$visible = $this->model()->query_value('matter_visible_to_creater', 'xxt_mpsetting', "mpid='$mpid'");
			$visible === 'Y' && $q[2] .= " and (creater='$uid' or public_visible='Y')";
		}
		$q2['o'] = 'create_at desc';
		$channels = $this->model()->query_objs_ss($q, $q2);
		/**
		 * 获得子资源
		 */
		if ($channels && $cascade == 'Y') {
			foreach ($channels as $c) {
				/**
				 * matters
				 */
				$c->matters = $this->model('matter\channel')->getMatters($c->id, $c, $this->mpid);
				/**
				 * acl
				 */
				$c->acl = $this->model('acl')->byMatter($mpid, 'channel', $c->id);
			}
		}

		return new \ResponseData($channels);
	}
	/**
	 * 创建一个平道素材
	 */
	public function create_action() {
		$account = \TMS_CLIENT::account();
		$uid = \TMS_CLIENT::get_client_uid();

		$d = (array) $this->getPostJson();

		$d['mpid'] = $this->mpid;
		$d['creater'] = $uid;
		$d['create_at'] = time();
		$d['creater_src'] = 'A';
		$d['creater_name'] = $account->nickname;

		$id = $this->model()->insert('xxt_channel', $d, true);

		$channel = $this->model('matter\channel')->byId($id);

		return new \ResponseData($channel);
	}
	/**
	 * 更新频道的属性信息
	 *
	 * $id channel's id
	 * $nv pair of name and value
	 */
	public function update_action($id) {
		$nv = $this->getPostJson();

		$rst = $this->model()->update('xxt_channel',
			(array) $nv,
			"mpid='$this->mpid' and id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * $id channel's id.
	 * $pos top|bottom
	 *
	 * post
	 * $t matter's type.
	 * $id matter's id.
	 *
	 */
	public function setfixed_action($id, $pos) {
		$matter = $this->getPostJson();

		if ($pos === 'top') {
			$this->model()->update('xxt_channel',
				array(
					'top_type' => $matter->t,
					'top_id' => $matter->id,
				),
				"mpid='$this->mpid' and id=$id"
			);
		} else if ($pos === 'bottom') {
			$this->model()->update('xxt_channel',
				array(
					'bottom_type' => $matter->t,
					'bottom_id' => $matter->id,
				),
				"mpid='$this->mpid' and id=$id"
			);
		}

		$matters = $this->model('matter\channel')->getMatters($id);

		return new \ResponseData($matters);
	}
	/**
	 *
	 */
	public function addMatter_action() {
		$account = \TMS_CLIENT::account();
		$creater = \TMS_CLIENT::get_client_uid();
		$createrName = $account->nickname;

		$relations = $this->getPostJson();

		$channels = $relations->channels;
		$matter = $relations->matter;

		$model = $this->model('matter\channel');
		foreach ($channels as $channel) {
			$model->addMatter($channel->id, $matter, $creater, $createrName);
		}

		return new \ResponseData('success');
	}
	/**
	 *
	 */
	public function removeMatter_action($id, $reload = 'N') {
		$matter = $this->getPostJson();

		$model = $this->model('matter\channel');

		$rst = $model->removeMatter($id, $matter);

		if ($reload === 'Y') {
			$matters = $model->getMatters($id);
			return new \ResponseData($matters);
		} else {
			return new \ResponseData($rst);
		}
	}
	/**
	 * 删除频道
	 */
	public function delete_action($id) {
		$rst = $this->model()->update('xxt_channel', array('state' => 0), "mpid='$this->mpid' and id=$id");

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	protected function getMatterType() {
		return 'channel';
	}
}
