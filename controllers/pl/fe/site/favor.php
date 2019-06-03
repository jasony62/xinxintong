<?php
namespace pl\fe\site;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 团队收藏
 */
class favor extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/favor/main');
		exit;
	}
	/**
	 * 返回当前团队收藏的素材,增加了素材的标题、头图、摘要
	 * @param string $site site'id
	 */
	public function list_action($site = null, $page = 1, $size = 10) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('site');
		$sites = [];
		if(empty($site)){
			//获取用户有权管理的团队
			$sites2 = $model->byUser($user->id);
			foreach ($sites2 as $site) {
				$sites[] = $site->id;
			}
		}else{
			$sites[0] = $site;
		}

		$sites = "('" . implode("','", $sites) . "')";

		$q = array(
			'*',
			'xxt_site_friend_favor',
			"siteid in $sites",
		);
		$q2 = array(
			'o' => 'favor_at desc',
			'r' => array('o' => ($page - 1) * $size, $size, 'l' => $size),
		);

		$matters = $model->query_objs_ss($q, $q2);
		foreach ($matters as $k => $v) {
			if ($v->matter_type == 'custom') {
				$type = 'article';
			} else {
				$type = $v->matter_type;
			}
			$d = $model->query_obj_ss(['id,title,summary,pic', 'xxt_' . $type, "siteid='$v->from_siteid' and id='$v->matter_id'"]);
			$v->data = $d;
			$b[$k] = $v;
		}
		if (isset($b)) {
			$matters = (object) $b;
		}
		$result = new \stdClass;
		$result->matters = $matters;
		if (empty($matters)) {
			$result->total = 0;
		} else {
			$q[0] = 'count(*)';
			$result->total = $model->query_val_ss($q);
		}

		return new \ResponseData($result);
	}
	/**
	 * 加入收藏
	 *
	 * @param string $id 素材id
	 * @param string $type 素材type
	 *
	 */
	public function add_action($id, $type) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		if (empty($id) || empty($type)) {
			return new \ParameterError();
		}
		$aTargetSites = $this->getPostJson();
		if (empty($aTargetSites)) {
			return new \ResponseError('没有选择需要收藏素材的团队');
		}

		$modelMat = $this->model('matter\\' . $type);
		$oMatter = $modelMat->byId($id, ['cascaded' => 'N']);
		if (false === $oMatter) {
			return new \ObjectNotFoundError();
		}

		$current = time();
		$fromSite = $this->model('site')->byId($oMatter->siteid, ['fields' => 'name']);
		$log = [
			'creater' => $user->id,
			'creater_name' => $modelMat->escape($user->name),
			'favor_at' => $current,
			'from_siteid' => $oMatter->siteid,
			'from_site_name' => $modelMat->escape($fromSite->name),
			'matter_id' => $oMatter->id,
			'matter_type' => $oMatter->type,
			'matter_title' => $modelMat->escape($oMatter->title),
		];
		foreach ($aTargetSites as $targetSiteId) {
			$q = [
				'id',
				'xxt_site_friend_favor',
				['siteid' => $targetSiteId, 'from_siteid' => $oMatter->siteid, 'matter_id' => $id, 'matter_type' => $type],
			];
			if (false === $modelMat->query_obj_ss($q)) {
				$log['siteid'] = $targetSiteId;
				$modelMat->insert('xxt_site_friend_favor', $log, false);
			}
		}

		return new \ResponseData('ok');
	}
	/**
	 * 取消收藏
	 */
	public function remove_action($id, $type) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$aTargetSites = $this->getPostJson();
		if (empty($aTargetSites)) {
			return new \ResponseError('没有选择取消收藏素材的团队');
		}

		$model = $this->model();
		foreach ($aTargetSites as $targetSiteId) {
			$rst = $model->delete(
				'xxt_site_friend_favor',
				['siteid' => $targetSiteId, 'matter_id' => $id, 'matter_type' => $type]
			);
		}

		return new \ResponseData($rst);
	}
	/**
	 * 返回当前可以收藏指定素材的团队
	 *
	 * @param string $site site'id
	 */
	public function sitesByUser_action($site, $id, $type) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		if (empty($site) || empty($id) || empty($type)) {
			return new \ResponseError('请检查参数');
		}

		$fromSiteId = $site;
		$modelSite = $this->model('site');
		/* 当前用户管理的团队 */
		$mySites = $modelSite->byUser($user->id);
		$targets = []; // 符合条件的团队
		foreach ($mySites as &$mySite) {
			if ($mySite->id === $fromSiteId) {
				continue;
			}
			$q = [
				'id',
				'xxt_site_friend_favor',
				['siteid' => $mySite->id, 'from_siteid' => $fromSiteId, 'matter_id' => $id, 'matter_type' => $type],
			];
			if (false === $modelSite->query_obj_ss($q)) {
				$mySite->_favored = 'N';
			} else {
				$mySite->_favored = 'Y';
			}
			$targets[] = $mySite;
		}

		return new \ResponseData($targets);
	}
	/**
	 * 检查团队是否收藏了指定素材
	 */
	public function bySite_action($site, $fromSiteId, $id, $type) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		if (empty($fromSiteId) || empty($id) || empty($type) || empty($site)) {
			return new \ResponseError('请检查参数');
		}

		$model = $this->model();
		$q = [
			'id,favor_at',
			'xxt_site_friend_favor',
			['siteid' => $site, 'from_siteid' => $fromSiteId, 'matter_id' => $id, 'matter_type' => $type],
		];
		$log = $model->query_obj_ss($q);

		return new \ResponseData($log);
	}
}