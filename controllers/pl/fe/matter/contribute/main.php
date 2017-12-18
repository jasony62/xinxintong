<?php
namespace pl\fe\matter\contribute;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/*
 * 投稿活动主控制器
 */
class main extends \pl\fe\matter\main_base {
	/**
	 *
	 */
	public function index_action($id) {
		$access = $this->accessControlUser('contribute', $id);
		if ($access[0] === false) {
			die($access[1]);
		}

		\TPL::output('/pl/fe/matter/contribute/frame');
		exit;
	}
	/**
	 *
	 */
	public function setting_action() {
		\TPL::output('/pl/fe/matter/contribute/frame');
		exit;
	}
	/**
	 *
	 */
	public function running_action() {
		\TPL::output('/pl/fe/matter/contribute/frame');
		exit;
	}
	/**
	 * 返回投稿应用
	 */
	public function get_action($site, $app) {
		$modelCtr = $this->model('matter\contribute');
		$modelRole = $this->model('matter\contribute\role');
		$c = $modelCtr->byId($app);
		if (!$c) {
			return new \ResponseError('指定的活动不存在');
		}

		!empty($c->matter_mg_tag) && $c->matter_mg_tag = json_decode($c->matter_mg_tag);
		/**
		 * belong to channel
		 */
		$c->channels = $this->model('matter\channel')->byMatter($app, 'contribute');
		/**
		 * 参与人
		 */
		$c->initiator = $modelRole->users($site, $app, 'I');
		$c->reviewer = $modelRole->users($site, $app, 'R');
		$c->typesetter = $modelRole->users($site, $app, 'T');
		/**
		 * return
		 */
		return new \ResponseData($c);
	}
	/**
	 * 投稿活动列表
	 */
	public function list_action($site, $page = 1, $size = 30) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();
		$oPosted = $this->getPostJson();

		$site = $model->escape($site);
		$q = [
			'*',
			'xxt_contribute c',
			"siteid='$site' and state<>0",
		];
		if (!empty($oPosted->byTitle)) {
			$q[2] .= " and title like '%" . $model->escape($oPosted->byTitle) . "%'";
		}
		if (!empty($oPosted->byTags)) {
			foreach ($oPosted->byTags as $tag) {
				$q[2] .= " and matter_mg_tag like '%" . $model->escape($tag->id) . "%'";
			}
		}
		if (isset($oPosted->byStar) && $oPosted->byStar === 'Y') {
			$q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='contribute' and t.matter_id=c.id and userid='{$oUser->id}')";
		}

		$q2['o'] = 'create_at desc';
		if ($contribute = $model->query_objs_ss($q, $q2)) {
			$modelContribute = $this->model('matter\contribute');
			foreach ($contribute as $c) {
				$c->url = $modelContribute->getEntryUrl($site, $c->id);
				$c->type = 'contribute';
			}
			$result['apps'] = $contribute;
			$q[0] = 'count(*)';
			$total = (int) $model->query_val_ss($q);
			$result['total'] = $total;
			return new \ResponseData($result);
		}

		return new \ResponseData(array());
	}
	/**
	 * 创建投稿活动
	 */
	public function create_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oSite = $this->model('site')->byId($site, array('fields' => 'id,heading_pic'));
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}

		$modelCtb = $this->model('matter\contribute')->setOnlyWriteDbConn(true);

		$oNewApp = new \stdClass;
		$oNewApp->siteid = $oSite->id;
		$oNewApp->title = '新投稿活动';
		$oNewApp->summary = '';
		$oNewApp->pic = $oSite->heading_pic;

		$modelCtb->create($oUser, $oNewApp);

		/*记录操作日志*/
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');

		return new \ResponseData($oNewApp);
	}
	/**
	 * 更新
	 */
	public function update_action($site, $app) {
		if (false === $oUser = $this->accountUser()) {
			return new \ResponseTimeout();
		}
		$modelCtr = $this->model('matter\contribute');
		$oMatter = $modelCtr->byId($app, 'id,title,summary,pic');
		if (false === $oMatter) {
			return new \ObjectNotFoundError();
		}
		$oUpdated = $this->getPostJson();

		if (isset($oUpdated->params)) {
			$oUpdated->params = json_encode($oUpdated->params);
		}

		if ($oMatter = $modelCtr->modify($oUser, $oMatter, $oUpdated)) {
			$this->model('matter\log')->matterOp($site, $oUser, $oMatter, 'U');
		}

		return new \ResponseData($oMatter);
	}
	/**
	 * 删除
	 */
	public function remove_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\contribute');
		$oApp = $modelApp->byId($app, 'siteid,id,title,summary,pic,creater');
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		if ($oApp->creater !== $oUser->id) {
			return new \ResponseError('没有删除数据的权限');
		}

		$rst = $modelApp->remove($oUser, $oApp, 'Recycle');

		return new \ResponseData($rst);
	}
}