<?php
namespace pl\fe\matter\wall;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 信息墙
 */
class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'wall';
	}
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/pl/fe/matter/wall');
	}
	/**
	 *
	 */
	public function detail_action() {
		$this->view_action('/pl/fe/matter/wall/detail');
	}
	/**
	 *
	 */
	public function approve_action() {
		$this->view_action('/pl/fe/matter/wall/detail');
	}
	/**
	 *
	 */
	public function get_action($wall = null, $src = null,$siteid) {
		$w = $this->model('matter\wall')->byId($wall, '*');
		/**
		 * acl
		 */
		$w->acl = $this->model('acl')->byMatter($siteid, 'wall', $wall);

		return new \ResponseData($w);
	}
	/**
	 *
	 */
	public function list_action($src = null,$siteid) {
		$q = array('*', 'xxt_wall');
		$q[2] = "siteid='$siteid'";
		$q2['o'] = 'create_at desc';

		$w = $this->model()->query_objs_ss($q, $q2);

		return new \ResponseData($w);
	}
	/**
	 * 创建一个讨论组
	 */
	public function create_action($siteid) {
		$wid = uniqid();
		$newone['id'] = $wid;
		$newone['siteid'] = $siteid;
		$newone['title'] = '新信息墙';
		$newone['creater'] = \TMS_CLIENT::get_client_uid();
		$newone['create_at'] = time();
		$newone['quit_cmd'] = 'q';
		$newone['join_reply'] = '欢迎加入';
		$newone['quit_reply'] = '已经退出';

		$this->model()->insert('xxt_wall', $newone, false);

		return new \ResponseData($wid);
	}
	/**
	 * submit basic.
	 */
	public function update_action($wall) {
		$nv = $this->getPostJson();
		if (isset($nv->title)) {
			$nv->title = $this->model()->escape($nv->title);
		} else if (isset($nv->join_reply)) {
			$nv->join_reply = $this->model()->escape($nv->join_reply);
		} else if (isset($nv->quit_reply)) {
			$nv->quit_reply = $this->model()->escape($nv->quit_reply);
		} else if (isset($nv->entry_ele)) {
			$nv->entry_ele = $this->model()->escape($nv->entry_ele);
		} else if (isset($nv->entry_css)) {
			$nv->entry_css = $this->model()->escape($nv->entry_css);
		} else if (isset($nv->body_css)) {
			$nv->body_css = $this->model()->escape($nv->body_css);
		}

		$rst = $this->model()->update('xxt_wall', (array) $nv, "id='$wall'");

		return new \ResponseData($rst);
	}	
}