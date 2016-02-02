<?php
namespace mp\matter;

require_once dirname(__FILE__) . '/matter_ctrl.php';

class link extends matter_ctrl {
	/**
	 *
	 */
	public function index_action($id = null, $cascade = 'y') {
		return $this->get_action($id, $cascade);
	}
	/**
	 *
	 */
	public function get_action($id = null, $cascade = 'y') {
		$options = $this->getPostJson();

		$uid = \TMS_CLIENT::get_client_uid();

		$pmpid = $this->getParentMpid();

		if (!empty($id)) {
			$q = array(
				"l.*,a.nickname creater_name,'$uid' uid",
				'xxt_link l,account a',
				"(l.mpid='$this->mpid' or l.mpid='$pmpid') and l.id='$id' and l.state=1 and l.creater=a.uid",
			);
			$link = $this->model()->query_obj_ss($q);
			/**
			 * params
			 */
			$q = array(
				'id,pname,pvalue,authapi_id',
				'xxt_link_param',
				"link_id='$id'",
			);
			$link->params = $this->model()->query_objs_ss($q);
			/**
			 * channels
			 */
			$link->channels = $this->model('matter\channel')->byMatter($id, 'link');
			/**
			 * acl
			 */
			$link->acl = $this->model('acl')->byMatter($this->mpid, 'link', $id);

			return new \ResponseData($link);
		} else {
			/**
			 * 本公众号内的素材
			 */
			$mpid = (!empty($options->src) && $options->src === 'p') ? $this->getParentMpid() : $this->mpid;
			/**
			 * get links
			 */
			$q = array(
				"l.*,a.nickname creater_name,'$uid' uid",
				'xxt_link l,account a',
				"l.mpid='$mpid' and l.state=1 and l.creater=a.uid",
			);
			/**
			 * 仅限作者和管理员？
			 */
			if (!$this->model('mp\permission')->isAdmin($mpid, $uid, true)) {
				$limit = $this->model()->query_value('matter_visible_to_creater', 'xxt_mpsetting', "mpid='$mpid'");
				if ($limit === 'Y') {
					$q[2] .= " and (creater='$uid' or public_visible='Y')";
				}

			}

			$q2['o'] = 'create_at desc';
			$links = $this->model()->query_objs_ss($q, $q2);
			/**
			 * get params and channels
			 */
			if ($cascade === 'y') {
				foreach ($links as $l) {
					/**
					 * params
					 */
					$q = array('id,pname,pvalue,authapi_id',
						'xxt_link_param',
						"link_id=$l->id");
					$l->params = $this->model()->query_objs_ss($q);
					/**
					 * channels
					 */
					$q = array('c.id,c.title,lc.create_at',
						'xxt_channel_matter lc,xxt_channel c',
						"lc.matter_id=$l->id and lc.matter_type='link' and lc.channel_id=c.id");
					$q2['o'] = 'lc.create_at desc';
					$l->channels = $this->model()->query_objs_ss($q, $q2);
					/**
					 * acl
					 */
					$l->acl = $this->model('acl')->byMatter($mpid, 'link', $l->id);
				}
			}

			return new \ResponseData($links);
		}
	}
	/**
	 *
	 */
	public function list_action($cascade = 'y') {
		$options = $this->getPostJson();

		$uid = \TMS_CLIENT::get_client_uid();

		$pmpid = $this->getParentMpid();

		/**
		 * 本公众号内的素材
		 */
		$mpid = (!empty($options->src) && $options->src === 'p') ? $this->getParentMpid() : $this->mpid;
		/**
		 * get links
		 */
		$q = array(
			"l.*,a.nickname creater_name,'$uid' uid",
			'xxt_link l,account a',
			"l.mpid='$mpid' and l.state=1 and l.creater=a.uid",
		);
		/**
		 * 仅限作者和管理员？
		 */
		if (!$this->model('mp\permission')->isAdmin($mpid, $uid, true)) {
			$limit = $this->model()->query_value('matter_visible_to_creater', 'xxt_mpsetting', "mpid='$mpid'");
			if ($limit === 'Y') {
				$q[2] .= " and (creater='$uid' or public_visible='Y')";
			}

		}

		$q2['o'] = 'create_at desc';
		$links = $this->model()->query_objs_ss($q, $q2);
		/**
		 * get params and channels
		 */
		if ($cascade === 'y') {
			foreach ($links as $l) {
				/**
				 * params
				 */
				$q = array('id,pname,pvalue,authapi_id',
					'xxt_link_param',
					"link_id=$l->id");
				$l->params = $this->model()->query_objs_ss($q);
				/**
				 * channels
				 */
				$q = array('c.id,c.title,lc.create_at',
					'xxt_channel_matter lc,xxt_channel c',
					"lc.matter_id=$l->id and lc.matter_type='link' and lc.channel_id=c.id");
				$q2['o'] = 'lc.create_at desc';
				$l->channels = $this->model()->query_objs_ss($q, $q2);
				/**
				 * acl
				 */
				$l->acl = $this->model('acl')->byMatter($mpid, 'link', $l->id);
			}
		}

		return new \ResponseData($links);
	}
	/**
	 *
	 */
	public function cascade_action($id) {
		/**
		 * params
		 */
		$q = array(
			'id,pname,pvalue,authapi_id',
			'xxt_link_param',
			"link_id='$id'",
		);
		$l['params'] = $this->model()->query_objs_ss($q);
		/**
		 * channels
		 */
		$l['channels'] = $this->model('matter\channel')->byMatter($id, 'link');
		/**
		 * acl
		 */
		$l['acl'] = $this->model('acl')->byMatter($this->mpid, 'link', $id);

		return new \ResponseData($l);
	}
	/**
	 * 创建外部链接素材
	 */
	public function create_action($title = '新链接') {
		$uid = \TMS_CLIENT::get_client_uid();
		$d['mpid'] = $this->mpid;
		$d['creater'] = $uid;
		$d['create_at'] = time();
		$d['title'] = $title;

		$id = $this->model()->insert('xxt_link', $d, true);

		$q = array(
			"l.*,a.nickname creater_name,'$uid' uid",
			'xxt_link l,account a',
			"l.id=$id and l.creater=a.uid",
		);

		$link = $this->model()->query_obj_ss($q);

		return new \ResponseData($link);
	}
	/**
	 * 删除链接
	 */
	public function remove_action($id) {
		$model = $this->model();

		$rst = $model->update('xxt_link', array('state' => 0), "mpid='$this->mpid' and id=$id");

		if ($rst) {
			$model->delete('xxt_channel_matter', "matter_id='$id' and matter_type='link'");
			$modelNews = $this->model('matter\news');
			if ($news = $modelNews->byMatter($id, 'link')) {
				foreach ($news as $n) {
					$modelNews->removeMatter($n->id, $id, 'link');
				}

			}
		}

		return new \ResponseData($rst);
	}
	/**
	 * 更新链接属性
	 */
	public function update_action($id) {
		$nv = $this->getPostJson();
		$ret = $this->model()->update('xxt_link', $nv, "mpid='$this->mpid' and id=$id");

		return new \ResponseData($ret);
	}
	/**
	 *
	 * $linkid link's id
	 */
	public function addParam_action($linkid) {
		$p['link_id'] = $linkid;

		$id = $this->model()->insert('xxt_link_param', $p);

		return new \ResponseData($id);
	}
	/**
	 *
	 * 更新参数定义
	 *
	 * 因为参数的属性之间存在关联，因此要整体更新
	 *
	 * $id parameter's id
	 */
	public function updateParam_action($id) {
		$p = $this->getPostJson();

		!empty($p->pvalue) && $p->pvalue = urldecode($p->pvalue);

		$rst = $this->model()->update(
			'xxt_link_param',
			(array) $p,
			"id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * $id parameter's id
	 */
	public function removeParam_action($id) {
		$rst = $this->model()->delete('xxt_link_param', "id=$id");

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	protected function getMatterType() {
		return 'link';
	}
}
