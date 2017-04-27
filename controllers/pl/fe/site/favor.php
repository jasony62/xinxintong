<?php
namespace pl\fe\site;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户收藏
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
	public function list_action($site, $page = 1, $size = 10) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$q = array(
			'*',
			'xxt_site_friend_favor',
			['siteid' => $site]
		);
		$q2 = array(
			'o' => 'favor_at desc',
			'r' => array('o' => ($page - 1) * $size, $size, 'l' => $size),
		);
		$matters = $model->query_objs_ss($q, $q2);
		foreach ($matters as $k => $v) {
			if($v->matter_type=='custom'){
				$type='article';	
			}else{
				$type=$v->matter_type;
			}
			$d=$model->query_obj_ss(['id,title,summary,pic','xxt_'.$type,"siteid='$v->from_siteid' and id='$v->matter_id'"]);
			$v->data=$d;
			$b[$k]=$v;
		}
		if(isset($b)){
			$matters=(object)$b;
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
	 * @param string $siteFrom 被收藏素材所在站点
	 */
	public function add_action($siteFrom, $id, $type, $title) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		if(empty($siteFrom) || empty($id) || empty($type) || empty($title)){
			return new \ResponseError('请检查参数');
		}

		$sites = $this->getPostJson();
		if(empty($sites)) {
			return new \ResponseError('没有选择需要收藏素材的团队');
		}
		
		$model = $this->model();
		$current = time();

		$log = [
			'creater' => $user->id,
			'creater_name' => $model->escape($user->name),
			'favor_at' => $current,
			'from_siteid' => $model->escape($siteFrom),
			'matter_id' => $model->escape($id),
			'matter_type' => $model->escape($type),
			'matter_title' => $model->escape($title),
		];
		foreach($sites as $site){
			if($site->siteid === $siteFrom){
				continue;
			}
			$q = [
				'id',
				'xxt_site_friend_favor',
				['siteid' => $site->siteid, 'from_siteid' => $siteFrom, 'matter_id' => $id, 'matter_type' => $type]
			];
			if (false === $model->query_obj_ss($q)) {
				$log['siteid'] = $model->escape($site->siteid);
				$model->insert('xxt_site_friend_favor', $log, true);
			}
		}

		return new \ResponseData('ok');
	}
	/**
	 * 检查团队是否收藏了指定素材
	 */
	public function bySite_action($site, $siteFrom, $id, $type) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		if(empty($siteFrom) || empty($id) || empty($type) || empty($site)){
			return new \ResponseError('请检查参数');
		}

		$model = $this->model();
		$q = [
			'id,favor_at',
			'xxt_site_friend_favor',
			['siteid' => $site, 'from_siteid' => $siteFrom, 'matter_id' => $id, 'matter_type' => $type]
		];
		$log = $model->query_obj_ss($q);

		return new \ResponseData($log);
	}
	/**
	 * 取消收藏
	 */
	public function remove_action($site, $siteFrom, $id, $type) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->delete(
			'xxt_site_friend_favor',
			['siteid' => $site, 'from_siteid' => $siteFrom, 'matter_id' => $id, 'matter_type' => $type]
		);

		return new \ResponseData($rst);
	}
	/**
	 * 返回当前可以收藏指定素材的团队
	 *
	 * @param string $site site'id
	 */
	public function canFavor_action($siteFrom, $id, $type) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		if(empty($siteFrom) || empty($id) || empty($type)){
			return new \ResponseError('请检查参数');
		}

		$modelSite = $this->model('site');

		/* 当前用户管理的团队 */
		$mySites = $modelSite->byUser($user->id);
		$targets = []; // 符合条件的团队
		foreach ($mySites as &$mySite) {
			if ($mySite->id === $siteFrom) {
				continue;
			}

			$q = [
				'id',
				'xxt_site_friend_favor',
				['siteid' => $mySite->id, 'from_siteid' => $siteFrom, 'matter_id' => $id, 'matter_type' => $type]
			];
			if (false === $modelSite->query_obj_ss($q)) {
				$mySite->_subscribed = 'N';
			}else{
				$mySite->_subscribed = 'Y';
			}
			$targets[] = $mySite;
		}

		return new \ResponseData($targets);
	}
}