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
		\TPL::output('/pl/fe/matter/wall/frame');
		exit;
	}
	/**
	 *
	 */
	public function detail_action() {
		\TPL::output('/pl/fe/matter/wall/frame');
		exit;
	}
	/**
	 *
	 */
	public function approve_action() {
		\TPL::output('/pl/fe/matter/wall/frame');
		exit;
	}
	/**
	 *
	 */
	public function get_action($id = null, $src = null, $site) {
		$modelWall = $this->model('matter\wall');
		$w = $modelWall->byId($id, '*');
		/**
		 * 获得讨论组的url
		 */
		$w->user_url = $modelWall->getEntryUrl($site, $id);
		/**
		 * acl
		 */
		$w->acl = $this->model('acl')->byMatter($site, 'wall', $id);
		if (!empty($w->source_app)) {
			$sourceApp = json_decode($w->source_app);
			$options = array('cascaded' => 'N', 'fields' => 'id,title');
			$w->sourceApp = $this->model('matter\\' . $sourceApp->type)->byId($sourceApp->id, $options);
		}

		return new \ResponseData($w);
	}
	/**
	 *
	 */
	public function list_action($src = null, $site) {
		$q = array('*', 'xxt_wall', ['siteid' => $site]);
		$q2['o'] = 'create_at desc';

		$walls = $this->model()->query_objs_ss($q, $q2);
		/**
		 * 获得每个讨论组的url
		 */
		if ($walls) {
			foreach ($walls as $wall) {
				$wall->user_url = $this->model('matter\wall')->getEntryUrl($site, $wall->id);
			}
		}

		return new \ResponseData($walls);
	}
	/**
	 * 创建一个讨论组
	 */
	public function create_action($site) {
		$wid = uniqid();
		$newone['id'] = $wid;
		$newone['siteid'] = $site;
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
	public function update_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\wall');
		$modelApp->setOnlyWriteDbConn(true);

		$nv = $this->getPostJson();
		if (isset($nv->title)) {
			$nv->title = $modelApp->escape($nv->title);
		} else if (isset($nv->join_reply)) {
			$nv->join_reply = $modelApp->escape($nv->join_reply);
		} else if (isset($nv->quit_reply)) {
			$nv->quit_reply = $modelApp->escape($nv->quit_reply);
		} else if (isset($nv->entry_ele)) {
			$nv->entry_ele = $modelApp->escape($nv->entry_ele);
		} else if (isset($nv->entry_css)) {
			$nv->entry_css = $modelApp->escape($nv->entry_css);
		} else if (isset($nv->body_css)) {
			$nv->body_css = $modelApp->escape($nv->body_css);
		} else if (isset($nv->active) && $nv->active === 'N') {
			//如果停用信息墙，退出所有用户
			$modelApp->update('xxt_wall_enroll', array('close_at' => time()), ['wid' => $app]);
		}

		$rst = $modelApp->update('xxt_wall', (array) $nv, ['id' => $app]);
		/*记录操作日志*/
		if ($rst) {
			$matter = $modelApp->byId($app, 'id,title,summary,pic');
			$this->model('matter\log')->matterOp($site, $user, $matter, 'U');
		}

		return new \ResponseData($rst);
	}
}