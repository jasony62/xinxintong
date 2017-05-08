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
	public function get_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelWall = $this->model('matter\wall');
		$oWall = $modelWall->byId($id, '*');
		/**
		 * 获得讨论组的url
		 */
		$oWall->user_url = $modelWall->getEntryUrl($site, $id);
		/*所属项目*/
		if ($oWall->mission_id) {
			$oWall->mission = $this->model('matter\mission')->byId($oWall->mission_id, ['cascaded' => 'phase']);
		}
		/**
		 * acl
		 */
		$oWall->acl = $this->model('acl')->byMatter($site, 'wall', $id);
		if (!empty($oWall->source_app)) {
			$sourceApp = json_decode($oWall->source_app);
			$options = array('cascaded' => 'N', 'fields' => 'id,title');
			$oWall->sourceApp = $this->model('matter\\' . $sourceApp->type)->byId($sourceApp->id, $options);
		}

		return new \ResponseData($oWall);
	}
	/**
	 *
	 */
	public function list_action($site = null, $mission = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		if (empty($site) && empty($mission)) {
			return new \ParameterError();
		}
		$modelWall = $this->model('matter\wall');
		if (!empty($mission)) {
			$q = [
				'*',
				'xxt_wall',
				['mission_id' => $mission],
			];
		} else {
			$q = [
				'*',
				'xxt_wall',
				['siteid' => $site],
			];
		}
		$q2['o'] = 'create_at desc';

		$walls = $modelWall->query_objs_ss($q, $q2);
		/**
		 * 获得每个讨论组的url
		 */
		if ($walls) {
			foreach ($walls as &$wall) {
				$wall->type = 'wall';
				$wall->user_url = $modelWall->getEntryUrl($site, $wall->id);
			}
		}

		return new \ResponseData($walls);
	}
	/**
	 * 创建一个讨论组
	 */
	public function create_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();

		$wid = uniqid();
		$newone['id'] = $wid;
		$newone['siteid'] = $site;
		$newone['title'] = '新信息墙';
		$newone['creater'] = $user->id;
		$newapp['creater_name'] = $model->escape($user->name);
		$newone['create_at'] = time();
		$newone['quit_cmd'] = 'q';
		$newone['join_reply'] = '欢迎加入';
		$newone['quit_reply'] = '已经退出';

		$model->insert('xxt_wall', $newone, false);

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